<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            // Email is the login identifier and is shown read-only in the UI, so
            // it is never submitted here. It is deliberately NOT accepted: a
            // hijacked session could otherwise POST a new email and silently take
            // over the account. Email changes must go through a dedicated,
            // password-gated flow if ever added. SettingsService also forces the
            // stored email, as defence in depth.
            'email_visible' => ['nullable', 'string', Rule::in(['visible_public', 'visible_friend', 'unvisible'])],
            'whatsapp_country_code' => ['nullable', 'string', 'regex:/^\+\d{1,4}$/', 'required_with:whatsapp_number'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_visible' => ['nullable', 'string', Rule::in(['visible_public', 'visible_friend', 'unvisible'])],
            'vehicle_model' => ['nullable', 'string', 'max:80'],
            'vehicle_plate' => ['nullable', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'payment_account_name' => ['nullable', 'string', 'max:255'],
            'payment_account_number' => ['nullable', 'string', 'max:50'],
            'payment_bank_name' => ['nullable', 'string', 'max:255'],
            'payment_qr_duitnow' => ['nullable', 'image', 'max:2048'],
            'payment_qr_tng'      => ['nullable', 'image', 'max:2048'],
            'selfie_photo'        => ['nullable', 'image', 'max:5120'],
            // The settings form posts this field and SettingsService handles it,
            // but with no rule here it was stripped from validated() and silently
            // discarded, so licence uploads never saved. Validate so it works.
            'driving_license_photo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $countryCode = (string) $this->input('whatsapp_country_code', '');
        $number = (string) $this->input('whatsapp_number', '');

        if ($countryCode !== '' || $number !== '') {
            $normalized = $this->normalizeWhatsappPhone($countryCode, $number);
            $this->merge([
                'phone' => $normalized,
            ]);
        }
    }

    private function normalizeWhatsappPhone(string $countryCode, string $number): ?string
    {
        $codeDigits = preg_replace('/\D+/', '', $countryCode);
        $numberDigits = preg_replace('/\D+/', '', $number);

        if (! $numberDigits) {
            return null;
        }

        // Local numbers usually start with 0; remove it after country code selection.
        $numberDigits = ltrim($numberDigits, '0');

        if (! $codeDigits) {
            return null;
        }

        return '+' . $codeDigits . $numberDigits;
    }
}
