<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function request(): Response { return Inertia::render('Auth/ForgotPassword'); }

    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Если адрес зарегистрирован, инструкция по сбросу пароля будет отправлена на почту.');
    }

    public function reset(string $token): Response { return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => request('email')]); }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset($request->validated(), function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        return $status === Password::PASSWORD_RESET ? to_route('login')->with('status', 'Пароль изменён.') : back()->withErrors(['email' => __($status)]);
    }
}
