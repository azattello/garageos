<?php

namespace App\Actions\Subscriptions;

use App\Models\SubscriptionChangeRequest;
use App\Models\User;
use App\Services\Billing\PlanCatalog;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestPlanChange
{
    public function __construct(private PlanCatalog $plans) {}

    public function handle(User $user, string $requestedPlan): SubscriptionChangeRequest
    {
        abort_unless($user->role === 'owner' && $user->organization_id, 403);
        if (! $this->plans->get($requestedPlan)) {
            throw ValidationException::withMessages(['plan' => 'Неизвестный тариф.']);
        }
        $targetPlan = $this->plans->get($requestedPlan);

        return DB::transaction(function () use ($user, $requestedPlan, $targetPlan): SubscriptionChangeRequest {
            $subscription = $user->organization->subscription()->lockForUpdate()->firstOrFail();
            $isExpired = $subscription->status === 'expired'
                || ($subscription->ends_at !== null && $subscription->ends_at->isPast());
            if ($subscription->plan === $requestedPlan && ! $isExpired) {
                throw ValidationException::withMessages(['plan' => 'Этот тариф уже подключён.']);
            }
            if (SubscriptionChangeRequest::where('organization_id', $user->organization_id)->where('status', 'pending')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['plan' => 'У организации уже есть ожидающая заявка на изменение тарифа.']);
            }

            $changeRequest = SubscriptionChangeRequest::create([
                'organization_id' => $user->organization_id,
                'subscription_id' => $subscription->id,
                'requested_by' => $user->id,
                'current_plan' => $subscription->plan,
                'requested_plan' => $requestedPlan,
                'quoted_amount' => $targetPlan['price'],
                'currency' => $targetPlan['currency'],
                'status' => 'pending',
                'requested_at' => now(),
            ]);
            Activity::record('subscription.plan_change_requested', $changeRequest, $user, [
                'from' => $subscription->plan,
                'to' => $requestedPlan,
            ]);

            return $changeRequest;
        });
    }
}
