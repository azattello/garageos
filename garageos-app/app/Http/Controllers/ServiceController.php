<?php

namespace App\Http\Controllers;

use App\Actions\Services\CreateService;
use App\Actions\Services\SetServiceActive;
use App\Actions\Services\UpdateService;
use App\Http\Requests\StoreServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->organization_id, 403);
        Gate::forUser($user)->authorize('viewAny', Service::class);
        $archived = $request->boolean('archived');

        return Inertia::render('Services/Index', [
            'archived' => $archived,
            'services' => Service::where('organization_id', $user->organization_id)
                ->where('active', ! $archived)
                ->with('category:id,name')
                ->latest()
                ->get(),
            'categories' => ServiceCategory::where('organization_id', $user->organization_id)
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreServiceRequest $request, CreateService $createService): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', Service::class);
        $createService->handle($request->user(), $request->validated());
        return to_route('services.index')->with('success', 'Услуга создана.');
    }

    public function update(StoreServiceRequest $request, Service $service, UpdateService $updateService): RedirectResponse
    {
        abort_unless($service->organization_id === $request->user()->organization_id, 404);
        Gate::forUser($request->user())->authorize('update', $service);
        $updateService->handle($request->user(), $service, $request->validated());

        return to_route('services.index')->with('success', 'Услуга обновлена.');
    }

    public function archive(Request $request, Service $service, SetServiceActive $setServiceActive): RedirectResponse
    {
        return $this->setActive($request, $service, $setServiceActive, false);
    }

    public function restore(Request $request, Service $service, SetServiceActive $setServiceActive): RedirectResponse
    {
        return $this->setActive($request, $service, $setServiceActive, true);
    }

    private function setActive(Request $request, Service $service, SetServiceActive $setServiceActive, bool $active): RedirectResponse
    {
        abort_unless($service->organization_id === $request->user()->organization_id, 404);
        Gate::forUser($request->user())->authorize($active ? 'update' : 'delete', $service);
        $setServiceActive->handle($request->user(), $service, $active);

        return to_route('services.index', $active ? [] : ['archived' => 1])
            ->with('success', $active ? 'Услуга восстановлена.' : 'Услуга архивирована.');
    }
}
