<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_uses_tenant_scoped_payments_orders_and_workload_for_selected_period(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'business', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 10]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'slug' => 'in_progress', 'name' => 'В работе', 'sort_order' => 1]);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $order = Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00001',
            'client_id' => $client->id,
            'car_id' => $car->id,
            'mechanic_id' => $mechanic->id,
            'status_id' => $status->id,
            'opened_at' => now(),
            'subtotal' => 40000,
            'total' => 40000,
            'paid_total' => 15000,
            'balance' => 25000,
        ]);
        OrderItem::create([
            'organization_id' => $organization->id,
            'order_id' => $order->id,
            'type' => 'service',
            'name_snapshot' => 'Диагностика',
            'quantity' => 1,
            'unit_price' => 40000,
            'total' => 40000,
        ]);
        Payment::create([
            'organization_id' => $organization->id,
            'order_id' => $order->id,
            'amount' => 15000,
            'method' => 'cash',
            'status' => 'successful',
            'received_by' => $owner->id,
            'paid_at' => now(),
        ]);
        Expense::create(['organization_id' => $organization->id, 'amount' => 2000, 'description' => 'Rent', 'expense_date' => today()]);
        $this->assertDatabaseHas('expenses', ['organization_id' => $organization->id, 'amount' => 2000, 'expense_date' => today()->toDateTimeString()]);

        $otherOrganization = Organization::create(['name' => 'Garage B', 'slug' => 'garage-b']);
        $otherClient = Client::create(['organization_id' => $otherOrganization->id, 'name' => 'Другой клиент', 'phone' => '+77000000000']);
        $otherCar = Car::create(['organization_id' => $otherOrganization->id, 'client_id' => $otherClient->id, 'make' => 'BMW', 'model' => 'X5']);
        $otherStatus = OrderStatus::create(['organization_id' => $otherOrganization->id, 'slug' => 'new', 'name' => 'Новый', 'sort_order' => 1]);
        $otherOrder = Order::create([
            'organization_id' => $otherOrganization->id,
            'number' => 'ЗН-00001',
            'client_id' => $otherClient->id,
            'car_id' => $otherCar->id,
            'status_id' => $otherStatus->id,
            'opened_at' => now(),
            'total' => 90000,
            'balance' => 90000,
        ]);
        Payment::create([
            'organization_id' => $otherOrganization->id,
            'order_id' => $otherOrder->id,
            'amount' => 90000,
            'method' => 'cash',
            'status' => 'successful',
            'paid_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get('/analytics?period=7d')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Analytics/Index')
                ->where('period', '7d')
                ->where('metrics.revenue', 15000)
                ->where('metrics.revenueChange', null)
                ->where('metrics.expenses', 2000)
                ->where('metrics.operatingResult', 13000)
                ->where('metrics.orders', 1)
                ->where('metrics.ordersChange', null)
                ->where('metrics.averageCheck', 40000)
                ->where('metrics.debt', 25000)
                ->where('metrics.activeOrders', 1)
                ->has('trend', 7)
                ->where('trend.6.expenses', 2000)
                ->where('topServices.0.name', 'Диагностика')
                ->where('mechanics.0.active_orders', 1));
    }

    public function test_unknown_analytics_period_falls_back_to_thirty_days(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'business', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 10]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->get('/analytics?period=unexpected')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('period', '30d')->has('trend', 30));
    }

    public function test_analytics_csv_contains_aggregate_data_only_for_authorized_tenant(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-csv-a']);
        $otherOrganization = Organization::create(['name' => 'Garage B', 'slug' => 'garage-csv-b']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'business', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 10]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);
        Expense::create(['organization_id' => $organization->id, 'amount' => 1200, 'expense_date' => today()]);
        Expense::create(['organization_id' => $otherOrganization->id, 'amount' => 99000, 'expense_date' => today()]);
        $this->assertDatabaseHas('expenses', ['organization_id' => $organization->id, 'amount' => 1200, 'expense_date' => today()->toDateTimeString()]);

        $response = $this->actingAs($owner)->get('/analytics/export?period=7d')->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Получено платежей', $csv);
        $this->assertStringContainsString('1200', $csv);
        $this->assertStringNotContainsString('99000', $csv);
        $this->assertStringNotContainsString('Garage B', $csv);

        $this->actingAs($mechanic)->get('/analytics/export?period=7d')->assertForbidden();
    }
}
