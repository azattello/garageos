<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeOrderStatusRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id; }
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:accepted,diagnostics,in_progress,waiting_parts,ready,delivered,closed,cancelled'],
            'allow_unpaid' => ['sometimes', 'boolean'],
        ];
    }
}
