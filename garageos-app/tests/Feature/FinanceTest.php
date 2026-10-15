<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_totals_use_successful_tenant_payments_and_tenant_expenses(): void
    {
        [$organization, $owner, $order] = $this->createFinanceFixture();
        $category = ExpenseCategory::create(['organization_id' => $organization->id, 'name' => 'Расходники', 'sort_order' => 1, 'active' => true]);
        Payment::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'amount' => 50000, 'method' => 'kaspi', 'status' => 'successful', 'paid_at' => now()]);
        Payment::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'amount' => 90000, 'method' => 'cash', 'status' => 'cancelled', 'paid_at' => now()]);
        Expense::create(['organization_id' => $organization->id, 'category_id' => $category->id, 'amount' => 15000, 'description' => 'Материалы', 'expense_date' => today(), 'created_by' => $owner->id]);
        $this->assertSame(15000, (int) Expense::where('organization_id', $organization->id)->whereDate('expense_date', today())->sum('amount'));

        $otherOrganization = Organization::create(['name' => 'Garage B', 'slug' => 'garage-b']);
        ExpenseCategory::create(['organization_id' => $otherOrganization->id, 'name' => 'Чужая категория', 'sort_order' => 1, 'active' => true]);
        Expense::create(['organization_id' => $otherOrganization->id, 'amount' => 80000, 'description' => 'Другой сервис', 'expense_date' => today()]);

        $this->actingAs($owner)
            ->get('/finance?period=30d')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Finance/Index')
                ->where('metrics.income', 50000)
                ->where('metrics.expenses', 15000)
                ->where('metrics.profit', 35000)
                ->where('metrics.averageCheck', 70000)
                ->where('metrics.orders', 1)
                ->where('metrics.debt', 70000)
                ->has('debtors', 1)
                ->where('debtors.0.debt', 70000)
                ->where('categoryBreakdown.0.name', 'Расходники')
                ->where('categoryBreakdown.0.amount', 15000));
    }

    public function test_owner_can_record_expense_and_add_or_archive_categories_without_erasing_history(): void
    {
        [$organization, $owner] = $this->createFinanceFixture();
        $category = ExpenseCategory::create(['organization_id' => $organization->id, 'name' => 'Транспорт', 'sort_order' => 1, 'active' => true]);

        $this->actingAs($owner)
            ->post('/finance/expenses?period=7d', [
                'category_id' => $category->id,
                'amount' => 12000,
                'description' => 'Доставка детали',
                'expense_date' => today()->toDateString(),
            ])
            ->assertRedirect(route('finance.index', ['period' => '7d']));

        $expense = Expense::where('organization_id', $organization->id)->firstOrFail();
        $this->assertSame($category->id, $expense->category_id);
        $this->assertSame(12000, $expense->amount);

        $this->actingAs($owner)
            ->post('/finance/categories', ['name' => 'Аренда'])
            ->assertRedirect(route('finance.index'));
        $this->assertDatabaseHas('expense_categories', ['organization_id' => $organization->id, 'name' => 'Аренда', 'active' => true]);

        $this->actingAs($owner)
            ->put("/finance/categories/{$category->id}", ['name' => 'Логистика'])
            ->assertRedirect(route('finance.index'));
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id, 'name' => 'Логистика', 'active' => true]);

        $this->actingAs($owner)
            ->delete("/finance/categories/{$category->id}")
            ->assertRedirect(route('finance.index'));
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id, 'active' => false]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'category_id' => $category->id]);
    }

    public function test_expense_update_and_archive_preserve_audited_history(): void
    {
        [$organization, $owner] = $this->createFinanceFixture();
        $category = ExpenseCategory::create(['organization_id' => $organization->id, 'name' => 'Материалы', 'sort_order' => 1, 'active' => true]);
        $otherOrganization = Organization::create(['name' => 'Garage B', 'slug' => 'garage-b']);
        $otherCategory = ExpenseCategory::create(['organization_id' => $otherOrganization->id, 'name' => 'Чужая категория', 'sort_order' => 1, 'active' => true]);
        $this->actingAs($owner)->post('/finance/expenses', [
            'category_id' => $category->id,
            'amount' => 12000,
            'description' => 'Запчасти',
            'expense_date' => today()->toDateString(),
        ])->assertRedirect(route('finance.index'));
        $expense = Expense::where('organization_id', $organization->id)->firstOrFail();

        $this->actingAs($owner)
            ->from(route('finance.index'))
            ->put("/finance/expenses/{$expense->id}", [
                'category_id' => $otherCategory->id,
                'amount' => 1,
                'description' => 'Cross-tenant attempt',
                'expense_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('category_id');
        $this->assertSame(12000, $expense->refresh()->amount);

        $this->actingAs($owner)->put("/finance/expenses/{$expense->id}", [
            'category_id' => $category->id,
            'amount' => 15000,
            'description' => 'Исправленная закупка',
            'expense_date' => today()->toDateString(),
        ])->assertRedirect(route('finance.index'));
        $this->assertSame(15000, $expense->refresh()->amount);
        $this->assertDatabaseHas('activity_logs', ['action' => 'finance.expense_updated', 'subject_id' => $expense->id]);

        $this->actingAs($owner)->delete("/finance/expenses/{$expense->id}")->assertRedirect(route('finance.index'));
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'finance.expense_archived', 'subject_id' => $expense->id]);
        $this->actingAs($owner)->get('/finance')->assertInertia(fn ($page) => $page->where('expenses.total', 0));
    }

    public function test_average_check_and_debtor_list_are_calculated_for_current_tenant(): void
    {
        [$organization, $owner, $firstOrder] = $this->createFinanceFixture();
        $firstOrder->update(['paid_total' => 50000, 'balance' => 20000]);
        $firstClient = $firstOrder->client;
        $secondClient = Client::create(['organization_id' => $organization->id, 'name' => 'Второй клиент', 'phone' => '+77000000002']);
        $secondCar = Car::create(['organization_id' => $organization->id, 'client_id' => $secondClient->id, 'make' => 'Honda', 'model' => 'Civic']);
        $status = $firstOrder->status;
        Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00002', 'client_id' => $secondClient->id, 'car_id' => $secondCar->id, 'status_id' => $status->id, 'opened_at' => now(), 'total' => 30000, 'balance' => 30000]);

        $otherOrganization = Organization::create(['name' => 'Garage B', 'slug' => 'garage-b']);
        $otherClient = Client::create(['organization_id' => $otherOrganization->id, 'name' => 'Скрытый должник', 'phone' => '+77000000003']);
        $otherCar = Car::create(['organization_id' => $otherOrganization->id, 'client_id' => $otherClient->id, 'make' => 'Ford', 'model' => 'Focus']);
        $otherStatus = OrderStatus::create(['organization_id' => $otherOrganization->id, 'name' => 'Новый', 'slug' => 'new']);
        Order::create(['organization_id' => $otherOrganization->id, 'number' => 'ЗН-00001', 'client_id' => $otherClient->id, 'car_id' => $otherCar->id, 'status_id' => $otherStatus->id, 'opened_at' => now(), 'total' => 900000, 'balance' => 900000]);

        $this->actingAs($owner)->get('/finance?period=7d')->assertInertia(fn ($page) => $page
            ->where('metrics.averageCheck', 50000)
            ->where('metrics.orders', 2)
            ->where('metrics.debt', 50000)
            ->has('debtors', 2)
            ->where('debtors.0.name', 'Второй клиент')
            ->missing('debtors.2'));
    }

    public function test_custom_finance_range_is_validated_and_dates_are_returned(): void
    {
        [$organization, $owner] = $this->createFinanceFixture();
        $from = today()->subDays(10)->toDateString();
        $to = today()->subDays(5)->toDateString();

        $this->actingAs($owner)->get("/finance?period=custom&from={$from}&to={$to}")->assertOk()->assertInertia(fn ($page) => $page
            ->where('period', 'custom')
            ->where('from', $from)
            ->where('to', $to));

        $tooEarly = today()->subYears(2)->toDateString();
        $this->actingAs($owner)->get("/finance?period=custom&from={$tooEarly}&to={$to}")->assertSessionHasErrors('to');
    }

    public function test_mechanic_cannot_access_financials_or_create_expenses(): void
    {
        [$organization] = $this->createFinanceFixture();
        $mechanic = User::factory()->create(['organization_id' => $organization->id, 'role' => 'mechanic']);

        $this->actingAs($mechanic)->get('/finance')->assertForbidden();
        $this->actingAs($mechanic)->post('/finance/expenses', [])->assertForbidden();
    }

    private function createFinanceFixture(): array
    {
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'business', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 10]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new', 'sort_order' => 1]);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now(), 'total' => 70000, 'balance' => 70000]);

        return [$organization, $owner, $order];
    }
}
