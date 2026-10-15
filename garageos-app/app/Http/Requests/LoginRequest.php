<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user();
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $email = strtolower(trim((string) $this->string('email')));
        $password = (string) $this->string('password');
        $throttleKey = Str::transliterate($email.'|'.$this->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Слишком много попыток входа. Повторите через '.RateLimiter::availableIn($throttleKey).' сек.',
            ]);
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => 'Неверный email или пароль.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $this->boolean('remember'));
    }
}
