<?php

namespace App\Http\Requests\Explore;

use Illuminate\Foundation\Http\FormRequest;

class ToggleTripRequestOpenState extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'is_open_for_request' => ['required', 'boolean'],
        ];
    }
}

