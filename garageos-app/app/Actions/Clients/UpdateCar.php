<?php

namespace App\Actions\Clients;

use App\Models\Car;
use App\Models\User;
use App\Support\Activity;

class UpdateCar
{
    public function handle(User $user, Car $car, array $data): Car
    {
        abort_unless($user->organization_id === $car->organization_id && $car->active, 404);

        $fields = ['make', 'model', 'year', 'plate_number', 'vin', 'color', 'mileage', 'fuel_type', 'comment'];
        $before = $car->only($fields);
        $car->update($data);
        Activity::record('car.updated', $car, $user, ['before' => $before, 'after' => $car->only($fields)]);

        return $car;
    }
}