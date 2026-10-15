<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Service $service): bool
    {
        return $user->organization_id === $service->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }
}
