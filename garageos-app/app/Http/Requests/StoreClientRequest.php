<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id; }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'phone' => ['required', 'string', 'max:32'], 'email' => ['nullable', 'email', 'max:255'], 'comment' => ['nullable', 'string', 'max:2000']];
    }
}
