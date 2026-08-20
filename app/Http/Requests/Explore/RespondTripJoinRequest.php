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
            'response_note' => [
                $this->input('action') === 'reject' ? 'required' : 'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'response_note.required' => 'Please state a reason for rejecting this request.',
        ];
    }
}

