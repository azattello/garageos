<?php

namespace App\Actions\Clients;

use App\Models\Car;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Validation\ValidationException;

class CreateCar
{
    public function handle(User $user, array $data): Car
    {
        abort_unless($user->organization_id, 403);
        $clientBelongsToTenant = $user->organization->clients()->whereKey($data['client_id'])->exists();
        if (! $clientBelongsToTenant) {
            throw ValidationException::withMessages(['client_id' => 'Клиент не найден в текущей организации.']);
        }
        $car = Car::create([...$data, 'organization_id' => $user->organization_id, 'active' => true]);
        Activity::record('car.created', $car, $user, ['client_id' => $car->client_id]);

        return $car;
    }
}
