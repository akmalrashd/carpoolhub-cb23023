<?php

namespace App\Http\Requests\Explore;

use Illuminate\Foundation\Http\FormRequest;

class RemoveTripParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please state a reason for removing this passenger.',
        ];
    }
}
