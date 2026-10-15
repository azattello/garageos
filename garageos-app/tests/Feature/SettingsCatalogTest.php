<?php

namespace Tests\Feature;

use App\Models\AppointmentStatus;
use App\Models\Organization;
use App\Models\ServiceCategory;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_categories_and_non_terminal_statuses(): void
    {
        $organization = Organization::create(['name' => 'Garage', 'slug' => 'garage']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($user)->post('/settings/catalog/categories', ['name' => 'Кузов'])
            ->assertRedirect();
        $category = ServiceCategory::where('organization_id', $organization->id)->where('name', 'Кузов')->firstOrFail();

        $this->actingAs($user)->delete("/settings/catalog/categories/{$category->id}")->assertRedirect();
        $this->assertDatabaseHas('service_categories', ['id' => $category->id, 'active' => 0]);

        $this->actingAs($user)->post('/settings/catalog/statuses', ['name' => 'Ожидает запчасти'])->assertRedirect();
        $status = AppointmentStatus::where('organization_id', $organization->id)->where('name', 'Ожидает запчасти')->firstOrFail();
        $this->actingAs($user)->delete("/settings/catalog/statuses/{$status->id}")->assertRedirect();
        $this->assertDatabaseMissing('appointment_statuses', ['id' => $status->id]);
    }

    public function test_category_cannot_be_archived_from_another_tenant(): void
    {
        $first = Organization::create(['name' => 'First', 'slug' => 'first']);
        $second = Organization::create(['name' => 'Second', 'slug' => 'second']);
        Subscription::create(['organization_id' => $first->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $user = User::factory()->create(['organization_id' => $first->id, 'role' => 'owner']);
        $category = ServiceCategory::create(['organization_id' => $second->id, 'name' => 'Private', 'sort_order' => 1]);

        $this->actingAs($user)->delete("/settings/catalog/categories/{$category->id}")->assertNotFound();
    }
}
