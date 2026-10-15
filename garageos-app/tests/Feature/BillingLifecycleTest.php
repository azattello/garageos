<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionChangeRequest;
use App\Models\SaasPayment;
use App\Models\SaasRevenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_to_paid_activation_to_expiry_hard_lock_lifecycle(): void
    {
        [$organization, $owner] = $this->createOwnerOrganization('trialing', 'start', now()->addDay());
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);

        $this->actingAs($owner)->get('/clients')->assertOk();
        $this->actingAs($owner)->post('/subscription/plan-change', ['plan' => 'business'])->assertRedirect('/subscription');
        $changeRequest = SubscriptionChangeRequest::firstOrFail();
        $this->assertSame(69900, $changeRequest->quoted_amount);
        $this->assertSame('KZT', $changeRequest->currency);

        $this->actingAs($admin)
            ->post("/admin/subscription-requests/{$changeRequest->id}", ['decision' => 'approve'])
            ->assertSessionHasErrors('payment_reference');

        $this->actingAs($admin)
            ->post("/admin/subscription-requests/{$changeRequest->id}", [
                'decision' => 'approve',
                'payment_reference' => 'BANK-REF-2026-001',
            ])
            ->assertRedirect();

        $subscription = $organization->subscription()->firstOrFail()->refresh();
        $this->assertSame('business', $subscription->plan);
        $this->assertSame('active', $subscription->status);
        $this->assertSame(10, $subscription->max_users);
        $this->assertTrue($subscription->ends_at->isFuture());
        $this->assertDatabaseHas('saas_payments', [
            'subscription_change_request_id' => $changeRequest->id,
            'amount' => 69900,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('saas_revenues', ['payment_id' => SaasPayment::firstOrFail()->id, 'amount' => 69900]);
        $this->assertSame(1, SaasRevenue::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'subscription.plan_activated', 'organization_id' => $organization->id]);
        $owner = $owner->fresh();
        $this->actingAs($owner)->get('/inventory')->assertOk();

        $this->actingAs($owner)->post('/subscription/plan-change', ['plan' => 'start'])->assertRedirect('/subscription');
        $renewal = SubscriptionChangeRequest::where('status', 'pending')->firstOrFail();
        $this->actingAs($admin)->post("/admin/subscription-requests/{$renewal->id}", [
            'decision' => 'approve',
            'payment_reference' => 'BANK-REF-2026-001',
        ])->assertSessionHasErrors('payment_reference');
        $this->assertSame('pending', $renewal->fresh()->status);

        $subscription->update(['ends_at' => now()->subSecond()]);
        $owner = $owner->fresh();
        $this->actingAs($owner)->get('/orders')->assertRedirect('/subscription');
        $this->assertSame('expired', $subscription->refresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'subscription.expired', 'organization_id' => $organization->id]);
        $this->actingAs($owner)->get('/subscription')->assertOk();
        $this->actingAs($owner)->get('/')->assertRedirect('/subscription');
    }

    public function test_start_plan_is_feature_gated_and_business_unlocks_business_features(): void
    {
        [$organization, $owner] = $this->createOwnerOrganization('active', 'start', null);
        $organization->subscription()->update(['ends_at' => now()->addMonth()]);

        $this->actingAs($owner)->get('/')->assertOk();
        $this->actingAs($owner)->get('/inventory')->assertRedirect('/subscription')->assertSessionHas('feature_unavailable', 'inventory');
        $this->actingAs($owner)->post('/inventory/parts', [])->assertRedirect('/subscription');

        $organization->subscription()->update(['plan' => 'business', 'max_users' => 10]);
        $owner = $owner->fresh();
        $this->actingAs($owner)->get('/inventory')->assertOk();
        $this->actingAs($owner)->get('/finance')->assertOk();
        $this->actingAs($owner)->get('/analytics')->assertOk();
    }

    public function test_downgrade_is_rejected_when_employee_count_exceeds_target_limit(): void
    {
        [$organization, $owner] = $this->createOwnerOrganization('active', 'business', now()->addMonth());
        $organization->subscription()->update(['ends_at' => now()->addMonth(), 'max_users' => 10]);
        User::factory()->count(4)->create(['organization_id' => $organization->id, 'role' => 'mechanic']);

        $this->actingAs($owner)->post('/subscription/plan-change', ['plan' => 'start'])->assertRedirect('/subscription');
        $changeRequest = SubscriptionChangeRequest::firstOrFail();
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);

        $this->actingAs($admin)
            ->post("/admin/subscription-requests/{$changeRequest->id}", [
                'decision' => 'approve',
                'payment_reference' => 'BANK-REF-2026-002',
            ])
            ->assertSessionHasErrors('request');

        $this->assertSame('business', $organization->subscription()->firstOrFail()->fresh()->plan);
        $this->assertSame('pending', $changeRequest->fresh()->status);
    }

    public function test_client_portal_accounts_do_not_consume_employee_seats(): void
    {
        [$organization, $owner] = $this->createOwnerOrganization('active', 'business', now()->addMonth());
        $organization->subscription()->update(['ends_at' => now()->addMonth(), 'max_users' => 10]);
        User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);
        User::factory()->count(5)->create(['organization_id' => $organization->id, 'role' => 'client']);

        $this->actingAs($owner)->post('/subscription/plan-change', ['plan' => 'start'])->assertRedirect('/subscription');
        $changeRequest = SubscriptionChangeRequest::firstOrFail();
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);

        $this->actingAs($admin)->post("/admin/subscription-requests/{$changeRequest->id}", [
            'decision' => 'approve',
            'payment_reference' => 'CLIENT-SEATS-REF',
        ])->assertRedirect();

        $this->assertSame('start', $organization->subscription()->firstOrFail()->refresh()->plan);
        $this->assertSame(5, $organization->users()->where('role', 'client')->count());
    }

    public function test_expiry_sweep_command_is_idempotent(): void
    {
        [$organization] = $this->createOwnerOrganization('trialing', 'start', now()->subMinute());

        $this->artisan('subscriptions:expire')->expectsOutput('Expired 1 subscriptions.')->assertSuccessful();
        $this->artisan('subscriptions:expire')->expectsOutput('Expired 0 subscriptions.')->assertSuccessful();

        $this->assertSame(1, ActivityLog::where('organization_id', $organization->id)->where('action', 'subscription.expired')->count());
    }

    public function test_expired_subscription_can_request_renewal_of_the_same_plan(): void
    {
        [$organization, $owner] = $this->createOwnerOrganization('expired', 'business', null);
        $organization->subscription()->update(['ends_at' => now()->subDay()]);
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);

        $this->actingAs($owner)->post('/subscription/plan-change', ['plan' => 'business'])->assertRedirect('/subscription');
        $changeRequest = SubscriptionChangeRequest::firstOrFail();
        $this->actingAs($admin)->post("/admin/subscription-requests/{$changeRequest->id}", [
            'decision' => 'approve',
            'payment_reference' => 'RENEWAL-REF-001',
        ])->assertRedirect();

        $subscription = $organization->subscription()->firstOrFail()->refresh();
        $this->assertSame('business', $subscription->plan);
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    public function test_platform_admin_trial_extension_clears_old_paid_period(): void
    {
        [$organization] = $this->createOwnerOrganization('expired', 'business', null);
        $organization->subscription()->update(['ends_at' => now()->subDay()]);
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);

        $this->actingAs($admin)->post("/admin/organizations/{$organization->id}/trial", ['days' => 14])->assertRedirect();

        $subscription = $organization->subscription()->firstOrFail()->refresh();
        $this->assertSame('trialing', $subscription->status);
        $this->assertTrue($subscription->trial_ends_at->isFuture());
        $this->assertNull($subscription->ends_at);
    }

    private function createOwnerOrganization(string $status, string $plan, $trialEndsAt): array
    {
        $organization = Organization::create(['name' => 'Billing Garage', 'slug' => 'billing-'.uniqid()]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        Subscription::create([
            'organization_id' => $organization->id,
            'plan' => $plan,
            'status' => $status,
            'max_users' => $plan === 'business' ? 10 : 3,
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => now(),
        ]);

        return [$organization, $owner];
    }
}
