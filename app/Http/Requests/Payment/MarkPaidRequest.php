<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class MarkPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:duitnow_qr,bank_account,digital_wallet,others'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
