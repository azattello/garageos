<?php

namespace App\Policies;

use App\Models\Part;
use App\Models\User;

class PartPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Part $part): bool
    {
        return $user->organization_id === $part->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function delete(User $user, Part $part): bool
    {
        return $this->update($user, $part);
    }
}
