<?php

namespace App\Actions\Clients;

use App\Models\Car;
use App\Models\User;
use App\Support\Activity;

class SetCarActive
{
    public function handle(User $user, Car $car, bool $active): Car
    {
        abort_unless($user->organization_id === $car->organization_id, 404);

        if ($car->active === $active) {
            return $car;
        }

        $car->update(['active' => $active]);
        Activity::record($active ? 'car.restored' : 'car.archived', $car, $user, ['active' => $active]);

        return $car;
    }
}