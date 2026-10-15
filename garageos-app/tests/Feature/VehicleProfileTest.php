<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_vehicle_profile_summary(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry', 'mileage' => 80000]);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'В работе', 'slug' => 'in_progress', 'sort_order' => 1, 'is_terminal' => false]);
        Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00002', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now(), 'total' => 120000, 'paid_total' => 20000, 'balance' => 100000]);

        $this->actingAs($user)->get("/cars/{$car->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cars/Show')
                ->where('car.summary.orders', 1)
                ->where('car.summary.balance', 100000)
                ->where('car.summary.active_order.number', 'ЗН-00002'));
    }

    public function test_vehicle_profile_is_tenant_scoped(): void
    {
        $first = Organization::create(['name' => 'First', 'slug' => 'first']);
        $second = Organization::create(['name' => 'Second', 'slug' => 'second']);
        Subscription::create(['organization_id' => $first->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $first->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $second->id, 'name' => 'Private', 'phone' => '+77000000001']);
        $car = Car::create(['organization_id' => $second->id, 'client_id' => $client->id, 'make' => 'BMW', 'model' => 'X5']);

        $this->actingAs($user)->get("/cars/{$car->id}")->assertNotFound();
    }

    public function test_owner_can_search_tenant_cars_by_vehicle_or_client(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        $otherOrganization = Organization::create(['name' => 'Other', 'slug' => 'other']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex Garage', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry', 'plate_number' => '777ABC01']);
        $otherClient = Client::create(['organization_id' => $otherOrganization->id, 'name' => 'Private Client', 'phone' => '+77000000001']);
        Car::create(['organization_id' => $otherOrganization->id, 'client_id' => $otherClient->id, 'make' => 'Toyota', 'model' => 'Private', 'plate_number' => '777XYZ01']);

        $this->actingAs($user)->get('/cars?q=777ABC01')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cars/Index')
                ->where('filters.q', '777ABC01')
                ->has('cars.data', 1)
                ->where('cars.data.0.id', $car->id));

        $this->actingAs($user)->get('/cars?q=Alex%20Garage')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('cars.data', 1)
                ->where('cars.data.0.client.name', 'Alex Garage'));
    }

    public function test_car_index_exposes_completed_visit_history_and_fleet_summary(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage-car-summary']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $servicedCar = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $newCar = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'BMW', 'model' => 'X5']);
        $closed = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Закрыт', 'slug' => 'closed', 'sort_order' => 1, 'is_terminal' => true]);
        Order::create(['organization_id' => $organization->id, 'number' => 'CAR-001', 'client_id' => $client->id, 'car_id' => $servicedCar->id, 'status_id' => $closed->id, 'opened_at' => now()->subDays(12), 'completed_at' => now()->subDays(10), 'total' => 10000, 'paid_total' => 10000, 'balance' => 0]);

        $this->actingAs($user)->get('/cars')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cars/Index')
                ->where('summary.cars', 2)
                ->where('summary.serviced90Days', 1)
                ->where('summary.withoutHistory', 1)
                ->where('cars.data.1.id', $servicedCar->id)
                ->where('cars.data.1.completed_visits_count', 1)
                ->where('cars.data.1.last_service_at', fn ($value) => str_contains((string) $value, now()->subDays(12)->toDateString())));
    }

    public function test_owner_can_update_archive_and_restore_car_without_losing_order_history(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry', 'mileage' => 80000]);
        $closed = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Закрыт', 'slug' => 'closed', 'sort_order' => 9, 'is_terminal' => true]);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00003', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $closed->id, 'opened_at' => now()->subDay(), 'total' => 10000, 'paid_total' => 10000, 'balance' => 0]);

        $this->actingAs($user)->put("/cars/{$car->id}", [
            'make' => 'Toyota',
            'model' => 'Camry Hybrid',
            'year' => 2024,
            'plate_number' => '777ABC01',
            'vin' => 'JT123456789012345',
            'color' => 'Белый',
            'mileage' => 81000,
            'fuel_type' => 'Гибрид',
            'comment' => 'Обновлённые данные',
        ])->assertRedirect(route('cars.history', $car));
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'model' => 'Camry Hybrid', 'mileage' => 81000]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'car.updated', 'subject_id' => $car->id]);

        $this->actingAs($user)->delete("/cars/{$car->id}")->assertRedirect('/cars?archived=1');
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'active' => false]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'car_id' => $car->id]);
        $this->actingAs($user)->get("/cars/{$car->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('car.active', false)->where('car.summary.orders', 1));

        $this->actingAs($user)->post('/orders', ['client_id' => $client->id, 'car_id' => $car->id])
            ->assertRedirect()
            ->assertSessionHasErrors('car_id');

        $this->actingAs($user)->post("/cars/{$car->id}/restore")->assertRedirect('/cars');
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'active' => true]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'car.archived', 'subject_id' => $car->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'car.restored', 'subject_id' => $car->id]);
    }

    public function test_car_with_open_order_cannot_be_archived(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'В работе', 'slug' => 'in_progress', 'sort_order' => 1]);
        Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00004', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now(), 'total' => 10000, 'balance' => 10000]);

        $this->actingAs($user)->from("/cars/{$car->id}")->delete("/cars/{$car->id}")
            ->assertRedirect("/cars/{$car->id}")
            ->assertSessionHasErrors('car');
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'active' => true]);
    }

    public function test_owner_cannot_update_or_archive_another_organizations_car(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        $otherOrganization = Organization::create(['name' => 'Other', 'slug' => 'other']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $otherOrganization->id, 'name' => 'Private', 'phone' => '+77000000001']);
        $car = Car::create(['organization_id' => $otherOrganization->id, 'client_id' => $client->id, 'make' => 'BMW', 'model' => 'X5']);

        $this->actingAs($user)->put("/cars/{$car->id}", ['make' => 'Changed', 'model' => 'Changed'])->assertNotFound();
        $this->actingAs($user)->delete("/cars/{$car->id}")->assertNotFound();
        $this->assertDatabaseHas('cars', ['id' => $car->id, 'make' => 'BMW', 'active' => true]);
    }
}
