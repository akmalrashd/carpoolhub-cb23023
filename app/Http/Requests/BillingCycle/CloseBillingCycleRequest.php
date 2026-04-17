<?php

namespace App\Http\Requests\BillingCycle;

use Illuminate\Foundation\Http\FormRequest;

class CloseBillingCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [];
    }
}

