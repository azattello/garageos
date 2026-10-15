<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_is_created_only_in_authenticated_organization(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($user)->post('/clients', ['name' => 'Иван Петров', 'phone' => '+77001234567'])->assertRedirect('/clients');

        $this->assertDatabaseHas('clients', ['organization_id' => $organization->id, 'name' => 'Иван Петров']);
        $this->assertSame(1, Client::where('organization_id', $organization->id)->count());
    }

    public function test_duplicate_client_by_phone_or_email_is_reused_within_the_same_organization(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $existing = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+7 (700) 123-45-67', 'email' => 'ivan@example.test']);

        $this->actingAs($user)
            ->post('/clients', ['name' => 'Иван Петров 2', 'phone' => '+77001234567', 'email' => 'ivan@example.test'])
            ->assertRedirect('/clients?client_id='.$existing->id);

        $this->assertSame(1, Client::where('organization_id', $organization->id)->count());
    }
}
