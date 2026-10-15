<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_registration_creates_tenant_and_trial_subscription(): void
    {
        $response = $this->post('/register', [
            'business_name' => 'АвтоМастер',
            'name' => 'Азамат',
            'phone' => '+77001234567',
            'email' => 'owner@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs(User::first());
        $this->assertDatabaseHas('organizations', [
            'name' => 'АвтоМастер',
            'slug' => 'avtomaster',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'plan' => 'start',
            'status' => 'trialing',
            'max_users' => 3,
        ]);
        $this->assertSame(1, Organization::count());
        $this->assertSame(1, Subscription::count());
    }
}