<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInviteRequest extends FormRequest
{
    public function authorize(): bool { return in_array($this->user()?->role, ['owner', 'manager'], true); }
    public function rules(): array { return ['email' => ['required', 'email', 'max:255'], 'role' => ['required', 'in:manager,mechanic']]; }
}
