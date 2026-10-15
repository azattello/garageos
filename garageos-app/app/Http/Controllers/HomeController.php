<?php

namespace App\Http\Controllers;

use App\Services\Billing\PlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, DashboardController $dashboard, PlanCatalog $plans): Response|RedirectResponse
    {
        if ($request->user()) {
            return $dashboard();
        }

        return Inertia::render('Landing/Index', [
            'plans' => array_values($plans->all()),
            'canonical' => url('/'),
        ]);
    }
}