<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPhoto;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\PublicOrderToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_order_link_uses_hash_and_exposes_only_client_safe_data(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Готов', 'slug' => 'ready']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now(), 'internal_comment' => 'Скрытое примечание', 'total' => 10000, 'balance' => 10000]);
        OrderItem::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'type' => 'part', 'name_snapshot' => 'Фильтр', 'quantity' => 1, 'unit_price' => 10000, 'unit_cost' => 4567, 'discount' => 0, 'total' => 10000]);
        OrderPhoto::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'path' => 'visible.jpg', 'category' => 'before', 'caption' => 'До ремонта', 'visible_to_client' => true]);
        $hiddenPhoto = OrderPhoto::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'path' => 'hidden.jpg', 'caption' => 'Для сотрудников', 'visible_to_client' => false]);
        $statusChangedAt = now();
        ActivityLog::create(['organization_id' => $organization->id, 'user_id' => $owner->id, 'action' => 'order.status_changed', 'subject_type' => Order::class, 'subject_id' => $order->id, 'metadata' => ['from' => 'in_progress', 'to' => 'ready'], 'created_at' => $statusChangedAt]);
        $token = Str::random(64);
        PublicOrderToken::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'token_hash' => hash('sha256', $token)]);

        $response = $this->get('/order/'.$token);

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('PublicOrder/Show')
            ->where('order.number', 'ЗН-00001')
            ->where('order.status.slug', 'ready')
            ->where('order.items.0.type', 'part')
            ->where('order.items.0.unit_price', 10000)
            ->where('order.status_updated_at', fn ($value) => Carbon::parse($value)->timestamp === $statusChangedAt->timestamp)
            ->has('order.photos', 1)
            ->where('order.photos.0.caption', 'До ремонта')
            ->where('order.photos.0.category', 'before'));
        $response->assertDontSee('Скрытое примечание');
        $response->assertDontSee('hidden.jpg');
        $response->assertDontSee('Для сотрудников');
        $response->assertDontSee('4567');
        $response->assertDontSee('Иван Петров');
        $this->get(route('orders.public.photos.show', ['token' => $token, 'photo' => $hiddenPhoto->id]))->assertNotFound();
        $this->assertDatabaseMissing('public_order_tokens', ['token_hash' => $token]);
    }

    public function test_revoked_and_expired_public_links_are_not_accessible(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00002', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now()]);
        $revokedToken = Str::random(64);
        $expiredToken = Str::random(64);
        PublicOrderToken::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'token_hash' => hash('sha256', $revokedToken), 'revoked_at' => now()]);
        PublicOrderToken::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'token_hash' => hash('sha256', $expiredToken), 'expires_at' => now()->subMinute()]);

        $this->get('/order/'.$revokedToken)->assertNotFound();
        $this->get('/order/'.$expiredToken)->assertNotFound();
        $this->get('/order/'.Str::random(64))->assertNotFound();
    }
}
