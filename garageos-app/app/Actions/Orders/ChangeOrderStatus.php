<?php

namespace App\Actions\Orders;

use App\Actions\Inventory\ManageInventoryStock;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\OrderStatusChanged;
use App\Support\Activity;

class ChangeOrderStatus
{
    private const TRANSITIONS = [
        'new' => ['accepted', 'cancelled'],
        'accepted' => ['diagnostics', 'cancelled'],
        'diagnostics' => ['in_progress', 'cancelled'],
        'in_progress' => ['waiting_parts', 'ready', 'cancelled'],
        'waiting_parts' => ['in_progress', 'cancelled'],
        'ready' => ['delivered'],
        'delivered' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function handle(User $user, Order $order, string $nextSlug, bool $allowUnpaid = false): Order
    {
        abort_unless($user->organization_id === $order->organization_id
            && (in_array($user->role, ['owner', 'manager'], true) || ($user->role === 'mechanic' && $order->mechanic_id === $user->id)), 403);

        if ($allowUnpaid && ! in_array($user->role, ['owner', 'manager'], true)) {
            abort(403);
        }

        return DB::transaction(function () use ($user, $order, $nextSlug, $allowUnpaid): Order {
            $lockedOrder = Order::where('organization_id', $user->organization_id)
                ->lockForUpdate()
                ->with('status')
                ->findOrFail($order->id);
            $current = $lockedOrder->status->slug;

            if (! in_array($nextSlug, self::TRANSITIONS[$current] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Недопустимый переход статуса.']);
            }

            if ($nextSlug === 'closed' && (int) $lockedOrder->balance !== 0 && ! $allowUnpaid) {
                throw ValidationException::withMessages(['status' => 'Нельзя закрыть заказ с ненулевым балансом без явного разрешения владельца или менеджера.']);
            }

            app(ManageInventoryStock::class)->transitionOrderItems($user, $lockedOrder, $nextSlug);
            $next = OrderStatus::firstOrCreate(['organization_id' => $user->organization_id, 'slug' => $nextSlug], ['name' => $this->name($nextSlug), 'sort_order' => $lockedOrder->status->sort_order + 1]);
            $lockedOrder->update(['status_id' => $next->id, 'completed_at' => $nextSlug === 'closed' ? now() : $lockedOrder->completed_at]);
            $updated = $lockedOrder->refresh();
            OrderStatusChanged::dispatch($updated, $current, $nextSlug);
            Activity::record('order.status_changed', $updated, $user, ['from' => $current, 'to' => $nextSlug, 'allow_unpaid' => $allowUnpaid]);

            return $updated;
        });
    }

    public function allowedTransitions(Order $order): array
    {
        return self::TRANSITIONS[$order->status->slug] ?? [];
    }

    private function name(string $slug): string
    {
        return ['accepted' => 'Принят', 'diagnostics' => 'Диагностика', 'in_progress' => 'В работе', 'waiting_parts' => 'Ожидание запчастей', 'ready' => 'Готов', 'delivered' => 'Выдан', 'closed' => 'Закрыт', 'cancelled' => 'Отменён'][$slug] ?? 'Новый';
    }
}
