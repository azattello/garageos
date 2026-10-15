<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_employee_invite(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $response = $this->actingAs($owner)->post('/employees/invite', ['email' => 'mechanic@example.test', 'role' => 'mechanic']);

        $response->assertRedirect('/employees')->assertSessionHas('invite_url');
        $inviteUrl = $this->app['session.store']->get('invite_url');
        $token = basename(parse_url($inviteUrl, PHP_URL_PATH));
        $this->assertDatabaseHas('user_invites', ['organization_id' => $organization->id, 'email' => 'mechanic@example.test', 'role' => 'mechanic']);
        $this->assertDatabaseHas('user_invites', ['organization_id' => $organization->id, 'token_hash' => hash('sha256', $token)]);
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->id, 'type' => 'employee_invited']);
    }

    public function test_owner_cannot_invite_beyond_plan_user_limit(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        User::factory()->create(['organization_id' => $organization->id, 'role' => 'manager']);
        User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);

        $response = $this->actingAs($owner)->post('/employees/invite', ['email' => 'mechanic@example.test', 'role' => 'mechanic']);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('user_invites', ['organization_id' => $organization->id, 'email' => 'mechanic@example.test']);
    }

    public function test_client_portal_accounts_do_not_consume_employee_seats(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        User::factory()->create(['organization_id' => $organization->id, 'role' => 'client']);

        $this->actingAs($owner)
            ->post('/employees/invite', ['email' => 'mechanic@example.test', 'role' => 'mechanic'])
            ->assertRedirect('/employees')
            ->assertSessionHas('invite_url');

        $this->assertDatabaseHas('user_invites', ['organization_id' => $organization->id, 'email' => 'mechanic@example.test']);
    }

    public function test_owner_can_resend_invite_and_old_link_is_expired(): void
    {
        [$organization, $owner] = $this->createOrganizationAndOwner();
        $oldInvite = UserInvite::create([
            'organization_id' => $organization->id,
            'email' => 'mechanic@example.test',
            'role' => 'mechanic',
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(7),
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->post("/employees/invites/{$oldInvite->id}/resend");
        $response->assertRedirect('/employees')->assertSessionHas('invite_url');

        $newToken = basename(parse_url($this->app['session.store']->get('invite_url'), PHP_URL_PATH));
        $oldInvite->refresh();
        $this->assertTrue($oldInvite->expires_at->isPast());
        $this->assertDatabaseHas('user_invites', ['organization_id' => $organization->id, 'token_hash' => hash('sha256', $newToken)]);
        $this->assertSame(1, UserInvite::where('organization_id', $organization->id)->whereNull('accepted_at')->where('expires_at', '>', now())->count());
    }

    public function test_owner_can_revoke_invite_without_deleting_its_audit_record(): void
    {
        [$organization, $owner] = $this->createOrganizationAndOwner();
        $invite = UserInvite::create([
            'organization_id' => $organization->id,
            'email' => 'mechanic@example.test',
            'role' => 'mechanic',
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(7),
            'invited_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->post("/employees/invites/{$invite->id}/revoke")
            ->assertRedirect('/employees')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_invites', ['id' => $invite->id]);
        $this->assertTrue($invite->refresh()->expires_at->isPast());
    }

    private function createOrganizationAndOwner(): array
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        return [$organization, $owner];
    }
}
