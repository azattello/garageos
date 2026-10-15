<?php

namespace App\Actions\Inventory;

use App\Models\InventoryTransaction;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Part;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageInventoryStock
{
    public function handle(
        User $user,
        Part $part,
        string $type,
        float $quantity,
        ?string $note = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryTransaction {
        abort_unless($user->organization_id === $part->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);

        if (! in_array($type, ['receive', 'issue', 'adjustment', 'return'], true)) {
            throw ValidationException::withMessages(['type' => 'Неизвестная операция склада.']);
        }

        return DB::transaction(function () use ($user, $part, $type, $quantity, $note, $referenceType, $referenceId): InventoryTransaction {
            $lockedPart = Part::where('organization_id', $user->organization_id)
                ->lockForUpdate()
                ->findOrFail($part->id);
            $currentStock = (float) $lockedPart->stock;
            $reservedStock = (float) $lockedPart->reserved_stock;
            $delta = match ($type) {
                'receive', 'return' => $quantity,
                'issue' => -$quantity,
                'adjustment' => $quantity - $currentStock,
            };
            $newStock = round($currentStock + $delta, 2);

            if ($newStock < $reservedStock) {
                throw ValidationException::withMessages(['quantity' => 'Нельзя списать больше доступного остатка с учётом резервов.']);
            }

            $lockedPart->update(['stock' => $newStock]);
            $availableStock = round($newStock - $reservedStock, 2);
            $this->syncLowStockNotifications($user, $lockedPart, $availableStock);

            $transaction = InventoryTransaction::create([
                'organization_id' => $user->organization_id,
                'part_id' => $lockedPart->id,
                'type' => $type,
                'quantity' => round($delta, 2),
                'reserved_delta' => 0,
                'unit_cost' => $lockedPart->cost_price,
                'user_id' => $user->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
            ]);

            Activity::record('inventory.stock_moved', $transaction, $user, [
                'part_id' => $lockedPart->id,
                'type' => $type,
                'quantity' => round($delta, 2),
                'stock' => $newStock,
                'reserved_stock' => $reservedStock,
                'available_stock' => $availableStock,
            ]);

            return $transaction;
        });
    }

    public function reserveForOrderItem(User $user, OrderItem $item, bool $issueImmediately): void
    {
        abort_unless($item->organization_id === $user->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);

        DB::transaction(function () use ($user, $item, $issueImmediately): void {
            $lockedItem = OrderItem::where('organization_id', $user->organization_id)->lockForUpdate()->findOrFail($item->id);
            if ($lockedItem->inventory_status !== 'none') {
                return;
            }

            $part = Part::where('organization_id', $user->organization_id)->lockForUpdate()->findOrFail($lockedItem->part_id);
            $quantity = (float) $lockedItem->quantity;
            $available = round((float) $part->stock - (float) $part->reserved_stock, 2);
            if ($available < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'На складе недостаточно доступных запчастей.']);
            }

            if ($issueImmediately) {
                $part->update(['stock' => round((float) $part->stock - $quantity, 2)]);
                $lockedItem->update(['inventory_status' => 'issued']);
                $type = 'issue';
                $ledgerQuantity = -$quantity;
            } else {
                $part->update(['reserved_stock' => round((float) $part->reserved_stock + $quantity, 2)]);
                $lockedItem->update(['inventory_status' => 'reserved']);
                $type = 'reserve';
                $ledgerQuantity = 0;
            }

                $this->recordOrderMovement($user, $part, $lockedItem, $type, $ledgerQuantity, $issueImmediately ? 0 : $quantity);
            $this->syncLowStockNotifications($user, $part, round((float) $part->stock - (float) $part->reserved_stock, 2));
        });
    }

    public function transitionOrderItems(User $user, Order $order, string $nextStatus): void
    {
        abort_unless($order->organization_id === $user->organization_id
            && in_array($user->role, ['owner', 'manager', 'mechanic'], true), 403);

        if (! in_array($nextStatus, ['ready', 'delivered', 'cancelled'], true)) {
            return;
        }

        DB::transaction(function () use ($user, $order, $nextStatus): void {
            $items = OrderItem::where('organization_id', $user->organization_id)
                ->where('order_id', $order->id)
                ->where('type', 'part')
                ->whereIn('inventory_status', ['reserved', 'issued'])
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $part = Part::where('organization_id', $user->organization_id)
                    ->lockForUpdate()
                    ->findOrFail($item->part_id);
                $quantity = (float) $item->quantity;

                if (in_array($nextStatus, ['ready', 'delivered'], true) && $item->inventory_status === 'reserved') {
                    $part->update([
                        'stock' => round((float) $part->stock - $quantity, 2),
                        'reserved_stock' => round((float) $part->reserved_stock - $quantity, 2),
                    ]);
                    $item->update(['inventory_status' => 'issued']);
                    $this->recordOrderMovement($user, $part, $item, 'issue', -$quantity, -$quantity);
                } elseif ($nextStatus === 'cancelled' && $item->inventory_status === 'reserved') {
                    $part->update(['reserved_stock' => round((float) $part->reserved_stock - $quantity, 2)]);
                    $item->update(['inventory_status' => 'released']);
                    $this->recordOrderMovement($user, $part, $item, 'release', 0, -$quantity);
                } elseif ($nextStatus === 'cancelled' && $item->inventory_status === 'issued') {
                    $part->update(['stock' => round((float) $part->stock + $quantity, 2)]);
                    $item->update(['inventory_status' => 'returned']);
                    $this->recordOrderMovement($user, $part, $item, 'return', $quantity);
                }

                $this->syncLowStockNotifications($user, $part, round((float) $part->stock - (float) $part->reserved_stock, 2));
            }
        });
    }

    private function recordOrderMovement(User $user, Part $part, OrderItem $item, string $type, float $quantity, float $reservedDelta = 0): void
    {
        $transaction = InventoryTransaction::create([
            'organization_id' => $user->organization_id,
            'part_id' => $part->id,
            'type' => $type,
            'quantity' => round($quantity, 2),
            'reserved_delta' => round($reservedDelta, 2),
            'unit_cost' => $part->cost_price,
            'user_id' => $user->id,
            'reference_type' => 'order_item',
            'reference_id' => $item->id,
            'note' => 'Заказ '.($item->order?->number ?? $item->order_id),
        ]);

        Activity::record('inventory.order_stock_changed', $transaction, $user, [
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'part_id' => $part->id,
            'type' => $type,
            'quantity' => round($quantity, 2),
            'reserved_delta' => round($reservedDelta, 2),
            'stock' => (float) $part->stock,
            'reserved_stock' => (float) $part->reserved_stock,
            'available_stock' => round((float) $part->stock - (float) $part->reserved_stock, 2),
        ]);
    }

    private function syncLowStockNotifications(User $actor, Part $part, float $stock): void
    {
        $minimum = (float) $part->min_stock;
        $recipients = User::where('organization_id', $actor->organization_id)
            ->where('status', 'active')
            ->whereIn('role', ['owner', 'manager'])
            ->get(['id']);

        foreach ($recipients as $recipient) {
            $activeNotifications = Notification::where('user_id', $recipient->id)
                ->where('type', 'inventory.low_stock')
                ->whereNull('read_at')
                ->get();
            $activeAlert = $activeNotifications->first(fn (Notification $notification) => (int) ($notification->data['part_id'] ?? 0) === $part->id);

            if ($stock <= $minimum && ! $activeAlert) {
                Notification::create([
                    'organization_id' => $actor->organization_id,
                    'user_id' => $recipient->id,
                    'type' => 'inventory.low_stock',
                    'title' => 'Низкий остаток',
                    'body' => "{$part->name}: доступно ".rtrim(rtrim(number_format($stock, 2, '.', ''), '0'), '.')." {$part->unit} при минимуме ".rtrim(rtrim(number_format($minimum, 2, '.', ''), '0'), '.').".",
                    'data' => ['part_id' => $part->id, 'stock' => $stock, 'min_stock' => $minimum],
                ]);
            }

            if ($stock > $minimum && $activeAlert) {
                $activeAlert->update(['read_at' => now()]);
            }
        }
    }
}
