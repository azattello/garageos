<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool { return ! $this->user(); }
    public function rules(): array { return ['email' => ['required', 'email']]; }
}
