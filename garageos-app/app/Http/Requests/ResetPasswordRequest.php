<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool { return ! $this->user(); }
    public function rules(): array { return ['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', Password::defaults()]]; }
}
