<?php

namespace Tests\Feature;

use App\Models\AppointmentStatus;
use App\Models\Organization;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_tenant_defaults(): void
    {
        $response = $this->post('/register', [
            'name' => 'Azat',
            'business_name' => 'Garage Almaty',
            'phone' => '+77000000000',
            'email' => 'owner@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/');
        $organization = Organization::firstOrFail();
        $this->assertSame(8, ServiceCategory::where('organization_id', $organization->id)->count());
        $this->assertSame(5, AppointmentStatus::where('organization_id', $organization->id)->count());
    }

    public function test_statuses_are_tenant_scoped(): void
    {
        $first = Organization::create(['name' => 'First', 'slug' => 'first']);
        $second = Organization::create(['name' => 'Second', 'slug' => 'second']);
        Subscription::create(['organization_id' => $first->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $first->id, 'role' => 'owner']);
        AppointmentStatus::create(['organization_id' => $second->id, 'name' => 'Private', 'slug' => 'private', 'sort_order' => 1]);

        $this->actingAs($user)->get('/appointments')->assertOk();
        $this->assertSame(0, $first->appointmentStatuses()->where('slug', 'private')->count());
    }

    public function test_owner_can_update_archive_and_restore_service_without_deleting_history(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'ТО', 'price' => 10000, 'duration_minutes' => 60, 'active' => true]);

        $this->actingAs($user)->put("/services/{$service->id}", [
            'name' => 'Расширенное ТО',
            'price' => 15000,
            'duration_minutes' => 90,
            'category_id' => null,
            'description' => 'Замена масла и фильтров',
        ])->assertRedirect('/services');
        $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'Расширенное ТО', 'price' => 15000, 'active' => true]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'service.updated', 'subject_id' => $service->id]);

        $this->actingAs($user)->delete("/services/{$service->id}")->assertRedirect('/services?archived=1');
        $this->assertDatabaseHas('services', ['id' => $service->id, 'active' => false]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'service.archived', 'subject_id' => $service->id]);

        $this->actingAs($user)->post("/services/{$service->id}/restore")->assertRedirect('/services');
        $this->assertDatabaseHas('services', ['id' => $service->id, 'active' => true]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'service.restored', 'subject_id' => $service->id]);
    }

    public function test_owner_cannot_update_another_organizations_service(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        $otherOrganization = Organization::create(['name' => 'Other', 'slug' => 'other']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $privateService = Service::create(['organization_id' => $otherOrganization->id, 'name' => 'Private', 'price' => 10000, 'duration_minutes' => 60, 'active' => true]);

        $this->actingAs($user)->put("/services/{$privateService->id}", [
            'name' => 'Changed',
            'price' => 1,
            'duration_minutes' => 60,
        ])->assertNotFound();

        $this->assertDatabaseHas('services', ['id' => $privateService->id, 'name' => 'Private', 'price' => 10000]);
    }
}
