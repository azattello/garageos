<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool { return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, ServiceCategory $category): bool { return $user->organization_id === $category->organization_id && in_array($user->role, ['owner', 'manager'], true); }
    public function delete(User $user, ServiceCategory $category): bool { return $this->update($user, $category); }
}