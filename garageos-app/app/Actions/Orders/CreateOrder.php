<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\OrderCreated;
use App\Support\Activity;

class CreateOrder
{
    public function handle(User $user, array $data): Order
    {
        abort_unless($user->organization_id, 403);
        return DB::transaction(function () use ($user, $data): Order {
            $organization = Organization::whereKey($user->organization_id)->lockForUpdate()->firstOrFail();
            $client = $organization->clients()->findOrFail($data['client_id']);
            $car = $client->cars()->where('organization_id', $user->organization_id)->where('active', true)->find($data['car_id']);
            if (! $car) {
                throw ValidationException::withMessages(['car_id' => 'Выберите активный автомобиль этого клиента.']);
            }
            $status = $this->status($user->organization_id, 'new', 'Новый', 1);
            $number = 'ЗН-'.str_pad((string) ($organization->orders()->count() + 1), 5, '0', STR_PAD_LEFT);
            $order = Order::create([...$data, 'organization_id' => $user->organization_id, 'number' => $number, 'status_id' => $status->id, 'opened_at' => now(), 'created_by' => $user->id, 'balance' => 0]);
            OrderCreated::dispatch($order);
            Activity::record('order.created', $order, $user);
            return $order;
        });
    }

    private function status(int $organizationId, string $slug, string $name, int $sortOrder): OrderStatus
    {
        return OrderStatus::firstOrCreate(['organization_id' => $organizationId, 'slug' => $slug], ['name' => $name, 'sort_order' => $sortOrder]);
    }
}
