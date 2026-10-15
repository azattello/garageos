<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_cannot_have_overlapping_appointments(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'Диагностика', 'price' => 15000, 'duration_minutes' => 60]);
        Appointment::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'car_id' => $car->id, 'mechanic_id' => $owner->id, 'service_id' => $service->id, 'start_at' => '2026-09-19 10:00:00', 'end_at' => '2026-09-19 11:00:00', 'created_by' => $owner->id]);

        $response = $this->actingAs($owner)->post('/appointments', ['client_id' => $client->id, 'car_id' => $car->id, 'mechanic_id' => $owner->id, 'service_id' => $service->id, 'start_at' => '2026-09-19 10:30:00', 'end_at' => '2026-09-19 11:30:00']);

        $response->assertSessionHasErrors('start_at');
        $this->assertSame(1, Appointment::count());
    }

    public function test_availability_check_is_tenant_scoped_and_detects_overlaps(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'Диагностика', 'price' => 15000, 'duration_minutes' => 60]);
        Appointment::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'car_id' => $car->id, 'mechanic_id' => $owner->id, 'service_id' => $service->id, 'start_at' => '2026-09-19 10:00:00', 'end_at' => '2026-09-19 11:00:00', 'created_by' => $owner->id]);

        $this->actingAs($owner)
            ->getJson('/appointments/availability?mechanic_id='.$owner->id.'&start_at=2026-09-19T10:30&end_at=2026-09-19T11:30')
            ->assertOk()
            ->assertJson(['available' => false]);

        $this->getJson('/appointments/availability?mechanic_id='.$owner->id.'&start_at=2026-09-19T11:00&end_at=2026-09-19T12:00')
            ->assertOk()
            ->assertJson(['available' => true]);

        $otherOrganization = Organization::create(['name' => 'Garage B', 'slug' => 'garage-availability-b']);
        $otherMechanic = User::factory()->create(['organization_id' => $otherOrganization->id, 'role' => 'mechanic']);
        $this->getJson('/appointments/availability?mechanic_id='.$otherMechanic->id.'&start_at=2026-09-19T11:00&end_at=2026-09-19T12:00')
            ->assertNotFound();
    }
}
