<?php

namespace App\Policies;

use App\Models\Car;
use App\Models\User;

class CarPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function view(User $user, Car $car): bool
    {
        return $user->organization_id === $car->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function viewPortal(User $user, Car $car): bool
    {
        return $user->role === 'client'
            && $user->organization_id === $car->organization_id
            && $user->clients()
                ->where('clients.id', $car->client_id)
                ->where('clients.organization_id', $car->organization_id)
                ->whereNull('clients.deleted_at')
                ->wherePivot('organization_id', $car->organization_id)
                ->exists();
    }

    public function create(User $user): bool
    {
        return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function update(User $user, Car $car): bool
    {
        return $this->view($user, $car);
    }

    public function delete(User $user, Car $car): bool
    {
        return $this->view($user, $car);
    }
}
