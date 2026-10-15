<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id && in_array($this->user()?->role, ['owner', 'manager'], true); }

    public function rules(): array
    {
        return ['category_id' => ['nullable', 'integer', 'exists:service_categories,id'], 'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:2000'], 'price' => ['required', 'integer', 'min:0'], 'duration_minutes' => ['required', 'integer', 'between:15,1440']];
    }
}
