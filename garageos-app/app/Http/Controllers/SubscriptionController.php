<?php

namespace App\Http\Controllers;

use App\Actions\Subscriptions\RequestPlanChange;
use App\Models\SaasSetting;
use App\Models\SubscriptionChangeRequest;
use App\Services\Billing\PlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function show(PlanCatalog $plans): Response|RedirectResponse
    {
        $user = request()->user();
        if ($user->isPlatformAdmin()) {
            return to_route('admin.index');
        }

        $organization = $user->organization;
        Gate::forUser($user)->authorize('viewSubscription', $organization);

        return Inertia::render('Subscription/Show', [
            'subscription' => $organization->subscription,
            'plans' => array_values($plans->all()),
            'employeeSeats' => $organization->users()->whereNotIn('role', ['client', 'platform_admin'])->count(),
            'pendingRequest' => SubscriptionChangeRequest::where('organization_id', $organization->id)->where('status', 'pending')->latest()->first(),
            'organizationStatus' => $organization->status,
            'blockedMessage' => session('organization_blocked'),
            'featureUnavailable' => session('feature_unavailable'),
            'subscriptionExpired' => session('subscription_expired'),
                        'canManageSubscription' => $user->role === 'owner',
                        'supportContact' => SaasSetting::query()->whereIn('key', ['support_email', 'support_phone', 'support_url'])->pluck('value', 'key'),
        ]);
    }

    public function requestPlanChange(Request $request, RequestPlanChange $requestPlanChange): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'owner' && $user->organization_id, 403);
        Gate::forUser($user)->authorize('manageSubscription', $user->organization);
        $data = $request->validate(['plan' => ['required', 'string', Rule::in(array_keys(app(PlanCatalog::class)->all()))]]);
        $requestPlanChange->handle($user, $data['plan']);

        return to_route('subscription')->with('success', 'Заявка на изменение тарифа отправлена. Доступ обновится после подтверждения оплаты администратором.');
    }
}
