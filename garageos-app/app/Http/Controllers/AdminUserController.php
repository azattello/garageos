<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
	public function index(Request $request): Response
	{
		$filters = $request->validate([
			'search' => ['nullable', 'string', 'max:120'],
			'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
			'role' => ['nullable', Rule::in(['owner', 'manager', 'mechanic', 'client'])],
			'status' => ['nullable', Rule::in(['active', 'inactive'])],
		]);
		$query = User::query()->with('organization:id,name')->whereNotNull('organization_id')->where('role', '!=', 'platform_admin');
		$query->when($filters['search'] ?? null, fn (Builder $builder, string $search) => $builder->where(fn (Builder $nested) => $nested->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')));
		$query->when($filters['organization_id'] ?? null, fn (Builder $builder, int $organizationId) => $builder->where('organization_id', $organizationId));
		$query->when($filters['role'] ?? null, fn (Builder $builder, string $role) => $builder->where('role', $role));
		$query->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status));

		return Inertia::render('Admin/Users/Index', [
			'users' => $query->orderBy('name')->paginate(25)->withQueryString()->through(fn (User $user) => [
				'id' => $user->id,
				'name' => $user->name,
				'email' => $user->email,
				'organization' => $user->organization?->name ?? '—',
				'organization_id' => $user->organization_id,
				'role' => $user->role,
				'status' => $user->status,
				'last_login_at' => $user->last_login_at?->toIso8601String(),
				'created_at' => $user->created_at?->toIso8601String(),
				'can_manage' => $user->role !== 'owner',
			]),
			'filters' => $filters,
			'organizations' => \App\Models\Organization::query()->orderBy('name')->get(['id', 'name']),
			'roles' => ['owner' => 'Владелец', 'manager' => 'Менеджер', 'mechanic' => 'Механик', 'client' => 'Клиент'],
		]);
	}

	public function action(Request $request, User $user): RedirectResponse
	{
		$data = $request->validate([
			'action' => ['required', Rule::in(['change_role', 'set_status'])],
			'role' => [Rule::requiredIf(fn () => $request->input('action') === 'change_role'), 'nullable', Rule::in(['manager', 'mechanic', 'client'])],
			'status' => [Rule::requiredIf(fn () => $request->input('action') === 'set_status'), 'nullable', Rule::in(['active', 'inactive'])],
		]);

		if (! $user->organization_id || $user->role === 'platform_admin') {
			abort(404);
		}
		if ($user->role === 'owner') {
			throw ValidationException::withMessages(['action' => 'Роль и доступ владельца изменяются через отдельный процесс передачи организации.']);
		}

		DB::transaction(function () use ($request, $user, $data): void {
			$locked = User::query()->lockForUpdate()->findOrFail($user->id);
			$before = $data['action'] === 'change_role' ? $locked->role : $locked->status;
			$after = $data['action'] === 'change_role' ? $data['role'] : $data['status'];
			if ($data['action'] === 'change_role' && $locked->role === 'client' && $after !== 'client') {
				$subscription = Subscription::query()->where('organization_id', $locked->organization_id)->first();
				if ($subscription && ! $subscription->hasAvailableSeats(1)) {
					throw ValidationException::withMessages(['role' => 'Лимит сотрудников тарифа достигнут.']);
				}
			}
			$locked->update([$data['action'] === 'change_role' ? 'role' : 'status' => $after]);
			ActivityLog::create([
				'organization_id' => $locked->organization_id,
				'user_id' => $request->user()->id,
				'action' => $data['action'] === 'change_role' ? 'user.role_changed' : 'user.status_changed',
				'subject_type' => $locked->getMorphClass(),
				'subject_id' => $locked->id,
				'metadata' => ['from' => $before, 'to' => $after],
				'ip_address' => $request->ip(),
				'user_agent' => $request->userAgent(),
				'created_at' => now(),
			]);
		});

		return back()->with('success', $data['action'] === 'change_role' ? 'Роль пользователя обновлена.' : 'Статус пользователя обновлён.');
	}
}