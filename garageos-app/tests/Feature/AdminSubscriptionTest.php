<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_extend_suspend_reactivate_and_cancel_subscription(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Lifecycle Garage', 'slug' => 'lifecycle-garage']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $subscription = Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'active', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addDays(10), 'max_users' => 3]);
        $originalEnd = $subscription->ends_at->copy();

        $this->actingAs($admin)->post("/admin/subscriptions/{$subscription->id}/action", ['action' => 'extend', 'days' => 14])->assertRedirect();
        $this->assertTrue($subscription->fresh()->ends_at->greaterThan($originalEnd));

        $this->actingAs($admin)->post("/admin/subscriptions/{$subscription->id}/action", ['action' => 'suspend'])->assertRedirect();
        $this->assertSame('suspended', $subscription->fresh()->status);
        $this->actingAs($owner)->get('/clients')->assertRedirect('/subscription');

        $this->actingAs($admin)->post("/admin/subscriptions/{$subscription->id}/action", ['action' => 'reactivate', 'days' => 30])->assertRedirect();
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertTrue($subscription->fresh()->ends_at->isFuture());

        $this->actingAs($admin)->post("/admin/subscriptions/{$subscription->id}/action", ['action' => 'cancel'])->assertRedirect();
        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertSame(4, ActivityLog::where('organization_id', $organization->id)->where('action', 'like', 'subscription.admin_%')->count());
    }

    public function test_plan_change_requires_a_paid_payment_for_same_organization_and_plan(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Plan Garage', 'slug' => 'plan-garage']);
        $subscription = Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'active', 'ends_at' => now()->addMonth(), 'max_users' => 3]);
        SaasPayment::create(['organization_id' => $organization->id, 'subscription_id' => $subscription->id, 'plan' => 'business', 'amount' => 69900, 'status' => 'paid', 'reference' => 'PLAN-BUSINESS-REF', 'paid_at' => now()]);

        $this->actingAs($admin)->post("/admin/subscriptions/{$subscription->id}/action", [
            'action' => 'change_plan', 'plan' => 'business', 'payment_reference' => 'PLAN-BUSINESS-REF',
        ])->assertRedirect();
        $this->assertSame('business', $subscription->fresh()->plan);
        $this->assertSame(10, $subscription->fresh()->max_users);

        $this->actingAs($admin)->post("/admin/subscriptions/{$subscription->id}/action", [
            'action' => 'change_plan', 'plan' => 'start', 'payment_reference' => 'UNKNOWN-REF',
        ])->assertSessionHasErrors('payment_reference');
        $this->assertSame('business', $subscription->fresh()->plan);
    }

    public function test_manual_activation_requires_confirmed_payment_reference(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Activation Garage', 'slug' => 'activation-garage']);
        $subscription = Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'max_users' => 3]);

        $this->actingAs($admin)->post("/admin/subscriptions/{$subscription->id}/action", [
            'action' => 'activate', 'days' => 30,
        ])->assertSessionHasErrors('payment_reference');

        SaasPayment::create(['organization_id' => $organization->id, 'subscription_id' => $subscription->id, 'plan' => 'start', 'amount' => 39900, 'status' => 'paid', 'reference' => 'ACTIVATION-PAID-001', 'paid_at' => now()]);
        $this->actingAs($admin)->post("/admin/subscriptions/{$subscription->id}/action", [
            'action' => 'activate', 'days' => 30, 'payment_reference' => 'ACTIVATION-PAID-001',
        ])->assertRedirect();

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertTrue($subscription->fresh()->ends_at->isFuture());
    }

    public function test_tenant_user_cannot_manage_a_subscription(): void
    {
        $organization = Organization::create(['name' => 'Tenant Garage', 'slug' => 'tenant-subscription']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $subscription = Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'max_users' => 3]);

        $this->actingAs($owner)->post("/admin/subscriptions/{$subscription->id}/action", ['action' => 'cancel'])->assertForbidden();
    }
}