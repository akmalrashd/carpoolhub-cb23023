<?php

namespace App\Http\Requests\Explore;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripJoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'request_note' => ['nullable', 'string', 'max:500'],
            'pickup_mode' => ['nullable', 'in:default,custom'],
            'pickup_name' => ['nullable', 'string', 'max:255', 'required_if:pickup_mode,custom'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_mode' => ['nullable', 'in:default,custom'],
            'dropoff_name' => ['nullable', 'string', 'max:255', 'required_if:dropoff_mode,custom'],
            'dropoff_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'dropoff_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'requested_pickup_time' => ['nullable', 'date'],
            'detour_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'fare_override_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
