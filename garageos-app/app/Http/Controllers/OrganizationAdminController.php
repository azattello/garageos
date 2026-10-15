<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\RegisterOrganization;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\Billing\PlanCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrganizationAdminController extends Controller
{
	public function index(Request $request, PlanCatalog $plans): Response
	{
		$filters = $request->validate([
			'search' => ['nullable', 'string', 'max:120'],
			'status' => ['nullable', Rule::in(['active', 'blocked', 'archived'])],
			'plan' => ['nullable', 'string', Rule::in(array_keys($plans->all()))],
			'subscription_status' => ['nullable', Rule::in(['trialing', 'active', 'expired', 'suspended', 'cancelled'])],
			'from' => ['nullable', 'date_format:Y-m-d'],
			'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
		]);
		$query = Organization::query()
			->with(['owner:id,organization_id,name,email,phone,status,last_login_at', 'subscription:id,organization_id,plan,status,starts_at,ends_at,trial_ends_at'])
			->withCount([
				'users as users_count' => fn (Builder $builder) => $builder->whereNotIn('role', ['client', 'platform_admin']),
				'clients', 'cars', 'appointments', 'orders',
			]);
		$query->when($filters['search'] ?? null, fn (Builder $builder, string $search) => $builder->where(function (Builder $nested) use ($search): void {
			$nested->where('name', 'like', '%'.$search.'%')
				->orWhere('slug', 'like', '%'.$search.'%')
				->orWhere('email', 'like', '%'.$search.'%')
				->orWhere('phone', 'like', '%'.$search.'%')
				->orWhereHas('owner', fn (Builder $owner) => $owner->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
		}));
		$query->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status));
		$query->when($filters['plan'] ?? null, fn (Builder $builder, string $plan) => $builder->whereHas('subscription', fn (Builder $subscription) => $subscription->where('plan', $plan)));
		$query->when($filters['subscription_status'] ?? null, fn (Builder $builder, string $status) => $builder->whereHas('subscription', fn (Builder $subscription) => $subscription->where('status', $status)));
		$query->when($filters['from'] ?? null, fn (Builder $builder, string $from) => $builder->whereDate('created_at', '>=', $from));
		$query->when($filters['to'] ?? null, fn (Builder $builder, string $to) => $builder->whereDate('created_at', '<=', $to));

		return Inertia::render('Admin/Organizations/Index', [
			'organizations' => $query->latest('created_at')->paginate(25)->withQueryString()->through(fn (Organization $organization) => [
				'id' => $organization->id,
				'name' => $organization->name,
				'slug' => $organization->slug,
				'status' => $organization->status,
				'email' => $organization->email,
				'phone' => $organization->phone,
				'owner' => $organization->owner ? ['name' => $organization->owner->name, 'email' => $organization->owner->email, 'status' => $organization->owner->status, 'last_login_at' => $organization->owner->last_login_at?->toIso8601String()] : null,
				'subscription' => $organization->subscription ? [
					'plan' => $organization->subscription->plan,
					'plan_name' => $plans->get($organization->subscription->plan)['name'] ?? $organization->subscription->plan,
					'status' => $organization->subscription->status,
					'starts_at' => $organization->subscription->starts_at?->toIso8601String(),
					'ends_at' => $organization->subscription->ends_at?->toIso8601String(),
					'trial_ends_at' => $organization->subscription->trial_ends_at?->toIso8601String(),
				] : null,
				'counts' => ['users' => $organization->users_count, 'clients' => $organization->clients_count, 'cars' => $organization->cars_count, 'appointments' => $organization->appointments_count, 'orders' => $organization->orders_count],
				'created_at' => $organization->created_at?->toIso8601String(),
			]),
			'filters' => $filters,
			'plans' => array_values($plans->all()),
		]);
	}

	public function store(Request $request, RegisterOrganization $register, PlanCatalog $plans): RedirectResponse
	{
		$data = $request->validate([
			'name' => ['required', 'string', 'max:160'],
			'owner_name' => ['required', 'string', 'max:120'],
			'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
			'phone' => ['nullable', 'string', 'max:50'],
			'email' => ['nullable', 'email', 'max:255'],
			'plan' => ['required', 'string', Rule::in(array_keys($plans->all()))],
			'trial_days' => ['required', 'integer', 'min:1', 'max:365'],
			'status' => ['required', Rule::in(['active', 'blocked'])],
		]);

		$owner = DB::transaction(function () use ($request, $register, $plans, $data) {
			$owner = $register->handle([
				'name' => $data['owner_name'],
				'business_name' => $data['name'],
				'email' => $data['owner_email'],
				'phone' => $data['phone'] ?? null,
				'password' => Str::random(64),
			]);
			$organization = $owner->organization;
			$organization->update([
				'phone' => $data['phone'] ?? null,
				'email' => $data['email'] ?? $data['owner_email'],
				'status' => $data['status'],
			]);
			$organization->subscription()->update([
				'plan' => $data['plan'],
				'status' => 'trialing',
				'max_users' => $plans->get($data['plan'])['max_users'],
				'trial_ends_at' => now()->addDays((int) $data['trial_days']),
				'starts_at' => now(),
				'ends_at' => null,
			]);
			ActivityLog::create([
				'organization_id' => $organization->id,
				'user_id' => $request->user()->id,
				'action' => 'organization.created_by_platform_admin',
				'subject_type' => $organization->getMorphClass(),
				'subject_id' => $organization->id,
				'metadata' => ['owner_id' => $owner->id, 'plan' => $data['plan'], 'trial_days' => (int) $data['trial_days'], 'status' => $data['status']],
				'ip_address' => $request->ip(),
				'user_agent' => $request->userAgent(),
				'created_at' => now(),
			]);

			return $owner;
		});

		$resetLinkSent = false;
		try {
			$resetLinkSent = Password::sendResetLink(['email' => $owner->email]) === Password::RESET_LINK_SENT;
		} catch (Throwable $exception) {
			report($exception);
		}

		return back()->with('success', $resetLinkSent
			? 'Организация создана. Ссылка для установки пароля отправлена владельцу.'
			: 'Организация создана. Владелец может задать пароль через «Забыли пароль?» на странице входа.');
	}
}