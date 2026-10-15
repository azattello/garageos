<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Car;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_team_summary_and_mechanic_cannot_manage_team(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic', 'last_login_at' => now()->subDay()]);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'slug' => 'delivered', 'name' => 'Выдан', 'sort_order' => 1]);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77000000000']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        Order::create([
            'organization_id' => $organization->id,
            'number' => 'ЗН-00001',
            'client_id' => $client->id,
            'car_id' => $car->id,
            'mechanic_id' => $mechanic->id,
            'status_id' => $status->id,
            'opened_at' => now()->subDays(4),
            'completed_at' => now()->subDays(2),
            'total' => 10000,
            'balance' => 0,
        ]);

        $this->actingAs($owner)
            ->get('/employees')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Employees/Team')
                ->where('summary.total', 2)
                ->where('summary.active', 2)
                ->where('summary.mechanics', 1)
                ->where('summary.pendingInvites', 0)
                ->where('summary.completedOrders30Days', 1)
                ->where('employeeAnalytics.0.name', $mechanic->name)
                ->where('employeeAnalytics.0.completed_orders', 1)
                ->where('employees.1.last_login_at', $mechanic->last_login_at->toISOString()));

        $mechanic = User::where('organization_id', $organization->id)->where('role', 'mechanic')->firstOrFail();
        $this->actingAs($mechanic)->get('/employees')->assertForbidden();
    }

    public function test_owner_can_update_and_deactivate_employee_without_active_orders(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $employee = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);

        $this->actingAs($owner)
            ->put("/employees/{$employee->id}", ['name' => 'Арман', 'phone' => '+77001234567', 'specialization' => 'Электрик'])
            ->assertRedirect('/employees');
        $this->assertDatabaseHas('users', ['id' => $employee->id, 'name' => 'Арман', 'specialization' => 'Электрик']);

        $this->post("/employees/{$employee->id}/status", ['active' => false])->assertRedirect('/employees');
        $this->assertDatabaseHas('users', ['id' => $employee->id, 'status' => 'inactive']);
        $this->post("/employees/{$employee->id}/status", ['active' => true])->assertRedirect('/employees');
        $this->assertDatabaseHas('users', ['id' => $employee->id, 'status' => 'active']);
    }

    public function test_employee_with_active_orders_cannot_be_deactivated_and_manager_cannot_manage_accounts(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $manager = User::factory()->create(['organization_id' => $organization->id, 'role' => 'manager']);
        $employee = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'slug' => 'in_progress', 'name' => 'В работе', 'sort_order' => 1]);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Клиент', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'mechanic_id' => $employee->id, 'status_id' => $status->id, 'opened_at' => now(), 'total' => 0, 'balance' => 0]);

        $this->actingAs($owner)
            ->post("/employees/{$employee->id}/status", ['active' => false])
            ->assertSessionHasErrors('active');
        $this->assertDatabaseHas('users', ['id' => $employee->id, 'status' => 'active']);

        $this->actingAs($manager)
            ->put("/employees/{$employee->id}", ['name' => 'Изменение', 'phone' => '', 'specialization' => ''])
            ->assertForbidden();
    }

    public function test_employee_with_unfinished_appointments_cannot_be_deactivated(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-upcoming-appointment']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Клиент', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $service = Service::create(['organization_id' => $organization->id, 'name' => 'Осмотр', 'price' => 5000, 'duration_minutes' => 60]);
        Appointment::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'car_id' => $car->id, 'service_id' => $service->id, 'mechanic_id' => $mechanic->id, 'start_at' => now()->subMinutes(30), 'end_at' => now()->addMinutes(30), 'status' => 'arrived']);
        Appointment::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'car_id' => $car->id, 'service_id' => $service->id, 'mechanic_id' => $mechanic->id, 'start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHour(), 'status' => 'pending']);

        $this->actingAs($owner)
            ->post("/employees/{$mechanic->id}/status", ['active' => false])
            ->assertSessionHasErrors('active');
        $this->assertDatabaseHas('users', ['id' => $mechanic->id, 'status' => 'active']);
    }

    public function test_active_employee_can_sign_in_but_inactive_employee_cannot(): void
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-login']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $active = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'mechanic',
            'status' => 'active',
            'email' => 'active-mechanic@example.test',
            'password' => bcrypt('secure-test-password'),
        ]);
        $inactive = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'mechanic',
            'status' => 'inactive',
            'email' => 'inactive-mechanic@example.test',
            'password' => bcrypt('secure-test-password'),
        ]);

        $this->post('/login', ['email' => $active->email, 'password' => 'secure-test-password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($active);
        $this->assertNotNull($active->refresh()->last_login_at);

        $active->update(['status' => 'inactive']);
        $this->get('/notifications')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => $inactive->email, 'password' => 'secure-test-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull($inactive->refresh()->last_login_at);
    }
}
