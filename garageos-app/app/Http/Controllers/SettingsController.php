<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use App\Support\Activity;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $organization = request()->user()->organization;
        Gate::forUser(request()->user())->authorize('update', $organization);

        return Inertia::render('Settings/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'phone' => $organization->phone,
                'email' => $organization->email,
                'timezone' => $organization->timezone,
                'currency' => $organization->currency,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $organization = $request->user()->organization;
        Gate::forUser($request->user())->authorize('update', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'string', 'size:3', 'uppercase'],
        ]);

        $changedFields = collect($validated)
            ->filter(fn ($value, string $key) => $organization->{$key} !== $value)
            ->keys()
            ->values()
            ->all();
        $organization->update($validated);
        if ($changedFields) {
            Activity::record('organization.settings_updated', $organization, $request->user(), ['changed_fields' => $changedFields]);
        }

        return redirect()->route('dashboard')->with('success', 'Настройки организации обновлены.');
    }
}
