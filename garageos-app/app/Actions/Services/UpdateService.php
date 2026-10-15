<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\Activity;

class UpdateService
{
    public function handle(User $user, Service $service, array $data): Service
    {
        abort_unless($user->organization_id === $service->organization_id, 404);

        if (! empty($data['category_id']) && ! ServiceCategory::where('organization_id', $user->organization_id)->whereKey($data['category_id'])->exists()) {
            abort(422, 'Категория не принадлежит текущей организации.');
        }

        $before = $service->only(['category_id', 'name', 'description', 'price', 'duration_minutes']);
        $service->update($data);
        Activity::record('service.updated', $service, $user, ['before' => $before, 'after' => $service->only(array_keys($before))]);

        return $service;
    }
}