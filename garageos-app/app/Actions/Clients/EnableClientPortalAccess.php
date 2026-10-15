<?php

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class EnableClientPortalAccess
{
    public function handle(User $actor, Client $client): User
    {
        Gate::forUser($actor)->authorize('managePortalAccess', $client);

        if (! $client->email) {
            throw ValidationException::withMessages(['email' => 'Для доступа в кабинет укажите email клиента.']);
        }

        $account = DB::transaction(function () use ($actor, $client): User {
            $lockedClient = Client::where('organization_id', $actor->organization_id)
                ->lockForUpdate()
                ->findOrFail($client->id);
            $user = User::where('email', $lockedClient->email)->lockForUpdate()->first();

            if ($user && ($user->role !== 'client' || $user->organization_id !== $lockedClient->organization_id)) {
                throw ValidationException::withMessages(['email' => 'Этот email уже используется другим типом пользователя.']);
            }

            if (! $user) {
                $user = User::create([
                    'name' => $lockedClient->name,
                    'email' => $lockedClient->email,
                    'phone' => $lockedClient->phone,
                    'organization_id' => $lockedClient->organization_id,
                    'role' => 'client',
                    'status' => 'active',
                    'password' => bin2hex(random_bytes(32)),
                ]);
            }

            $lockedClient->portalUsers()->syncWithoutDetaching([$user->id => ['organization_id' => $actor->organization_id]]);
            Activity::record('client.portal_access_enabled', $lockedClient, $actor, ['client_user_id' => $user->id, 'email' => $user->email]);

            return $user;
        });

        Password::sendResetLink(['email' => $account->email]);

        return $account;
    }
}