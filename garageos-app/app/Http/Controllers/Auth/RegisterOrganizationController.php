<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Organizations\RegisterOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterOrganizationController
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterOrganizationRequest $request, RegisterOrganization $register): RedirectResponse
    {
        $owner = $register->handle($request->validated());
        Auth::login($owner);
        $request->session()->regenerate();

        return redirect('/');
    }
}