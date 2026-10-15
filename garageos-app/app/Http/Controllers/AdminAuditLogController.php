<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $query = ActivityLog::query()->with(['user:id,name,email', 'organization:id,name']);
        $query->when($filters['action'] ?? null, fn (Builder $builder, string $action) => $builder->where('action', 'like', '%'.$action.'%'));
        $query->when($filters['organization_id'] ?? null, fn (Builder $builder, int $id) => $builder->where('organization_id', $id));
        $query->when($filters['user_id'] ?? null, fn (Builder $builder, int $id) => $builder->where('user_id', $id));
        $query->when($filters['from'] ?? null, fn (Builder $builder, string $date) => $builder->whereDate('created_at', '>=', $date));
        $query->when($filters['to'] ?? null, fn (Builder $builder, string $date) => $builder->whereDate('created_at', '<=', $date));
        $query->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
            $builder->where(fn (Builder $nested) => $nested
                ->where('action', 'like', '%'.$search.'%')
                ->orWhere('subject_type', 'like', '%'.$search.'%')
                ->orWhere('metadata', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                ->orWhereHas('organization', fn (Builder $organization) => $organization->where('name', 'like', '%'.$search.'%')));
        });

        return Inertia::render('Admin/AuditLogs/Index', [
            'events' => $query->latest('created_at')->paginate(50)->withQueryString()->through(fn (ActivityLog $event) => [
                'id' => $event->id,
                'actor' => $event->user?->name ?? 'Система',
                'actor_email' => $event->user?->email,
                'action' => $event->action,
                'target_type' => $event->subject_type ? class_basename($event->subject_type) : null,
                'target_id' => $event->subject_id,
                'organization' => $event->organization?->name ?? ($event->organization_id ? 'Организация удалена' : 'Платформа'),
                'organization_id' => $event->organization_id,
                'metadata' => $event->metadata,
                'ip' => $event->ip_address,
                'user_agent' => $event->user_agent,
                'created_at' => $event->created_at?->toIso8601String(),
            ]),
            'filters' => $filters,
            'organizations' => Organization::query()->orderBy('name')->limit(500)->get(['id', 'name']),
            'users' => User::query()->where('role', 'platform_admin')->orderBy('name')->limit(100)->get(['id', 'name']),
        ]);
    }
}