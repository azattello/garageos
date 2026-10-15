<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user();
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }
}