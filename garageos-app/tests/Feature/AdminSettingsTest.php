<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_update_non_secret_support_contacts(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);

        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings/Index')
                ->where('system.automaticCharges', false)
                ->where('system.csrfProtection', true));

        $this->actingAs($admin)->put('/admin/settings/general', [
            'support_email' => 'support@GarageOS.example',
            'support_phone' => '+77001234567',
            'support_url' => 'https://GarageOS.example/help',
        ])->assertRedirect();

        $this->assertDatabaseHas('saas_settings', ['key' => 'support_email', 'value' => 'support@GarageOS.example', 'updated_by' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'platform.settings.updated']);

        $this->actingAs($admin)->put('/admin/settings/general', [
            'support_email' => 'invalid', 'support_phone' => '', 'support_url' => '',
        ])->assertSessionHasErrors('support_email');
    }

    public function test_tenant_user_cannot_read_or_update_platform_settings(): void
    {
        $organization = Organization::create(['name' => 'Tenant', 'slug' => 'settings-tenant']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)->get('/admin/settings')->assertForbidden();
        $this->actingAs($owner)->put('/admin/settings/general', [])->assertForbidden();
    }
}