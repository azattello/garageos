<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\Activity;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        if ($request->user()->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Доступ пользователя отключён.']);
        }

        $request->user()->forceFill(['last_login_at' => now()])->save();
        if ($request->user()->isPlatformAdmin()) {
            Activity::record('admin.login', null, $request->user(), [], $request);
        }
        if ($request->user()->role === 'client') {
            return to_route('client-portal.index');
        }

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()?->isPlatformAdmin()) {
            Activity::record('admin.logout', null, $request->user(), [], $request);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return to_route('login');
    }
}
