<?php

namespace App\Policies;

use App\Models\AppointmentStatus;
use App\Models\User;

class AppointmentStatusPolicy
{
    public function viewAny(User $user): bool { return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, AppointmentStatus $status): bool { return $user->organization_id === $status->organization_id && in_array($user->role, ['owner', 'manager'], true); }
    public function delete(User $user, AppointmentStatus $status): bool { return $this->update($user, $status); }
}