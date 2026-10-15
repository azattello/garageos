<?php

namespace App\Actions\Organizations;

use App\Models\Appointment;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Validation\ValidationException;

class SetEmployeeActive
{
    private const ACTIVE_ORDER_STATUSES = ['accepted', 'diagnostics', 'in_progress', 'waiting_parts'];

    public function handle(User $actor, User $employee, bool $active): User
    {
        abort_unless($actor->organization_id === $employee->organization_id && $actor->role === 'owner' && $employee->role !== 'owner' && $actor->id !== $employee->id, 404);

        if ($employee->status === ($active ? 'active' : 'inactive')) {
            return $employee;
        }

        $hasActiveOrders = $employee->assignedOrders()->whereHas('status', fn ($query) => $query->whereIn('slug', self::ACTIVE_ORDER_STATUSES))->exists();
        $hasUpcomingAppointments = Appointment::query()
            ->where('organization_id', $employee->organization_id)
            ->where('mechanic_id', $employee->id)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('end_at', '>', now())
            ->exists();
        if (! $active && ($hasActiveOrders || $hasUpcomingAppointments)) {
            throw ValidationException::withMessages(['active' => 'Сначала переназначьте активные заказы и будущие записи сотрудника.']);
        }

        $employee->update(['status' => $active ? 'active' : 'inactive']);
        Activity::record($active ? 'employee.activated' : 'employee.deactivated', $employee, $actor, [
            'status' => $employee->status,
        ]);

        return $employee->refresh();
    }
}