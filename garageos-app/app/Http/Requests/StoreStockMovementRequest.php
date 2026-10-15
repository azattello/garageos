<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['owner', 'manager'], true) && (bool) $this->user()?->organization_id;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:receive,issue,adjustment,return'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
