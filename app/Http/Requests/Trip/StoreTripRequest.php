<?php

namespace App\Http\Requests\Trip;

use App\Http\Requests\Trip\Concerns\ValidatesTripPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreTripRequest extends FormRequest
{
    use ValidatesTripPayload;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return $this->baseTripRules() + [
            'trip_type' => ['required', 'in:one_way,two_way'],
            'include_driver_in_split' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->applySharedTripValidation($validator);

            if ($this->input('visibility') === 'public' && $this->filled('trip_datetime')) {
                try {
                    $tripAt = Carbon::parse((string) $this->input('trip_datetime'));
                    if ($tripAt->lessThanOrEqualTo(now())) {
                        $validator->errors()->add('trip_datetime', 'For public trips, date and time must be later than current time.');
                    }
                } catch (\Throwable $exception) {
                    // Base date validation rule handles invalid datetime format.
                }
            }
        });
    }
}
