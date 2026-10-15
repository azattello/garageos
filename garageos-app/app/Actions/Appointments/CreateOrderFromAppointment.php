<?php

namespace App\Actions\Appointments;

use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\AddOrderItem;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderFromAppointment
{
    public function __construct(private CreateOrder $createOrder, private AddOrderItem $addOrderItem) {}

    public function handle(User $user, Appointment $appointment): Order
    {
        abort_unless($user->organization_id === $appointment->organization_id, 403);
        abort_unless(in_array($user->role, ['owner', 'manager'], true), 403);
        if ($appointment->order_id) {
            throw ValidationException::withMessages(['appointment' => 'Для этой записи заказ уже создан.']);
        }
        return DB::transaction(function () use ($user, $appointment): Order {
            $order = $this->createOrder->handle($user, ['client_id' => $appointment->client_id, 'car_id' => $appointment->car_id, 'mechanic_id' => $appointment->mechanic_id, 'planned_completion_at' => $appointment->end_at]);
            $this->addOrderItem->handle($user, $order, ['service_id' => $appointment->service_id, 'quantity' => 1]);
            $appointment->update(['order_id' => $order->id, 'status' => 'arrived']);
            Activity::record('appointment.order_created', $appointment, $user, ['order_id' => $order->id]);
            return $order;
        });
    }
}
