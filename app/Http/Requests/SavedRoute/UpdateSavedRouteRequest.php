<?php

namespace App\Http\Requests\SavedRoute;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSavedRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'route_name' => ['nullable', 'string', 'max:255'],
            'point_a_name' => ['required', 'string', 'max:255'],
            'point_a_latitude' => ['required', 'numeric', 'between:-90,90'],
            'point_a_longitude' => ['required', 'numeric', 'between:-180,180'],
            'point_b_name' => ['required', 'string', 'max:255'],
            'point_b_latitude' => ['required', 'numeric', 'between:-90,90'],
            'point_b_longitude' => ['required', 'numeric', 'between:-180,180'],
            'default_fare' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'passenger_stops' => ['nullable', 'array'],
            'passenger_stops.*.user_id' => ['nullable', 'integer', 'exists:users,id', 'distinct'],
            'passenger_stops.*.pickup_name' => ['nullable', 'string', 'max:255'],
            'passenger_stops.*.pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'passenger_stops.*.pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'passenger_stops.*.dropoff_name' => ['nullable', 'string', 'max:255'],
            'passenger_stops.*.dropoff_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'passenger_stops.*.dropoff_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'passenger_stops.*.extra_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'passenger_stops.*.note' => ['nullable', 'string', 'max:255'],
            'passenger_stops.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
