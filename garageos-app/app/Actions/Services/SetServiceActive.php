<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\User;
use App\Support\Activity;

class SetServiceActive
{
    public function handle(User $user, Service $service, bool $active): Service
    {
        abort_unless($user->organization_id === $service->organization_id, 404);

        if ($service->active === $active) {
            return $service;
        }

        $service->update(['active' => $active]);
        Activity::record($active ? 'service.restored' : 'service.archived', $service, $user, ['active' => $active]);

        return $service;
    }
}