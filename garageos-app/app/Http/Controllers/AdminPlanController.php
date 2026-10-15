<?php

namespace App\Http\Controllers;

use App\Services\Billing\PlanCatalog;
use Inertia\Inertia;
use Inertia\Response;

class AdminPlanController extends Controller
{
    public function index(PlanCatalog $plans): Response
    {
        return Inertia::render('Admin/Plans/Index', ['plans' => array_values($plans->all())]);
    }
}