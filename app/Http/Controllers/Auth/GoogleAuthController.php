<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = $this->googleUserFromCallback();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth user fetch failed', [
                'error' => $e->getMessage(),
                'type' => $e::class,
            ]);

            return redirect()->route('login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $request->session()->put('pending_google_signup', [
                'name' => $googleUser->getName() ?? $googleUser->getNickname(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            return redirect()->route('register.complete');
        }

        $updates = [];
        if (! $user->google_id) {
            $updates['google_id'] = $googleUser->getId();
        }
        if (! $user->profile_photo && $googleUser->getAvatar()) {
            $updates['profile_photo'] = $googleUser->getAvatar();
        }
        if (! $user->email_verified_at) {
            $updates['email_verified_at'] = now();
        }
        if ($updates) {
            $user->update($updates);
        }

        // Same gate LoginController::store() enforces for a password login —
        // Google sign-in must not be a side door around driver approval.
        if (! $user->is_active) {
            $message = $user->role === 'driver'
                ? 'Your driver account is pending admin approval.'
                : 'Your account has been deactivated. Please contact support.';

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function googleUserFromCallback(): SocialiteUser
    {
        try {
            return Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            Log::warning('Google OAuth state mismatch; retrying stateless callback', [
                'error' => $e->getMessage(),
            ]);

            return Socialite::driver('google')->stateless()->user();
        }
    }
}
