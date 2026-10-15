<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_filter_users_and_audit_role_and_status_changes(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Users Garage', 'slug' => 'users-garage']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic', 'name' => 'Alex Mechanic']);

        $this->actingAs($admin)->get('/admin/users?organization_id='.$organization->id.'&role=mechanic')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Alex Mechanic'));

        $this->actingAs($admin)->post("/admin/users/{$mechanic->id}/action", ['action' => 'change_role', 'role' => 'manager'])->assertRedirect();
        $this->assertSame('manager', $mechanic->fresh()->role);
        $this->assertDatabaseHas('activity_logs', ['organization_id' => $organization->id, 'user_id' => $admin->id, 'action' => 'user.role_changed']);

        $this->actingAs($admin)->post("/admin/users/{$mechanic->id}/action", ['action' => 'set_status', 'status' => 'inactive'])->assertRedirect();
        $this->assertSame('inactive', $mechanic->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['organization_id' => $organization->id, 'user_id' => $admin->id, 'action' => 'user.status_changed']);

        $this->actingAs($admin)->post("/admin/users/{$owner->id}/action", ['action' => 'set_status', 'status' => 'inactive'])->assertSessionHasErrors('action');
        $this->actingAs($admin)->post("/admin/users/{$mechanic->id}/action", ['action' => 'change_role', 'role' => 'platform_admin'])->assertSessionHasErrors('role');
    }

    public function test_tenant_user_cannot_view_or_change_global_users(): void
    {
        $organization = Organization::create(['name' => 'Tenant Garage', 'slug' => 'tenant-users']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $employee = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);

        $this->actingAs($owner)->get('/admin/users')->assertForbidden();
        $this->actingAs($owner)->post("/admin/users/{$employee->id}/action", ['action' => 'set_status', 'status' => 'inactive'])->assertForbidden();
    }
}