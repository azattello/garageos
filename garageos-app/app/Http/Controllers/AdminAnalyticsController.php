<?php

namespace App\Http\Controllers;

use App\Services\Admin\SaasMetrics;
use App\Services\Billing\PlanCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAnalyticsController extends Controller
{
	public function index(Request $request, SaasMetrics $metrics, PlanCatalog $plans): Response
	{
		$filters = $request->validate([
			'period' => ['nullable', Rule::in(['today', '7d', '30d', '90d', 'year', 'custom'])],
			'from' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
			'to' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:from'],
		]);
		$period = $filters['period'] ?? '30d';
		$today = Carbon::now(config('app.timezone'))->startOfDay();
		if ($period === 'custom') {
			$start = Carbon::createFromFormat('Y-m-d', $filters['from'], config('app.timezone'))->startOfDay();
			$end = Carbon::createFromFormat('Y-m-d', $filters['to'], config('app.timezone'))->endOfDay();
			if ($start->diffInDays($end) > 365) {
				throw ValidationException::withMessages(['to' => 'Период не может превышать 366 дней.']);
			}
		} else {
			$start = match ($period) {
				'today' => $today->copy(),
				'7d' => $today->copy()->subDays(6),
				'90d' => $today->copy()->subDays(89),
				'year' => $today->copy()->startOfYear(),
				default => $today->copy()->subDays(29),
			};
			$end = Carbon::now(config('app.timezone'));
		}

		return Inertia::render('Admin/Analytics/Index', [
			'period' => $period,
			'from' => $start->toDateString(),
			'to' => $end->toDateString(),
			'periodOptions' => [
				['value' => 'today', 'label' => 'Сегодня'], ['value' => '7d', 'label' => '7 дней'],
				['value' => '30d', 'label' => '30 дней'], ['value' => '90d', 'label' => '90 дней'],
				['value' => 'year', 'label' => 'Этот год'], ['value' => 'custom', 'label' => 'Произвольный'],
			],
			...$metrics->analytics($period, $start, $end, $plans),
		]);
	}
}