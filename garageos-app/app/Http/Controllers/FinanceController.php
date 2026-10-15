<?php

namespace App\Http\Controllers;

use App\Actions\Finance\ArchiveExpense;
use App\Actions\Finance\CreateExpense;
use App\Actions\Finance\UpdateExpense;
use App\Actions\Finance\UpdateExpenseCategory;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        Gate::forUser($user)->authorize('viewAny', Expense::class);
        $organizationId = $user->organization_id;
        $period = (string) request()->query('period', '30d');
        $periods = ['today', '7d', '30d', '90d', 'month', 'year', 'custom'];
        if (! in_array($period, $periods, true)) {
            $period = '30d';
        }

        [$start, $end] = $this->periodRange($period);
        $days = max(1, (int) $start->diffInDays($end) + 1);
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subSecond();

        $payments = Payment::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'successful');
        $income = (int) (clone $payments)->whereBetween('paid_at', [$start, $end])->sum('amount');
        $previousIncome = (int) (clone $payments)->whereBetween('paid_at', [$previousStart, $previousEnd])->sum('amount');
        $expenses = Expense::query()->where('organization_id', $organizationId);
        $expenseTotal = (int) (clone $expenses)
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->sum('amount');
        $previousExpenses = (int) (clone $expenses)
            ->whereDate('expense_date', '>=', $previousStart->toDateString())
            ->whereDate('expense_date', '<=', $previousEnd->toDateString())
            ->sum('amount');

        $periodOrders = Order::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('opened_at', [$start, $end]);
        $orderCount = (clone $periodOrders)->count();
        $averageCheck = $orderCount > 0
            ? (int) round((int) (clone $periodOrders)->sum('total') / $orderCount)
            : 0;

        $dailyIncome = (clone $payments)
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as amount')
            ->groupByRaw('DATE(paid_at)')
            ->pluck('amount', 'day');
        $dailyExpenses = (clone $expenses)
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->selectRaw('expense_date as day, SUM(amount) as amount')
            ->groupBy('expense_date')
            ->pluck('amount', 'day');
        $trend = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $dailyIncome, $dailyExpenses) {
            $day = $start->copy()->addDays($offset);
            $date = $day->toDateString();
            $dayIncome = (int) $dailyIncome->get($date, 0);
            $dayExpenses = (int) $dailyExpenses->get($date, 0);

            return [
                'date' => $date,
                'label' => $day->locale('ru')->translatedFormat('d M'),
                'income' => $dayIncome,
                'expenses' => $dayExpenses,
                'profit' => $dayIncome - $dayExpenses,
            ];
        })->values();

        $categoryTotals = (clone $expenses)
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->selectRaw('category_id, SUM(amount) as amount')
            ->groupBy('category_id')
            ->pluck('amount', 'category_id');
        $categoryBreakdown = ExpenseCategory::where('organization_id', $organizationId)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->map(fn (ExpenseCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'amount' => (int) $categoryTotals->get($category->id, 0),
            ])
            ->filter(fn (array $category) => $category['amount'] > 0)
            ->values();

        $debtors = DB::table('orders')
            ->join('clients', function ($join) use ($organizationId) {
                $join->on('clients.id', '=', 'orders.client_id')
                    ->where('clients.organization_id', '=', $organizationId);
            })
            ->where('orders.organization_id', $organizationId)
            ->where('orders.balance', '>', 0)
            ->select(['clients.id', 'clients.name', 'clients.phone', 'clients.deleted_at'])
            ->selectRaw('SUM(orders.balance) as debt, COUNT(orders.id) as open_orders, MAX(orders.opened_at) as last_order_at')
            ->groupBy('clients.id', 'clients.name', 'clients.phone', 'clients.deleted_at')
            ->orderByDesc('debt')
            ->limit(100)
            ->get()
            ->map(fn (object $debtor) => [
                'client_id' => (int) $debtor->id,
                'name' => $debtor->name,
                'phone' => $debtor->phone,
                'debt' => (int) $debtor->debt,
                'open_orders' => (int) $debtor->open_orders,
                'last_order_at' => $debtor->last_order_at,
                'archived' => $debtor->deleted_at !== null,
            ])->values();

        $expenseRows = (clone $expenses)
            ->with(['category:id,name', 'creator:id,name'])
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Expense $expense) => [
                'id' => $expense->id,
                'amount' => (int) $expense->amount,
                'description' => $expense->description,
                'expense_date' => $expense->expense_date->toDateString(),
                'category_id' => $expense->category_id,
                'category' => $expense->category?->name ?? 'Без категории',
                'creator' => $expense->creator?->name,
            ]);

        return Inertia::render('Finance/Index', [
            'period' => $period,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'periodOptions' => [
                ['value' => 'today', 'label' => 'Сегодня'],
                ['value' => '7d', 'label' => '7 дней'],
                ['value' => '30d', 'label' => '30 дней'],
                ['value' => '90d', 'label' => '90 дней'],
                ['value' => 'month', 'label' => 'Месяц'],
                ['value' => 'year', 'label' => 'Год'],
                ['value' => 'custom', 'label' => 'Период'],
            ],
            'metrics' => [
                'income' => $income,
                'incomeChange' => $this->percentageChange($income, $previousIncome),
                'expenses' => $expenseTotal,
                'expensesChange' => $this->percentageChange($expenseTotal, $previousExpenses),
                'profit' => $income - $expenseTotal,
                'profitChange' => $this->percentageChange($income - $expenseTotal, $previousIncome - $previousExpenses),
                'debt' => (int) Order::where('organization_id', $organizationId)->where('balance', '>', 0)->sum('balance'),
                'averageCheck' => $averageCheck,
                'orders' => $orderCount,
            ],
            'debtors' => $debtors,
            'expenses' => $expenseRows,
            'trend' => $trend,
            'categories' => ExpenseCategory::where('organization_id', $organizationId)->where('active', true)->orderBy('sort_order')->get(['id', 'name']),
            'categoryBreakdown' => $categoryBreakdown,
        ]);
    }

    public function storeExpense(StoreExpenseRequest $request, CreateExpense $createExpense): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', Expense::class);
        $createExpense->handle($request->user(), $request->validated());

        return $this->backToReport($request, 'Расход записан.');
    }

    public function updateExpense(UpdateExpenseRequest $request, Expense $expense, UpdateExpense $updateExpense): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $expense);
        $updateExpense->handle($request->user(), $expense, $request->validated());

        return $this->backToReport($request, 'Расход обновлён.');
    }

    public function archiveExpense(Request $request, Expense $expense, ArchiveExpense $archiveExpense): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('delete', $expense);
        $archiveExpense->handle($request->user(), $expense);

        return $this->backToReport($request, 'Расход архивирован; запись сохранена в истории.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $user = $request->user();
        Gate::forUser($user)->authorize('create', ExpenseCategory::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('expense_categories', 'name')->where(fn ($query) => $query->where('organization_id', $user->organization_id))],
        ]);
        $organization = $user->organization;
        $category = $organization->expenseCategories()->create([
            'name' => $data['name'],
            'sort_order' => $organization->expenseCategories()->max('sort_order') + 1,
            'active' => true,
        ]);
        ActivityLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'action' => 'finance.expense_category_created',
            'subject_type' => $category->getMorphClass(),
            'subject_id' => $category->id,
            'metadata' => ['name' => $category->name],
            'created_at' => now(),
        ]);

        return $this->backToReport($request, 'Категория расхода добавлена.');
    }

    public function updateCategory(Request $request, ExpenseCategory $category, UpdateExpenseCategory $updateExpenseCategory): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $category);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $updateExpenseCategory->handle($request->user(), $category, $data['name']);

        return $this->backToReport($request, 'Категория расхода обновлена.');
    }

    public function archiveCategory(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $user = $request->user();
        Gate::forUser($user)->authorize('delete', $category);
        $category->update(['active' => false]);
        ActivityLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'action' => 'finance.expense_category_archived',
            'subject_type' => $category->getMorphClass(),
            'subject_id' => $category->id,
            'metadata' => ['name' => $category->name],
            'created_at' => now(),
        ]);

        return $this->backToReport($request, 'Категория архивирована. История расходов сохранена.');
    }

    private function periodRange(string $period): array
    {
        $today = Carbon::today();
        if ($period === 'custom') {
            $range = Validator::make(request()->query(), [
                'from' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'to' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:from'],
            ])->validate();
            $start = Carbon::createFromFormat('Y-m-d', $range['from'], config('app.timezone'))->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $range['to'], config('app.timezone'))->endOfDay();
            if ($start->diffInDays($end) > 365) {
                throw ValidationException::withMessages(['to' => 'Произвольный период не может превышать 366 дней.']);
            }

            return [$start, $end];
        }

        $start = match ($period) {
            'today' => $today->copy()->startOfDay(),
            'month' => $today->copy()->startOfMonth(),
            'year' => $today->copy()->startOfYear(),
            '7d' => $today->copy()->subDays(6)->startOfDay(),
            '90d' => $today->copy()->subDays(89)->startOfDay(),
            default => $today->copy()->subDays(29)->startOfDay(),
        };

        return [$start, now()];
    }

    private function backToReport(Request $request, string $message): RedirectResponse
    {
        $parameters = [];
        if ($request->query('period')) {
            $period = (string) $request->query('period');
            $parameters['period'] = in_array($period, ['today', '7d', '30d', '90d', 'month', 'year', 'custom'], true) ? $period : '30d';
            foreach (['from', 'to'] as $key) {
                if ($request->query($key)) {
                    $parameters[$key] = $request->query($key);
                }
            }
        }

        return to_route('finance.index', $parameters ?: [])->with('success', $message);
    }

    private function percentageChange(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return $current === 0 ? 0 : null;
        }

        return (int) round((($current - $previous) / abs($previous)) * 100);
    }
}