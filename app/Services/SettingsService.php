<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SettingsService
{
    public function updateProfile(User $user, array $data): User
    {
        $photoPath = $user->profile_photo;
        $duitnowQrPath = $user->payment_qr_duitnow;
        $tngQrPath = $user->payment_qr_tng;

        if (isset($data['profile_photo']) && $data['profile_photo'] instanceof UploadedFile) {
            $photoPath = $this->storeProfilePhoto($user, $data['profile_photo']);
        }
        if (isset($data['payment_qr_duitnow']) && $data['payment_qr_duitnow'] instanceof UploadedFile) {
            $duitnowQrPath = $this->storePaymentQr($user->payment_qr_duitnow, $data['payment_qr_duitnow']);
        }
        if (isset($data['payment_qr_tng']) && $data['payment_qr_tng'] instanceof UploadedFile) {
            $tngQrPath = $this->storePaymentQr($user->payment_qr_tng, $data['payment_qr_tng']);
        }

        $name = array_key_exists('name', $data) ? (string) $data['name'] : $user->name;
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

        $user->update([
            'name' => $name,
            'email' => $email,
            'email_visible' => $emailVisible,
            'phone' => $phone,
            'phone_visible' => $phoneVisible,
            'profile_photo' => $photoPath,
            'payment_account_name' => $paymentAccountName,
            'payment_account_number' => $paymentAccountNumber,
            'payment_bank_name' => $paymentBankName,
            'payment_qr_duitnow' => $duitnowQrPath,
            'payment_qr_tng' => $tngQrPath,
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
    }

    private function storeProfilePhoto(User $user, UploadedFile $file): string
    {
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        return $file->store('profile-photos', 'public');
    }

    private function storePaymentQr(?string $existingPath, UploadedFile $file): string
    {
        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        return $file->store('payment-qr', 'public');
    }
}
