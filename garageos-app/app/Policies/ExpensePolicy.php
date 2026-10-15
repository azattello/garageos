<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->organization_id === $expense->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }
}
