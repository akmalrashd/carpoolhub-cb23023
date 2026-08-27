<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'fuel_price_ron95_budi' => ['required', 'numeric', 'min:0', 'max:20'],
            'fuel_price_ron95_market' => ['required', 'numeric', 'min:0', 'max:20'],
            'fuel_price_ron97_market' => ['required', 'numeric', 'min:0', 'max:20'],
            'fuel_price_diesel_market' => ['required', 'numeric', 'min:0', 'max:20'],
        ];
    }
}
