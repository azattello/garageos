<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\ActivityLog;
use App\Models\Car;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_defaults_use_almaty_timezone_and_kazakhstani_organization_defaults(): void
    {
        $this->assertSame('Asia/Almaty', config('app.timezone'));
        $this->assertSame('mysql', config('database.connections.mysql.driver'));
        $this->assertSame('+05:00', config('database.connections.mysql.timezone'));

        $organization = Organization::create(['name' => 'Garage Defaults', 'slug' => 'garage-defaults']);

        $organization->refresh();
        $this->assertSame('Asia/Almaty', $organization->timezone);
        $this->assertSame('KZT', $organization->currency);
    }

    public function test_owner_can_update_organization_settings(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->put('/settings', [
                'name' => 'Garage Premium',
                'phone' => '+7 777 123 45 67',
                'email' => 'team@garagepremium.test',
                'timezone' => 'Asia/Almaty',
                'currency' => 'KZT',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Garage Premium',
            'phone' => '+7 777 123 45 67',
            'email' => 'team@garagepremium.test',
            'timezone' => 'Asia/Almaty',
            'currency' => 'KZT',
        ]);
        $this->assertDatabaseHas('activity_logs', ['organization_id' => $organization->id, 'action' => 'organization.settings_updated', 'user_id' => $owner->id]);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'manager']);

        Notification::create(['organization_id' => $organization->id, 'user_id' => $user->id, 'type' => 'system', 'title' => 'Первое', 'body' => 'Тест 1']);
        Notification::create(['organization_id' => $organization->id, 'user_id' => $user->id, 'type' => 'system', 'title' => 'Второе', 'body' => 'Тест 2']);

        $this->actingAs($user)->post('/notifications/read-all')->assertRedirect();

        $this->assertSame(2, Notification::where('user_id', $user->id)->whereNotNull('read_at')->count());
    }

    public function test_organization_settings_reject_invalid_timezone_and_currency_format(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->put('/settings', [
                'name' => 'Garage A',
                'timezone' => 'Mars/Olympus',
                'currency' => 'kz',
            ])
            ->assertSessionHasErrors(['timezone', 'currency']);
    }

    public function test_owner_can_filter_activity_and_receives_safe_before_after_details(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        $otherOrganization = Organization::create(['name' => 'Other', 'slug' => 'other']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner', 'name' => 'Manager View']);
        $otherOwner = User::factory()->create(['organization_id' => $otherOrganization->id, 'role' => 'owner']);
        $today = now()->toDateString();

        ActivityLog::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'action' => 'car.updated',
            'subject_type' => Car::class,
            'subject_id' => 42,
            'metadata' => [
                'before' => ['model' => 'Camry', 'access_token' => 'do-not-show'],
                'after' => ['model' => 'Camry Hybrid', 'password' => 'do-not-show'],
                'token' => 'do-not-show',
            ],
            'created_at' => now(),
        ]);
        ActivityLog::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'action' => 'order.created',
            'subject_type' => 'App\\Models\\Order',
            'subject_id' => 43,
            'metadata' => [],
            'created_at' => now(),
        ]);
        ActivityLog::create([
            'organization_id' => $otherOrganization->id,
            'user_id' => $otherOwner->id,
            'action' => 'car.updated',
            'subject_type' => Car::class,
            'subject_id' => 99,
            'metadata' => [],
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get('/notifications?'.http_build_query([
                'q' => 'car.updated',
                'actor' => $owner->id,
                'entity' => Car::class,
                'from' => $today,
                'to' => $today,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Notifications/Index')
                ->where('canViewActivities', true)
                ->where('activities.total', 1)
                ->where('activities.data.0.actor', 'Manager View')
                ->where('activities.data.0.entity', 'Car')
                ->where('activities.data.0.subject_id', 42)
                ->where('activities.data.0.before.model', 'Camry')
                ->where('activities.data.0.after.model', 'Camry Hybrid')
                ->missing('activities.data.0.before.access_token')
                ->missing('activities.data.0.after.password'));
    }

    public function test_mechanic_does_not_receive_activity_log_data(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);

        $this->actingAs($mechanic)->get('/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canViewActivities', false)
                ->where('activities', null)
                ->has('activityActors', 0)
                ->has('activityEntities', 0));
    }
}
