<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_trial_redirects_to_subscription_page(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->subDay(), 'starts_at' => now()->subDays(15), 'max_users' => 3]);

        $this->actingAs($user)->get('/clients')->assertRedirect('/subscription');
        $this->assertSame('expired', $user->organization->subscription()->firstOrFail()->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['organization_id' => $organization->id, 'action' => 'subscription.expired']);
        $this->actingAs($user)->get('/subscription')->assertOk();
    }

    public function test_blocked_organization_cannot_access_workspace_but_owner_can_see_block_reason(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a', 'status' => 'blocked']);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'business', 'status' => 'active', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonth(), 'max_users' => 10]);

        $this->actingAs($user)
            ->get('/clients')
            ->assertRedirect('/subscription')
            ->assertSessionHas('organization_blocked');

        $this->get('/subscription')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Subscription/Show')
                ->where('organizationStatus', 'blocked')
                ->where('blockedMessage', 'Организация заблокирована администратором платформы.'));
    }

    public function test_active_subscription_without_an_end_date_fails_closed(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-no-end']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $subscription = Subscription::create(['organization_id' => $organization->id, 'plan' => 'business', 'status' => 'active', 'starts_at' => now()->subDay(), 'max_users' => 10]);

        $this->actingAs($owner)->get('/clients')->assertRedirect('/subscription');

        $this->assertSame('expired', $subscription->refresh()->status);
    }

    public function test_manager_can_view_subscription_lock_but_cannot_request_a_plan_change(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-manager-lock']);
        $manager = User::factory()->create(['organization_id' => $organization->id, 'role' => 'manager']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->subMinute(), 'max_users' => 3]);

        $this->actingAs($manager)->get('/clients')->assertRedirect('/subscription');
        $this->actingAs($manager)->get('/subscription')->assertOk();
        $this->actingAs($manager)->post('/subscription/plan-change', ['plan' => 'business'])->assertForbidden();
    }
}
