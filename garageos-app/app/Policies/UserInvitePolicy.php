<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserInvite;

class UserInvitePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user, UserInvite $invite): bool
    {
        return $user->organization_id === $invite->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }
}
