<?php

namespace App\Http\Controllers;

use App\Actions\Appointments\CreateAppointment;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Car;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Actions\Appointments\CreateOrderFromAppointment;
use Illuminate\Support\Carbon;
use App\Models\AppointmentStatus;
use App\Http\Requests\UpdateAppointmentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;
use App\Support\Activity;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->organization_id, 403);
        Gate::forUser($user)->authorize('viewAny', Appointment::class);
        $view = $request->string('view', 'day')->toString();
        $view = in_array($view, ['day', 'week', 'month'], true) ? $view : 'day';
        $date = Carbon::parse($request->string('date', now()->toDateString())->toString());
        $query = Appointment::where('organization_id', $user->organization_id)->with(['client', 'car', 'service', 'mechanic'])->orderBy('start_at');
        if ($view === 'week') {
            $query->whereBetween('start_at', [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()]);
        } elseif ($view === 'month') {
            $query->whereBetween('start_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()]);
        } else {
            $query->whereDate('start_at', $date);
        }
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $query
            ->when($search !== '', fn ($builder) => $builder->where(function ($builder) use ($search) {
                $like = "%{$search}%";
                $builder->whereHas('client', fn ($client) => $client->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                    ->orWhereHas('car', fn ($car) => $car->where('make', 'like', $like)->orWhere('model', 'like', $like)->orWhere('plate_number', 'like', $like))
                    ->orWhereHas('service', fn ($service) => $service->where('name', 'like', $like))
                    ->orWhereHas('mechanic', fn ($mechanic) => $mechanic->where('name', 'like', $like));
            }))
            ->when($status !== '' && AppointmentStatus::where('organization_id', $user->organization_id)->where('slug', $status)->exists(), fn ($builder) => $builder->where('status', $status));
        $page = match ($view) {
            'week' => 'Appointments/WeekCalendar',
            'month' => 'Appointments/Month',
            default => 'Appointments/Index',
        };
        $periodTotal = (clone $query)->count();
        $dayCounts = $view === 'month'
            ? (clone $query)
                ->reorder()
                ->selectRaw('DATE(start_at) as appointment_date, COUNT(*) as total')
                ->groupByRaw('DATE(start_at)')
                ->orderBy('appointment_date')
                ->pluck('total', 'appointment_date')
                ->map(fn ($total) => (int) $total)
                ->all()
            : [];
        $appointmentData = $view === 'month' ? collect() : $query->get();
        $requestedClientId = $request->integer('client_id');
        $selectedClientId = $requestedClientId > 0
            ? Client::where('organization_id', $user->organization_id)->whereKey($requestedClientId)->value('id')
            : null;
        $requestedCarId = $request->integer('car_id');
        $selectedCarId = $requestedCarId > 0 && $selectedClientId
            ? Car::where('organization_id', $user->organization_id)->where('client_id', $selectedClientId)->where('active', true)->whereKey($requestedCarId)->value('id')
            : null;

        return Inertia::render($page, [
            'view' => $view,
            'date' => $date->toDateString(),
            'periodTotal' => $periodTotal,
            'dayCounts' => $dayCounts,
            'openCreate' => $request->boolean('create'),
            'selectedClientId' => $selectedClientId,
            'selectedCarId' => $selectedCarId,
            'filters' => ['q' => $search, 'status' => $status],
            'canCreateOrder' => in_array($user->role, ['owner', 'manager'], true),
            'appointments' => ['data' => $appointmentData],
            'clients' => Client::where('organization_id', $user->organization_id)->with(['cars' => fn ($query) => $query->where('active', true)])->orderBy('name')->get(),
            'services' => Service::where('organization_id', $user->organization_id)->where('active', true)->with('category')->orderBy('name')->get(),
            'appointmentStatuses' => AppointmentStatus::where('organization_id', $user->organization_id)->orderBy('sort_order')->get(),
            'mechanics' => $user->organization->users()->whereIn('role', ['owner', 'manager', 'mechanic'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function store(StoreAppointmentRequest $request, CreateAppointment $createAppointment): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', Appointment::class);
        $createAppointment->handle($request->user(), $request->validated());
        return to_route('appointments.index')->with('success', 'Запись создана.');
    }

    public function availability(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->organization_id, 403);
        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'mechanic_id' => ['required', 'integer'],
        ]);
        $mechanicExists = $user->organization->users()
            ->whereKey($data['mechanic_id'])
            ->whereIn('role', ['owner', 'manager', 'mechanic'])
            ->where('status', 'active')
            ->exists();
        abort_unless($mechanicExists, 404);

        $startAt = Carbon::parse($data['start_at']);
        $endAt = Carbon::parse($data['end_at']);
        $hasConflict = Appointment::query()
            ->where('organization_id', $user->organization_id)
            ->where('mechanic_id', $data['mechanic_id'])
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->exists();

        return response()->json(['available' => ! $hasConflict]);
    }

    public function edit(Appointment $appointment): Response
    {
        $user = request()->user();
        abort_unless($appointment->organization_id === $user->organization_id, 404);
        Gate::forUser($user)->authorize('view', $appointment);

        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment->load(['client', 'car', 'service']),
            'clients' => Client::where('organization_id', $user->organization_id)->with(['cars' => fn ($query) => $query->where('active', true)])->orderBy('name')->get(),
            'services' => Service::where('organization_id', $user->organization_id)->where('active', true)->orderBy('name')->get(),
            'appointmentStatuses' => AppointmentStatus::where('organization_id', $user->organization_id)->orderBy('sort_order')->get(),
            'mechanics' => $user->organization->users()->whereIn('role', ['owner', 'manager', 'mechanic'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function createOrder(\App\Models\Appointment $appointment, CreateOrderFromAppointment $createOrderFromAppointment): RedirectResponse
    {
        $order = $createOrderFromAppointment->handle(request()->user(), $appointment);
        return to_route('orders.show', $order)->with('success', "Заказ-наряд {$order->number} создан из записи.");
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($appointment->organization_id === $user->organization_id, 404);
        Gate::forUser($user)->authorize('update', $appointment);
        $organization = $user->organization;
        $data = $request->validated();
        $client = $organization->clients()->find($data['client_id']);
        $car = $client?->cars()->where('active', true)->find($data['car_id']);
        $service = $organization->services()->where('active', true)->find($data['service_id']);
        $mechanic = empty($data['mechanic_id']) ? null : $organization->users()->whereKey($data['mechanic_id'])->whereIn('role', ['owner', 'manager', 'mechanic'])->where('status', 'active')->first();
        $status = $organization->appointmentStatuses()->where('slug', $data['status'])->first();

        if (! $client || ! $car || ! $service || ! $status || (! empty($data['mechanic_id']) && ! $mechanic)) {
            throw ValidationException::withMessages(['client_id' => 'Выбранные данные не принадлежат текущей организации.']);
        }

        if ($mechanic && Appointment::query()->where('organization_id', $user->organization_id)->where('id', '<>', $appointment->id)->where('mechanic_id', $mechanic->id)->whereNotIn('status', ['cancelled', 'completed'])->where('start_at', '<', $data['end_at'])->where('end_at', '>', $data['start_at'])->exists()) {
            throw ValidationException::withMessages(['start_at' => 'Мастер уже занят в это время.']);
        }

        $previousStatus = $appointment->status;
        $appointment->update([...$data, 'status_id' => $status->id]);
        Activity::record('appointment.updated', $appointment, $user, [
            'changed_fields' => array_values(array_diff(array_keys($data), ['comment'])),
            'from_status' => $previousStatus,
            'to_status' => $data['status'],
        ]);
        return to_route('appointments.index')->with('success', 'Запись обновлена.');
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        Gate::forUser(request()->user())->authorize('delete', $appointment);
        $status = request()->user()->organization->appointmentStatuses()->where('slug', 'cancelled')->first();
        $previousStatus = $appointment->status;
        $appointment->update(['status' => 'cancelled', 'status_id' => $status?->id]);
        Activity::record('appointment.cancelled', $appointment, request()->user(), ['from_status' => $previousStatus, 'to_status' => 'cancelled']);
        return back()->with('success', 'Запись отменена.');
    }
}
