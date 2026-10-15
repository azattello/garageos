<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderItemRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id; }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('type')) {
            $this->merge(['type' => $this->filled('part_id') ? 'part' : 'service']);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:service,part'],
            'service_id' => ['nullable', 'required_if:type,service', 'prohibited_if:type,part', 'integer'],
            'part_id' => ['nullable', 'required_if:type,part', 'prohibited_if:type,service', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'discount' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
