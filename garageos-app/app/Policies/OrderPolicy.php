<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool { return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager', 'mechanic'], true); }
    public function view(User $user, Order $order): bool
    {
        return $user->organization_id === $order->organization_id
            && (in_array($user->role, ['owner', 'manager'], true) || ($user->role === 'mechanic' && $order->mechanic_id === $user->id));
    }
    public function create(User $user): bool { return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true); }
    public function update(User $user, Order $order): bool { return $this->view($user, $order); }
    public function manageFinances(User $user, Order $order): bool
    {
        return $user->organization_id === $order->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }

    public function viewPortal(User $user, Order $order): bool
    {
        return $user->role === 'client'
            && $user->organization_id === $order->organization_id
            && $user->clients()->where('clients.id', $order->client_id)->whereNull('clients.deleted_at')->exists();
    }
}
