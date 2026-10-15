<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SaasExpense;
use App\Models\SaasPayment;
use App\Models\SaasRevenue;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_subscription_catalog_and_saas_ledger_for_metrics(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $activeOrganization = Organization::create(['name' => 'Active', 'slug' => 'dashboard-active']);
        $expiredOrganization = Organization::create(['name' => 'Expired', 'slug' => 'dashboard-expired']);
        $unpaidOrganization = Organization::create(['name' => 'Unpaid', 'slug' => 'dashboard-unpaid']);
        $activeSubscription = Subscription::create(['organization_id' => $activeOrganization->id, 'plan' => 'start', 'status' => 'active', 'max_users' => 3, 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonth()]);
        Subscription::create(['organization_id' => $expiredOrganization->id, 'plan' => 'business', 'status' => 'expired', 'max_users' => 10, 'starts_at' => now()->subYear(), 'ends_at' => now()->subMonth()]);
        Subscription::create(['organization_id' => $unpaidOrganization->id, 'plan' => 'business', 'status' => 'active', 'max_users' => 10, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        SaasPayment::create(['organization_id' => $activeOrganization->id, 'subscription_id' => $activeSubscription->id, 'plan' => 'start', 'amount' => 39900, 'currency' => 'KZT', 'status' => 'paid', 'reference' => 'DASHBOARD-PAID-001', 'paid_at' => now()]);
        SaasRevenue::create(['organization_id' => $activeOrganization->id, 'category' => 'subscription', 'amount' => 25000, 'currency' => 'KZT', 'received_at' => now()->toDateString(), 'description' => 'Текущий период']);
        SaasExpense::create(['category' => 'hosting', 'amount' => 5000, 'currency' => 'KZT', 'expense_date' => now()->toDateString(), 'description' => 'Infrastructure']);

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Index')
                ->where('metrics.organizations', 3)
                ->where('metrics.activeSubscriptions', 1)
                ->where('metrics.mrr', 39900)
                ->where('metrics.arr', 478800)
                ->where('metrics.arpu', 39900)
                ->where('metrics.payingOrganizations', 1)
                ->where('metrics.revenueMonth', 25000)
                ->where('metrics.expensesMonth', 5000)
                ->where('metrics.netMonth', 20000)
                ->where('planDistribution.0.count', 1)
                ->where('planDistribution.1.count', 0)
                ->has('trend', 12));
    }
}