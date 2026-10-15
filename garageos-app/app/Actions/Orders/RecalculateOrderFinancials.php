<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\User;
use App\Support\Activity;

class RecalculateOrderFinancials
{
    public function handle(Order $order, User $user, string $reason): Order
    {
        $previous = [
            'subtotal' => (int) $order->subtotal,
            'discount' => (int) $order->discount,
            'total' => (int) $order->total,
            'paid_total' => (int) $order->paid_total,
            'balance' => (int) $order->balance,
        ];
        $subtotal = (int) $order->items()->sum('total');
        $total = max(0, $subtotal - (int) $order->discount);
        $paid = (int) $order->payments()->where('status', 'successful')->sum('amount');
        $balance = $total - $paid;

        $order->update([
            'subtotal' => $subtotal,
            'total' => $total,
            'paid_total' => $paid,
            'balance' => $balance,
        ]);

        $current = [
            'subtotal' => $subtotal,
            'discount' => (int) $order->discount,
            'total' => $total,
            'paid_total' => $paid,
            'balance' => $balance,
        ];

        if ($previous !== $current) {
            Activity::record('order.financials_recalculated', $order, $user, [
                'reason' => $reason,
                'before' => $previous,
                'after' => $current,
            ]);
        }

        return $order->refresh();
    }
}