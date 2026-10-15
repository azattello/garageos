<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manageAccount(User $user, User $employee): bool
    {
        return $user->organization_id !== null
            && $user->organization_id === $employee->organization_id
            && $user->role === 'owner'
            && $user->id !== $employee->id
            && $employee->role !== 'owner';
    }
}