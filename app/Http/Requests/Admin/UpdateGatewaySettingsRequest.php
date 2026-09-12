<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGatewaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'gateway_fee_flat_amount' => ['required', 'numeric', 'min:0', 'max:50'],
            'wallet_min_withdrawal_amount' => ['required', 'numeric', 'min:0', 'max:1000'],
        ];
    }
}
