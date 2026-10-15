<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteAcceptanceAndCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_user_can_accept_invite_and_create_account(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);

        $token = 'invite-token-123';
        $invite = UserInvite::create([
            'organization_id' => $organization->id,
            'email' => 'mechanic@example.test',
            'role' => 'mechanic',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
            'invited_by' => User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner'])->id,
        ]);

        $this->post("/invite/{$token}", [
            'name' => 'Иван Механик',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('users', [
            'organization_id' => $organization->id,
            'email' => 'mechanic@example.test',
            'role' => 'mechanic',
            'status' => 'active',
        ]);

        $this->assertNotNull(UserInvite::find($invite->id)->accepted_at);
    }

    public function test_calendar_supports_week_view_filter(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->get('/appointments?view=week&date=' . now()->toDateString())
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Appointments/WeekCalendar')->where('view', 'week'));
    }
}
