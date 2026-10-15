<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\InviteEmployee;
use App\Actions\Organizations\SetEmployeeActive;
use App\Actions\Organizations\UpdateEmployee;
use App\Http\Requests\AcceptInviteRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\StoreInviteRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserInvite;
use App\Support\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        Gate::forUser($user)->authorize('viewAny', UserInvite::class);

        $employees = User::query()
            ->where('organization_id', $user->organization_id)
            ->withCount([
                'assignedOrders as active_orders_count' => fn (Builder $query) => $query->whereHas('status', fn (Builder $status) => $status->whereIn('slug', ['accepted', 'diagnostics', 'in_progress', 'waiting_parts'])),
                'assignedOrders as completed_orders_count' => fn (Builder $query) => $query->whereHas('status', fn (Builder $status) => $status->whereIn('slug', ['ready', 'delivered', 'closed']))->where('completed_at', '>=', now()->subDays(30)),
            ])
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'manager' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'specialization', 'role', 'status', 'last_login_at']);

        $invites = UserInvite::where('organization_id', $user->organization_id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get(['id', 'email', 'role', 'expires_at', 'accepted_at']);
        $employeeAnalytics = $employees
            ->where('role', 'mechanic')
            ->sortByDesc('completed_orders_count')
            ->take(8)
            ->map(fn (User $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'active_orders' => (int) $employee->active_orders_count,
                'completed_orders' => (int) $employee->completed_orders_count,
            ])
            ->values();

        return Inertia::render('Employees/Team', [
            'employees' => $employees,
            'invites' => $invites,
            'employeeAnalytics' => $employeeAnalytics,
            'canManageAccounts' => $user->role === 'owner',
            'summary' => [
                'total' => $employees->count(),
                'active' => $employees->where('status', 'active')->count(),
                'mechanics' => $employees->where('role', 'mechanic')->count(),
                'pendingInvites' => $invites->count(),
                'activeOrders' => $employees->sum('active_orders_count'),
                'completedOrders30Days' => $employees->where('role', 'mechanic')->sum('completed_orders_count'),
            ],
        ]);
    }

    public function updateEmployee(UpdateEmployeeRequest $request, User $employee, UpdateEmployee $updateEmployee): RedirectResponse
    {
        abort_unless($employee->organization_id === $request->user()->organization_id, 404);
        Gate::forUser($request->user())->authorize('manageAccount', $employee);
        $updateEmployee->handle($request->user(), $employee, $request->validated());

        return to_route('employees.index')->with('success', 'Данные сотрудника обновлены.');
    }

    public function setEmployeeActive(Request $request, User $employee, SetEmployeeActive $setEmployeeActive): RedirectResponse
    {
        abort_unless($employee->organization_id === $request->user()->organization_id, 404);
        Gate::forUser($request->user())->authorize('manageAccount', $employee);
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $setEmployeeActive->handle($request->user(), $employee, (bool) $data['active']);

        return to_route('employees.index')->with('success', $data['active'] ? 'Сотрудник активирован.' : 'Сотрудник деактивирован.');
    }

    public function invite(StoreInviteRequest $request, InviteEmployee $inviteEmployee): RedirectResponse
    {
        $result = $inviteEmployee->handle($request->user(), $request->validated());
        $inviteUrl = route('invite.accept.form', ['token' => $result['token']]);

        return to_route('employees.index')
            ->with('success', 'Ссылка-приглашение создана. Отправьте её сотруднику.')
            ->with('invite_url', $inviteUrl);
    }

    public function resendInvite(UserInvite $invite, InviteEmployee $inviteEmployee): RedirectResponse
    {
        $user = request()->user();
        Gate::forUser($user)->authorize('manage', $invite);
        abort_if($invite->accepted_at, 422, 'Приглашение уже принято.');

        $invite->update(['expires_at' => now()]);
        $result = $inviteEmployee->handle($user, ['email' => $invite->email, 'role' => $invite->role]);

        return to_route('employees.index')
            ->with('success', 'Создана новая ссылка-приглашение.')
            ->with('invite_url', route('invite.accept.form', ['token' => $result['token']]));
    }

    public function revokeInvite(UserInvite $invite): RedirectResponse
    {
        $user = request()->user();
        Gate::forUser($user)->authorize('manage', $invite);
        abort_if($invite->accepted_at, 422, 'Приглашение уже принято.');

        $invite->update(['expires_at' => now()]);

        return to_route('employees.index')->with('success', 'Приглашение отозвано.');
    }

    public function acceptForm(string $token): Response
    {
        $invite = UserInvite::where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return Inertia::render('Auth/InviteAccept', [
            'token' => $token,
            'email' => $invite->email,
            'role' => $invite->role,
        ]);
    }

    public function accept(AcceptInviteRequest $request, string $token): RedirectResponse
    {
        $invite = UserInvite::where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invite) {
            abort(404);
        }

        if (User::where('email', $invite->email)->exists()) {
            return back()->withErrors(['email' => 'Пользователь с таким email уже существует.']);
        }

        $subscription = $invite->organization?->subscription()->first();
        if ($subscription && ! $subscription->hasAvailableSeats(1)) {
            return back()->withErrors(['email' => 'Лимит пользователей по тарифу достигнут.']);
        }

        $user = User::create([
            'organization_id' => $invite->organization_id,
            'name' => $request->string('name')->trim(),
            'email' => $invite->email,
            'phone' => null,
            'role' => $invite->role,
            'status' => 'active',
            'password' => $request->string('password'),
        ]);

        $invite->update(['accepted_at' => now()]);
        Activity::record('employee.invite_accepted', $invite, $user, ['user_id' => $user->id, 'role' => $user->role]);

        Notification::create([
            'organization_id' => $invite->organization_id,
            'user_id' => $user->id,
            'type' => 'employee_invite_accepted',
            'title' => 'Приглашение принято',
            'body' => 'Аккаунт создан и вы добавлены в команду.',
            'data' => ['invite_id' => $invite->id],
        ]);

        if ($invite->invited_by) {
            Notification::create([
                'organization_id' => $invite->organization_id,
                'user_id' => $invite->invited_by,
                'type' => 'employee_invite_accepted',
                'title' => 'Сотрудник подключился',
                'body' => "{$user->name} принял приглашение и присоединился к команде.",
                'data' => ['invite_id' => $invite->id, 'user_id' => $user->id],
            ]);
        }

        Auth::login($user);

        return redirect()->intended('/');
    }
}
