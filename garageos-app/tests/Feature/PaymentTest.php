<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Service;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_payment_updates_paid_total_and_balance_and_cancel_restores_balance(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        Service::create(['organization_id' => $organization->id, 'name' => 'Диагностика', 'price' => 30000, 'duration_minutes' => 60]);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now(), 'total' => 30000, 'balance' => 30000]);
        OrderItem::create([
            'organization_id' => $organization->id,
            'order_id' => $order->id,
            'type' => 'service',
            'name_snapshot' => 'Диагностика',
            'quantity' => 1,
            'unit_price' => 30000,
            'discount' => 0,
            'total' => 30000,
        ]);

        $this->actingAs($owner)->post("/orders/{$order->id}/payments", ['amount' => 10000, 'method' => 'kaspi'])->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'paid_total' => 10000, 'balance' => 20000]);

        $this->actingAs($owner)->post("/orders/{$order->id}/payments", ['amount' => 5000, 'method' => 'cash'])->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'paid_total' => 15000, 'balance' => 15000]);

        $firstPayment = $order->payments()->oldest()->firstOrFail();
        $this->actingAs($owner)->post("/orders/{$order->id}/payments/{$firstPayment->id}/cancel")->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('payments', ['id' => $firstPayment->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'paid_total' => 5000, 'balance' => 25000]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment.registered', 'subject_id' => $firstPayment->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment.cancelled', 'subject_id' => $firstPayment->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'order.financials_recalculated', 'subject_id' => $order->id]);
    }

    public function test_payment_cannot_exceed_current_backend_balance(): void
    {
        [$owner, $order] = $this->createOrderWithBalance(30000);

        $this->actingAs($owner)
            ->from(route('orders.show', $order))
            ->post("/orders/{$order->id}/payments", ['amount' => 30001, 'method' => 'card'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, $order->payments()->count());
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'paid_total' => 0, 'balance' => 30000]);
    }

    public function test_order_with_nonzero_balance_requires_explicit_owner_override_to_close(): void
    {
        [$owner, $order] = $this->createOrderWithBalance(30000);
        $delivered = OrderStatus::create(['organization_id' => $order->organization_id, 'name' => 'Выдан', 'slug' => 'delivered']);
        $order->update(['status_id' => $delivered->id]);

        $this->actingAs($owner)
            ->from(route('orders.show', $order))
            ->post("/orders/{$order->id}/status", ['status' => 'closed'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('status');
        $this->assertSame('delivered', $order->refresh()->status->slug);

        $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => 'closed', 'allow_unpaid' => true])
            ->assertRedirect(route('orders.show', $order));
        $this->assertSame('closed', $order->refresh()->status->slug);
    }

    public function test_order_with_negative_balance_cannot_close_without_explicit_override(): void
    {
        [$owner, $order] = $this->createOrderWithBalance(0);
        $order->update(['balance' => -500]);
        $delivered = OrderStatus::create(['organization_id' => $order->organization_id, 'name' => 'Выдан', 'slug' => 'delivered']);
        $order->update(['status_id' => $delivered->id]);

        $this->actingAs($owner)
            ->from(route('orders.show', $order))
            ->post("/orders/{$order->id}/status", ['status' => 'closed'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('status');
        $this->assertSame('delivered', $order->refresh()->status->slug);

        $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => 'closed', 'allow_unpaid' => true])
            ->assertRedirect(route('orders.show', $order));
        $this->assertSame('closed', $order->refresh()->status->slug);
    }

    private function createOrderWithBalance(int $balance): array
    {
        $organization = Organization::create(['name' => 'Garage Test', 'slug' => 'garage-test-'.uniqid()]);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Тестовый клиент', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00001',
            'client_id' => $client->id,
            'car_id' => $car->id,
            'status_id' => $status->id,
            'opened_at' => now(),
            'total' => $balance,
            'balance' => $balance,
        ]);

        return [$owner, $order];
    }
    }
