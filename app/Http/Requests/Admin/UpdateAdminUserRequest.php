<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'in:admin,driver,passenger'],
            'is_active' => ['required', 'boolean'],
            // Required-when-deactivating is enforced in AdminUserService,
            // which alone knows whether this call is the deactivating
            // transition (needs the target's current state, not just this
            // request's payload) — kept nullable here, not required_if.
            'reason' => ['nullable', 'string', 'max:1000'],
            // Same nullable-not-required_if reasoning as `reason` above: a
            // blank value here means permanent, enforced in AdminUserService.
            'suspended_until' => ['nullable', 'date', 'after:now'],
        ];
    }
}
