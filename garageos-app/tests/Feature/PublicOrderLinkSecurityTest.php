<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\PublicOrderToken;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicOrderLinkSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_revoke_a_public_order_link_and_access_is_immediately_denied(): void
    {
        [$organization, $owner, $order] = $this->createOrder();
        $this->actingAs($owner)->post("/orders/{$order->id}/public-link")->assertRedirect(route('orders.show', $order));
        $url = $this->app['session.store']->get('public_order_url');
        $token = basename(parse_url($url, PHP_URL_PATH));

        $this->get('/order/'.$token)->assertOk();
        $this->actingAs($owner)->post("/orders/{$order->id}/public-link/revoke")->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('public_order_tokens', ['organization_id' => $organization->id, 'order_id' => $order->id, 'revoked_at' => now()]);
        $this->get('/order/'.$token)->assertNotFound();
        $this->assertDatabaseHas('activity_logs', ['organization_id' => $organization->id, 'action' => 'order.public_links_revoked']);
    }

    public function test_rotating_public_link_revokes_the_previous_token(): void
    {
        [$organization, $owner, $order] = $this->createOrder();
        $this->actingAs($owner)->post("/orders/{$order->id}/public-link");
        $firstUrl = $this->app['session.store']->get('public_order_url');
        $firstToken = basename(parse_url($firstUrl, PHP_URL_PATH));

        $this->actingAs($owner)->post("/orders/{$order->id}/public-link");
        $secondUrl = $this->app['session.store']->get('public_order_url');
        $secondToken = basename(parse_url($secondUrl, PHP_URL_PATH));

        $this->assertNotSame($firstToken, $secondToken);
        $this->get('/order/'.$firstToken)->assertNotFound();
        $this->get('/order/'.$secondToken)->assertOk();
        $this->assertSame(1, PublicOrderToken::where('organization_id', $organization->id)->where('order_id', $order->id)->whereNull('revoked_at')->count());
    }

    private function createOrder(): array
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'public-link-'.uniqid()]);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now()]);

        return [$organization, $owner, $order];
    }
}
