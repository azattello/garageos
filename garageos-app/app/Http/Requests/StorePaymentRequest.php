<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id; }
    public function rules(): array
    {
        return ['amount' => ['required', 'integer', 'min:1'], 'method' => ['required', 'in:cash,card,kaspi,transfer,other'], 'comment' => ['nullable', 'string', 'max:1000']];
    }
}