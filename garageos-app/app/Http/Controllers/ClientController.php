<?php

namespace App\Http\Controllers;

use App\Actions\Clients\CreateClient;
use App\Actions\Clients\EnableClientPortalAccess;
use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPhoto;
use App\Models\Payment;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use App\Support\Activity;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->organization_id, 403);
        Gate::forUser($user)->authorize('viewAny', Client::class);
        $search = trim((string) $request->query('q', ''));
        $requestedClientId = $request->integer('client_id');
        $selectedClientId = $requestedClientId > 0
            ? Client::where('organization_id', $user->organization_id)->whereKey($requestedClientId)->value('id')
            : null;
        $cutoff = now()->subDays(90);
        $completedVisit = fn (Builder $orders) => $orders->whereHas('status', fn (Builder $status) => $status->whereIn('slug', ['delivered', 'closed']));
        $applyFollowUpSegment = function (Builder $query) use ($completedVisit, $cutoff): Builder {
            return $query
                ->whereHas('orders', $completedVisit)
                ->whereDoesntHave('orders', fn (Builder $orders) => $orders->where('opened_at', '>', $cutoff)->whereHas('status', fn (Builder $status) => $status->whereIn('slug', ['delivered', 'closed'])))
                ->whereDoesntHave('orders', fn (Builder $orders) => $orders->whereHas('status', fn (Builder $status) => $status->where('is_terminal', false)))
                ->whereDoesntHave('appointments', fn (Builder $appointments) => $appointments->where('start_at', '>=', now())->whereNotIn('status', ['cancelled', 'completed']));
        };
        $segment = $request->query('segment') === 'follow_up' ? 'follow_up' : 'all';
        $clientsQuery = Client::query()
            ->where('organization_id', $user->organization_id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('cars', fn ($carQuery) => $carQuery
                            ->where('active', true)
                            ->where(fn ($carQuery) => $carQuery
                                ->where('plate_number', 'like', "%{$search}%")
                                ->orWhere('vin', 'like', "%{$search}%")));
                });
            })
            ->withCount([
                'cars' => fn (Builder $query) => $query->where('active', true),
                'orders as completed_visits_count' => $completedVisit,
                'orders as active_orders_count' => fn (Builder $query) => $query->whereHas('status', fn (Builder $status) => $status->where('is_terminal', false)),
                'appointments as upcoming_appointments_count' => fn (Builder $query) => $query->where('start_at', '>=', now())->whereNotIn('status', ['cancelled', 'completed']),
            ])
            ->withMax(['orders as last_visit_at' => $completedVisit], 'opened_at');

        if ($segment === 'follow_up') {
            $applyFollowUpSegment($clientsQuery);
        }

        return Inertia::render('Clients/Index', [
            'filters' => ['q' => $search, 'segment' => $segment],
            'selectedClientId' => $selectedClientId,
            'summary' => [
                'clients' => Client::where('organization_id', $user->organization_id)->count(),
                'servicedClients' => Client::where('organization_id', $user->organization_id)->whereHas('orders', $completedVisit)->count(),
                'repeatClients' => Client::where('organization_id', $user->organization_id)->whereHas('orders', $completedVisit, '>=', 2)->count(),
                'followUp' => $applyFollowUpSegment(Client::where('organization_id', $user->organization_id))->count(),
            ],
            'clients' => $clientsQuery
                ->orderByRaw('CASE WHEN last_visit_at IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('last_visit_at')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Client $client) => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'phone' => $client->phone,
                    'email' => $client->email,
                    'cars_count' => (int) $client->cars_count,
                    'completed_visits_count' => (int) $client->completed_visits_count,
                    'last_visit_at' => $client->last_visit_at,
                    'needs_follow_up' => $client->completed_visits_count > 0
                        && $client->last_visit_at !== null
                        && Carbon::parse($client->last_visit_at)->lte($cutoff)
                        && $client->active_orders_count === 0
                        && $client->upcoming_appointments_count === 0,
                ]),
        ]);
    }

    public function store(StoreClientRequest $request, CreateClient $createClient): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', Client::class);

        $client = $createClient->handle($request->user(), $request->validated());

        if ($request->input('return_to') === 'appointments') {
            return to_route('appointments.index', ['client_id' => $client->id])
                ->with('success', 'Клиент создан и выбран.');
        }

        if ($request->input('return_to') === 'orders') {
            return to_route('orders.index', ['client_id' => $client->id, 'car_id' => $request->integer('car_id')])
                ->with('success', 'Клиент создан и выбран.');
        }

        if ($client->wasRecentlyCreated) {
            return to_route('clients.index')->with('success', 'Клиент создан.');
        }

        return to_route('clients.index', ['client_id' => $client->id])
            ->with('success', 'Существующий клиент найден и выбран.');
    }

    public function show(Client $client): Response
    {
        $user = request()->user();
        abort_unless($client->organization_id === $user->organization_id, 404);
        Gate::forUser($user)->authorize('view', $client);

        $client->load('cars');
        $orderSummary = Order::query()->where('organization_id', $client->organization_id)->where('client_id', $client->id);
        $completedOrders = fn (Builder $query) => $query->whereHas('status', fn (Builder $status) => $status->whereIn('slug', ['delivered', 'closed']));
        $completedVisits = (clone $orderSummary)->where($completedOrders);
        $completedVisitsCount = (clone $completedVisits)->count();
        $lastVisitAt = (clone $completedVisits)->max('opened_at');
        $hasActiveOrder = (clone $orderSummary)->whereHas('status', fn (Builder $status) => $status->where('is_terminal', false))->exists();
        $hasUpcomingAppointment = $client->appointments()->where('start_at', '>=', now())->whereNotIn('status', ['cancelled', 'completed'])->exists();
        $needsFollowUp = $completedVisitsCount > 0
            && $lastVisitAt !== null
            && Carbon::parse($lastVisitAt)->lte(now()->subDays(90))
            && ! $hasActiveOrder
            && ! $hasUpcomingAppointment;

        $timelineQuery = DB::table('orders')
            ->where('organization_id', $client->organization_id)
            ->where('client_id', $client->id)
            ->selectRaw("created_at as occurred_at, 'order' as event_type, id as event_id")
            ->unionAll(DB::table('appointments')
                ->where('organization_id', $client->organization_id)
                ->where('client_id', $client->id)
                ->selectRaw("start_at as occurred_at, 'appointment' as event_type, id as event_id"))
            ->unionAll(DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.organization_id', $client->organization_id)
                ->where('orders.client_id', $client->id)
                ->selectRaw("order_items.created_at as occurred_at, 'work' as event_type, order_items.id as event_id"))
            ->unionAll(DB::table('payments')
                ->join('orders', 'orders.id', '=', 'payments.order_id')
                ->where('payments.organization_id', $client->organization_id)
                ->where('orders.client_id', $client->id)
                ->selectRaw("payments.paid_at as occurred_at, 'payment' as event_type, payments.id as event_id"))
            ->unionAll(DB::table('order_photos')
                ->join('orders', 'orders.id', '=', 'order_photos.order_id')
                ->where('order_photos.organization_id', $client->organization_id)
                ->where('orders.client_id', $client->id)
                ->whereNull('order_photos.deleted_at')
                ->selectRaw("order_photos.created_at as occurred_at, 'photo' as event_type, order_photos.id as event_id"))
            ->unionAll(DB::table('activity_logs')
                ->where('organization_id', $client->organization_id)
                ->where('subject_type', (new Order())->getMorphClass())
                ->whereIn('subject_id', (clone $orderSummary)->select('id'))
                ->where('action', 'order.status_changed')
                ->selectRaw("created_at as occurred_at, 'status' as event_type, id as event_id"));
        $timeline = DB::query()->fromSub($timelineQuery, 'client_timeline')
            ->orderByDesc('occurred_at')
            ->orderByDesc('event_id')
            ->paginate(20)
            ->withQueryString();

        $eventRows = $timeline->getCollection();
        $eventIds = fn (string $type) => $eventRows->where('event_type', $type)->pluck('event_id')->all();
        $orders = Order::query()->where('organization_id', $client->organization_id)->whereIn('id', $eventIds('order'))->with(['car', 'status'])->get()->keyBy('id');
        $appointments = Appointment::query()->where('organization_id', $client->organization_id)->whereIn('id', $eventIds('appointment'))->with(['car', 'service'])->get()->keyBy('id');
        $workItems = OrderItem::query()->where('organization_id', $client->organization_id)->whereIn('id', $eventIds('work'))->with('order')->get()->keyBy('id');
        $payments = Payment::query()->where('organization_id', $client->organization_id)->whereIn('id', $eventIds('payment'))->with('order')->get()->keyBy('id');
        $photos = OrderPhoto::query()->where('organization_id', $client->organization_id)->whereIn('id', $eventIds('photo'))->with('order')->get()->keyBy('id');
        $statusEvents = ActivityLog::query()->where('organization_id', $client->organization_id)->whereIn('id', $eventIds('status'))->get()->keyBy('id');
        $timeline->setCollection($eventRows->map(function ($event) use ($orders, $appointments, $workItems, $payments, $photos, $statusEvents) {
            $record = match ($event->event_type) {
                'order' => $orders->get($event->event_id),
                'appointment' => $appointments->get($event->event_id),
                'work' => $workItems->get($event->event_id),
                'payment' => $payments->get($event->event_id),
                'photo' => $photos->get($event->event_id),
                'status' => $statusEvents->get($event->event_id),
                default => null,
            };
            if (! $record) return null;

            return match ($event->event_type) {
                'order' => ['id' => 'order-'.$record->id, 'type' => 'order', 'title' => 'Заказ-наряд '.$record->number, 'detail' => ($record->car?->make.' '.$record->car?->model).' · '.$record->status?->name, 'occurred_at' => $event->occurred_at, 'url' => route('orders.show', $record)],
                'appointment' => ['id' => 'appointment-'.$record->id, 'type' => 'appointment', 'title' => 'Запись: '.$record->service->name, 'detail' => $record->car->make.' '.$record->car->model.' · '.$record->status, 'occurred_at' => $event->occurred_at, 'url' => route('appointments.edit', $record)],
                'work' => ['id' => 'work-'.$record->id, 'type' => 'work', 'title' => $record->name_snapshot, 'detail' => ($record->type === 'part' ? 'Запчасть' : 'Работа').' · заказ '.$record->order->number, 'occurred_at' => $event->occurred_at, 'url' => route('orders.show', $record->order)],
                'payment' => ['id' => 'payment-'.$record->id, 'type' => 'payment', 'title' => 'Платёж · заказ '.$record->order->number, 'detail' => number_format((int) $record->amount, 0, ',', ' ').' ₸ · '.($record->status === 'successful' ? 'успешно' : 'отменён'), 'occurred_at' => $event->occurred_at, 'url' => route('orders.show', $record->order)],
                'photo' => ['id' => 'photo-'.$record->id, 'type' => 'photo', 'title' => $record->caption ?: 'Фото · '.$record->category, 'detail' => ($record->visible_to_client ? 'Доступно клиенту' : 'Внутреннее').' · заказ '.$record->order->number, 'occurred_at' => $event->occurred_at, 'url' => route('orders.show', $record->order), 'image' => route('orders.photos.show', ['order' => $record->order_id, 'photo' => $record->id])],
                'status' => ['id' => 'status-'.$record->id, 'type' => 'status', 'title' => 'Изменён статус заказа', 'detail' => (string) ($record->metadata['from_status'] ?? '').' → '.(string) ($record->metadata['to_status'] ?? ''), 'occurred_at' => $event->occurred_at, 'url' => route('orders.show', $record->subject_id)],
            };
        })->filter()->values());

        return Inertia::render('Clients/Show', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'portalAccess' => $client->portalUsers()->exists(),
                'comment' => $client->comment,
                'cars' => $client->cars,
                'summary' => [
                    'cars' => $client->cars->count(),
                    'orders' => (clone $orderSummary)->count(),
                    'total' => (int) (clone $orderSummary)->sum('total'),
                    'paid' => (int) (clone $orderSummary)->sum('paid_total'),
                    'balance' => (int) (clone $orderSummary)->sum('balance'),
                    'completedVisits' => $completedVisitsCount,
                    'lastVisitAt' => $lastVisitAt,
                    'needsFollowUp' => $needsFollowUp,
                ],
            ],
            'timeline' => $timeline,
        ]);
    }

    public function destroy(Client $client): RedirectResponse
    {
        $user = request()->user();
        abort_unless($client->organization_id === $user->organization_id, 404);
        Gate::forUser($user)->authorize('delete', $client);
        Activity::record('client.archived', $client, $user);
        $client->delete();
        return to_route('clients.index')->with('success', 'Клиент архивирован.');
    }

    public function enablePortalAccess(Client $client, EnableClientPortalAccess $enableClientPortalAccess): RedirectResponse
    {
        abort_unless($client->organization_id === request()->user()->organization_id, 404);
        $enableClientPortalAccess->handle(request()->user(), $client);

        return back()->with('success', 'Доступ к клиентскому кабинету создан. Ссылка для задания пароля отправлена на email клиента.');
    }
}
