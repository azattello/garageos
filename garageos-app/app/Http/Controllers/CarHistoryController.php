<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

class CarHistoryController extends Controller
{
    public function show(Car $car): Response
    {
        $user = request()->user();
        abort_unless($car->organization_id === $user->organization_id, 404);
        Gate::forUser($user)->authorize('view', $car);
        $car->load(['client', 'orders.status', 'orders.items', 'orders.payments', 'orders.photos']);

        return Inertia::render('Cars/Show', [
            'car' => [
                'id' => $car->id,
                'make' => $car->make,
                'model' => $car->model,
                'year' => $car->year,
                'plate_number' => $car->plate_number,
                'vin' => $car->vin,
                'color' => $car->color,
                'mileage' => $car->mileage,
                'fuel_type' => $car->fuel_type,
                'comment' => $car->comment,
                'active' => $car->active,
                'client' => $car->client,
                'orders' => $car->orders,
                'photos' => $car->orders->flatMap->photos->values(),
                'summary' => [
                    'orders' => $car->orders->count(),
                    'total' => (int) $car->orders->sum('total'),
                    'paid' => (int) $car->orders->sum('paid_total'),
                    'balance' => (int) $car->orders->sum('balance'),
                    'active_order' => $car->orders->first(fn ($order) => ! $order->status?->is_terminal),
                ],
            ],
        ]);
    }
}
