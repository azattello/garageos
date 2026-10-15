<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_financials_and_assigned_mechanic_sees_only_work_details(): void
    {
        [$organization, $owner, $mechanic, $order] = $this->createOrderFixture();

        $this->actingAs($owner)
            ->get("/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Show')
                ->where('canManageFinances', true)
                ->where('order.total', 32000)
                ->where('order.balance', 32000)
                ->where('nextStatuses.0.slug', 'accepted')
                ->where('nextStatuses.0.name', 'Принят')
                ->has('mechanics', 1)
                ->where('mechanics.0.id', $mechanic->id));

        $this->actingAs($mechanic)
            ->get("/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Show')
                ->where('canManageFinances', false)
                ->where('order.total', null)
                ->where('order.balance', null)
                ->where('order.items.0.total', null)
                ->has('mechanics', 0)
                ->has('order.payments', 0));

        $this->assertSame($organization->id, $order->organization_id);
    }

    public function test_mechanic_cannot_open_another_mechanics_order_or_kanban(): void
    {
        [, , $mechanic, $order] = $this->createOrderFixture();
        $otherMechanic = User::factory()->create(['organization_id' => $order->organization_id, 'role' => 'mechanic']);

        $this->actingAs($mechanic)
            ->get('/orders')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/List')
                ->where('canViewFinancials', false)
                ->has('orders.data', 1)
                ->where('orders.data.0.number', $order->number));

        $this->actingAs($otherMechanic)->get("/orders/{$order->id}")->assertForbidden();
        $this->actingAs($mechanic)->get('/orders/kanban')->assertForbidden();
    }

    public function test_kanban_exposes_backend_allowed_status_transitions(): void
    {
        [, $owner, , $order] = $this->createOrderFixture();

        $this->actingAs($owner)->get('/orders/kanban')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Kanban')
                ->where('orders.0.status.slug', 'new')
                ->where('orders.0.nextStatuses.0.slug', 'accepted')
                ->where('orders.0.nextStatuses.1.slug', 'cancelled'));
    }

    public function test_order_form_can_preselect_a_tenant_client_and_active_car(): void
    {
        [, $owner, , $order] = $this->createOrderFixture();

        $this->actingAs($owner)->get("/orders?client_id={$order->client_id}&car_id={$order->car_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/List')
                ->where('selectedClientId', $order->client_id)
                ->where('selectedCarId', $order->car_id));
    }

    public function test_mechanic_cannot_register_payment_for_assigned_order(): void
    {
        [, , $mechanic, $order] = $this->createOrderFixture();

        $this->actingAs($mechanic)
            ->post("/orders/{$order->id}/payments", ['amount' => 1000, 'method' => 'cash'])
            ->assertForbidden();
    }

    public function test_owner_can_assign_only_an_active_mechanic_from_the_same_organization(): void
    {
        [$organization, $owner, , $order] = $this->createOrderFixture();
        $nextMechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);
        $otherOrganization = Organization::create(['name' => 'Other Garage', 'slug' => 'other-garage']);
        $privateMechanic = User::factory()->create(['organization_id' => $otherOrganization->id, 'role' => 'mechanic']);

        $this->actingAs($owner)->put("/orders/{$order->id}/mechanic", ['mechanic_id' => $nextMechanic->id])->assertRedirect(route('orders.show', $order));
        $this->assertSame($nextMechanic->id, $order->refresh()->mechanic_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'order.mechanic_assigned', 'subject_id' => $order->id]);

        $this->actingAs($owner)->put("/orders/{$order->id}/mechanic", ['mechanic_id' => $privateMechanic->id])->assertSessionHasErrors('mechanic_id');
        $this->assertSame($nextMechanic->id, $order->refresh()->mechanic_id);
    }

    private function createOrderFixture(): array
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
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new', 'sort_order' => 1]);
        $order = Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00001',
            'client_id' => $client->id,
            'car_id' => $car->id,
            'mechanic_id' => $mechanic->id,
            'status_id' => $status->id,
            'opened_at' => now(),
            'subtotal' => 32000,
            'total' => 32000,
            'balance' => 32000,
        ]);
        OrderItem::create([
            'organization_id' => $organization->id,
            'order_id' => $order->id,
            'type' => 'service',
            'name_snapshot' => 'Диагностика',
            'quantity' => 1,
            'unit_price' => 32000,
            'total' => 32000,
        ]);

        return [$organization, $owner, $mechanic, $order];
    }
}
