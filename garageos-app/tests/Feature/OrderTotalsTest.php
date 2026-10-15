<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_total_is_calculated_on_backend_from_work_items(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'Замена масла', 'price' => 8000, 'duration_minutes' => 60]);

        $response = $this->actingAs($owner)->post('/orders', ['client_id' => $client->id, 'car_id' => $car->id]);
        $order = \App\Models\Order::first();
        $response->assertRedirect(route('orders.show', $order));
        $order->update(['discount' => 2000]);

        $this->actingAs($owner)->post("/orders/{$order->id}/items", ['service_id' => $service->id, 'quantity' => 2])->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame(16000, $order->subtotal);
        $this->assertSame(14000, $order->total);
        $this->assertSame(14000, $order->balance);
        $this->assertSame('Замена масла', $order->items()->first()->name_snapshot);

        $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => 'accepted'])->assertRedirect(route('orders.show', $order));
        $this->assertSame('accepted', $order->refresh()->status->slug);
    }
}
