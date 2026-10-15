<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function update(User $user, Organization $organization): bool
    {
        return $user->organization_id === $organization->id && $user->role === 'owner';
    }

    public function manageSubscription(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }

    public function viewSubscription(User $user, Organization $organization): bool
    {
        return $user->organization_id === $organization->id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function managePlatform(User $user, Organization $organization): bool
    {
        return $user->isPlatformAdmin();
    }
}
