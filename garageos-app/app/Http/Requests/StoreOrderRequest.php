<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id; }
    public function rules(): array { return ['client_id' => ['required', 'integer', 'exists:clients,id'], 'car_id' => ['required', 'integer', 'exists:cars,id'], 'mechanic_id' => ['nullable', 'integer', 'exists:users,id'], 'planned_completion_at' => ['nullable', 'date'], 'internal_comment' => ['nullable', 'string', 'max:2000']]; }
}
