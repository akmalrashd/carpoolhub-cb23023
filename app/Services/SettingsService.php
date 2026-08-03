<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingsService
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    public function updateProfile(User $user, array $data): User
    {
        // Every image is compressed on upload and stored as a base64 data URI in
        // the DB (see ImageService). Sizes chosen per use: avatars small, QR
        // mid + PNG to stay scannable, licence/selfie larger so they stay legible.
        $photoPath = $user->profile_photo;
        $duitnowQrPath = $user->payment_qr_duitnow;
        $tngQrPath = $user->payment_qr_tng;

        $selfiePath = $user->selfie_photo;
        $licensePath = $user->driving_license_photo;

        if (isset($data['profile_photo']) && $data['profile_photo'] instanceof UploadedFile) {
            $photoPath = $this->imageService->toCompressedBase64($data['profile_photo'], 512, 78);
        }
        if (isset($data['payment_qr_duitnow']) && $data['payment_qr_duitnow'] instanceof UploadedFile) {
            $duitnowQrPath = $this->imageService->toCompressedBase64($data['payment_qr_duitnow'], 600, 90, true);
        }
        if (isset($data['payment_qr_tng']) && $data['payment_qr_tng'] instanceof UploadedFile) {
            $tngQrPath = $this->imageService->toCompressedBase64($data['payment_qr_tng'], 600, 90, true);
        }
        if (isset($data['selfie_photo']) && $data['selfie_photo'] instanceof UploadedFile) {
            $selfiePath = $this->imageService->toCompressedBase64($data['selfie_photo'], 800, 78);
        }
        if (isset($data['driving_license_photo']) && $data['driving_license_photo'] instanceof UploadedFile) {
            $licensePath = $this->imageService->toCompressedBase64($data['driving_license_photo'], 1200, 80);
        }

        $name = array_key_exists('name', $data) ? (string) $data['name'] : $user->name;
        // Email is never changed through the profile form (see UpdateProfileRequest);
        // always keep the stored value so an injected email cannot take over the account.
        $email = $user->email;
        $emailVisible = array_key_exists('email_visible', $data)
            ? (string) $data['email_visible']
            : (string) ($user->email_visible ?: 'visible_friend');
        $phone = array_key_exists('phone', $data) ? ($data['phone'] ?: null) : $user->phone;
        $phoneVisible = array_key_exists('phone_visible', $data)
            ? (string) $data['phone_visible']
            : (string) ($user->phone_visible ?: 'visible_friend');
        $paymentAccountName = array_key_exists('payment_account_name', $data)
            ? (($data['payment_account_name'] !== '') ? $data['payment_account_name'] : null)
            : $user->payment_account_name;
        $paymentAccountNumber = array_key_exists('payment_account_number', $data)
            ? (($data['payment_account_number'] !== '') ? $data['payment_account_number'] : null)
            : $user->payment_account_number;
        $paymentBankName = array_key_exists('payment_bank_name', $data)
            ? (($data['payment_bank_name'] !== '') ? $data['payment_bank_name'] : null)
            : $user->payment_bank_name;
        $vehicleModel = array_key_exists('vehicle_model', $data)
            ? (($data['vehicle_model'] !== '') ? $data['vehicle_model'] : null)
            : $user->vehicle_model;
        $vehiclePlate = array_key_exists('vehicle_plate', $data)
            ? (($data['vehicle_plate'] !== '') ? strtoupper((string) $data['vehicle_plate']) : null)
            : $user->vehicle_plate;

        $user->update([
            'name' => $name,
            'email' => $email,
            'email_visible' => $emailVisible,
            'phone' => $phone,
            'phone_visible' => $phoneVisible,
            'vehicle_model' => $vehicleModel,
            'vehicle_plate' => $vehiclePlate,
            'profile_photo' => $photoPath,
            'payment_account_name' => $paymentAccountName,
            'payment_account_number' => $paymentAccountNumber,
            'payment_bank_name' => $paymentBankName,
            'payment_qr_duitnow' => $duitnowQrPath,
            'payment_qr_tng' => $tngQrPath,
            'selfie_photo' => $selfiePath,
            'driving_license_photo' => $licensePath,
        ]);

        return $user->refresh();
    }

    public function updatePassword(User $user, array $data): void
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => $data['new_password'],
        ]);

        // Changing the password must also invalidate any outstanding "remember
        // me" cookie. Laravel authenticates those against remember_token, not
        // the password, so without this a stolen cookie keeps working forever —
        // defeating the whole point of changing the password after a
        // compromise. The current session is re-authenticated by the caller.
        $user->setRememberToken(Str::random(60));
        $user->save();
    }

}
