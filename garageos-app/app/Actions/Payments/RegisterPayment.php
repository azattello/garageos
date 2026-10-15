<?php

namespace App\Actions\Payments;

use App\Actions\Orders\RecalculateOrderFinancials;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterPayment
{
    public function handle(User $user, Order $order, array $data): Payment
    {
        abort_unless($user->organization_id === $order->organization_id, 403);

        return DB::transaction(function () use ($user, $order, $data): Payment {
            $lockedOrder = Order::where('organization_id', $user->organization_id)
                ->lockForUpdate()
                ->findOrFail($order->id);
            $lockedOrder = app(RecalculateOrderFinancials::class)->handle($lockedOrder, $user, 'payment_validation');
            if ((int) $data['amount'] > (int) $lockedOrder->balance) {
                throw ValidationException::withMessages(['amount' => 'Сумма оплаты не может быть больше текущего долга.']);
            }

            $payment = $lockedOrder->payments()->create([
                'organization_id' => $user->organization_id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'status' => 'successful',
                'comment' => $data['comment'] ?? null,
                'received_by' => $user->id,
                'paid_at' => now(),
            ]);
            app(RecalculateOrderFinancials::class)->handle($lockedOrder, $user, 'payment_registered');
            Activity::record('payment.registered', $payment, $user, [
                'order_id' => $lockedOrder->id,
                'amount' => (int) $payment->amount,
                'method' => $payment->method,
                'paid_total' => (int) $lockedOrder->paid_total,
                'balance' => (int) $lockedOrder->balance,
            ]);
            return $payment;
        });
    }

    public function cancel(User $user, Payment $payment): Payment
    {
        abort_unless($user->organization_id === $payment->organization_id, 403);
        return DB::transaction(function () use ($user, $payment): Payment {
            $paymentOrderId = Payment::where('organization_id', $user->organization_id)
                ->whereKey($payment->id)
                ->value('order_id');
            abort_if(! $paymentOrderId, 404);
            $lockedOrder = Order::where('organization_id', $user->organization_id)
                ->lockForUpdate()
                ->findOrFail($paymentOrderId);
            $lockedPayment = Payment::where('organization_id', $user->organization_id)
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->findOrFail($payment->id);
            if ($lockedPayment->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'Платёж уже отменён.']);
            }

            $lockedPayment->update(['status' => 'cancelled']);
            app(RecalculateOrderFinancials::class)->handle($lockedOrder, $user, 'payment_cancelled');
            Activity::record('payment.cancelled', $lockedPayment, $user, [
                'order_id' => $lockedOrder->id,
                'amount' => (int) $lockedPayment->amount,
                'paid_total' => (int) $lockedOrder->paid_total,
                'balance' => (int) $lockedOrder->balance,
            ]);
            return $lockedPayment->refresh();
        });
    }
}