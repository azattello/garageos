<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->organization_id, 403);
        $canViewActivities = in_array($user->role, ['owner', 'manager'], true);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'actor' => ['nullable', 'integer'],
            'entity' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $activities = null;
        $actors = [];
        $entities = [];
        if ($canViewActivities) {
            $activityQuery = ActivityLog::where('organization_id', $user->organization_id)
                ->with('user:id,name')
                ->when($filters['q'] ?? null, function ($query, string $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('action', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                        if (is_numeric($search)) {
                            $query->orWhere('subject_id', (int) $search);
                        }
                    });
                })
                ->when($filters['actor'] ?? null, fn ($query, int $actorId) => $query->where('user_id', $actorId))
                ->when($filters['entity'] ?? null, fn ($query, string $entity) => $query->where('subject_type', $entity))
                ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
                ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
                ->latest('created_at');

            $activities = $activityQuery->paginate(20)->withQueryString()->through(fn (ActivityLog $activity) => [
                'id' => $activity->id,
                'action' => $activity->action,
                'created_at' => $activity->created_at,
                'actor' => $activity->user?->name ?? 'Система',
                'entity' => $activity->subject_type ? class_basename($activity->subject_type) : 'Система',
                'subject_id' => $activity->subject_id,
                'before' => $this->safeDetails($activity->metadata['before'] ?? null),
                'after' => $this->safeDetails($activity->metadata['after'] ?? null),
            ]);

            $actorIds = ActivityLog::where('organization_id', $user->organization_id)->whereNotNull('user_id')->distinct()->pluck('user_id');
            $actors = User::whereIn('id', $actorIds)->orderBy('name')->get(['id', 'name']);
            $entities = ActivityLog::where('organization_id', $user->organization_id)
                ->whereNotNull('subject_type')
                ->distinct()
                ->orderBy('subject_type')
                ->pluck('subject_type')
                ->map(fn (string $entity) => ['value' => $entity, 'label' => class_basename($entity)])
                ->values();
        }

        return Inertia::render('Notifications/Index', [
            'notifications' => Notification::where('user_id', $user->id)->latest()->paginate(25),
            'activities' => $activities,
            'activityFilters' => [
                'q' => (string) ($filters['q'] ?? ''),
                'actor' => (string) ($filters['actor'] ?? ''),
                'entity' => (string) ($filters['entity'] ?? ''),
                'from' => (string) ($filters['from'] ?? ''),
                'to' => (string) ($filters['to'] ?? ''),
            ],
            'activityActors' => $actors,
            'activityEntities' => $entities,
            'canViewActivities' => $canViewActivities,
        ]);
    }

    private function safeDetails(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $details = [];
        foreach ($value as $key => $item) {
            if (preg_match('/password|token|secret|credential|authorization|cookie|api[_-]?key/i', (string) $key)) {
                continue;
            }
            $details[$key] = $this->safeDetails($item);
        }

        return $details;
    }

    public function read(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === request()->user()->id, 404);
        $notification->update(['read_at' => now()]);
        return back();
    }

    public function readAll(): RedirectResponse
    {
        $user = request()->user();

        Notification::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
