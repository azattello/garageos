<?php

namespace App\Http\Controllers;

use App\Actions\Clients\CreateCar;
use App\Actions\Clients\SetCarActive;
use App\Actions\Clients\UpdateCar;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Car;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class CarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->organization_id, 403);
        Gate::forUser($user)->authorize('viewAny', Car::class);

        $search = trim((string) $request->query('q', ''));
        $archived = $request->boolean('archived');
        $completedVisit = fn (Builder $orders) => $orders->whereHas('status', fn (Builder $status) => $status->whereIn('slug', ['delivered', 'closed']));
        $activeCars = Car::query()->where('organization_id', $user->organization_id)->where('active', true);

        return Inertia::render('Cars/Index', [
            'filters' => ['q' => $search],
            'archived' => $archived,
            'summary' => [
                'cars' => (clone $activeCars)->count(),
                'serviced90Days' => (clone $activeCars)->whereHas('orders', fn (Builder $orders) => $orders->where('opened_at', '>=', now()->subDays(90))->whereHas('status', fn (Builder $status) => $status->whereIn('slug', ['delivered', 'closed'])))->count(),
                'withoutHistory' => (clone $activeCars)->whereDoesntHave('orders', $completedVisit)->count(),
            ],
            'cars' => Car::query()
                ->where('organization_id', $user->organization_id)
                ->where('active', ! $archived)
                ->with('client:id,name,phone')
                ->withCount(['orders as completed_visits_count' => $completedVisit])
                ->withMax(['orders as last_service_at' => $completedVisit], 'opened_at')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('make', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('plate_number', 'like', "%{$search}%")
                            ->orWhere('vin', 'like', "%{$search}%")
                            ->orWhereHas('client', fn ($clientQuery) => $clientQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%"));
                    });
                })
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Car $car) => [
                    'id' => $car->id,
                    'make' => $car->make,
                    'model' => $car->model,
                    'year' => $car->year,
                    'plate_number' => $car->plate_number,
                    'vin' => $car->vin,
                    'active' => $car->active,
                    'completed_visits_count' => (int) $car->completed_visits_count,
                    'last_service_at' => $car->last_service_at,
                    'client' => $car->client ? [
                        'id' => $car->client->id,
                        'name' => $car->client->name,
                        'phone' => $car->client->phone,
                    ] : null,
                ]),
        ]);
    }

    public function store(StoreCarRequest $request, CreateCar $createCar): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', Car::class);
        $car = $createCar->handle($request->user(), $request->validated());

        if ($request->input('return_to') === 'appointments') {
            return to_route('appointments.index', ['client_id' => $car->client_id, 'car_id' => $car->id])
                ->with('success', 'Автомобиль добавлен и выбран.');
        }

        if ($request->input('return_to') === 'orders') {
            return to_route('orders.index', ['client_id' => $car->client_id, 'car_id' => $car->id])
                ->with('success', 'Автомобиль добавлен и выбран.');
        }

        return to_route('clients.show', $car->client_id)->with('success', 'Автомобиль добавлен.');
    }

    public function update(UpdateCarRequest $request, Car $car, UpdateCar $updateCar): RedirectResponse
    {
        abort_unless($car->organization_id === $request->user()->organization_id, 404);
        Gate::forUser($request->user())->authorize('update', $car);
        $updateCar->handle($request->user(), $car, $request->validated());

        return to_route('cars.history', $car)->with('success', 'Данные автомобиля обновлены.');
    }

    public function archive(Request $request, Car $car, SetCarActive $setCarActive): RedirectResponse
    {
        return $this->setActive($request, $car, $setCarActive, false);
    }

    public function restore(Request $request, Car $car, SetCarActive $setCarActive): RedirectResponse
    {
        return $this->setActive($request, $car, $setCarActive, true);
    }

    private function setActive(Request $request, Car $car, SetCarActive $setCarActive, bool $active): RedirectResponse
    {
        abort_unless($car->organization_id === $request->user()->organization_id, 404);
        Gate::forUser($request->user())->authorize($active ? 'update' : 'delete', $car);

        if (! $active && (
            $car->appointments()->whereNotIn('status', ['cancelled', 'completed'])->exists()
            || $car->orders()->whereHas('status', fn ($query) => $query->where('is_terminal', false))->exists()
        )) {
            throw ValidationException::withMessages(['car' => 'Нельзя архивировать автомобиль с активной заявкой или заказом.']);
        }

        $setCarActive->handle($request->user(), $car, $active);

        return to_route('cars.index', $active ? [] : ['archived' => 1])
            ->with('success', $active ? 'Автомобиль восстановлен.' : 'Автомобиль архивирован; история заказов сохранена.');
    }
}