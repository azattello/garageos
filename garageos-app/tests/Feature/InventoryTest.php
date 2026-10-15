<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Part;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_part_with_initial_stock_and_record_audit_movement(): void
    {
        [$organization, $owner] = $this->createOrganizationAndOwner();

        $this->actingAs($owner)->post('/inventory/parts', [
            'name' => 'Масляный фильтр',
            'sku' => 'OF-100',
            'supplier' => 'Parts Co',
            'cost_price' => 2500,
            'sale_price' => 4000,
            'initial_stock' => 12,
            'min_stock' => 3,
            'unit' => 'шт',
        ])->assertRedirect('/inventory');

        $part = Part::where('organization_id', $organization->id)->firstOrFail();
        $this->assertSame(12.0, (float) $part->stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'organization_id' => $organization->id,
            'part_id' => $part->id,
            'type' => 'receive',
            'quantity' => 12,
            'user_id' => $owner->id,
        ]);
    }

    public function test_adding_part_to_order_atomically_updates_order_and_stock_ledger(): void
    {
        [$organization, $owner, $order] = $this->createOrderFixture(stock: 5);
        $part = Part::where('organization_id', $organization->id)->firstOrFail();

        $this->actingAs($owner)
            ->post("/orders/{$order->id}/items", ['type' => 'part', 'part_id' => $part->id, 'quantity' => 2])
            ->assertRedirect(route('orders.show', $order));

        $part->refresh();
        $order->refresh();
        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(5.0, (float) $part->stock);
        $this->assertSame(2.0, (float) $part->reserved_stock);
        $this->assertSame('reserved', $item->inventory_status);
        $this->assertSame('part', $item->type);
        $this->assertSame($part->id, $item->part_id);
        $this->assertSame('Масляный фильтр', $item->name_snapshot);
        $this->assertSame(2500, $item->unit_cost);
        $this->assertSame(8000, $item->total);
        $this->assertSame(8000, $order->total);
        $this->assertDatabaseHas('inventory_transactions', [
            'part_id' => $part->id,
            'type' => 'reserve',
            'quantity' => 0,
            'reserved_delta' => 2,
            'reference_type' => 'order_item',
            'reference_id' => $item->id,
        ]);
        $this->actingAs($owner)
            ->get(route('orders.show', $order))
            ->assertInertia(fn ($page) => $page
                ->where('order.items.0.type', 'part')
                ->where('order.items.0.name', 'Масляный фильтр')
                ->where('parts.0.name', 'Масляный фильтр')
                ->where('parts.0.available_stock', 3)
                ->where('parts.0.unit', 'шт'));
    }

    public function test_reserved_order_part_is_issued_when_order_becomes_ready(): void
    {
        [$organization, $owner, $order] = $this->createOrderFixture(stock: 5);
        $part = Part::where('organization_id', $organization->id)->firstOrFail();
        $this->actingAs($owner)->post("/orders/{$order->id}/items", ['type' => 'part', 'part_id' => $part->id, 'quantity' => 2]);

        foreach (['accepted', 'diagnostics', 'in_progress', 'ready'] as $status) {
            $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => $status])->assertRedirect(route('orders.show', $order));
        }

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(3.0, (float) $part->refresh()->stock);
        $this->assertSame(0.0, (float) $part->reserved_stock);
        $this->assertSame('issued', $item->inventory_status);
        $this->assertSame(1, $part->transactions()->where('type', 'reserve')->count());
        $this->assertSame(1, $part->transactions()->where('type', 'issue')->count());
    }

    public function test_cancelling_order_releases_reservation_and_logs_movement(): void
    {
        [$organization, $owner, $order] = $this->createOrderFixture(stock: 5);
        $part = Part::where('organization_id', $organization->id)->firstOrFail();
        $this->actingAs($owner)->post("/orders/{$order->id}/items", ['type' => 'part', 'part_id' => $part->id, 'quantity' => 2]);

        $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => 'cancelled'])->assertRedirect(route('orders.show', $order));

        $this->assertSame(5.0, (float) $part->refresh()->stock);
        $this->assertSame(0.0, (float) $part->reserved_stock);
        $this->assertSame('released', OrderItem::where('order_id', $order->id)->firstOrFail()->inventory_status);
        $this->assertDatabaseHas('inventory_transactions', [
            'part_id' => $part->id,
            'type' => 'release',
            'quantity' => 0,
            'reserved_delta' => -2,
            'reference_type' => 'order_item',
        ]);
    }

    public function test_part_added_to_in_progress_order_is_issued_and_returned_on_cancellation(): void
    {
        [$organization, $owner, $order] = $this->createOrderFixture(stock: 5);
        $part = Part::where('organization_id', $organization->id)->firstOrFail();
        $inProgress = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'В работе', 'slug' => 'in_progress']);
        $order->update(['status_id' => $inProgress->id]);

        $this->actingAs($owner)->post("/orders/{$order->id}/items", ['type' => 'part', 'part_id' => $part->id, 'quantity' => 2])->assertRedirect(route('orders.show', $order));
        $this->assertSame(3.0, (float) $part->refresh()->stock);
        $this->assertSame('issued', OrderItem::where('order_id', $order->id)->firstOrFail()->inventory_status);

        $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => 'cancelled'])->assertRedirect(route('orders.show', $order));
        $this->assertSame(5.0, (float) $part->refresh()->stock);
        $this->assertSame('returned', OrderItem::where('order_id', $order->id)->firstOrFail()->inventory_status);
        $this->assertSame(1, $part->transactions()->where('type', 'return')->count());
    }

    public function test_manual_issue_cannot_consume_reserved_stock(): void
    {
        [$organization, $owner, $order] = $this->createOrderFixture(stock: 5);
        $part = Part::where('organization_id', $organization->id)->firstOrFail();
        $this->actingAs($owner)->post("/orders/{$order->id}/items", ['type' => 'part', 'part_id' => $part->id, 'quantity' => 3]);

        $this->actingAs($owner)
            ->from('/inventory')
            ->post("/inventory/parts/{$part->id}/stock", ['type' => 'issue', 'quantity' => 3])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(5.0, (float) $part->refresh()->stock);
        $this->assertSame(3.0, (float) $part->reserved_stock);
    }

    public function test_reservation_triggers_low_stock_alert_using_available_stock(): void
    {
        [$organization, $owner, $order] = $this->createOrderFixture(stock: 5);
        $part = Part::where('organization_id', $organization->id)->firstOrFail();

        $this->actingAs($owner)->post("/orders/{$order->id}/items", ['type' => 'part', 'part_id' => $part->id, 'quantity' => 3]);

        $this->assertSame(5.0, (float) $part->refresh()->stock);
        $this->assertSame(3.0, (float) $part->reserved_stock);
        $this->assertSame(1, Notification::where('user_id', $owner->id)->where('type', 'inventory.low_stock')->whereNull('read_at')->count());
    }

    public function test_order_rejects_part_quantity_above_stock_without_partial_changes(): void
    {
        [$organization, $owner, $order] = $this->createOrderFixture(stock: 1);
        $part = Part::where('organization_id', $organization->id)->firstOrFail();

        $this->actingAs($owner)
            ->from("/orders/{$order->id}")
            ->post("/orders/{$order->id}/items", ['type' => 'part', 'part_id' => $part->id, 'quantity' => 2])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(1.0, (float) $part->refresh()->stock);
        $this->assertSame(0, OrderItem::where('order_id', $order->id)->count());
        $this->assertSame(0, $part->transactions()->count());
        $this->assertSame(0, (int) $order->refresh()->total);
    }

    public function test_stock_receipt_issue_return_and_adjustment_are_recorded(): void
    {
        [$organization, $owner] = $this->createOrganizationAndOwner();
        $part = Part::create([
            'organization_id' => $organization->id,
            'name' => 'Свеча зажигания',
            'cost_price' => 1000,
            'sale_price' => 1500,
            'stock' => 0,
            'min_stock' => 2,
            'unit' => 'шт',
        ]);

        $this->actingAs($owner)->post("/inventory/parts/{$part->id}/stock", ['type' => 'receive', 'quantity' => 5])->assertRedirect('/inventory');
        $this->actingAs($owner)->post("/inventory/parts/{$part->id}/stock", ['type' => 'issue', 'quantity' => 1])->assertRedirect('/inventory');
        $this->actingAs($owner)->post("/inventory/parts/{$part->id}/stock", ['type' => 'return', 'quantity' => 0.5])->assertRedirect('/inventory');
        $this->actingAs($owner)->post("/inventory/parts/{$part->id}/stock", ['type' => 'adjustment', 'quantity' => 3])->assertRedirect('/inventory');

        $this->assertSame(3.0, (float) $part->refresh()->stock);
        $this->assertSame(4, $part->transactions()->count());
        $this->assertSame(-1.5, (float) $part->transactions()->where('type', 'adjustment')->value('quantity'));
    }

    public function test_inventory_is_tenant_scoped_and_unavailable_to_mechanics(): void
    {
        [$organization, $owner] = $this->createOrganizationAndOwner();
        Part::create([
            'organization_id' => $organization->id,
            'name' => 'Масляный фильтр',
            'cost_price' => 2000,
            'sale_price' => 3000,
            'stock' => 4,
            'min_stock' => 2,
            'unit' => 'шт',
        ]);
        $otherOrganization = Organization::create(['name' => 'Garage B', 'slug' => 'garage-b']);
        Part::create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Чужая запчасть',
            'cost_price' => 1000,
            'sale_price' => 1500,
            'stock' => 8,
            'min_stock' => 2,
            'unit' => 'шт',
        ]);

        $this->actingAs($owner)
            ->get('/inventory')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Inventory/Index')
                ->where('summary.parts', 1)
                ->has('parts.data', 1)
                ->where('parts.data.0.name', 'Масляный фильтр'));

        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);
        $this->actingAs($mechanic)->get('/inventory')->assertForbidden();
        $this->actingAs($mechanic)->post('/inventory/parts', [])->assertForbidden();
    }

    public function test_inventory_search_and_sort_are_applied_before_server_pagination(): void
    {
        [$organization, $owner] = $this->createOrganizationAndOwner();
        foreach (range(1, 31) as $index) {
            Part::create([
                'organization_id' => $organization->id,
                'name' => sprintf('Запчасть %02d', $index),
                'cost_price' => 1000,
                'sale_price' => 1500,
                'stock' => 10,
                'min_stock' => 2,
                'unit' => 'шт',
            ]);
        }
        Part::create([
            'organization_id' => $organization->id,
            'name' => 'Якорная позиция',
            'sku' => 'UNIQUE-31',
            'cost_price' => 2000,
            'sale_price' => 3000,
            'stock' => 1,
            'min_stock' => 4,
            'unit' => 'шт',
        ]);

        $this->actingAs($owner)
            ->get('/inventory?q=UNIQUE-31&low_stock=1&sort=sale_price&direction=desc&per_page=25')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Inventory/Index')
                ->where('filters.q', 'UNIQUE-31')
                ->where('filters.lowStock', true)
                ->where('filters.sort', 'sale_price')
                ->where('parts.total', 1)
                ->has('parts.data', 1)
                ->where('parts.data.0.sku', 'UNIQUE-31'));
    }

    public function test_low_stock_notification_is_deduplicated_and_resolved_after_restock(): void
    {
        [$organization, $owner] = $this->createOrganizationAndOwner();
        $part = Part::create([
            'organization_id' => $organization->id,
            'name' => 'Тормозные колодки',
            'cost_price' => 10000,
            'sale_price' => 16000,
            'stock' => 4,
            'min_stock' => 2,
            'unit' => 'компл.',
        ]);

        $this->actingAs($owner)->post("/inventory/parts/{$part->id}/stock", ['type' => 'issue', 'quantity' => 2])->assertRedirect('/inventory');
        $this->assertSame(1, Notification::where('user_id', $owner->id)->where('type', 'inventory.low_stock')->whereNull('read_at')->count());

        $this->actingAs($owner)->post("/inventory/parts/{$part->id}/stock", ['type' => 'issue', 'quantity' => 0.5])->assertRedirect('/inventory');
        $this->assertSame(1, Notification::where('user_id', $owner->id)->where('type', 'inventory.low_stock')->count());

        $this->actingAs($owner)->post("/inventory/parts/{$part->id}/stock", ['type' => 'receive', 'quantity' => 5])->assertRedirect('/inventory');
        $this->assertSame(0, Notification::where('user_id', $owner->id)->where('type', 'inventory.low_stock')->whereNull('read_at')->count());
    }

    private function createOrganizationAndOwner(): array
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create([
            'organization_id' => $organization->id,
            'plan' => 'business',
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
            'max_users' => 10,
        ]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        return [$organization, $owner];
    }

    private function createOrderFixture(float $stock): array
    {
        [$organization, $owner] = $this->createOrganizationAndOwner();
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new', 'sort_order' => 1]);
        $order = Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00001',
            'client_id' => $client->id,
            'car_id' => $car->id,
            'status_id' => $status->id,
            'opened_at' => now(),
        ]);
        Part::create([
            'organization_id' => $organization->id,
            'name' => 'Масляный фильтр',
            'sku' => 'OF-100',
            'cost_price' => 2500,
            'sale_price' => 4000,
            'stock' => $stock,
            'min_stock' => 2,
            'unit' => 'шт',
        ]);

        return [$organization, $owner, $order];
    }
}
