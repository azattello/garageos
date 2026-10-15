<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\UserInvite;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        $user = request()->user();
        if ($user->isPlatformAdmin()) {
            return to_route('admin.index');
        }
        if ($user->role === 'mechanic') {
            return to_route('orders.index');
        }
        abort_unless($user->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);

        $organizationId = $user->organization_id;
        $organization = $user->organization;
        $today = Carbon::today();
        $orders = Order::where('organization_id', $organizationId);
        $revenue = (int) Order::where('organization_id', $organizationId)->sum('paid_total');
        $activeOrders = (clone $orders)->whereHas('status', fn ($query) => $query->whereIn('slug', ['accepted', 'diagnostics', 'in_progress', 'waiting_parts']))->count();
        $readyOrders = (clone $orders)->whereHas('status', fn ($query) => $query->whereIn('slug', ['ready', 'delivered']))->count();
        $appointmentsToday = Appointment::where('organization_id', $organizationId)->whereDate('start_at', $today)->count();
        $newClients = Client::where('organization_id', $organizationId)->whereDate('created_at', $today)->count();
        $debt = (int) (clone $orders)->sum('balance');
        $trend = collect(range(6, 0))->map(function (int $days) use ($organizationId) {
            $date = Carbon::today()->subDays($days);
            $dayOrders = Order::where('organization_id', $organizationId)->whereDate('created_at', $date);
            $orderCount = (clone $dayOrders)->count();
            $revenue = (int) (clone $dayOrders)->sum('paid_total');

            return [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('d.m'),
                'orders' => $orderCount,
                'revenue' => $revenue,
                'average_check' => $orderCount > 0 ? (int) round($revenue / $orderCount) : 0,
            ];
        })->values();

        $hasTeamMember = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereIn('role', ['manager', 'mechanic'])
            ->exists()
            || UserInvite::where('organization_id', $organizationId)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->exists();

        $onboarding = [
            ['key' => 'organization', 'title' => 'Проверьте профиль сервиса', 'description' => 'Название и контакты компании', 'href' => '/settings', 'completed' => filled($organization?->name) && (filled($organization?->phone) || filled($organization?->email))],
            ['key' => 'service', 'title' => 'Добавьте первую услугу', 'description' => 'Например, диагностику или замену масла', 'href' => '/services', 'completed' => Service::where('organization_id', $organizationId)->where('active', true)->exists()],
            ['key' => 'team', 'title' => 'Пригласите коллегу', 'description' => 'Добавьте механика или менеджера', 'href' => '/employees', 'completed' => $hasTeamMember],
            ['key' => 'client', 'title' => 'Добавьте первого клиента', 'description' => 'Карточка клиента сохранит историю визитов', 'href' => '/clients', 'completed' => Client::where('organization_id', $organizationId)->exists()],
            ['key' => 'car', 'title' => 'Добавьте автомобиль', 'description' => 'Свяжите его с клиентом для истории обслуживания', 'href' => '/clients', 'completed' => Car::where('organization_id', $organizationId)->where('active', true)->exists()],
            ['key' => 'order', 'title' => 'Создайте первый заказ-наряд', 'description' => 'Начните работу по заявке или создайте заказ напрямую', 'href' => '/orders', 'completed' => (clone $orders)->exists()],
        ];

        return Inertia::render('Dashboard', [
            'metrics' => compact('revenue', 'activeOrders', 'readyOrders', 'appointmentsToday', 'newClients', 'debt'),
            'analytics' => ['period' => '7d', 'trend' => $trend],
            'schedule' => Appointment::where('organization_id', $organizationId)->whereDate('start_at', $today)->with(['client', 'car', 'service'])->orderBy('start_at')->limit(5)->get(),
            'onboarding' => $onboarding,
            'subscription' => $organization->subscription,
        ]);
    }
}
