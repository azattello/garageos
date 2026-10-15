<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\Activity;

class CreateService
{
    public function handle(User $user, array $data): Service
    {
        abort_unless($user->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);
        if (! empty($data['category_id']) && ! ServiceCategory::where('organization_id', $user->organization_id)->whereKey($data['category_id'])->exists()) {
            abort(422, 'Категория не принадлежит текущей организации.');
        }
        $service = Service::create([...$data, 'organization_id' => $user->organization_id]);
        Activity::record('service.created', $service, $user, ['name' => $service->name]);

        return $service;
    }
}
