<?php

namespace App\Http\Requests\SavedRoute;

use Illuminate\Foundation\Http\FormRequest;

class RedeemSavedRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Codes are generated and stored uppercase (SavedRouteService::
     * generateShareCode()). The form uppercases as the user types, but that
     * only happens in the browser and is not a guarantee, so the value is
     * normalised here too and a lowercase paste or a raw POST still matches.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]{6}$/'],
        ];
    }
}
