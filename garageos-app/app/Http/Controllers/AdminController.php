<?php

namespace App\Http\Controllers;

use App\Actions\Subscriptions\ReviewPlanChange;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\SaasPayment;
use App\Models\SubscriptionChangeRequest;
use App\Services\Admin\SaasMetrics;
use App\Services\Billing\PlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(Request $request, PlanCatalog $plans, SaasMetrics $metrics): Response
    {
        $this->authorizePlatformAdmin($request);
        $dashboard = $metrics->dashboard($plans);

        return Inertia::render('Admin/Index', [
            ...$dashboard,
            'pendingPlanChanges' => SubscriptionChangeRequest::query()
                ->where('status', 'pending')
                ->with(['organization:id,name', 'requester:id,name,email'])
                ->oldest('requested_at')
                ->paginate(8, ['*'], 'requests_page')->withQueryString()->through(fn (SubscriptionChangeRequest $change) => [
                    'id' => $change->id,
                    'organization' => ['id' => $change->organization?->id, 'name' => $change->organization?->name],
                    'requester' => ['name' => $change->requester?->name, 'email' => $change->requester?->email],
                    'current_plan' => $plans->get($change->current_plan)['name'] ?? $change->current_plan,
                    'requested_plan' => $plans->get($change->requested_plan)['name'] ?? $change->requested_plan,
                    'quoted_amount' => (int) $change->quoted_amount,
                    'currency' => $change->currency,
                    'requested_at' => $change->requested_at?->toIso8601String(),
                ]),
        ]);
    }

    public function updateStatus(Request $request, Organization $organization): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('managePlatform', $organization);

        $validated = $request->validate([
            'status' => ['required', 'in:active,blocked,archived'],
        ]);

        DB::transaction(function () use ($request, $organization, $validated): void {
            $previousStatus = $organization->status;
            $organization->update(['status' => $validated['status']]);
            ActivityLog::create([
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
                'action' => $validated['status'] === 'archived' ? 'organization.archived' : 'organization.status_changed',
                'subject_type' => $organization->getMorphClass(),
                'subject_id' => $organization->id,
                'metadata' => ['from' => $previousStatus, 'to' => $validated['status']],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', match ($validated['status']) {
            'blocked' => 'Организация заблокирована.',
            'archived' => 'Организация архивирована без удаления данных.',
            default => 'Организация активирована.',
        });
    }

    public function extendTrial(Request $request, Organization $organization): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('managePlatform', $organization);

        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        DB::transaction(function () use ($request, $organization, $validated): void {
            $subscription = $organization->subscription()->lockForUpdate()->firstOrFail();
            $baseDate = $subscription->trial_ends_at?->isFuture() ? $subscription->trial_ends_at : Carbon::now();
            $subscription->update([
                'status' => 'trialing',
                'trial_ends_at' => $baseDate->copy()->addDays($validated['days']),
                'ends_at' => null,
            ]);
            ActivityLog::create([
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
                'action' => 'subscription.trial_extended',
                'subject_type' => $subscription->getMorphClass(),
                'subject_id' => $subscription->id,
                'metadata' => ['days' => $validated['days'], 'trial_ends_at' => $subscription->trial_ends_at],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', "Trial продлён на {$validated['days']} дн.");
    }

    public function reviewPlanChange(Request $request, SubscriptionChangeRequest $subscriptionChangeRequest, ReviewPlanChange $reviewPlanChange): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('review', $subscriptionChangeRequest);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'payment_reference' => [Rule::requiredIf(fn () => $request->input('decision') === 'approve'), 'nullable', 'string', 'max:255'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviewPlanChange->handle(
            $request->user(),
            $subscriptionChangeRequest,
            $validated['decision'] === 'approve',
            $validated['payment_reference'] ?? null,
            $validated['review_note'] ?? null,
            $request,
        );

        return back()->with('success', $validated['decision'] === 'approve' ? 'Тариф активирован.' : 'Заявка отклонена.');
    }

    public function show(Request $request, Organization $organization, PlanCatalog $plans): Response
    {
        $this->authorizePlatformAdmin($request);
        $subscription = $organization->subscription;
        $payments = SaasPayment::query()->where('organization_id', $organization->id);
        $recentActivity = ActivityLog::query()->with('user:id,name,email')
            ->where('organization_id', $organization->id)->latest('created_at')->limit(20)->get()
            ->map(fn (ActivityLog $event) => [
                'id' => $event->id,
                'actor' => $event->user?->name ?? 'Система',
                'actor_email' => $event->user?->email,
                'action' => $event->action,
                'target' => $event->subject_type ? class_basename($event->subject_type) : null,
                'metadata' => $event->metadata,
                'ip' => $event->ip_address,
                'created_at' => $event->created_at?->toIso8601String(),
            ])->values();

        return Inertia::render('Admin/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'status' => $organization->status,
                'phone' => $organization->phone,
                'email' => $organization->email,
                'timezone' => $organization->timezone,
                'currency' => $organization->currency,
                'created_at' => $organization->created_at,
                'owner' => $organization->owner ? [
                    'id' => $organization->owner->id,
                    'name' => $organization->owner->name,
                    'email' => $organization->owner->email,
                    'phone' => $organization->owner->phone,
                    'status' => $organization->owner->status,
                    'last_login_at' => $organization->owner->last_login_at,
                ] : null,
                'users_count' => $organization->users()->whereNotIn('role', ['client', 'platform_admin'])->count(),
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'plan' => $subscription->plan,
                    'plan_name' => $plans->get($subscription->plan)['name'] ?? $subscription->plan,
                    'price' => $plans->get($subscription->plan)['price'] ?? null,
                    'max_users' => $plans->get($subscription->plan)['max_users'] ?? $subscription->max_users,
                    'status' => $subscription->status,
                    'starts_at' => $subscription->starts_at,
                    'ends_at' => $subscription->ends_at,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'auto_renew' => false,
                ] : null,
            ],
            'users' => $organization->users()->orderBy('name')->get(['id', 'name', 'email', 'role', 'status', 'last_login_at']),
            'stats' => [
                'users' => $organization->users()->whereNotIn('role', ['client', 'platform_admin'])->count(),
                'clients' => $organization->clients()->count(),
                'cars' => $organization->cars()->count(),
                'appointments' => $organization->appointments()->count(),
                'orders' => $organization->orders()->count(),
            ],
            'saasFinance' => [
                'paid' => (int) (clone $payments)->where('status', 'paid')->sum('amount'),
                'refunded' => (int) (clone $payments)->where('status', 'refunded')->sum('amount'),
                'outstanding' => (int) (clone $payments)->where('status', 'pending')->sum('amount'),
                'payments' => (clone $payments)->latest('created_at')->paginate(10, ['*'], 'payments_page')->withQueryString()->through(fn (SaasPayment $payment) => [
                    'id' => $payment->id, 'amount' => (int) $payment->amount, 'currency' => $payment->currency,
                    'plan' => $plans->get($payment->plan ?? '')['name'] ?? $payment->plan,
                    'method' => $payment->method, 'status' => $payment->status, 'reference' => $payment->reference,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ]),
            ],
            'recentActivity' => $recentActivity,
            'subscriptionRequests' => SubscriptionChangeRequest::query()->where('organization_id', $organization->id)
                ->latest('requested_at')->limit(10)->get(['id', 'current_plan', 'requested_plan', 'quoted_amount', 'currency', 'status', 'requested_at', 'reviewed_at'])
                ->map(fn (SubscriptionChangeRequest $change) => [
                    'id' => $change->id,
                    'current_plan' => $plans->get($change->current_plan)['name'] ?? $change->current_plan,
                    'requested_plan' => $plans->get($change->requested_plan)['name'] ?? $change->requested_plan,
                    'amount' => (int) $change->quoted_amount,
                    'currency' => $change->currency,
                    'status' => $change->status,
                    'requested_at' => $change->requested_at?->toIso8601String(),
                    'reviewed_at' => $change->reviewed_at?->toIso8601String(),
                ])->values(),
        ]);
    }

    private function authorizePlatformAdmin(Request $request): void
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);
    }
}
