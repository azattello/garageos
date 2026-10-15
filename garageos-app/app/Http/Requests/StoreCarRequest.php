<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id && in_array($this->user()?->role, ['owner', 'manager'], true); }

    public function rules(): array
    {
        return ['client_id' => ['required', 'integer', 'exists:clients,id'], 'make' => ['required', 'string', 'max:80'], 'model' => ['required', 'string', 'max:80'], 'year' => ['nullable', 'integer', 'between:1900,2100'], 'plate_number' => ['nullable', 'string', 'max:24'], 'vin' => ['nullable', 'string', 'max:32'], 'mileage' => ['nullable', 'integer', 'min:0']];
    }
}
