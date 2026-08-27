<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendAdminMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'audience' => ['required', Rule::in(['user', 'role', 'all'])],
            'user_id' => ['required_if:audience,user', 'nullable', 'exists:users,id'],
            'role' => ['required_if:audience,role', 'nullable', Rule::in(['admin', 'driver', 'passenger'])],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
