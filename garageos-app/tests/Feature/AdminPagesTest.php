<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_render_every_admin_module(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $this->actingAs($admin);

        $pages = [
            '/admin' => 'Admin/Index',
            '/admin/organizations' => 'Admin/Organizations/Index',
            '/admin/users' => 'Admin/Users/Index',
            '/admin/plans' => 'Admin/Plans/Index',
            '/admin/subscriptions' => 'Admin/Subscriptions/Index',
            '/admin/payments' => 'Admin/Payments/Index',
            '/admin/finance' => 'Admin/Finance/Index',
            '/admin/analytics?period=7d' => 'Admin/Analytics/Index',
            '/admin/support' => 'Admin/Support/Index',
            '/admin/audit-logs' => 'Admin/AuditLogs/Index',
            '/admin/settings' => 'Admin/Settings/Index',
        ];

        foreach ($pages as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->component($component));
        }
    }
}