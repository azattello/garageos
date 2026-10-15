<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_mechanic_cannot_open_owner_or_manager_routes_or_mutate_master_data(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'business', 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'max_users' => 10]);
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic', 'status' => 'active']);

        $this->actingAs($mechanic)->get('/')->assertRedirect('/orders');

        foreach (['/analytics', '/clients', '/services', '/appointments', '/settings', '/settings/catalog', '/employees', '/finance', '/inventory', '/subscription'] as $path) {
            $this->get($path)->assertForbidden();
        }

        $this->post('/services', [])->assertForbidden();
        $this->post('/cars', [])->assertForbidden();
        $this->post('/appointments', [])->assertForbidden();
        $this->put('/settings', [])->assertForbidden();
    }
}
