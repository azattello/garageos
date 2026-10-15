<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_public_landing_with_current_plan_catalog(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Landing/Index')
                ->where('plans.0.name', 'Start')
                ->where('plans.0.price', 39900)
                ->where('plans.1.name', 'Business')
                ->where('plans.1.price', 69900));
    }

    public function test_authenticated_owner_keeps_the_existing_dashboard_home(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $organization->subscription()->create([
            'plan' => 'start',
            'status' => 'trialing',
            'max_users' => 3,
            'trial_ends_at' => now()->addDays(14),
        ]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard'));
    }
}