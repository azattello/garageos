<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->organization_id
            && in_array($this->user()?->role, ['owner', 'manager'], true);
    }

    public function rules(): array
    {
        return [
            'make' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'plate_number' => ['nullable', 'string', 'max:24'],
            'vin' => ['nullable', 'string', 'max:32'],
            'color' => ['nullable', 'string', 'max:80'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:40'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}