<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Activity
{
    public static function record(string $action, ?Model $subject, ?User $user, array $metadata = [], ?Request $request = null): ActivityLog
    {
        return ActivityLog::create([
            'organization_id' => $user?->organization_id,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}