<?php

namespace App\Actions\Subscriptions;

use App\Models\ActivityLog;
use App\Models\SaasPayment;
use App\Models\SaasRevenue;
use App\Models\Subscription;
use App\Models\SubscriptionChangeRequest;
use App\Models\User;
use App\Services\Billing\PlanCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewPlanChange
{
    public function __construct(private PlanCatalog $plans) {}

    public function handle(User $admin, SubscriptionChangeRequest $changeRequest, bool $approve, ?string $paymentReference, ?string $reviewNote, ?Request $request = null): SubscriptionChangeRequest
    {
        Gate::forUser($admin)->authorize('review', $changeRequest);
        if ($approve && ! filled($paymentReference)) {
            throw ValidationException::withMessages(['payment_reference' => 'Подтвердите платёж и укажите его reference до активации.']);
        }

        return DB::transaction(function () use ($admin, $changeRequest, $approve, $paymentReference, $reviewNote, $request): SubscriptionChangeRequest {
            $subscription = Subscription::where('organization_id', $changeRequest->organization_id)
                ->lockForUpdate()
                ->findOrFail($changeRequest->subscription_id);
            $lockedRequest = SubscriptionChangeRequest::where('organization_id', $changeRequest->organization_id)
                ->lockForUpdate()
                ->findOrFail($changeRequest->id);
            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'Заявка уже рассмотрена.']);
            }

            if ($approve && SubscriptionChangeRequest::where('payment_reference', $paymentReference)->where('id', '!=', $lockedRequest->id)->exists()) {
                throw ValidationException::withMessages(['payment_reference' => 'Этот reference уже использован для другой подписки.']);
            }
            if ($approve && SaasPayment::where('reference', $paymentReference)->exists()) {
                throw ValidationException::withMessages(['payment_reference' => 'Этот reference уже записан как SaaS-платёж.']);
            }

            $target = $this->plans->get($lockedRequest->requested_plan);
            if (! $target) {
                throw ValidationException::withMessages(['request' => 'Тариф больше недоступен.']);
            }

            if ($approve) {
                $employeeSeats = $subscription->organization()->withCount([
                    'users' => fn ($query) => $query->whereNotIn('role', ['client', 'platform_admin']),
                ])->firstOrFail()->users_count;
                if ($employeeSeats > $target['max_users']) {
                    throw ValidationException::withMessages(['request' => 'Сначала уменьшите число сотрудников до лимита тарифа.']);
                }

                $subscription->update([
                    'plan' => $target['key'],
                    'status' => 'active',
                    'max_users' => $target['max_users'],
                    'trial_ends_at' => null,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                ]);
            }

            $lockedRequest->update([
                'status' => $approve ? 'approved' : 'rejected',
                'payment_reference' => $approve ? $paymentReference : null,
                'reviewed_by' => $admin->id,
                'review_note' => $reviewNote,
                'reviewed_at' => now(),
            ]);

            if ($approve) {
                $payment = SaasPayment::create([
                    'organization_id' => $lockedRequest->organization_id,
                    'subscription_id' => $subscription->id,
                    'subscription_change_request_id' => $lockedRequest->id,
                    'plan' => $lockedRequest->requested_plan,
                    'amount' => (int) $lockedRequest->quoted_amount,
                    'currency' => $lockedRequest->currency,
                    'method' => 'bank_transfer',
                    'status' => 'paid',
                    'reference' => $paymentReference,
                    'description' => 'Оплата тарифа '.($target['name'] ?? $lockedRequest->requested_plan),
                    'paid_at' => now(),
                    'created_by' => $admin->id,
                ]);
                SaasRevenue::create([
                    'organization_id' => $lockedRequest->organization_id,
                    'payment_id' => $payment->id,
                    'category' => 'subscription',
                    'amount' => (int) $lockedRequest->quoted_amount,
                    'currency' => $lockedRequest->currency,
                    'received_at' => now()->toDateString(),
                    'description' => 'Оплата тарифа '.($target['name'] ?? $lockedRequest->requested_plan),
                    'created_by' => $admin->id,
                ]);
                ActivityLog::create([
                    'organization_id' => $lockedRequest->organization_id,
                    'user_id' => $admin->id,
                    'action' => 'saas.payment.created',
                    'subject_type' => $payment->getMorphClass(),
                    'subject_id' => $payment->id,
                    'metadata' => ['amount' => (int) $payment->amount, 'currency' => $payment->currency, 'reference' => $payment->reference],
                    'ip_address' => $request?->ip(),
                    'user_agent' => $request?->userAgent(),
                    'created_at' => now(),
                ]);
            }

            ActivityLog::create([
                'organization_id' => $lockedRequest->organization_id,
                'user_id' => $admin->id,
                'action' => $approve ? 'subscription.plan_activated' : 'subscription.plan_change_rejected',
                'subject_type' => $lockedRequest->getMorphClass(),
                'subject_id' => $lockedRequest->id,
                'metadata' => [
                    'from' => $lockedRequest->current_plan,
                    'to' => $lockedRequest->requested_plan,
                    'quoted_amount' => (int) $lockedRequest->quoted_amount,
                    'currency' => $lockedRequest->currency,
                    'payment_reference' => $approve ? $paymentReference : null,
                    'review_note' => $reviewNote,
                ],
                'created_at' => now(),
            ]);

            return $lockedRequest->refresh();
        });
    }
}
