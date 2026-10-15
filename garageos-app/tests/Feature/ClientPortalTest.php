<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPhoto;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_enable_client_portal_with_password_setup_notification(): void
    {
        Notification::fake();
        [$organization, $owner, $client] = $this->createOrganizationOwnerAndClient();

        $this->actingAs($owner)
            ->post("/clients/{$client->id}/portal-access")
            ->assertRedirect()
            ->assertSessionHas('success');

        $portalUser = User::where('email', $client->email)->firstOrFail();
        $this->assertSame('client', $portalUser->role);
        $this->assertSame($organization->id, $portalUser->organization_id);
        $this->assertTrue($client->portalUsers()->whereKey($portalUser->id)->exists());
        $this->assertDatabaseHas('activity_logs', ['action' => 'client.portal_access_enabled', 'subject_id' => $client->id]);
        Notification::assertSentTo($portalUser, ResetPassword::class);
    }

    public function test_manager_can_enable_portal_access_for_a_client_in_their_tenant(): void
    {
        Notification::fake();
        [$organization, , $client] = $this->createOrganizationOwnerAndClient();
        $manager = User::factory()->create(['organization_id' => $organization->id, 'role' => 'manager']);

        $this->actingAs($manager)->post("/clients/{$client->id}/portal-access")
            ->assertRedirect()
            ->assertSessionHas('success');

        $portalUser = User::where('email', $client->email)->firstOrFail();
        $this->assertSame('client', $portalUser->role);
        $this->assertTrue($client->portalUsers()->whereKey($portalUser->id)->exists());
        Notification::assertSentTo($portalUser, ResetPassword::class);
    }

    public function test_client_portal_shows_only_linked_tenant_data_and_client_visible_fields(): void
    {
        [$organization, , $client] = $this->createOrganizationOwnerAndClient();
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry', 'plate_number' => '123ABC']);
        $otherClient = Client::create(['organization_id' => $organization->id, 'name' => 'Чужой клиент', 'phone' => '+77000000001', 'email' => 'other@example.test']);
        $otherCar = Car::create(['organization_id' => $organization->id, 'client_id' => $otherClient->id, 'make' => 'BMW', 'model' => 'X5']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Готов', 'slug' => 'ready']);
        $order = Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00001',
            'client_id' => $client->id,
            'car_id' => $car->id,
            'status_id' => $status->id,
            'opened_at' => now(),
            'internal_comment' => 'Внутреннее примечание секрет',
            'total' => 10000,
            'paid_total' => 3000,
            'balance' => 7000,
        ]);
        Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00002',
            'client_id' => $otherClient->id,
            'car_id' => $otherCar->id,
            'status_id' => $status->id,
            'opened_at' => now(),
            'total' => 50000,
            'paid_total' => 0,
            'balance' => 50000,
        ]);
        $closedStatus = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Закрыт', 'slug' => 'closed', 'is_terminal' => true]);
        $closedOrder = Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00003',
            'client_id' => $client->id,
            'car_id' => $car->id,
            'status_id' => $closedStatus->id,
            'opened_at' => now()->subDays(10),
            'total' => 20000,
            'paid_total' => 20000,
            'balance' => 0,
        ]);
        OrderItem::create([
            'organization_id' => $organization->id,
            'order_id' => $order->id,
            'type' => 'part',
            'name_snapshot' => 'Фильтр',
            'quantity' => 1,
            'unit_price' => 10000,
            'unit_cost' => 4567,
            'discount' => 0,
            'total' => 10000,
        ]);
        OrderPhoto::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'path' => 'public.jpg', 'visible_to_client' => true]);
        OrderPhoto::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'path' => 'private.jpg', 'visible_to_client' => false]);
        $portalUser = User::factory()->create(['organization_id' => $organization->id, 'role' => 'client']);
        $client->portalUsers()->attach($portalUser->id, ['organization_id' => $organization->id]);
        $this->assertTrue(Gate::forUser($portalUser)->allows('viewPortal', $client));
        $this->assertTrue(Gate::forUser($portalUser)->allows('viewPortal', $order));
        $this->assertTrue(Gate::forUser($portalUser)->allows('viewPortal', $car));

        $response = $this->actingAs($portalUser)->get('/portal');

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('ClientPortal/Index')
            ->has('cars', 1)
            ->has('orders', 1)
            ->where('orders.0.number', 'ЗН-00001')
            ->where('orders.0.total', 10000)
            ->where('orders.0.paid', 3000)
            ->where('orders.0.balance', 7000));

        $detail = $this->actingAs($portalUser)->get("/portal/orders/{$order->id}");
        $detail->assertOk()->assertInertia(fn ($page) => $page
            ->component('ClientPortal/Orders/Show')
            ->where('order.items.0.name', 'Фильтр')
            ->where('order.items.0.unit_price', 10000)
            ->has('order.photos', 1));
        $detail->assertDontSee('Внутреннее примечание секрет');
        $detail->assertDontSee('private.jpg');
        $detail->assertDontSee('4567');

        $this->actingAs($portalUser)->get("/portal/cars/{$car->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ClientPortal/Cars/Show')
                ->where('car.make', 'Toyota')
                ->where('activeOrder.number', 'ЗН-00001')
                ->has('orders.0.photos', 1));

        $this->actingAs($portalUser)->get('/portal/orders')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ClientPortal/Orders/Index')
                ->where('view', 'active')
                ->has('orders', 1)
                ->where('orders.0.number', 'ЗН-00001'));

        $this->actingAs($portalUser)->get('/portal/orders?view=all')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('view', 'all')->has('orders', 2));

        $this->actingAs($portalUser)->get("/portal/history?car_id={$car->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ClientPortal/History')
                ->where('carId', $car->id)
                ->has('orders', 1)
                ->where('orders.0.number', $closedOrder->number));

        $this->actingAs($portalUser)->get('/portal/account')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ClientPortal/Account')
                ->where('clientName', $portalUser->name)
                ->where('organizationName', $organization->name));

        $this->actingAs($portalUser)->get("/portal/cars/{$otherCar->id}")->assertForbidden();
        $this->actingAs($portalUser)->get('/portal/history?car_id='.$otherCar->id)->assertNotFound();
        $this->actingAs($portalUser)->get('/portal/orders/2')->assertForbidden();
    }

    public function test_client_role_cannot_open_staff_workspace_routes(): void
    {
        [$organization, , $client] = $this->createOrganizationOwnerAndClient();
        $portalUser = User::factory()->create(['organization_id' => $organization->id, 'role' => 'client']);
        $client->portalUsers()->attach($portalUser->id, ['organization_id' => $organization->id]);

        $this->actingAs($portalUser)->get('/')->assertForbidden();
        $this->actingAs($portalUser)->get('/clients')->assertForbidden();
        $this->actingAs($portalUser)->get('/orders')->assertForbidden();
        $this->actingAs($portalUser)->get('/admin')->assertForbidden();
        $this->actingAs($portalUser)->get('/finance')->assertForbidden();
    }

    private function createOrganizationOwnerAndClient(): array
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a-'.uniqid()]);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567', 'email' => 'ivan@example.test']);

        return [$organization, $owner, $client];
    }
}
