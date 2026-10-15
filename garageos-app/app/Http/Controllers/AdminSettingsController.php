<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SaasSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
	private const GENERAL_KEYS = ['support_email', 'support_phone', 'support_url'];

	public function index(): Response
	{
		$settings = SaasSetting::query()->whereIn('key', self::GENERAL_KEYS)->pluck('value', 'key');

		return Inertia::render('Admin/Settings/Index', [
			'settings' => [
				'support_email' => $settings->get('support_email', ''),
				'support_phone' => $settings->get('support_phone', ''),
				'support_url' => $settings->get('support_url', ''),
			],
			'system' => [
				'application' => 'GarageOS',
				'environment' => app()->environment(),
				'timezone' => config('app.timezone'),
				'planSource' => 'PlanCatalog (server-side)',
				'billing' => 'Manual bank transfer review',
				'automaticCharges' => false,
				'csrfProtection' => true,
				'secrets' => 'Не отображаются в панели',
			],
		]);
	}

	public function updateGeneral(Request $request): RedirectResponse
	{
		$data = $request->validate([
			'support_email' => ['nullable', 'email', 'max:255'],
			'support_phone' => ['nullable', 'string', 'max:50'],
			'support_url' => ['nullable', 'url', 'max:255'],
		]);

		DB::transaction(function () use ($request, $data): void {
			$before = SaasSetting::query()->whereIn('key', self::GENERAL_KEYS)->pluck('value', 'key')->all();
			foreach (self::GENERAL_KEYS as $key) {
				SaasSetting::updateOrCreate(['key' => $key], ['value' => $data[$key] ?? null, 'updated_by' => $request->user()->id]);
			}
			ActivityLog::create([
				'user_id' => $request->user()->id,
				'action' => 'platform.settings.updated',
				'subject_type' => SaasSetting::class,
				'metadata' => ['group' => 'support_contacts', 'before' => $before, 'after' => $data],
				'ip_address' => $request->ip(),
				'user_agent' => $request->userAgent(),
				'created_at' => now(),
			]);
		});

		return back()->with('success', 'Контакты поддержки GarageOS обновлены.');
	}
}