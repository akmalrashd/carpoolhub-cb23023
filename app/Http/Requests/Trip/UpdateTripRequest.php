<?php

namespace App\Http\Requests\Trip;

use App\Http\Requests\Trip\Concerns\ValidatesTripPayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    use ValidatesTripPayload;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return $this->baseTripRules() + [
            'trip_type' => ['nullable', 'in:one_way,two_way'],
            'include_driver_in_split' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->applySharedTripValidation($validator);
        });
    }
}
