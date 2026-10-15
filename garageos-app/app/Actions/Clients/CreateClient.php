<?php

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\User;
use App\Support\Activity;

class CreateClient
{
    /** @param array{name: string, phone: string, email?: string|null, comment?: string|null} $data */
    public function handle(User $user, array $data): Client
    {
        abort_unless($user->organization_id, 403);

        $phoneDigits = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        $existingClient = Client::query()
            ->where('organization_id', $user->organization_id)
            ->where(function ($query) use ($phoneDigits, $email) {
                if ($phoneDigits !== '') {
                    $query->orWhere(function ($phoneQuery) use ($phoneDigits) {
                        $phoneQuery->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', '') = ?", [$phoneDigits]);
                    });
                }

                if ($email !== '') {
                    $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]);
                }
            })
            ->first();

        if ($existingClient) {
            return $existingClient;
        }

        $client = Client::create([...$data, 'organization_id' => $user->organization_id]);
        Activity::record('client.created', $client, $user);

        return $client;
    }
}