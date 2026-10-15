<?php

namespace App\Http\Controllers;

use App\Actions\Orders\AddOrderItem;
use App\Actions\Orders\CreateOrder;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\ChangeOrderStatusRequest;
use App\Actions\Orders\ChangeOrderStatus;
use App\Actions\Payments\RegisterPayment;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\OrderStatus;
use App\Models\Part;
use App\Actions\Orders\UploadOrderPhoto;
use App\Http\Requests\StoreOrderPhotoRequest;
use App\Actions\Orders\CreatePublicOrderLink;
use App\Actions\Orders\RevokePublicOrderLinks;
use App\Models\Client;
use App\Models\Car;
use App\Models\Order;
use App\Models\Service;
use App\Models\PublicOrderToken;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->organization_id, 403);
        Gate::forUser($user)->authorize('viewAny', Order::class);
        $canManageFinances = in_array($user->role, ['owner', 'manager'], true);
        $requestedClientId = $canManageFinances ? $request->integer('client_id') : 0;
        $requestedCarId = $canManageFinances ? $request->integer('car_id') : 0;
        $selectedCar = $requestedCarId > 0
            ? Car::where('organization_id', $user->organization_id)->where('active', true)->whereKey($requestedCarId)->first()
            : null;
        $requestedClient = $requestedClientId > 0
            ? Client::where('organization_id', $user->organization_id)->whereKey($requestedClientId)->first()
            : null;
        if ($selectedCar && $requestedClient && $selectedCar->client_id !== $requestedClient->id) {
            $selectedCar = null;
        }
        $selectedClientId = $selectedCar?->client_id ?? $requestedClient?->id;
        $query = Order::where('organization_id', $user->organization_id);
        if ($user->role === 'mechanic') {
            $query->where('mechanic_id', $user->id)
                ->select(['id', 'organization_id', 'number', 'client_id', 'car_id', 'mechanic_id', 'status_id', 'opened_at', 'planned_completion_at', 'created_at']);
        }

        return Inertia::render('Orders/List', [
            'orders' => $query->with([
                'client',
                'car',
                'status',
                'mechanic',
                'items' => fn ($items) => $canManageFinances ? $items : $items->select(['id', 'organization_id', 'order_id', 'name_snapshot', 'quantity']),
            ])->latest()->paginate(25),
            'clients' => $canManageFinances ? Client::where('organization_id', $user->organization_id)->with(['cars' => fn ($query) => $query->where('active', true)])->orderBy('name')->get() : [],
            'services' => $canManageFinances ? Service::where('organization_id', $user->organization_id)->where('active', true)->orderBy('name')->get() : [],
            'canCreate' => $canManageFinances,
            'canViewFinancials' => $canManageFinances,
            'selectedClientId' => $selectedClientId,
            'selectedCarId' => $selectedCar?->id,
        ]);
    }

    public function kanban(ChangeOrderStatus $changeOrderStatus): Response
    {
        $user = request()->user();
        abort_unless($user?->organization_id, 403);
        abort_unless(in_array($user->role, ['owner', 'manager'], true), 403);
        Gate::forUser($user)->authorize('viewAny', Order::class);

        $statuses = OrderStatus::where('organization_id', $user->organization_id)->get(['slug', 'name'])->keyBy('slug');
        $fallbackNames = [
            'new' => 'Новый',
            'accepted' => 'Принят',
            'diagnostics' => 'Диагностика',
            'in_progress' => 'В работе',
            'waiting_parts' => 'Ожидание запчастей',
            'ready' => 'Готов',
            'delivered' => 'Выдан',
            'closed' => 'Закрыт',
            'cancelled' => 'Отменён',
        ];
        $orders = Order::where('organization_id', $user->organization_id)
            ->with(['client', 'car', 'status', 'mechanic'])
            ->latest()
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'total' => (int) $order->total,
                'client' => ['name' => $order->client->name],
                'car' => $order->car->only(['make', 'model', 'plate_number']),
                'status' => $order->status->only(['name', 'slug']),
                'mechanic' => $order->mechanic?->only(['name']),
                'nextStatuses' => collect($changeOrderStatus->allowedTransitions($order))
                    ->map(fn (string $slug) => [
                        'slug' => $slug,
                        'name' => $statuses->get($slug)?->name ?? $fallbackNames[$slug] ?? $slug,
                    ])
                    ->values(),
            ])
            ->values();

        return Inertia::render('Orders/Kanban', ['orders' => $orders]);
    }

    public function show(Order $order, ChangeOrderStatus $changeOrderStatus): Response
    {
        $user = request()->user();
        Gate::forUser($user)->authorize('view', $order);
        $canManageFinances = Gate::forUser($user)->allows('manageFinances', $order);
        $order->load([
            'client',
            'car',
            'mechanic',
            'status',
            'items',
            'payments' => fn ($query) => $query->latest('paid_at'),
            'photos' => fn ($query) => $query->latest(),
        ]);

        $nextStatusSlugs = $changeOrderStatus->allowedTransitions($order);
        $storedNextStatuses = OrderStatus::where('organization_id', $order->organization_id)
            ->whereIn('slug', $nextStatusSlugs)
            ->get(['slug', 'name'])
            ->keyBy('slug');
        $fallbackStatusNames = [
            'accepted' => 'Принят',
            'diagnostics' => 'Диагностика',
            'in_progress' => 'В работе',
            'waiting_parts' => 'Ожидание запчастей',
            'ready' => 'Готов',
            'delivered' => 'Выдан',
            'closed' => 'Закрыт',
            'cancelled' => 'Отменён',
        ];

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'client' => $order->client->only(['id', 'name', 'phone', 'email']),
                'car' => $order->car->only(['id', 'make', 'model', 'year', 'plate_number', 'mileage']),
                'mechanic' => $order->mechanic?->only(['id', 'name']),
                'status' => $order->status->only(['name', 'slug']),
                'opened_at' => $order->opened_at,
                'planned_completion_at' => $order->planned_completion_at,
                'internal_comment' => $canManageFinances ? $order->internal_comment : null,
                'client_comment' => $order->client_comment,
                'subtotal' => $canManageFinances ? (int) $order->subtotal : null,
                'discount' => $canManageFinances ? (int) $order->discount : null,
                'total' => $canManageFinances ? (int) $order->total : null,
                'paid_total' => $canManageFinances ? (int) $order->paid_total : null,
                'balance' => $canManageFinances ? (int) $order->balance : null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'name' => $item->name_snapshot,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => $canManageFinances ? (int) $item->unit_price : null,
                    'total' => $canManageFinances ? (int) $item->total : null,
                ])->values(),
                'payments' => $canManageFinances ? $order->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'amount' => (int) $payment->amount,
                    'method' => $payment->method,
                    'status' => $payment->status,
                    'paid_at' => $payment->paid_at,
                ])->values() : [],
                'photos' => $order->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => route('orders.photos.show', ['order' => $order->id, 'photo' => $photo->id]),
                    'category' => $photo->category,
                    'caption' => $photo->caption,
                    'visible_to_client' => $photo->visible_to_client,
                ])->values(),
            ],
            'services' => Service::where('organization_id', $order->organization_id)
                ->where('active', true)
                ->orderBy('name')
                ->get($canManageFinances ? ['id', 'name', 'price'] : ['id', 'name']),
            'parts' => $canManageFinances
                ? Part::where('organization_id', $order->organization_id)->where('active', true)->whereRaw('(stock - reserved_stock) > 0')->orderBy('name')->select(['id', 'name', 'stock', 'reserved_stock', 'unit', 'sale_price'])->selectRaw('(stock - reserved_stock) as available_stock')->get()
                : [],
            'mechanics' => $canManageFinances
                ? User::where('organization_id', $order->organization_id)->where('role', 'mechanic')->where('status', 'active')->orderBy('name')->get(['id', 'name'])
                : [],
            'nextStatuses' => collect($nextStatusSlugs)->map(fn (string $slug) => [
                'slug' => $slug,
                'name' => $storedNextStatuses->get($slug)?->name ?? $fallbackStatusNames[$slug] ?? $slug,
            ])->values(),
            'canManageFinances' => $canManageFinances,
            'canUpdate' => Gate::forUser($user)->allows('update', $order),
            'publicOrderUrl' => $canManageFinances ? session('public_order_url') : null,
            'hasActivePublicLink' => $canManageFinances && PublicOrderToken::where('organization_id', $order->organization_id)
                ->where('order_id', $order->id)
                ->whereNull('revoked_at')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists(),
        ]);
    }

    public function store(StoreOrderRequest $request, CreateOrder $createOrder): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', Order::class);
        $order = $createOrder->handle($request->user(), $request->validated());
        return to_route('orders.show', $order)->with('success', "Заказ-наряд {$order->number} создан.");
    }

    public function addItem(StoreOrderItemRequest $request, Order $order, AddOrderItem $addOrderItem): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $order);
        $addOrderItem->handle($request->user(), $order, $request->validated());
        return to_route('orders.show', $order)->with('success', 'Работа добавлена в заказ.');
    }

    public function assignMechanic(Request $request, Order $order): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('manageFinances', $order);
        $data = $request->validate(['mechanic_id' => ['nullable', 'integer', 'exists:users,id']]);
        $mechanic = null;

        if (! empty($data['mechanic_id'])) {
            $mechanic = User::where('organization_id', $order->organization_id)
                ->where('role', 'mechanic')
                ->where('status', 'active')
                ->whereKey($data['mechanic_id'])
                ->first();

            if (! $mechanic) {
                throw ValidationException::withMessages(['mechanic_id' => 'Выберите активного механика своей организации.']);
            }
        }

        $previousMechanicId = $order->mechanic_id;
        $order->update(['mechanic_id' => $mechanic?->id]);

        if ($previousMechanicId !== $order->mechanic_id) {
            Activity::record('order.mechanic_assigned', $order, $request->user(), [
                'from' => $previousMechanicId,
                'to' => $order->mechanic_id,
            ]);
        }

        return to_route('orders.show', $order)->with('success', $mechanic ? 'Механик назначен.' : 'Механик снят с заказа.');
    }

    public function changeStatus(ChangeOrderStatusRequest $request, Order $order, ChangeOrderStatus $changeOrderStatus): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $order);
        $changeOrderStatus->handle(
            $request->user(),
            $order,
            $request->validated('status'),
            (bool) $request->validated('allow_unpaid', false),
        );
        return to_route('orders.show', $order)->with('success', 'Статус заказа изменён.');
    }

    public function registerPayment(StorePaymentRequest $request, Order $order, RegisterPayment $registerPayment): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('manageFinances', $order);
        $registerPayment->handle($request->user(), $order, $request->validated());
        return to_route('orders.show', $order)->with('success', 'Оплата добавлена.');
    }

    public function cancelPayment(Order $order, Payment $payment, RegisterPayment $registerPayment): RedirectResponse
    {
        Gate::forUser(request()->user())->authorize('manageFinances', $order);
        abort_unless($payment->order_id === $order->id, 404);
        $registerPayment->cancel(request()->user(), $payment);
        return to_route('orders.show', $order)->with('success', 'Оплата отменена.');
    }

    public function uploadPhoto(StoreOrderPhotoRequest $request, Order $order, UploadOrderPhoto $uploadOrderPhoto): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $order);
        $uploadOrderPhoto->handle($request->user(), $order, $request->file('photo'), $request->validated());
        return to_route('orders.show', $order)->with('success', 'Фотография добавлена.');
    }

    public function createPublicLink(Order $order, CreatePublicOrderLink $createPublicOrderLink): RedirectResponse
    {
        Gate::forUser(request()->user())->authorize('manageFinances', $order);
        $token = $createPublicOrderLink->handle(request()->user(), $order);
        return to_route('orders.show', $order)->with('public_order_url', url('/order/'.$token));
    }

    public function revokePublicLinks(Order $order, RevokePublicOrderLinks $revokePublicOrderLinks): RedirectResponse
    {
        Gate::forUser(request()->user())->authorize('manageFinances', $order);
        $count = $revokePublicOrderLinks->handle(request()->user(), $order);

        return to_route('orders.show', $order)->with('success', $count ? 'Публичные ссылки отозваны.' : 'Активных публичных ссылок нет.');
    }
}
