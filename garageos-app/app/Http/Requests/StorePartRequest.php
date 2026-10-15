<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['owner', 'manager'], true) && (bool) $this->user()?->organization_id;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'sale_price' => ['required', 'integer', 'min:0'],
            'initial_stock' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'unit' => ['required', 'string', 'max:20'],
        ];
    }
}
