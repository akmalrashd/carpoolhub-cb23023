<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:100'],
            'email'                  => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'                  => ['nullable', 'string', 'max:20'],
            'role'                   => ['required', 'in:passenger,driver'],
            'vehicle_model'          => ['required_if:role,driver', 'nullable', 'string', 'max:100'],
            'vehicle_plate'          => ['required_if:role,driver', 'nullable', 'string', 'max:20'],
            'driving_license_photo'  => ['required_if:role,driver', 'nullable', 'image', 'max:4096'],
            'selfie_photo'           => ['nullable', 'image', 'max:5120'],
            'password'               => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'vehicle_model.required_if'         => 'Vehicle model is required for drivers.',
            'vehicle_plate.required_if'         => 'Vehicle plate is required for drivers.',
            'driving_license_photo.required_if' => 'Driving license photo is required for drivers.',
            'driving_license_photo.image'       => 'License photo must be an image (JPG, PNG, etc).',
            'driving_license_photo.max'         => 'License photo must not exceed 4MB.',
            'selfie_photo.image'                => 'Selfie photo must be an image (JPG, PNG, etc).',
            'selfie_photo.max'                  => 'Selfie photo must not exceed 5MB.',
        ]);

        $isDriver = $data['role'] === 'driver';

        $licenseBase64 = null;
        $selfieBase64  = null;
        if ($isDriver && $request->hasFile('driving_license_photo')) {
            $file          = $request->file('driving_license_photo');
            $mime          = $file->getMimeType();
            $licenseBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }
        if ($isDriver && $request->hasFile('selfie_photo')) {
            $sfile        = $request->file('selfie_photo');
            $smime        = $sfile->getMimeType();
            $selfieBase64 = 'data:' . $smime . ';base64,' . base64_encode(file_get_contents($sfile->getRealPath()));
        }

        User::create([
            'name'                  => $data['name'],
            'email'                 => $data['email'],
            'phone'                 => $data['phone'] ?? null,
            'role'                  => $data['role'],
            'vehicle_model'         => $data['vehicle_model'] ?? null,
            'vehicle_plate'         => $data['vehicle_plate'] ?? null,
            'driving_license_photo' => $licenseBase64,
            'selfie_photo'          => $selfieBase64,
            'password'              => Hash::make($data['password']),
            'is_active'             => ! $isDriver,
        ]);

        if ($isDriver) {
            return redirect()->route('login')
                ->with('status', 'Your driver account has been created and is pending admin approval. You will be able to log in once approved.');
        }

        return redirect()->route('login')
            ->with('status', 'Account created successfully. You can now log in.');
    }
}
