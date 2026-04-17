<?php

namespace App\Http\Requests\Explore;

use Illuminate\Foundation\Http\FormRequest;

class RespondTripJoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'response_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}

