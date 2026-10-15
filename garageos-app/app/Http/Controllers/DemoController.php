<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class DemoController extends Controller
{
    public function __invoke(): Response
    {
        $organization = Organization::query()
            ->where('slug', 'auto-service-demo')
            ->with(['subscription', 'orders' => fn ($query) => $query->with(['client', 'car', 'status'])->latest()->limit(8)])
            ->withCount(['clients', 'orders', 'services', 'users'])
            ->firstOrFail();

        return Inertia::render('Demo/Index', [
            'organization' => [
                'name' => $organization->name,
                'phone' => $organization->phone,
                'email' => $organization->email,
            ],
            'metrics' => [
                'clients' => $organization->clients_count,
                'orders' => $organization->orders_count,
                'services' => $organization->services_count,
                'users' => $organization->users_count,
            ],
            'orders' => $organization->orders->map(fn ($order) => [
                'number' => $order->number,
                'client' => $order->client?->name,
                'vehicle' => trim(($order->car?->make ?? '').' '.($order->car?->model ?? '')),
                'status' => $order->status?->name,
                'total' => $order->total,
            ])->values(),
        ]);
    }
}
