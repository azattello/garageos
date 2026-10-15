<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrganizationAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_organization_using_existing_setup_flow(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);

        $this->actingAs($admin)->post('/admin/organizations', [
            'name' => 'North Service',
            'owner_name' => 'Owner North',
            'owner_email' => 'north@example.test',
            'email' => 'office@example.test',
            'phone' => '+77010000000',
            'plan' => 'business',
            'trial_days' => 21,
            'status' => 'active',
        ])->assertRedirect();

        $organization = Organization::where('name', 'North Service')->firstOrFail();
        $owner = $organization->owner()->firstOrFail();
        $subscription = $organization->subscription()->firstOrFail();
        $this->assertSame('north@example.test', $owner->email);
        $this->assertSame('business', $subscription->plan);
        $this->assertSame(10, $subscription->max_users);
        $this->assertSame('trialing', $subscription->status);
        $this->assertTrue($subscription->trial_ends_at->isFuture());
        $this->assertSame(8, $organization->serviceCategories()->count());
        $this->assertDatabaseHas('activity_logs', ['organization_id' => $organization->id, 'user_id' => $admin->id, 'action' => 'organization.created_by_platform_admin']);
        Notification::assertSentTo($owner, ResetPassword::class);
    }

    public function test_organization_directory_is_paginated_and_filterable(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $active = Organization::create(['name' => 'Active North', 'slug' => 'active-north', 'status' => 'active']);
        $blocked = Organization::create(['name' => 'Blocked South', 'slug' => 'blocked-south', 'status' => 'blocked']);
        Subscription::create(['organization_id' => $active->id, 'plan' => 'start', 'status' => 'trialing', 'max_users' => 3]);
        Subscription::create(['organization_id' => $blocked->id, 'plan' => 'business', 'status' => 'active', 'max_users' => 10]);

        $this->actingAs($admin)->get('/admin/organizations?status=active&plan=start')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Organizations/Index')
                ->where('organizations.total', 1)
                ->where('organizations.data.0.name', 'Active North')
                ->where('organizations.data.0.counts.users', 0));
    }

    public function test_tenant_owner_cannot_create_platform_organization(): void
    {
        $organization = Organization::create(['name' => 'Tenant', 'slug' => 'tenant-org-admin']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)->get('/admin/organizations')->assertForbidden();
        $this->actingAs($owner)->post('/admin/organizations', [])->assertForbidden();
    }
}