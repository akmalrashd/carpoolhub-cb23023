<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GoogleRegisterController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('pending_google_signup');

        if (! $pending) {
            return redirect()->route('register');
        }

        return view('auth.complete-registration', [
            'name' => $pending['name'],
            'email' => $pending['email'],
        ]);
    }

    public function store(Request $request, ImageService $imageService): RedirectResponse
    {
        $pending = $request->session()->get('pending_google_signup');

        if (! $pending) {
            return redirect()->route('register');
        }

        // Another tab, or a second Google attempt, may have already created
        // this account while this form was open.
        if (User::where('email', $pending['email'])->exists()) {
            $request->session()->forget('pending_google_signup');

            return redirect()->route('login')->withErrors([
                'email' => 'That account already exists — please log in instead.',
            ]);
        }

        $data = $request->validate([
            'phone'                  => ['required', 'string', 'max:20'],
            'role'                   => ['required', 'in:passenger,driver'],
            'vehicle_model'          => ['required_if:role,driver', 'nullable', 'string', 'max:100'],
            'vehicle_plate'          => ['required_if:role,driver', 'nullable', 'string', 'max:20'],
            'driving_license_photo'  => ['required_if:role,driver', 'nullable', 'image', 'max:4096'],
            'selfie_photo'           => ['required_if:role,driver', 'nullable', 'image', 'max:5120'],
            'driving_license_expiry' => ['required_if:role,driver', 'nullable', 'date', 'after:today'],
        ], [
            'vehicle_model.required_if'         => 'Vehicle model is required for drivers.',
            'vehicle_plate.required_if'         => 'Vehicle plate is required for drivers.',
            'driving_license_photo.required_if' => 'Driving license photo is required for drivers.',
            'driving_license_photo.image'       => 'License photo must be an image (JPG, PNG, etc).',
            'driving_license_photo.max'         => 'License photo must not exceed 4MB.',
            'selfie_photo.required_if'          => 'A selfie holding your license is required for drivers.',
            'selfie_photo.image'                => 'Selfie photo must be an image (JPG, PNG, etc).',
            'selfie_photo.max'                  => 'Selfie photo must not exceed 5MB.',
            'driving_license_expiry.required_if' => 'Your license expiry date is required for drivers.',
            'driving_license_expiry.after'       => 'Your license appears to be already expired — please renew before registering as a driver.',
        ]);

        $isDriver = $data['role'] === 'driver';

        $licenseBase64 = null;
        $selfieBase64  = null;
        if ($isDriver && $request->hasFile('driving_license_photo')) {
            $licenseBase64 = $imageService->toCompressedBase64($request->file('driving_license_photo'), 1200, 80);
        }
        if ($isDriver && $request->hasFile('selfie_photo')) {
            $selfieBase64 = $imageService->toCompressedBase64($request->file('selfie_photo'), 800, 78);
        }

        $user = User::create([
            'name'                  => $pending['name'],
            'email'                 => $pending['email'],
            'google_id'             => $pending['google_id'],
            'profile_photo'         => $pending['avatar'],
            'email_verified_at'     => now(),
            'password'              => null,
            'phone'                 => $data['phone'],
            'role'                  => $data['role'],
            'vehicle_model'         => $data['vehicle_model'] ?? null,
            'vehicle_plate'         => $data['vehicle_plate'] ?? null,
            'driving_license_photo' => $licenseBase64,
            'selfie_photo'          => $selfieBase64,
            'driving_license_expiry' => $data['driving_license_expiry'] ?? null,
            'is_active'             => ! $isDriver,
            'driver_verification_status' => $isDriver ? 'pending' : null,
        ]);

        $request->session()->forget('pending_google_signup');

        if ($isDriver) {
            return redirect()->route('login')
                ->with('status', 'Your driver account has been created and is pending admin approval. You will be able to log in once approved.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
