<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_named_login_from_application_routes(): void
    {
        foreach (['/clients', '/services', '/appointments', '/orders'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('Landing/Index'));
        $this->get('/login')->assertOk();
    }

    public function test_user_can_login_and_logout(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'owner@example.test',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_can_login_with_mixed_case_email(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'Owner@Example.Test',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->post('/login', ['email' => 'OWNER@EXAMPLE.TEST', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => 'throttle@example.test', 'password' => 'wrong-password'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => 'throttle@example.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Слишком много попыток входа. Повторите через 60 сек.']);
    }

    public function test_registration_route_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/register', [])->assertSessionHasErrors();
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_public_and_client_portal_routes_have_rate_limits(): void
    {
        $router = app('router')->getRoutes();
        $this->assertContains('throttle:30,1', $router->getByName('orders.public.show')->gatherMiddleware());
        $this->assertContains('throttle:60,1', $router->getByName('client-portal.index')->gatherMiddleware());
        $this->assertContains('throttle:120,1', $router->getByName('client-portal.photos.show')->gatherMiddleware());
        $this->assertContains('throttle:3,60', $router->getByName('register.store')->gatherMiddleware());
        $this->assertContains('throttle:3,60', $router->getByName('password.email')->gatherMiddleware());
        $this->assertContains('throttle:5,1', $router->getByName('password.update')->gatherMiddleware());
        $this->assertContains('throttle:10,1', $router->getByName('invite.accept')->gatherMiddleware());
    }

    public function test_dashboard_uses_authenticated_user_and_organization_context(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $organization->subscription()->create([
            'plan' => 'start',
            'status' => 'trialing',
            'max_users' => 3,
            'trial_ends_at' => now()->addDays(14),
        ]);
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Азамат',
            'email' => 'owner@example.test',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Garage A');
        $response->assertSee('\\u0410\\u0437\\u0430\\u043c\\u0430\\u0442');
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('analytics.period', '7d')
            ->has('analytics.trend', 7));
    }
}
