<?php

namespace App\Actions\Organizations;

use App\Models\User;
use App\Support\Activity;

class UpdateEmployee
{
    public function handle(User $actor, User $employee, array $data): User
    {
        abort_unless($actor->organization_id === $employee->organization_id && $actor->role === 'owner' && $employee->role !== 'owner', 404);
        $before = $employee->only(['name', 'phone', 'specialization']);
        $employee->update($data);

        Activity::record('employee.updated', $employee, $actor, [
            'before' => $before,
            'after' => $employee->only(['name', 'phone', 'specialization']),
        ]);

        return $employee->refresh();
    }
}