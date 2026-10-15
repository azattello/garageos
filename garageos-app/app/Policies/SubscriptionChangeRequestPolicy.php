<?php

namespace App\Policies;

use App\Models\SubscriptionChangeRequest;
use App\Models\User;

class SubscriptionChangeRequestPolicy
{
    public function review(User $user, SubscriptionChangeRequest $request): bool
    {
        return $user->isPlatformAdmin();
    }
}
