<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SaasExpense;
use App\Models\SaasRevenue;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_uses_one_date_filter_for_growth_and_saas_finance(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Analytics Garage', 'slug' => 'analytics-garage']);
        User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'active', 'max_users' => 3, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        SaasRevenue::create(['organization_id' => $organization->id, 'category' => 'subscription', 'amount' => 10000, 'currency' => 'KZT', 'received_at' => now()->toDateString()]);
        SaasExpense::create(['category' => 'hosting', 'amount' => 2000, 'currency' => 'KZT', 'expense_date' => now()->toDateString()]);

        $this->actingAs($admin)->get('/admin/analytics?period=7d')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Analytics/Index')
                ->where('metrics.newOrganizations', 1)
                ->where('metrics.newSubscriptions', 1)
                ->where('metrics.newUsers', 1)
                ->where('metrics.revenue', 10000)
                ->where('metrics.expenses', 2000)
                ->where('metrics.net', 8000)
                ->has('trend', 7)
                ->where('trend.6.revenue', 10000)
                ->where('trend.6.expenses', 2000));

        $today = now()->toDateString();
        $this->actingAs($admin)->get("/admin/analytics?period=custom&from={$today}&to={$today}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('from', $today)
                ->where('to', $today)
                ->where('metrics.revenue', 10000)
                ->has('trend', 1));
    }

    public function test_tenant_user_cannot_view_platform_analytics(): void
    {
        $organization = Organization::create(['name' => 'Tenant', 'slug' => 'analytics-tenant']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)->get('/admin/analytics')->assertForbidden();
    }
}