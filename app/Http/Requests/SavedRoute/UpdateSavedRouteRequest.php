<?php

namespace App\Http\Requests\SavedRoute;

use App\Http\Requests\SavedRoute\Concerns\ValidatesSavedRoutePayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSavedRouteRequest extends FormRequest
{
    use ValidatesSavedRoutePayload;

    public function authorize(): bool
    {
        return auth()->check();
    }
}
