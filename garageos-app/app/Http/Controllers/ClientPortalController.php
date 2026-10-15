<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Car;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClientPortalController extends Controller
{
    public function index(): Response
    {
        $user = $this->clientUser();
        $clients = $this->accessibleClients($user);
        $clientIds = $clients->modelKeys();
        $cars = Car::query()
            ->where('organization_id', $user->organization_id)
            ->whereIn('client_id', $clientIds)
            ->where('active', true)
            ->with('client:id,name')
            ->withMax(['orders' => fn ($query) => $query->where('organization_id', $user->organization_id)], 'opened_at')
            ->orderBy('make')
            ->orderBy('model')
            ->get();
        foreach ($cars as $car) Gate::forUser($user)->authorize('viewPortal', $car);
        $orders = $this->accessibleOrders($user, $clientIds, activeOnly: true);
        $statusTimes = $this->statusChangeTimes($user, $orders);

        return Inertia::render('ClientPortal/Index', [
            'clientName' => $user->name,
            'organizationName' => $user->organization->name,
            'phone' => $user->phone,
            'cars' => $cars->map(fn (Car $car) => [
                'id' => $car->id,
                'make' => $car->make,
                'model' => $car->model,
                'year' => $car->year,
                'plate' => $car->plate_number,
                'mileage' => $car->mileage,
                'last_visit_at' => $car->orders_max_opened_at,
            ])->values(),
            'orders' => $orders->map(fn (Order $order) => $this->orderSummary($order, $statusTimes->get($order->id)))->values(),
        ]);
    }

    public function carsShow(Car $car): Response
    {
        $user = $this->clientUser();
        abort_unless($car->organization_id === $user->organization_id, 404);
        Gate::forUser($user)->authorize('viewPortal', $car);
        $clientIds = $this->accessibleClients($user)->modelKeys();
        $orders = $this->accessibleOrders($user, $clientIds, carId: $car->id);
        $statusTimes = $this->statusChangeTimes($user, $orders);
        $activeOrder = $orders->first(fn (Order $order) => ! $order->status?->is_terminal);

        return Inertia::render('ClientPortal/Cars/Show', [
            'clientName' => $user->name,
            'organizationName' => $user->organization->name,
            'car' => [
                'id' => $car->id,
                'make' => $car->make,
                'model' => $car->model,
                'year' => $car->year,
                'plate' => $car->plate_number,
                'mileage' => $car->mileage,
                'active' => $car->active,
            ],
            'activeOrder' => $activeOrder ? $this->orderSummary($activeOrder, $statusTimes->get($activeOrder->id)) : null,
            'orders' => $orders->map(fn (Order $order) => $this->orderDetails($order, $statusTimes->get($order->id)))->values(),
        ]);
    }

    public function orders(Request $request): Response
    {
        $user = $this->clientUser();
        $clientIds = $this->accessibleClients($user)->modelKeys();
        $view = $request->query('view', 'active') === 'all' ? 'all' : 'active';
        $orders = $this->accessibleOrders($user, $clientIds, activeOnly: $view === 'active');
        $statusTimes = $this->statusChangeTimes($user, $orders);

        return Inertia::render('ClientPortal/Orders/Index', [
            'clientName' => $user->name,
            'organizationName' => $user->organization->name,
            'view' => $view,
            'orders' => $orders->map(fn (Order $order) => $this->orderSummary($order, $statusTimes->get($order->id)))->values(),
        ]);
    }

    public function order(Order $order): Response
    {
        $user = $this->clientUser();
        abort_unless($order->organization_id === $user->organization_id, 404);
        Gate::forUser($user)->authorize('viewPortal', $order);
        $order = $this->loadSafeOrder($user, $order);
        $statusTime = $this->statusChangeTimes($user, collect([$order]))->get($order->id);

        return Inertia::render('ClientPortal/Orders/Show', [
            'clientName' => $user->name,
            'organizationName' => $user->organization->name,
            'order' => $this->orderDetails($order, $statusTime),
        ]);
    }

    public function history(Request $request): Response
    {
        $user = $this->clientUser();
        $clientIds = $this->accessibleClients($user)->modelKeys();
        $carId = $request->integer('car_id') ?: null;
        if ($carId) {
            Car::where('organization_id', $user->organization_id)->whereIn('client_id', $clientIds)->whereKey($carId)->firstOrFail();
        }
        $orders = $this->accessibleOrders($user, $clientIds, terminalOnly: true, carId: $carId);
        $statusTimes = $this->statusChangeTimes($user, $orders);

        return Inertia::render('ClientPortal/History', [
            'clientName' => $user->name,
            'organizationName' => $user->organization->name,
            'carId' => $carId,
            'cars' => Car::where('organization_id', $user->organization_id)->whereIn('client_id', $clientIds)->orderBy('make')->get(['id', 'make', 'model', 'plate_number']),
            'orders' => $orders->map(fn (Order $order) => $this->orderSummary($order, $statusTimes->get($order->id)))->values(),
        ]);
    }

    public function account(): Response
    {
        $user = $this->clientUser();

        return Inertia::render('ClientPortal/Account', [
            'clientName' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'organizationName' => $user->organization->name,
        ]);
    }

    private function clientUser(): User
    {
        $user = request()->user();
        abort_unless($user?->role === 'client' && $user->organization_id, 403);
        return $user;
    }

    private function accessibleClients(User $user): Collection
    {
        $clients = $user->clients()
            ->where('clients.organization_id', $user->organization_id)
            ->wherePivot('organization_id', $user->organization_id)
            ->whereNull('clients.deleted_at')
            ->get(['clients.id', 'clients.organization_id']);

        foreach ($clients as $client) Gate::forUser($user)->authorize('viewPortal', $client);

        return $clients;
    }

    private function accessibleOrders(User $user, array $clientIds, ?bool $activeOnly = null, bool $terminalOnly = false, ?int $carId = null): Collection
    {
        $query = Order::query()
            ->where('organization_id', $user->organization_id)
            ->whereIn('client_id', $clientIds)
            ->when($carId, fn ($query, int $id) => $query->where('car_id', $id))
            ->with([
                'car:id,organization_id,client_id,make,model,year,plate_number,mileage',
                'status:id,name,slug,is_terminal',
                'items' => fn ($query) => $query->where('organization_id', $user->organization_id)->select(['id', 'organization_id', 'order_id', 'type', 'name_snapshot', 'quantity', 'unit_price', 'total']),
                'photos' => fn ($query) => $query->where('organization_id', $user->organization_id)->where('visible_to_client', true)->select(['id', 'organization_id', 'order_id', 'caption', 'category', 'created_at'])->latest(),
            ])
            ->latest('opened_at');

        if ($activeOnly === true) $query->whereHas('status', fn ($status) => $status->where('is_terminal', false));
        if ($terminalOnly) $query->whereHas('status', fn ($status) => $status->where('is_terminal', true));

        $orders = $query->get();
        foreach ($orders as $order) Gate::forUser($user)->authorize('viewPortal', $order);

        return $orders;
    }

    private function loadSafeOrder(User $user, Order $order): Order
    {
        $order->load([
            'car:id,organization_id,client_id,make,model,year,plate_number,mileage',
            'status:id,name,slug,is_terminal',
            'items' => fn ($query) => $query->where('organization_id', $user->organization_id)->select(['id', 'organization_id', 'order_id', 'type', 'name_snapshot', 'quantity', 'unit_price', 'total']),
            'photos' => fn ($query) => $query->where('organization_id', $user->organization_id)->where('visible_to_client', true)->select(['id', 'organization_id', 'order_id', 'caption', 'category', 'created_at'])->latest(),
        ]);

        return $order;
    }

    private function statusChangeTimes(User $user, Collection $orders): Collection
    {
        if ($orders->isEmpty()) return collect();

        return ActivityLog::where('organization_id', $user->organization_id)
            ->where('subject_type', (new Order)->getMorphClass())
            ->where('action', 'order.status_changed')
            ->whereIn('subject_id', $orders->map(fn (Order $order) => $order->getKey())->all())
            ->latest('created_at')
            ->get(['subject_id', 'created_at'])
            ->unique('subject_id')
            ->keyBy('subject_id')
            ->map(fn (ActivityLog $activity) => $activity->created_at);
    }

    private function orderSummary(Order $order, mixed $statusUpdatedAt): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status?->name,
            'status_slug' => $order->status?->slug,
            'status_updated_at' => $statusUpdatedAt,
            'opened_at' => $order->opened_at,
            'car' => trim(($order->car?->make ?? '').' '.($order->car?->model ?? '')),
            'plate' => $order->car?->plate_number,
            'total' => (int) $order->total,
            'paid' => (int) $order->paid_total,
            'balance' => (int) $order->balance,
        ];
    }

    private function orderDetails(Order $order, mixed $statusUpdatedAt): array
    {
        return [
            ...$this->orderSummary($order, $statusUpdatedAt),
            'status' => [
                'slug' => $order->status?->slug,
                'name' => $order->status?->name,
            ],
            'items' => $order->items->map(fn ($item) => [
                'type' => $item->type,
                'name' => $item->name_snapshot,
                'quantity' => (float) $item->quantity,
                'unit_price' => (int) $item->unit_price,
                'total' => (int) $item->total,
            ])->values(),
            'photos' => $order->photos->map(fn ($photo) => [
                'url' => route('client-portal.photos.show', ['photo' => $photo->id]),
                'caption' => $photo->caption,
                'category' => $photo->category,
                'created_at' => $photo->created_at,
            ])->values(),
        ];
    }
}