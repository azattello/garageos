<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\SaasExpense;
use App\Models\SaasRevenue;
use App\Services\Billing\PlanCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SaasFinanceController extends Controller
{
    private const REVENUE_CATEGORIES = [
        'subscription' => 'Подписка',
        'setup' => 'Внедрение',
        'support' => 'Поддержка',
        'add_on' => 'Дополнения',
        'other' => 'Другое',
    ];

    private const EXPENSE_CATEGORIES = [
        'hosting' => 'Хостинг',
        'domain' => 'Домен',
        'infrastructure' => 'Инфраструктура',
        'ai' => 'AI',
        'sms' => 'SMS',
        'email' => 'Email',
        'marketing' => 'Маркетинг',
        'software' => 'ПО',
        'contractor' => 'Подрядчик',
        'other' => 'Другое',
    ];

    public function index(Request $request, PlanCatalog $plans): Response
    {
        $period = $this->period($request);
        [$start, $end] = $this->range($request, $period);
        $revenues = $this->realizedRevenues();
        $expenses = SaasExpense::query();
        $revenueTotal = (int) (clone $revenues)
            ->whereDate('received_at', '>=', $start->toDateString())
            ->whereDate('received_at', '<=', $end->toDateString())
            ->sum('amount');
        $expenseTotal = (int) (clone $expenses)
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->sum('amount');
        $dailyRevenue = (clone $revenues)
            ->whereDate('received_at', '>=', $start->toDateString())
            ->whereDate('received_at', '<=', $end->toDateString())
            ->selectRaw('DATE(received_at) as day, SUM(amount) as total')
            ->groupByRaw('DATE(received_at)')
            ->pluck('total', 'day');
        $dailyExpenses = (clone $expenses)
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->selectRaw('DATE(expense_date) as day, SUM(amount) as total')
            ->groupByRaw('DATE(expense_date)')
            ->pluck('total', 'day');
        $days = min(366, max(1, (int) $start->diffInDays($end) + 1));
        $trend = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $dailyRevenue, $dailyExpenses): array {
            $day = $start->copy()->addDays($offset);
            $key = $day->toDateString();
            $income = (int) $dailyRevenue->get($key, 0);
            $expense = (int) $dailyExpenses->get($key, 0);

            return ['date' => $key, 'label' => $day->locale('ru')->translatedFormat('d M'), 'revenue' => $income, 'expenses' => $expense, 'net' => $income - $expense];
        })->values();

        return Inertia::render('Admin/Finance/Index', [
            'period' => $period,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'periodOptions' => $this->periodOptions(),
            'metrics' => ['revenue' => $revenueTotal, 'expenses' => $expenseTotal, 'net' => $revenueTotal - $expenseTotal],
            'trend' => $trend,
            'revenueByCategory' => (clone $revenues)
                ->whereDate('received_at', '>=', $start->toDateString())
                ->whereDate('received_at', '<=', $end->toDateString())
                ->selectRaw('category, SUM(amount) as amount')->groupBy('category')->get()
                ->map(fn ($row) => ['name' => self::REVENUE_CATEGORIES[$row->category] ?? $row->category, 'amount' => (int) $row->amount])->values(),
            'expensesByCategory' => (clone $expenses)
                ->whereDate('expense_date', '>=', $start->toDateString())
                ->whereDate('expense_date', '<=', $end->toDateString())
                ->selectRaw('category, SUM(amount) as amount')->groupBy('category')->get()
                ->map(fn ($row) => ['name' => self::EXPENSE_CATEGORIES[$row->category] ?? $row->category, 'amount' => (int) $row->amount])->values(),
            'revenueByPlan' => DB::table('saas_revenues')
                ->join('saas_payments', 'saas_payments.id', '=', 'saas_revenues.payment_id')
                ->where('saas_payments.status', 'paid')
                ->whereDate('saas_revenues.received_at', '>=', $start->toDateString())
                ->whereDate('saas_revenues.received_at', '<=', $end->toDateString())
                ->select('saas_payments.plan')->selectRaw('SUM(saas_revenues.amount) as amount')
                ->groupBy('saas_payments.plan')->get()
                ->map(fn ($row) => ['name' => $plans->get((string) $row->plan)['name'] ?? $row->plan ?? 'Без тарифа', 'amount' => (int) $row->amount])->values(),
            'revenueRows' => (clone $revenues)->whereDate('received_at', '>=', $start->toDateString())->whereDate('received_at', '<=', $end->toDateString())->with('organization:id,name')->latest('received_at')->paginate(10, ['*'], 'revenue_page')->withQueryString()->through(fn (SaasRevenue $row) => [
                'id' => $row->id, 'amount' => (int) $row->amount, 'currency' => $row->currency,
                'category' => self::REVENUE_CATEGORIES[$row->category] ?? $row->category,
                'organization' => $row->organization?->name, 'description' => $row->description, 'date' => $row->received_at->toDateString(),
            ]),
            'expenseRows' => (clone $expenses)->whereDate('expense_date', '>=', $start->toDateString())->whereDate('expense_date', '<=', $end->toDateString())->latest('expense_date')->paginate(10, ['*'], 'expense_page')->withQueryString()->through(fn (SaasExpense $row) => [
                'id' => $row->id, 'amount' => (int) $row->amount, 'currency' => $row->currency,
                'category' => self::EXPENSE_CATEGORIES[$row->category] ?? $row->category,
                'vendor' => $row->vendor, 'description' => $row->description, 'date' => $row->expense_date->toDateString(),
            ]),
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'revenueCategories' => self::REVENUE_CATEGORIES,
            'expenseCategories' => self::EXPENSE_CATEGORIES,
        ]);
    }

    public function storeRevenue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'received_at' => ['required', 'date_format:Y-m-d'],
            'category' => ['required', Rule::in(array_keys(self::REVENUE_CATEGORIES))],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $revenue = SaasRevenue::create([...$data, 'currency' => 'KZT', 'created_by' => $request->user()->id]);
            $this->recordActivity($request, 'saas.revenue.created', $revenue, [
                'amount' => (int) $revenue->amount, 'currency' => $revenue->currency, 'category' => $revenue->category,
            ], $revenue->organization_id);
        });

        return back()->with('success', 'Доход GarageOS записан.');
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'expense_date' => ['required', 'date_format:Y-m-d'],
            'category' => ['required', Rule::in(array_keys(self::EXPENSE_CATEGORIES))],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $expense = SaasExpense::create([...$data, 'currency' => 'KZT', 'created_by' => $request->user()->id]);
            $this->recordActivity($request, 'saas.expense.created', $expense, [
                'amount' => (int) $expense->amount, 'currency' => $expense->currency, 'category' => $expense->category,
            ]);
        });

        return back()->with('success', 'Расход GarageOS записан.');
    }

    private function realizedRevenues(): Builder
    {
        return SaasRevenue::query()->where(fn (Builder $query) => $query
            ->whereNull('payment_id')
            ->orWhereHas('payment', fn (Builder $payment) => $payment->where('status', 'paid')));
    }

    private function period(Request $request): string
    {
        $period = (string) $request->query('period', '30d');
        return in_array($period, ['today', '7d', '30d', '90d', 'year', 'custom'], true) ? $period : '30d';
    }

    private function range(Request $request, string $period): array
    {
        $today = Carbon::now(config('app.timezone'))->startOfDay();
        if ($period === 'custom') {
            $data = $request->validate([
                'from' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'to' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:from'],
            ]);
            $start = Carbon::createFromFormat('Y-m-d', $data['from'], config('app.timezone'))->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $data['to'], config('app.timezone'))->endOfDay();
            if ($start->diffInDays($end) > 365) {
                throw ValidationException::withMessages(['to' => 'Период не может превышать 366 дней.']);
            }

            return [$start, $end];
        }

        $start = match ($period) {
            'today' => $today->copy()->startOfDay(),
            '7d' => $today->copy()->subDays(6)->startOfDay(),
            '90d' => $today->copy()->subDays(89)->startOfDay(),
            'year' => $today->copy()->startOfYear(),
            default => $today->copy()->subDays(29)->startOfDay(),
        };

        return [$start, Carbon::now(config('app.timezone'))];
    }

    private function periodOptions(): array
    {
        return [
            ['value' => 'today', 'label' => 'Сегодня'], ['value' => '7d', 'label' => '7 дней'],
            ['value' => '30d', 'label' => '30 дней'], ['value' => '90d', 'label' => '90 дней'],
            ['value' => 'year', 'label' => 'Этот год'], ['value' => 'custom', 'label' => 'Период'],
        ];
    }

    private function recordActivity(Request $request, string $action, object $subject, array $metadata, ?int $organizationId = null): void
    {
        ActivityLog::create([
            'organization_id' => $organizationId,
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}