<?php

namespace App\Http\Requests\DriverRating;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'stars' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
