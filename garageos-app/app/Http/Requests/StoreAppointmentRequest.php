<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id && in_array($this->user()?->role, ['owner', 'manager'], true); }

    public function rules(): array
    {
        return ['client_id' => ['required', 'integer', 'exists:clients,id'], 'car_id' => ['required', 'integer', 'exists:cars,id'], 'mechanic_id' => ['nullable', 'integer', 'exists:users,id'], 'service_id' => ['required', 'integer', 'exists:services,id'], 'start_at' => ['required', 'date', 'after_or_equal:today'], 'end_at' => ['required', 'date', 'after:start_at'], 'comment' => ['nullable', 'string', 'max:2000']];
    }
}
