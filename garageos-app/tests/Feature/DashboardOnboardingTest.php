<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_progress_uses_only_records_from_the_authenticated_organization(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create([
            'organization_id' => $organization->id,
            'plan' => 'start',
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
            'max_users' => 3,
        ]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $otherOrganization = Organization::create(['name' => 'Garage B', 'slug' => 'garage-b']);
        Service::create(['organization_id' => $otherOrganization->id, 'name' => 'Other service', 'price' => 1000, 'duration_minutes' => 30]);
        Client::create(['organization_id' => $otherOrganization->id, 'name' => 'Other client', 'phone' => '+77000000000']);

        $this->actingAs($owner)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('onboarding', 6)
                ->where('onboarding.0.completed', false)
                ->where('onboarding.1.completed', false)
                ->where('onboarding.3.completed', false));

        $organization->update(['phone' => '+77001234567', 'email' => 'service@example.test']);
        $owner->refresh();
        $this->actingAs($owner);

        Service::create(['organization_id' => $organization->id, 'name' => 'Диагностика', 'price' => 15000, 'duration_minutes' => 60]);
        User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic', 'status' => 'active']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new', 'sort_order' => 1]);
        Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00001',
            'client_id' => $client->id,
            'car_id' => $car->id,
            'status_id' => $status->id,
            'opened_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('onboarding.1.completed', true)
                ->where('onboarding.2.completed', true)
                ->where('onboarding.3.completed', true)
                ->where('onboarding.4.completed', true)
                ->where('onboarding.5.completed', true)
                ->where('onboarding.0.completed', true));
    }
}
