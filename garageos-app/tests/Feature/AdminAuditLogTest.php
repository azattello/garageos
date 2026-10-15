<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_login_logout_are_audited_and_visible(): void
    {
        $admin = User::factory()->create([
            'organization_id' => null,
            'role' => 'platform_admin',
            'email' => 'platform@example.test',
            'password' => 'password',
        ]);

        Carbon::setTestNow(now());
        $this->post('/login', ['email' => 'platform@example.test', 'password' => 'password'])->assertRedirect('/');
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'admin.login']);
        Carbon::setTestNow(now()->addSecond());
        $this->post('/logout')->assertRedirect('/login');
        Carbon::setTestNow();
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'admin.logout']);

        $this->actingAs($admin)->get('/admin/audit-logs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/AuditLogs/Index')
                ->where('events.total', 2)
                ->where('events.data.0.actor', $admin->name));
    }

    public function test_audit_log_filters_by_organization_and_tenant_cannot_access(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Audited Garage', 'slug' => 'audited-garage']);
        ActivityLog::create(['organization_id' => $organization->id, 'user_id' => $admin->id, 'action' => 'saas.payment.created', 'metadata' => ['amount' => 1000], 'ip_address' => '127.0.0.1', 'created_at' => now()]);
        ActivityLog::create(['organization_id' => null, 'user_id' => $admin->id, 'action' => 'admin.login', 'created_at' => now()->subMinute()]);

        $this->actingAs($admin)->get('/admin/audit-logs?organization_id='.$organization->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('events.total', 1)
                ->where('events.data.0.organization', 'Audited Garage')
                ->where('events.data.0.ip', '127.0.0.1'));

        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $this->actingAs($owner)->get('/admin/audit-logs')->assertForbidden();
    }
}