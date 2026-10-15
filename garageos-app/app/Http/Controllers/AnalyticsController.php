<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    private const ACTIVE_ORDER_STATUSES = ['accepted', 'diagnostics', 'in_progress', 'waiting_parts'];

    public function __invoke(): Response
    {
        $user = request()->user();
        abort_unless($user?->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);

        $periodDays = ['7d' => 7, '30d' => 30, '90d' => 90];
        $period = (string) request()->query('period', '30d');
        if (! array_key_exists($period, $periodDays)) {
            $period = '30d';
        }

        $days = $periodDays[$period];
        $organizationId = $user->organization_id;
        $periodStart = Carbon::today()->subDays($days - 1)->startOfDay();
        $periodEnd = now();
        $previousStart = $periodStart->copy()->subDays($days);
        $previousEnd = $periodStart->copy()->subSecond();

        $orders = Order::query()->where('organization_id', $organizationId);
        $periodOrders = (clone $orders)->whereBetween('created_at', [$periodStart, $periodEnd]);
        $successfulPayments = Payment::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'successful')
            ->whereBetween('paid_at', [$periodStart, $periodEnd]);

        $revenue = (int) (clone $successfulPayments)->sum('amount');
        $previousRevenue = (int) Payment::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'successful')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->sum('amount');
        $periodExpenses = Expense::query()
            ->where('organization_id', $organizationId)
            ->whereDate('expense_date', '>=', $periodStart->toDateString())
            ->whereDate('expense_date', '<=', $periodEnd->toDateString());
        $expenses = (int) (clone $periodExpenses)->sum('amount');
        $previousExpenses = (int) Expense::query()
            ->where('organization_id', $organizationId)
            ->whereDate('expense_date', '>=', $previousStart->toDateString())
            ->whereDate('expense_date', '<=', $previousEnd->toDateString())
            ->sum('amount');
        $orderCount = (clone $periodOrders)->count();
        $previousOrderCount = (clone $orders)->whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $orderValue = (int) (clone $periodOrders)->sum('total');

        $trend = $this->dailyTrend($organizationId, $periodStart, $days);

        $statusCounts = (clone $periodOrders)
            ->selectRaw('status_id, COUNT(*) as count')
            ->groupBy('status_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status_id => (int) $row->count]);
        $statuses = OrderStatus::query()
            ->where('organization_id', $organizationId)
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name'])
            ->map(fn (OrderStatus $status) => [
                'slug' => $status->slug,
                'name' => $status->name,
                'count' => $statusCounts->get($status->id, 0),
            ])
            ->values();

        $topServices = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.organization_id', $organizationId)
            ->where('order_items.type', 'service')
            ->whereBetween('orders.created_at', [$periodStart, $periodEnd])
            ->select('order_items.name_snapshot as name')
            ->selectRaw('SUM(order_items.total) as revenue, SUM(order_items.quantity) as quantity')
            ->groupBy('order_items.name_snapshot')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($service) => [
                'name' => $service->name,
                'revenue' => (int) $service->revenue,
                'quantity' => (float) $service->quantity,
            ])
            ->values();

        $mechanics = User::query()
            ->where('organization_id', $organizationId)
            ->where('role', 'mechanic')
            ->where('status', 'active')
            ->withCount([
                'assignedOrders as active_orders_count' => fn (Builder $query) => $query->whereHas('status', fn (Builder $status) => $status->whereIn('slug', self::ACTIVE_ORDER_STATUSES)),
                'assignedOrders as completed_orders_count' => fn (Builder $query) => $query->whereHas('status', fn (Builder $status) => $status->whereIn('slug', ['ready', 'delivered', 'closed']))->where('completed_at', '>=', $periodStart),
            ])
            ->orderByDesc('active_orders_count')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn (User $mechanic) => [
                'id' => $mechanic->id,
                'name' => $mechanic->name,
                'active_orders' => (int) $mechanic->active_orders_count,
                'completed_orders' => (int) $mechanic->completed_orders_count,
            ])
            ->values();

        return Inertia::render('Analytics/Index', [
            'period' => $period,
            'periodOptions' => [
                ['value' => '7d', 'label' => '7 дней'],
                ['value' => '30d', 'label' => '30 дней'],
                ['value' => '90d', 'label' => '90 дней'],
            ],
            'metrics' => [
                'revenue' => $revenue,
                'revenueChange' => $this->percentageChange($revenue, $previousRevenue),
                'expenses' => $expenses,
                'expensesChange' => $this->percentageChange($expenses, $previousExpenses),
                'operatingResult' => $revenue - $expenses,
                'operatingResultChange' => $this->percentageChange($revenue - $expenses, $previousRevenue - $previousExpenses),
                'orders' => $orderCount,
                'ordersChange' => $this->percentageChange($orderCount, $previousOrderCount),
                'averageCheck' => $orderCount > 0 ? (int) round($orderValue / $orderCount) : 0,
                'debt' => (int) (clone $orders)->sum('balance'),
                'activeOrders' => (clone $orders)->whereHas('status', fn (Builder $status) => $status->whereIn('slug', self::ACTIVE_ORDER_STATUSES))->count(),
                'overdueOrders' => (clone $orders)->whereNotNull('planned_completion_at')->where('planned_completion_at', '<', now())->whereHas('status', fn (Builder $status) => $status->whereNotIn('slug', ['delivered', 'closed', 'cancelled']))->count(),
            ],
            'trend' => $trend,
            'statuses' => $statuses,
            'topServices' => $topServices,
            'mechanics' => $mechanics,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user?->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);
        $period = (string) $request->query('period', '30d');
        $days = ['7d' => 7, '30d' => 30, '90d' => 90][$period] ?? 30;
        $start = Carbon::today()->subDays($days - 1)->startOfDay();
        $trend = $this->dailyTrend($user->organization_id, $start, $days);
        $filename = 'GarageOS-analytics-'.$period.'-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($trend): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Дата', 'Получено платежей, ₸', 'Расходы, ₸', 'Заказов создано', 'Средний чек заказов, ₸'], ';');
            foreach ($trend as $point) {
                fputcsv($output, [$point['date'], $point['revenue'], $point['expenses'], $point['orders'], $point['average_check']], ';');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function dailyTrend(int $organizationId, Carbon $periodStart, int $days): \Illuminate\Support\Collection
    {
        $periodEnd = $periodStart->copy()->addDays($days)->subSecond();
        $dailyRevenue = Payment::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'successful')
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as amount')
            ->groupByRaw('DATE(paid_at)')
            ->pluck('amount', 'day');
        $dailyExpenses = Expense::query()
            ->where('organization_id', $organizationId)
            ->whereDate('expense_date', '>=', $periodStart->toDateString())
            ->whereDate('expense_date', '<=', $periodEnd->toDateString())
            ->selectRaw('DATE(expense_date) as day, SUM(amount) as amount')
            ->groupByRaw('DATE(expense_date)')
            ->pluck('amount', 'day');
        $dailyOrders = Order::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count, SUM(total) as order_value')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');

        return collect(range(0, $days - 1))->map(function (int $offset) use ($periodStart, $dailyRevenue, $dailyExpenses, $dailyOrders) {
            $day = $periodStart->copy()->addDays($offset);
            $key = $day->toDateString();
            $ordersForDay = $dailyOrders->get($key);
            $count = (int) ($ordersForDay->count ?? 0);

            return [
                'date' => $key,
                'label' => $day->locale('ru')->translatedFormat('d M'),
                'revenue' => (int) $dailyRevenue->get($key, 0),
                'expenses' => (int) $dailyExpenses->get($key, 0),
                'orders' => $count,
                'average_check' => $count > 0 ? (int) round(((int) $ordersForDay->order_value) / $count) : 0,
            ];
        })->values();
    }

    private function percentageChange(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return $current === 0 ? 0 : null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}
