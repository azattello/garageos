<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_creates_one_linked_order_without_duplication(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'Диагностика', 'price' => 15000, 'duration_minutes' => 60]);

        $appointment = \App\Models\Appointment::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'car_id' => $car->id, 'service_id' => $service->id, 'start_at' => now()->addDay()->setTime(10, 0), 'end_at' => now()->addDay()->setTime(11, 0), 'created_by' => $owner->id]);
        $response = $this->actingAs($owner)->post("/appointments/{$appointment->id}/order");

        $order = \App\Models\Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(1, \App\Models\Order::count());
        $this->assertSame($appointment->id, $appointment->refresh()->order_id);
        $this->assertSame($client->id, $order->client_id);
        $this->assertSame($car->id, $order->car_id);
        $this->assertSame(15000, $order->total);
        $this->assertSame('Диагностика', $order->items()->first()->name_snapshot);
        $this->assertSame('arrived', $appointment->refresh()->status);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Show')
                ->where('order.id', $order->id)
                ->where('order.items.0.name', 'Диагностика'));
    }
}
