<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Models\User;
use App\Models\AppointmentStatus;
use App\Support\Activity;
use Illuminate\Validation\ValidationException;

class CreateAppointment
{
    public function handle(User $user, array $data): Appointment
    {
        abort_unless($user->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);
        $organization = $user->organization;
        $client = $organization->clients()->find($data['client_id']);
        $car = $client?->cars()->where('organization_id', $user->organization_id)->where('active', true)->find($data['car_id']);
        $service = $organization->services()->where('active', true)->find($data['service_id']);
        $appointmentStatus = $organization->appointmentStatuses()->where('slug', 'pending')->first();
        $mechanic = empty($data['mechanic_id']) ? null : $organization->users()->whereKey($data['mechanic_id'])->whereIn('role', ['owner', 'manager', 'mechanic'])->where('status', 'active')->first();
        if (! $client || ! $car || ! $service || ! $appointmentStatus || (! empty($data['mechanic_id']) && ! $mechanic)) {
            $field = $client && ! $car ? 'car_id' : 'client_id';
            throw ValidationException::withMessages([$field => 'Выбранные данные не принадлежат текущей организации или автомобиль архивирован.']);
        }
        if ($mechanic && Appointment::query()->where('organization_id', $user->organization_id)->where('mechanic_id', $mechanic->id)->whereNotIn('status', ['cancelled', 'completed'])->where('start_at', '<', $data['end_at'])->where('end_at', '>', $data['start_at'])->exists()) {
            throw ValidationException::withMessages(['start_at' => 'Мастер уже занят в это время.']);
        }
        $appointment = Appointment::create([...$data, 'organization_id' => $user->organization_id, 'created_by' => $user->id, 'status_id' => $appointmentStatus->id, 'status' => $appointmentStatus->slug]);
        Activity::record('appointment.created', $appointment, $user);

        return $appointment;
    }
}
