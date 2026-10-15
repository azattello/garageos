<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\SubscriptionChangeRequest;
use App\Services\Billing\PlanCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminSubscriptionController extends Controller
{
	public function index(Request $request, PlanCatalog $plans): Response
	{
		$filters = $request->validate([
			'search' => ['nullable', 'string', 'max:120'],
			'status' => ['nullable', Rule::in(['trialing', 'active', 'expired', 'suspended', 'cancelled'])],
			'plan' => ['nullable', 'string', Rule::in(array_keys($plans->all()))],
			'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
		]);
		$query = Subscription::query()->with('organization:id,name,status');
		$query->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status));
		$query->when($filters['plan'] ?? null, fn (Builder $builder, string $plan) => $builder->where('plan', $plan));
		$query->when($filters['organization_id'] ?? null, fn (Builder $builder, int $organizationId) => $builder->where('organization_id', $organizationId));
		$query->when($filters['search'] ?? null, fn (Builder $builder, string $search) => $builder->whereHas('organization', fn (Builder $organization) => $organization->where('name', 'like', '%'.$search.'%')));

		return Inertia::render('Admin/Subscriptions/Index', [
			'subscriptions' => $query->latest('updated_at')->paginate(25)->withQueryString()->through(fn (Subscription $subscription) => [
				'id' => $subscription->id,
				'organization' => $subscription->organization?->name ?? '—',
				'organization_id' => $subscription->organization_id,
				'organization_status' => $subscription->organization?->status,
				'plan' => $subscription->plan,
				'plan_name' => $plans->get($subscription->plan)['name'] ?? $subscription->plan,
				'amount' => $plans->get($subscription->plan)['price'] ?? 0,
				'status' => $subscription->status,
				'starts_at' => $subscription->starts_at?->toIso8601String(),
				'ends_at' => $subscription->ends_at?->toIso8601String(),
				'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
				'max_users' => $plans->get($subscription->plan)['max_users'] ?? $subscription->max_users,
			]),
			'filters' => $filters,
			'plans' => array_values($plans->all()),
			'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
			'pendingRequests' => SubscriptionChangeRequest::query()->where('status', 'pending')
				->with(['organization:id,name', 'requester:id,name,email'])
				->oldest('requested_at')->paginate(15, ['*'], 'requests_page')->withQueryString()
				->through(fn (SubscriptionChangeRequest $change) => [
					'id' => $change->id,
					'organization' => $change->organization?->name,
					'requester' => $change->requester?->name,
					'current_plan' => $plans->get($change->current_plan)['name'] ?? $change->current_plan,
					'requested_plan' => $plans->get($change->requested_plan)['name'] ?? $change->requested_plan,
					'amount' => (int) $change->quoted_amount,
					'currency' => $change->currency,
					'requested_at' => $change->requested_at?->toIso8601String(),
				]),
		]);
	}

	public function action(Request $request, Subscription $subscription, PlanCatalog $plans): RedirectResponse
	{
		$data = $request->validate([
			'action' => ['required', Rule::in(['activate', 'extend', 'suspend', 'reactivate', 'cancel', 'change_plan'])],
			'days' => [Rule::requiredIf(fn () => in_array($request->input('action'), ['activate', 'extend', 'reactivate'], true)), 'nullable', 'integer', 'min:1', 'max:365'],
			'plan' => [Rule::requiredIf(fn () => $request->input('action') === 'change_plan'), 'nullable', 'string', Rule::in(array_keys($plans->all()))],
			'payment_reference' => [Rule::requiredIf(fn () => in_array($request->input('action'), ['activate', 'change_plan'], true)), 'nullable', 'string', 'max:255'],
			'note' => ['nullable', 'string', 'max:1000'],
		]);

		DB::transaction(function () use ($request, $subscription, $plans, $data): void {
			$locked = Subscription::query()->with('organization')->lockForUpdate()->findOrFail($subscription->id);
			$organization = $locked->organization;
			$before = ['status' => $locked->status, 'plan' => $locked->plan, 'ends_at' => $locked->ends_at?->toIso8601String()];
			$action = $data['action'];

			if ($action === 'change_plan') {
				$target = $plans->get($data['plan']);
				$payment = SaasPayment::query()
					->where('organization_id', $organization->id)
					->where('status', 'paid')
					->where('reference', $data['payment_reference'])
					->where('plan', $target['key'])
					->exists();
				if (! $payment) {
					throw ValidationException::withMessages(['payment_reference' => 'Для смены тарифа нужна проведённая SaaS-оплата с этим reference и выбранным тарифом.']);
				}
				$employeeSeats = $organization->users()->whereNotIn('role', ['client', 'platform_admin'])->count();
				if ($employeeSeats > $target['max_users']) {
					throw ValidationException::withMessages(['plan' => 'Число сотрудников превышает лимит выбранного тарифа.']);
				}
				$locked->update(['plan' => $target['key'], 'max_users' => $target['max_users']]);
			} elseif ($action === 'activate') {
				$target = $plans->get($locked->plan);
				$this->ensurePaidReference($organization->id, $target['key'], $data['payment_reference']);
				$this->ensureSeatLimit($organization, $target['max_users']);
				$locked->update(['status' => 'active', 'trial_ends_at' => null, 'starts_at' => now(), 'ends_at' => now()->addDays((int) $data['days'])]);
			} elseif ($action === 'extend') {
				if ($locked->status !== 'active') {
					throw ValidationException::withMessages(['action' => 'Продлить можно только активную подписку.']);
				}
				$baseDate = $locked->ends_at?->isFuture() ? $locked->ends_at->copy() : now();
				$locked->update(['ends_at' => $baseDate->addDays((int) $data['days'])]);
			} elseif ($action === 'suspend') {
				if ($locked->status === 'cancelled') {
					throw ValidationException::withMessages(['action' => 'Отменённую подписку нельзя приостановить.']);
				}
				$locked->update(['status' => 'suspended']);
			} elseif ($action === 'reactivate') {
				$target = $plans->get($locked->plan);
				$this->ensureSeatLimit($organization, $target['max_users']);
				if (! $locked->ends_at?->isFuture()) {
					if (! filled($data['payment_reference'] ?? null)) {
						throw ValidationException::withMessages(['payment_reference' => 'Для возобновления истёкшей подписки нужен reference оплаченного SaaS-платежа.']);
					}
					$this->ensurePaidReference($organization->id, $target['key'], $data['payment_reference']);
				}
				$endsAt = $locked->ends_at?->isFuture() ? $locked->ends_at : now()->addDays((int) $data['days']);
				$locked->update(['status' => 'active', 'trial_ends_at' => null, 'starts_at' => $locked->starts_at ?? now(), 'ends_at' => $endsAt]);
			} else {
				$locked->update(['status' => 'cancelled']);
			}

			ActivityLog::create([
				'organization_id' => $organization->id,
				'user_id' => $request->user()->id,
				'action' => 'subscription.admin_'.$action,
				'subject_type' => $locked->getMorphClass(),
				'subject_id' => $locked->id,
				'metadata' => ['operation' => $action, 'before' => $before, 'after' => [
					'status' => $locked->status, 'plan' => $locked->plan, 'ends_at' => $locked->ends_at?->toIso8601String(),
				], 'payment_reference' => $data['payment_reference'] ?? null, 'note' => $data['note'] ?? null],
				'ip_address' => $request->ip(),
				'user_agent' => $request->userAgent(),
				'created_at' => now(),
			]);
		});

		return back()->with('success', 'Действие над подпиской выполнено и записано в журнал аудита.');
	}

	private function ensureSeatLimit(Organization $organization, int $limit): void
	{
		$employeeSeats = $organization->users()->whereNotIn('role', ['client', 'platform_admin'])->count();
		if ($employeeSeats > $limit) {
			throw ValidationException::withMessages(['action' => 'Число сотрудников превышает лимит тарифа.']);
		}
	}

	private function ensurePaidReference(int $organizationId, string $plan, string $reference): void
	{
		$paymentExists = SaasPayment::query()
			->where('organization_id', $organizationId)
			->where('plan', $plan)
			->where('status', 'paid')
			->where('reference', $reference)
			->exists();
		if (! $paymentExists) {
			throw ValidationException::withMessages(['payment_reference' => 'Нужен проведённый SaaS-платёж с этим reference и выбранным тарифом.']);
		}
	}
}