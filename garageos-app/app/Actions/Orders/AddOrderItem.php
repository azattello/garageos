<?php

namespace App\Actions\Orders;

use App\Actions\Inventory\ManageInventoryStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddOrderItem
{
    public function __construct(private ManageInventoryStock $manageInventoryStock) {}

    public function handle(User $user, Order $order, array $data): OrderItem
    {
        abort_unless($user->organization_id === $order->organization_id, 403);
        return DB::transaction(function () use ($user, $order, $data): OrderItem {
            $order = Order::where('organization_id', $user->organization_id)
                ->lockForUpdate()
                ->with('status')
                ->findOrFail($order->id);
            if (in_array($order->status->slug, ['ready', 'delivered', 'closed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['order' => 'Нельзя добавлять позиции в завершённый или отменённый заказ.']);
            }
            $quantity = (float) ($data['quantity'] ?? 1);
            $discount = (int) ($data['discount'] ?? 0);
            $type = $data['type'] ?? (isset($data['part_id']) ? 'part' : 'service');

            if ($type === 'part') {
                $part = Part::where('organization_id', $user->organization_id)
                    ->where('active', true)
                    ->lockForUpdate()
                    ->find($data['part_id']);

                if (! $part) {
                    throw ValidationException::withMessages(['part_id' => 'Запчасть не найдена в текущей организации.']);
                }

                if (((float) $part->stock - (float) $part->reserved_stock) < $quantity) {
                    throw ValidationException::withMessages(['quantity' => 'На складе недостаточно доступных запчастей.']);
                }

                abort_unless(in_array($user->role, ['owner', 'manager'], true), 403);

                $unitPrice = (int) $part->sale_price;
                $total = max(0, (int) round($quantity * $unitPrice) - $discount);
                $item = $order->items()->create([
                    'organization_id' => $user->organization_id,
                    'type' => 'part',
                    'part_id' => $part->id,
                    'name_snapshot' => $part->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_cost' => (int) $part->cost_price,
                    'discount' => $discount,
                    'total' => $total,
                ]);

                $this->manageInventoryStock->reserveForOrderItem($user, $item, $order->status->slug === 'in_progress');
            } else {
                $service = Service::where('organization_id', $user->organization_id)
                    ->where('active', true)
                    ->find($data['service_id']);

                if (! $service) {
                    throw ValidationException::withMessages(['service_id' => 'Услуга не найдена в текущей организации.']);
                }

                $unitPrice = (int) $service->price;
                $total = max(0, (int) round($quantity * $unitPrice) - $discount);
                $item = $order->items()->create([
                    'organization_id' => $user->organization_id,
                    'type' => 'service',
                    'service_id' => $service->id,
                    'name_snapshot' => $service->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'total' => $total,
                ]);
            }

            app(RecalculateOrderFinancials::class)->handle($order, $user, 'item_added');
            return $item;
        });
    }

}
