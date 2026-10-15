<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Order;
use App\Models\SaasExpense;
use App\Models\SaasPayment;
use App\Models\SaasRevenue;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\PlanCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaasMetrics
{
	public function dashboard(PlanCatalog $plans): array
	{
		$now = Carbon::now(config('app.timezone'));
		$monthStart = $now->copy()->startOfMonth();
		$yearStart = $now->copy()->startOfYear();
		$activeSubscriptionsByPlan = $this->activeSubscriptionCounts($now);
		$payingOrganizations = (int) $activeSubscriptionsByPlan->sum();
		$mrr = (int) collect($plans->all())->sum(fn (array $plan) => $activeSubscriptionsByPlan->get($plan['key'], 0) * $plan['price']);
		$revenueMonth = $this->revenueTotal($monthStart, $now);
		$expensesMonth = (int) SaasExpense::query()->whereDate('expense_date', '>=', $monthStart->toDateString())->whereDate('expense_date', '<=', $now->toDateString())->sum('amount');
		$revenueYear = $this->revenueTotal($yearStart, $now);
		$expensesYear = (int) SaasExpense::query()->whereDate('expense_date', '>=', $yearStart->toDateString())->whereDate('expense_date', '<=', $now->toDateString())->sum('amount');
		$previousMonthStart = $monthStart->copy()->subMonth();
		$previousMonthEnd = $monthStart->copy()->subDay()->endOfDay();
		$organizationsThisMonth = Organization::query()->where('created_at', '>=', $monthStart)->count();
		$organizationsPreviousMonth = Organization::query()->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->count();
		$trendStart = $monthStart->copy()->subMonths(11)->startOfMonth();
		$trend = $this->series($trendStart, $now, 'month');

		return [
			'metrics' => [
				'organizations' => Organization::count(),
				'activeOrganizations' => Organization::where('status', 'active')->count(),
				'blockedOrganizations' => Organization::where('status', 'blocked')->count(),
				'archivedOrganizations' => Organization::where('status', 'archived')->count(),
				'activeSubscriptions' => $payingOrganizations,
				'trialingSubscriptions' => Subscription::where('status', 'trialing')->count(),
				'expiredSubscriptions' => Subscription::where('status', 'expired')->count(),
				'suspendedSubscriptions' => Subscription::where('status', 'suspended')->count(),
				'cancelledSubscriptions' => Subscription::where('status', 'cancelled')->count(),
				'users' => User::whereNotNull('organization_id')->whereNotIn('role', ['client', 'platform_admin'])->count(),
				'mrr' => $mrr,
				'arr' => $mrr * 12,
				'arpu' => $payingOrganizations > 0 ? (int) round($mrr / $payingOrganizations) : null,
				'payingOrganizations' => $payingOrganizations,
				'revenueMonth' => $revenueMonth,
				'expensesMonth' => $expensesMonth,
				'netMonth' => $revenueMonth - $expensesMonth,
				'revenueYear' => $revenueYear,
				'expensesYear' => $expensesYear,
				'netYear' => $revenueYear - $expensesYear,
				'newOrganizationsMonth' => $organizationsThisMonth,
				'newOrganizationsChange' => $this->percentageChange($organizationsThisMonth, $organizationsPreviousMonth),
				'newSubscriptionsMonth' => Subscription::whereBetween('created_at', [$monthStart, $now])->count(),
			],
			'planDistribution' => collect($plans->all())->map(fn (array $plan) => [
				'name' => $plan['name'],
				'count' => (int) $activeSubscriptionsByPlan->get($plan['key'], 0),
				'mrr' => (int) $activeSubscriptionsByPlan->get($plan['key'], 0) * $plan['price'],
			])->values(),
			'trend' => $trend,
			'recentOrganizations' => Organization::query()->with(['owner:id,organization_id,name,email', 'subscription:id,organization_id,plan,status'])
				->latest('created_at')->limit(8)->get()
				->map(fn (Organization $organization) => [
					'id' => $organization->id,
					'name' => $organization->name,
					'status' => $organization->status,
					'owner' => $organization->owner?->name,
					'plan' => $plans->get($organization->subscription?->plan ?? '')['name'] ?? $organization->subscription?->plan,
					'subscription_status' => $organization->subscription?->status,
					'created_at' => $organization->created_at?->toIso8601String(),
				])->values(),
			'recentActivity' => ActivityLog::query()->with('user:id,name')->latest('created_at')->limit(8)->get()
				->map(fn (ActivityLog $event) => ['id' => $event->id, 'action' => $event->action, 'actor' => $event->user?->name ?? 'Система', 'created_at' => $event->created_at?->toIso8601String()])->values(),
		];
	}

	public function analytics(string $period, Carbon $start, Carbon $end, PlanCatalog $plans): array
	{
		$mode = $period === 'year' || $start->diffInDays($end) > 90 ? 'month' : 'day';
		$activeSubscriptionsByPlan = $this->activeSubscriptionCounts(now());
		$payingOrganizations = (int) $activeSubscriptionsByPlan->sum();
		$mrr = (int) collect($plans->all())->sum(fn (array $plan) => $activeSubscriptionsByPlan->get($plan['key'], 0) * $plan['price']);
		$series = $this->series($start, $end, $mode);
		$revenue = $this->revenueTotal($start, $end);
		$expenses = (int) SaasExpense::query()->whereDate('expense_date', '>=', $start->toDateString())->whereDate('expense_date', '<=', $end->toDateString())->sum('amount');

		$revenueByPlan = DB::table('saas_revenues')
			->join('saas_payments', 'saas_payments.id', '=', 'saas_revenues.payment_id')
			->where('saas_payments.status', 'paid')
			->whereDate('saas_revenues.received_at', '>=', $start->toDateString())
			->whereDate('saas_revenues.received_at', '<=', $end->toDateString())
			->select('saas_payments.plan')->selectRaw('SUM(saas_revenues.amount) as amount')
			->groupBy('saas_payments.plan')->get()
			->map(fn ($row) => ['name' => $plans->get((string) $row->plan)['name'] ?? $row->plan ?? 'Без тарифа', 'amount' => (int) $row->amount])->values();

		return [
			'metrics' => [
				'organizations' => Organization::count(),
				'newOrganizations' => Organization::whereBetween('created_at', [$start, $end])->count(),
				'subscriptions' => $payingOrganizations,
				'newSubscriptions' => Subscription::whereBetween('created_at', [$start, $end])->count(),
				'users' => User::whereNotNull('organization_id')->whereNotIn('role', ['client', 'platform_admin'])->count(),
				'newUsers' => User::whereNotNull('organization_id')->whereNotIn('role', ['client', 'platform_admin'])->whereBetween('created_at', [$start, $end])->count(),
				'mrr' => $mrr,
				'arr' => $mrr * 12,
				'arpu' => $payingOrganizations > 0 ? (int) round($mrr / $payingOrganizations) : null,
				'payingOrganizations' => $payingOrganizations,
				'revenue' => $revenue,
				'expenses' => $expenses,
				'net' => $revenue - $expenses,
				'expiredSubscriptions' => Subscription::where('status', 'expired')->count(),
				'cancelledSubscriptions' => Subscription::where('status', 'cancelled')->count(),
			],
			'trend' => $series,
			'planDistribution' => collect($plans->all())->map(fn (array $plan) => [
				'name' => $plan['name'], 'value' => (int) $activeSubscriptionsByPlan->get($plan['key'], 0),
			])->values(),
			'revenueByPlan' => $revenueByPlan,
			'usage' => [
				['name' => 'Сотрудники', 'value' => User::whereNotNull('organization_id')->whereNotIn('role', ['client', 'platform_admin'])->count()],
				['name' => 'Клиенты', 'value' => Client::count()],
				['name' => 'Автомобили', 'value' => Car::count()],
				['name' => 'Записи', 'value' => Appointment::count()],
				['name' => 'Заказ-наряды', 'value' => Order::count()],
			],
		];
	}

	public function revenueTotal(Carbon $start, Carbon $end): int
	{
		return (int) $this->realizedRevenues()
			->whereDate('received_at', '>=', $start->toDateString())
			->whereDate('received_at', '<=', $end->toDateString())
			->sum('amount');
	}

	private function realizedRevenues(): Builder
	{
		return SaasRevenue::query()->where(fn (Builder $query) => $query
			->whereNull('payment_id')
			->orWhereHas('payment', fn (Builder $payment) => $payment->where('status', 'paid')));
	}

	private function activeSubscriptionCounts(Carbon $now): Collection
	{
		return Subscription::query()
			->where('status', 'active')
			->where('ends_at', '>', $now)
			->whereHas('saasPayments', fn (Builder $payment) => $payment->where('status', 'paid'))
			->select('plan')
			->selectRaw('COUNT(DISTINCT organization_id) as count')
			->groupBy('plan')
			->pluck('count', 'plan')
			->map(fn ($count) => (int) $count);
	}

	private function series(Carbon $start, Carbon $end, string $mode): Collection
	{
		$revenueDate = $this->dateExpression($mode, 'received_at');
		$revenue = $this->realizedRevenues()
			->whereDate('received_at', '>=', $start->toDateString())->whereDate('received_at', '<=', $end->toDateString())
			->selectRaw("{$revenueDate} as period, SUM(amount) as total")->groupByRaw($revenueDate)->pluck('total', 'period');
		$expenseDate = $this->dateExpression($mode, 'expense_date');
		$expenses = SaasExpense::query()
			->whereDate('expense_date', '>=', $start->toDateString())->whereDate('expense_date', '<=', $end->toDateString())
			->selectRaw("{$expenseDate} as period, SUM(amount) as total")->groupByRaw($expenseDate)->pluck('total', 'period');
		$createdDate = $this->dateExpression($mode, 'created_at');
		$organizations = Organization::query()
			->whereDate('created_at', '>=', $start->toDateString())->whereDate('created_at', '<=', $end->toDateString())
			->selectRaw("{$createdDate} as period, COUNT(*) as total")->groupByRaw($createdDate)->pluck('total', 'period');
		$subscriptions = Subscription::query()
			->whereDate('created_at', '>=', $start->toDateString())->whereDate('created_at', '<=', $end->toDateString())
			->selectRaw("{$createdDate} as period, COUNT(*) as total")->groupByRaw($createdDate)->pluck('total', 'period');
		$users = User::query()->whereNotNull('organization_id')->whereNotIn('role', ['client', 'platform_admin'])
			->whereDate('created_at', '>=', $start->toDateString())->whereDate('created_at', '<=', $end->toDateString())
			->selectRaw("{$createdDate} as period, COUNT(*) as total")->groupByRaw($createdDate)->pluck('total', 'period');
		$points = collect();
		if ($mode === 'month') {
			$cursor = $start->copy()->startOfMonth();
			$last = $end->copy()->startOfMonth();
			while ($cursor <= $last) {
				$key = $cursor->format('Y-m');
				$points->push($this->point($cursor, $key, $revenue, $expenses, $organizations, $subscriptions, $users, 'month'));
				$cursor->addMonth();
			}
		} else {
			$days = min(366, max(1, (int) $start->diffInDays($end) + 1));
			foreach (range(0, $days - 1) as $offset) {
				$day = $start->copy()->addDays($offset);
				$key = $day->toDateString();
				$points->push($this->point($day, $key, $revenue, $expenses, $organizations, $subscriptions, $users, 'day'));
			}
		}

		return $points;
	}

	private function point(Carbon $date, string $key, Collection $revenue, Collection $expenses, Collection $organizations, Collection $subscriptions, Collection $users, string $mode): array
	{
		$income = (int) $revenue->get($key, 0);
		$expense = (int) $expenses->get($key, 0);

		return [
			'date' => $date->toDateString(),
			'label' => $mode === 'month' ? $date->locale('ru')->translatedFormat('M y') : $date->locale('ru')->translatedFormat('d M'),
			'revenue' => $income,
			'expenses' => $expense,
			'net' => $income - $expense,
			'organizations' => (int) $organizations->get($key, 0),
			'subscriptions' => (int) $subscriptions->get($key, 0),
			'users' => (int) $users->get($key, 0),
		];
	}

	private function dateExpression(string $mode, string $column): string
	{
		if (! in_array($column, ['received_at', 'expense_date', 'created_at'], true)) {
			throw new \InvalidArgumentException('Unsupported SaaS metrics date column.');
		}
		$driver = DB::connection()->getDriverName();
		if ($driver === 'sqlite') {
			return $mode === 'month' ? "strftime('%Y-%m', {$column})" : "DATE({$column})";
		}

		return $mode === 'month' ? "DATE_FORMAT({$column}, '%Y-%m')" : "DATE({$column})";
	}

	private function percentageChange(int $current, int $previous): ?int
	{
		if ($previous === 0) {
			return $current === 0 ? 0 : null;
		}

		return (int) round((($current - $previous) / abs($previous)) * 100);
	}
}