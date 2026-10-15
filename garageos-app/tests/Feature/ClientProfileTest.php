<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Appointment;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_client_profile_summary(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'BMW', 'model' => 'X5']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new', 'sort_order' => 1]);
        Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now(), 'total' => 100000, 'paid_total' => 40000, 'balance' => 60000]);

        $this->actingAs($user)->get("/clients/{$client->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Show')
                ->where('client.summary.cars', 1)
                ->where('client.summary.orders', 1)
                ->where('client.summary.completedVisits', 0)
                ->where('client.summary.needsFollowUp', false)
                ->where('client.summary.balance', 60000));
    }

    public function test_client_profile_is_tenant_scoped(): void
    {
        $first = Organization::create(['name' => 'First', 'slug' => 'first']);
        $second = Organization::create(['name' => 'Second', 'slug' => 'second']);
        Subscription::create(['organization_id' => $first->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $first->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $second->id, 'name' => 'Private', 'phone' => '+77000000001']);

        $this->actingAs($user)->get("/clients/{$client->id}")->assertNotFound();
    }

    public function test_client_history_is_paginated_and_includes_related_events(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage-history']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'BMW', 'model' => 'X5']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'Диагностика', 'price' => 15000, 'duration_minutes' => 60]);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'В работе', 'slug' => 'in_progress', 'sort_order' => 1]);

        for ($index = 0; $index < 21; $index++) {
            Order::create([
                'organization_id' => $organization->id,
                'number' => 'ЗН-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'client_id' => $client->id,
                'car_id' => $car->id,
                'status_id' => $status->id,
                'opened_at' => now()->subDays($index),
                'created_at' => now()->subDays($index),
                'total' => 1000,
                'paid_total' => 500,
                'balance' => 500,
            ]);
        }
        $firstOrder = Order::where('organization_id', $organization->id)->orderBy('id')->firstOrFail();
        OrderItem::create(['organization_id' => $organization->id, 'order_id' => $firstOrder->id, 'type' => 'service', 'name_snapshot' => 'Диагностика', 'quantity' => 1, 'unit_price' => 15000, 'total' => 15000, 'created_at' => now()->subHours(2)]);
        Payment::create(['organization_id' => $organization->id, 'order_id' => $firstOrder->id, 'amount' => 500, 'method' => 'cash', 'status' => 'successful', 'paid_at' => now()->subHours(2), 'created_at' => now()->subHours(2)]);
        $firstOrder->photos()->create(['organization_id' => $organization->id, 'path' => 'history/photo.jpg', 'storage_disk' => 'local', 'category' => 'after', 'visible_to_client' => true, 'created_at' => now()->subHours(2)]);
        Appointment::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'car_id' => $car->id, 'service_id' => $service->id, 'start_at' => now()->subHours(2), 'end_at' => now()->subHour(), 'status' => 'completed', 'created_at' => now()->subHours(2)]);
        ActivityLog::create(['organization_id' => $organization->id, 'user_id' => $user->id, 'action' => 'order.status_changed', 'subject_type' => $firstOrder->getMorphClass(), 'subject_id' => $firstOrder->id, 'metadata' => ['from_status' => 'new', 'to_status' => 'in_progress'], 'created_at' => now()->subHours(2)]);

        $this->actingAs($user)->get("/clients/{$client->id}?page=1")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('client.summary.orders', 21)
                ->where('client.summary.total', 21000)
                ->has('timeline.data', 20));

        $this->get("/clients/{$client->id}?page=2")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('timeline.data', 6)
                ->where('timeline.data', function ($events) {
                    $types = collect($events)->pluck('type')->unique()->sort()->values()->all();
                    return $types === ['appointment', 'order', 'payment', 'photo', 'status', 'work'];
                }));
    }

    public function test_client_index_searches_contacts_and_cars_within_tenant(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        $otherOrganization = Organization::create(['name' => 'Other', 'slug' => 'other']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Alex Garage', 'phone' => '+77000000000']);
        Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry', 'plate_number' => '777ABC01']);
        $privateClient = Client::create(['organization_id' => $otherOrganization->id, 'name' => 'Private Alex', 'phone' => '+77000000001']);
        Car::create(['organization_id' => $otherOrganization->id, 'client_id' => $privateClient->id, 'make' => 'Toyota', 'model' => 'Private', 'plate_number' => '777XYZ01']);

        $this->actingAs($user)->get('/clients?q=777ABC01')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Index')
                ->where('filters.q', '777ABC01')
                ->has('clients.data', 1)
                ->where('clients.data.0.id', $client->id));

        $this->actingAs($user)->get('/clients?q=Private')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('clients.data', 0));

        $this->actingAs($user)->get("/clients?client_id={$client->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selectedClientId', $client->id));

        $this->actingAs($user)->get('/clients?client_id='.$privateClient->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selectedClientId', null));
    }

    public function test_follow_up_segment_only_includes_clients_without_recent_or_upcoming_work(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage-follow-up']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $closed = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Закрыт', 'slug' => 'closed', 'sort_order' => 1, 'is_terminal' => true]);
        $inProgress = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'В работе', 'slug' => 'in_progress', 'sort_order' => 2]);
        $clientToContact = Client::create(['organization_id' => $organization->id, 'name' => 'Давно не был', 'phone' => '+77000000001']);
        $activeClient = Client::create(['organization_id' => $organization->id, 'name' => 'Заказ открыт', 'phone' => '+77000000002']);
        $upcomingClient = Client::create(['organization_id' => $organization->id, 'name' => 'Запись впереди', 'phone' => '+77000000003']);
        $recentClient = Client::create(['organization_id' => $organization->id, 'name' => 'Недавно был', 'phone' => '+77000000004']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $clientToContact->id, 'make' => 'Toyota', 'model' => 'Camry']);

        $makeOrder = function (Client $client, Car $car, OrderStatus $status, string $number, int $daysAgo) use ($organization): Order {
            return Order::create([
                'organization_id' => $organization->id,
                'number' => $number,
                'client_id' => $client->id,
                'car_id' => $car->id,
                'status_id' => $status->id,
                'opened_at' => now()->subDays($daysAgo),
                'completed_at' => $status->slug === 'closed' ? now()->subDays($daysAgo) : null,
                'total' => 10000,
                'paid_total' => 10000,
                'balance' => 0,
            ]);
        };

        $carToContact = $car;
        $makeOrder($clientToContact, $carToContact, $closed, 'FU-001', 150);
        $makeOrder($clientToContact, $carToContact, $closed, 'FU-002', 120);
        $activeCar = Car::create(['organization_id' => $organization->id, 'client_id' => $activeClient->id, 'make' => 'BMW', 'model' => 'X5']);
        $makeOrder($activeClient, $activeCar, $closed, 'FU-003', 150);
        $makeOrder($activeClient, $activeCar, $inProgress, 'FU-004', 2);
        $upcomingCar = Car::create(['organization_id' => $organization->id, 'client_id' => $upcomingClient->id, 'make' => 'Kia', 'model' => 'Rio']);
        $makeOrder($upcomingClient, $upcomingCar, $closed, 'FU-005', 150);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'ТО', 'price' => 10000, 'duration_minutes' => 60]);
        Appointment::create(['organization_id' => $organization->id, 'client_id' => $upcomingClient->id, 'car_id' => $upcomingCar->id, 'service_id' => $service->id, 'start_at' => now()->addDays(5), 'end_at' => now()->addDays(5)->addHour(), 'status' => 'confirmed']);
        $recentCar = Car::create(['organization_id' => $organization->id, 'client_id' => $recentClient->id, 'make' => 'Honda', 'model' => 'Civic']);
        $makeOrder($recentClient, $recentCar, $closed, 'FU-006', 20);

        $this->actingAs($user)->get('/clients?segment=follow_up')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Index')
                ->where('filters.segment', 'follow_up')
                ->where('summary.clients', 4)
                ->where('summary.servicedClients', 4)
                ->where('summary.repeatClients', 1)
                ->where('summary.followUp', 1)
                ->has('clients.data', 1)
                ->where('clients.data.0.name', 'Давно не был')
                ->where('clients.data.0.completed_visits_count', 2)
                ->where('clients.data.0.needs_follow_up', true));
    }
}
