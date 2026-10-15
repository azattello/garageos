<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_and_manage_organizations(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $subscription = Subscription::create([
            'organization_id' => $organization->id,
            'plan' => 'start',
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(2),
            'max_users' => 3,
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk();

        $this->actingAs($admin)->post("/admin/organizations/{$organization->id}/status", ['status' => 'blocked'])
            ->assertRedirect();
        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'status' => 'blocked']);
        $this->assertDatabaseHas('activity_logs', ['organization_id' => $organization->id, 'action' => 'organization.status_changed', 'user_id' => $admin->id]);

        $this->actingAs($admin)->post("/admin/organizations/{$organization->id}/trial", ['days' => 14])
            ->assertRedirect();
        $this->assertTrue($subscription->fresh()->trial_ends_at->isFuture());

        $this->actingAs($admin)->post("/admin/organizations/{$organization->id}/status", ['status' => 'archived'])->assertRedirect();
        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'status' => 'archived']);
        $this->actingAs($owner)->get('/clients')->assertRedirect('/subscription')->assertSessionHas('organization_blocked', 'Организация архивирована администратором платформы.');

        $this->actingAs($admin)->post("/admin/organizations/{$organization->id}/status", ['status' => 'active'])->assertRedirect();
        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'status' => 'active']);
    }

    public function test_regular_user_cannot_access_platform_admin(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $user->update(['role' => 'platform_admin']);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
