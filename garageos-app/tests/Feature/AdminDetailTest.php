<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_organization_detail(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);

        $this->actingAs($admin)->get("/admin/organizations/{$organization->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Show')
                ->where('organization.id', $organization->id)
                ->where('organization.name', 'Garage A'));
    }

    public function test_regular_user_cannot_view_organization_detail(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($user)->get("/admin/organizations/{$organization->id}")->assertForbidden();
    }
}
