<?php

namespace App\Http\Controllers;

use App\Models\AppointmentStatus;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use App\Support\Activity;
use Illuminate\Support\Facades\Gate;

class SettingsCatalogController extends Controller
{
    public function index(): Response
    {
        Gate::forUser(request()->user())->authorize('viewAny', ServiceCategory::class);
        Gate::forUser(request()->user())->authorize('viewAny', AppointmentStatus::class);
        $organization = request()->user()->organization;

        return Inertia::render('Settings/Catalog', [
            'categories' => $organization->serviceCategories()->orderBy('sort_order')->orderBy('name')->get(),
            'appointmentStatuses' => $organization->appointmentStatuses()->orderBy('sort_order')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', ServiceCategory::class);
        $organization = $request->user()->organization;
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $category = $organization->serviceCategories()->create(['name' => $data['name'], 'sort_order' => $organization->serviceCategories()->max('sort_order') + 1, 'active' => true]);
        Activity::record('service_category.created', $category, $request->user(), ['name' => $category->name]);
        return back()->with('success', 'Категория добавлена.');
    }

    public function archiveCategory(Request $request, ServiceCategory $category): RedirectResponse
    {
        abort_unless($category->organization_id === $request->user()->organization_id, 404);
        Gate::forUser($request->user())->authorize('delete', $category);
        $category->update(['active' => false]);
        Activity::record('service_category.archived', $category, $request->user(), ['name' => $category->name]);
        return back()->with('success', 'Категория архивирована.');
    }

    public function storeStatus(Request $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', AppointmentStatus::class);
        $organization = $request->user()->organization;
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $name = $data['name'];
        $status = $organization->appointmentStatuses()->create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)), 'sort_order' => $organization->appointmentStatuses()->max('sort_order') + 1, 'is_terminal' => false]);
        Activity::record('appointment_status.created', $status, $request->user(), ['name' => $status->name]);
        return back()->with('success', 'Статус добавлен.');
    }

    public function archiveStatus(Request $request, AppointmentStatus $status): RedirectResponse
    {
        abort_unless($status->organization_id === $request->user()->organization_id, 404);
        Gate::forUser($request->user())->authorize('delete', $status);
        abort_if($status->is_terminal, 422, 'Терминальный статус нельзя архивировать.');
        $status->delete();
        Activity::record('appointment_status.archived', $status, $request->user(), ['name' => $status->name]);
        return back()->with('success', 'Статус удалён.');
    }
}
