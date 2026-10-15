<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool { return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true); }
    public function view(User $user, Client $client): bool
    {
        return $user->organization_id === $client->organization_id && in_array($user->role, ['owner', 'manager'], true);
    }
    public function create(User $user): bool { return (bool) $user->organization_id && in_array($user->role, ['owner', 'manager'], true); }
    public function update(User $user, Client $client): bool { return $this->view($user, $client) && in_array($user->role, ['owner', 'manager'], true); }
    public function delete(User $user, Client $client): bool { return $this->update($user, $client); }

    public function viewPortal(User $user, Client $client): bool
    {
        return $user->role === 'client'
            && $user->organization_id === $client->organization_id
            && $user->clients()->where('clients.id', $client->id)->whereNull('clients.deleted_at')->exists();
    }

    public function managePortalAccess(User $user, Client $client): bool
    {
        return in_array($user->role, ['owner', 'manager'], true)
            && $user->organization_id === $client->organization_id;
    }
}
