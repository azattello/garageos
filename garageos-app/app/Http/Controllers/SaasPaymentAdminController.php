<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\SaasPayment;
use App\Models\SaasRevenue;
use App\Models\Subscription;
use App\Services\Billing\PlanCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SaasPaymentAdminController extends Controller
{
	private const METHODS = ['bank_transfer', 'cash', 'kaspi', 'halyk', 'other'];

	public function index(Request $request, PlanCatalog $plans): Response
	{
		$filters = $request->validate([
			'search' => ['nullable', 'string', 'max:120'],
			'status' => ['nullable', Rule::in(['paid', 'pending', 'failed', 'refunded'])],
			'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
			'from' => ['nullable', 'date_format:Y-m-d'],
			'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
		]);
		$query = SaasPayment::query()->with(['organization:id,name', 'subscription:id,organization_id,plan']);
		$query->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status));
		$query->when($filters['organization_id'] ?? null, fn (Builder $builder, int $organizationId) => $builder->where('organization_id', $organizationId));
		$query->when($filters['from'] ?? null, fn (Builder $builder, string $from) => $builder->whereDate('paid_at', '>=', $from));
		$query->when($filters['to'] ?? null, fn (Builder $builder, string $to) => $builder->whereDate('paid_at', '<=', $to));
		$query->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
			$builder->where(fn (Builder $nested) => $nested
				->where('reference', 'like', '%'.$search.'%')
				->orWhere('description', 'like', '%'.$search.'%')
				->orWhereHas('organization', fn (Builder $organization) => $organization->where('name', 'like', '%'.$search.'%')));
		});

		return Inertia::render('Admin/Payments/Index', [
			'payments' => $query->latest('created_at')->paginate(25)->withQueryString()->through(fn (SaasPayment $payment) => [
				'id' => $payment->id,
				'organization' => $payment->organization?->name ?? 'Без организации',
				'organization_id' => $payment->organization_id,
				'plan' => $plans->get($payment->plan ?? $payment->subscription?->plan ?? '')['name'] ?? $payment->plan,
				'amount' => (int) $payment->amount,
				'currency' => $payment->currency,
				'method' => $payment->method,
				'status' => $payment->status,
				'reference' => $payment->reference,
				'description' => $payment->description,
				'paid_at' => $payment->paid_at?->toIso8601String(),
			]),
			'filters' => $filters,
			'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
			'subscriptions' => Subscription::query()->with('organization:id,name')->orderByDesc('created_at')->get(['id', 'organization_id', 'plan'])->map(fn (Subscription $subscription) => [
				'id' => $subscription->id,
				'organization_id' => $subscription->organization_id,
				'label' => ($subscription->organization?->name ?? 'Организация').' · '.($plans->get($subscription->plan)['name'] ?? $subscription->plan),
			]),
			'methods' => ['bank_transfer' => 'Банковский перевод', 'cash' => 'Наличные', 'kaspi' => 'Kaspi', 'halyk' => 'Halyk', 'other' => 'Другое'],
		]);
	}

	public function store(Request $request, PlanCatalog $plans): RedirectResponse
	{
		$data = $request->validate([
			'organization_id' => ['required', 'integer', 'exists:organizations,id'],
			'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
			'plan' => ['nullable', 'string', Rule::in(array_keys($plans->all()))],
			'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
			'method' => ['required', Rule::in(self::METHODS)],
			'status' => ['required', Rule::in(['paid', 'pending', 'failed'])],
			'reference' => ['nullable', 'string', 'max:255', 'unique:saas_payments,reference'],
			'paid_at' => ['nullable', 'date_format:Y-m-d'],
			'description' => ['nullable', 'string', 'max:2000'],
		]);

		DB::transaction(function () use ($request, $data): void {
			$subscription = null;
			if (! empty($data['subscription_id'])) {
				$subscription = Subscription::query()
					->where('organization_id', $data['organization_id'])
					->findOrFail($data['subscription_id']);
			}
			$payment = SaasPayment::create([
				...$data,
				'subscription_id' => $subscription?->id,
				'plan' => $data['plan'] ?? $subscription?->plan,
				'currency' => 'KZT',
				'paid_at' => $data['status'] === 'paid' ? ($data['paid_at'] ?? now()) : null,
				'created_by' => $request->user()->id,
			]);
			if ($payment->status === 'paid') {
				$this->recordRevenue($payment, $request->user()->id);
			}
			$this->recordActivity($request, 'saas.payment.created', $payment, [
				'amount' => (int) $payment->amount, 'currency' => $payment->currency, 'status' => $payment->status,
			]);
		});

		return back()->with('success', 'Платёж GarageOS записан.');
	}

	public function updateStatus(Request $request, SaasPayment $payment): RedirectResponse
	{
		$data = $request->validate([
			'status' => ['required', Rule::in(['paid', 'failed', 'refunded'])],
			'reference' => ['nullable', 'string', 'max:255', Rule::unique('saas_payments', 'reference')->ignore($payment->id)],
		]);

		DB::transaction(function () use ($request, $payment, $data): void {
			$lockedPayment = SaasPayment::query()->lockForUpdate()->findOrFail($payment->id);
			if ($lockedPayment->status === 'refunded') {
				throw ValidationException::withMessages(['status' => 'Возвращённый платёж нельзя изменить.']);
			}
			if ($data['status'] === 'refunded' && $lockedPayment->status !== 'paid') {
				throw ValidationException::withMessages(['status' => 'Можно вернуть только проведённый платёж.']);
			}
			if ($data['status'] === 'failed' && $lockedPayment->status === 'paid') {
				throw ValidationException::withMessages(['status' => 'Проведённый платёж можно только вернуть, не пометить как failed.']);
			}
			$from = $lockedPayment->status;
			$lockedPayment->update([
				'status' => $data['status'],
				'reference' => $data['reference'] ?? $lockedPayment->reference,
				'paid_at' => $data['status'] === 'paid' ? ($lockedPayment->paid_at ?? now()) : $lockedPayment->paid_at,
			]);
			if ($data['status'] === 'paid') {
				$this->recordRevenue($lockedPayment, $request->user()->id);
			}
			$this->recordActivity($request, $data['status'] === 'refunded' ? 'saas.payment.refunded' : 'saas.payment.status_changed', $lockedPayment, [
				'from' => $from, 'to' => $data['status'], 'amount' => (int) $lockedPayment->amount,
			]);
		});

		return back()->with('success', $data['status'] === 'refunded' ? 'Платёж помечен как возвращённый.' : 'Статус платежа обновлён.');
	}

	private function recordRevenue(SaasPayment $payment, int $adminId): void
	{
		if ($payment->revenue()->exists()) {
			return;
		}
		SaasRevenue::create([
			'organization_id' => $payment->organization_id,
			'payment_id' => $payment->id,
			'category' => 'subscription',
			'amount' => $payment->amount,
			'currency' => $payment->currency,
			'received_at' => $payment->paid_at?->toDateString() ?? now()->toDateString(),
			'description' => $payment->description ?? 'Оплата подписки',
			'created_by' => $adminId,
		]);
	}

	private function recordActivity(Request $request, string $action, SaasPayment $payment, array $metadata): void
	{
		ActivityLog::create([
			'organization_id' => $payment->organization_id,
			'user_id' => $request->user()->id,
			'action' => $action,
			'subject_type' => $payment->getMorphClass(),
			'subject_id' => $payment->id,
			'metadata' => $metadata,
			'ip_address' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'created_at' => now(),
		]);
	}
}