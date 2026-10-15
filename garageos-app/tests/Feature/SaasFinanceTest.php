<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_record_and_view_platform_finance(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'saas-finance-a']);

        $this->actingAs($admin)->post('/admin/finance/revenues', [
            'amount' => 1000000,
            'received_at' => now()->toDateString(),
            'category' => 'subscription',
            'organization_id' => $organization->id,
            'description' => 'Оплата тарифа',
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/finance/expenses', [
            'amount' => 300000,
            'expense_date' => now()->toDateString(),
            'category' => 'hosting',
            'vendor' => 'Cloud provider',
            'description' => 'Сервер',
        ])->assertRedirect();

        $this->assertDatabaseHas('saas_revenues', ['organization_id' => $organization->id, 'amount' => 1000000, 'category' => 'subscription']);
        $this->assertDatabaseHas('saas_expenses', ['amount' => 300000, 'category' => 'hosting']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'saas.revenue.created', 'organization_id' => $organization->id]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'saas.expense.created', 'organization_id' => null]);
        $this->actingAs($admin)->get('/admin/finance')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Finance/Index')
                ->where('revenueRows.data.0.amount', 1000000)
                ->where('revenueRows.data.0.date', now()->toDateString())
                ->where('from', now()->subDays(29)->toDateString())
                ->where('to', now()->toDateString())
                ->where('metrics.revenue', 1000000)
                ->where('metrics.expenses', 300000)
                ->where('metrics.net', 700000));

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_tenant_user_cannot_view_or_mutate_platform_finance(): void
    {
        $organization = Organization::create(['name' => 'Garage B', 'slug' => 'saas-finance-b']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)->get('/admin/finance')->assertForbidden();
        $this->actingAs($owner)->post('/admin/finance/expenses', [])->assertForbidden();
    }
}