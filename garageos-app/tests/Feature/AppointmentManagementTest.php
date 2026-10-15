<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_and_cancel_appointment(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'ТО', 'price' => 10000, 'duration_minutes' => 60]);
        $pending = AppointmentStatus::create(['organization_id' => $organization->id, 'name' => 'Ожидает', 'slug' => 'pending', 'sort_order' => 1]);
        $confirmed = AppointmentStatus::create(['organization_id' => $organization->id, 'name' => 'Подтверждена', 'slug' => 'confirmed', 'sort_order' => 2]);
        $cancelled = AppointmentStatus::create(['organization_id' => $organization->id, 'name' => 'Отменена', 'slug' => 'cancelled', 'sort_order' => 3, 'is_terminal' => true]);
        $appointment = Appointment::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'car_id' => $car->id, 'service_id' => $service->id, 'status_id' => $pending->id, 'status' => 'pending', 'start_at' => now()->addDay()->setTime(10, 0), 'end_at' => now()->addDay()->setTime(11, 0), 'created_by' => $user->id]);

        $this->actingAs($user)->get("/appointments/{$appointment->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Appointments/Edit')->where('appointment.id', $appointment->id));

        $this->actingAs($user)->put("/appointments/{$appointment->id}", ['client_id' => $client->id, 'car_id' => $car->id, 'service_id' => $service->id, 'status' => 'confirmed', 'start_at' => now()->addDay()->setTime(12, 0), 'end_at' => now()->addDay()->setTime(13, 0)])->assertRedirect();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed', 'status_id' => $confirmed->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'appointment.updated', 'subject_id' => $appointment->id]);

        $this->actingAs($user)->delete("/appointments/{$appointment->id}")->assertRedirect();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelled', 'status_id' => $cancelled->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'appointment.cancelled', 'subject_id' => $appointment->id]);
    }

    public function test_appointment_entry_can_preselect_only_a_tenant_client(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        $otherOrganization = Organization::create(['name' => 'Other', 'slug' => 'other']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $privateClient = Client::create(['organization_id' => $otherOrganization->id, 'name' => 'Private', 'phone' => '+77000000001']);

        $this->actingAs($user)->get("/appointments?client_id={$client->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Appointments/Index')->where('selectedClientId', $client->id));

        $this->actingAs($user)->get("/appointments?client_id={$privateClient->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selectedClientId', null));
    }

    public function test_month_calendar_returns_all_daily_counts_for_the_selected_month(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'ТО', 'price' => 10000, 'duration_minutes' => 60]);
        $status = AppointmentStatus::create(['organization_id' => $organization->id, 'name' => 'Ожидает', 'slug' => 'pending', 'sort_order' => 1]);
        $month = now()->startOfMonth();

        foreach ([2, 2, 12] as $day) {
            $start = $month->copy()->addDays($day - 1)->setTime(10, 0);
            Appointment::create([
                'organization_id' => $organization->id,
                'client_id' => $client->id,
                'car_id' => $car->id,
                'service_id' => $service->id,
                'status_id' => $status->id,
                'status' => 'pending',
                'start_at' => $start,
                'end_at' => $start->copy()->addHour(),
                'created_by' => $user->id,
            ]);
        }

        $outsideMonth = $month->copy()->subDay()->setTime(10, 0);
        Appointment::create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'car_id' => $car->id,
            'service_id' => $service->id,
            'status_id' => $status->id,
            'status' => 'pending',
            'start_at' => $outsideMonth,
            'end_at' => $outsideMonth->copy()->addHour(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get('/appointments?view=month&date=' . $month->toDateString())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Month')
                ->where('periodTotal', 3)
                ->where('dayCounts.' . $month->copy()->addDay()->toDateString(), 2)
                ->where('dayCounts.' . $month->copy()->addDays(11)->toDateString(), 1));
    }

    public function test_archived_car_cannot_be_used_for_a_new_appointment(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry', 'active' => false]);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'ТО', 'price' => 10000, 'duration_minutes' => 60]);
        AppointmentStatus::create(['organization_id' => $organization->id, 'name' => 'Ожидает', 'slug' => 'pending', 'sort_order' => 1]);

        $this->actingAs($user)->post('/appointments', [
            'client_id' => $client->id,
            'car_id' => $car->id,
            'service_id' => $service->id,
            'start_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
            'end_at' => now()->addDay()->setTime(11, 0)->toDateTimeString(),
        ])->assertRedirect()->assertSessionHasErrors('car_id');

        $this->assertDatabaseCount('appointments', 0);
    }
}
