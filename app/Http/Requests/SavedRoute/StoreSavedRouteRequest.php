<?php

namespace App\Http\Requests\SavedRoute;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedRouteRequest extends FormRequest
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
        ];
    }
}
