<?php

namespace App\Actions\Subscriptions;

use App\Models\ActivityLog;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class ExpireSubscription
{
    public function handle(Subscription $subscription): Subscription
    {
        if (! $this->isDue($subscription)) {
            return $subscription;
        }

        return DB::transaction(function () use ($subscription): Subscription {
            $locked = Subscription::where('organization_id', $subscription->organization_id)
                ->lockForUpdate()
                ->findOrFail($subscription->id);
            if (! $this->isDue($locked)) {
                return $locked;
            }

            $previousStatus = $locked->status;
            $locked->update(['status' => 'expired']);
            ActivityLog::create([
                'organization_id' => $locked->organization_id,
                'action' => 'subscription.expired',
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->id,
                'metadata' => ['previous_status' => $previousStatus, 'plan' => $locked->plan],
                'created_at' => now(),
            ]);

            return $locked->refresh();
        });
    }

    private function isDue(Subscription $subscription): bool
    {
        if ($subscription->status === 'trialing') {
            return ! $subscription->trial_ends_at || $subscription->trial_ends_at->isPast();
        }

        return $subscription->status === 'active'
            && (! $subscription->ends_at || $subscription->ends_at->isPast());
    }
}
