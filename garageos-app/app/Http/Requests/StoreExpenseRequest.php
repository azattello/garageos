<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['owner', 'manager'], true) && (bool) $this->user()?->organization_id;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
            'expense_date' => ['required', 'date'],
        ];
    }
}
