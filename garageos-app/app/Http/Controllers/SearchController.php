<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = trim((string) $request->string('q'));
        $user = $request->user();
        abort_unless($user?->organization_id && in_array($user->role, ['owner', 'manager', 'mechanic'], true), 403);
        $organizationId = $user->organization_id;
        $isMechanic = $user->role === 'mechanic';
        $like = '%'.$query.'%';
        return Inertia::render('Search/Index', [
            'query' => $query,
            'clients' => $query === '' ? [] : Client::where('organization_id', $organizationId)
                ->when($isMechanic, fn ($builder) => $builder->whereHas('orders', fn ($orders) => $orders->where('mechanic_id', $user->id)))
                ->where(fn ($builder) => $builder->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like))
                ->limit(10)
                ->get(['id', 'name', 'phone']),
            'cars' => $query === '' ? [] : Car::where('organization_id', $organizationId)->where('active', true)
                ->when($isMechanic, fn ($builder) => $builder->whereHas('orders', fn ($orders) => $orders->where('mechanic_id', $user->id)))
                ->where(fn ($builder) => $builder->where('make', 'like', $like)->orWhere('model', 'like', $like)->orWhere('plate_number', 'like', $like)->orWhere('vin', 'like', $like))
                ->limit(10)
                ->get(['id', 'make', 'model', 'plate_number', 'vin']),
            'orders' => $query === '' ? [] : Order::where('organization_id', $organizationId)
                ->when($isMechanic, fn ($builder) => $builder->where('mechanic_id', $user->id))
                ->where('number', 'like', $like)
                ->with('status')
                ->limit(10)
                ->get(['id', 'number', 'status_id']),
        ]);
    }
}
