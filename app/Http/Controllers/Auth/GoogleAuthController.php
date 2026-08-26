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
    /**
     * Also reused from the settings page to connect Google to an already
     * logged-in account — ?purpose=link is how that call tells this method
     * apart from a plain login/register attempt. Stashed in the session
     * (not carried as a query param on the callback) because Google itself
     * controls what comes back on redirect.
     */
    public function redirect(Request $request): RedirectResponse
    {
        if ($request->query('purpose') === 'link' && Auth::check()) {
            $request->session()->put('google_auth_purpose', 'link_account');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $linking = $request->session()->pull('google_auth_purpose') === 'link_account';

        try {
            $googleUser = $this->googleUserFromCallback();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth user fetch failed', [
                'error' => $e->getMessage(),
                'type' => $e::class,
            ]);

            return $linking
                ? redirect(route('profile.index') . '#security')->withErrors(['google' => 'Google sign-in failed. Please try again.'])
                : redirect()->route('login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        if ($linking) {
            return $this->linkToCurrentUser($googleUser);
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

    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();
        $target = route('profile.index') . '#security';

        if (! $user->google_id) {
            return redirect($target);
        }

        // A Google-only account (password is null) has no other way back in
        // — disconnecting would lock them out on the spot.
        if (! $user->password) {
            return redirect($target)->withErrors([
                'google' => 'You signed up with Google and have no password set yet. Set a password first, then you can disconnect Google.',
            ]);
        }

        $user->update(['google_id' => null]);

        return redirect($target)->with('status', 'Google account disconnected.');
    }

    /**
     * The email match is the whole security model here: Google already
     * proved the visitor owns that inbox, so requiring it to equal the
     * signed-in account's email is what stops someone from linking a
     * Google account that isn't theirs (their own second account, a
     * friend's) onto this one and gaining a second way to log into it.
     */
    private function linkToCurrentUser(SocialiteUser $googleUser): RedirectResponse
    {
        $user = Auth::user();
        $target = route('profile.index') . '#security';

        if (! $user) {
            return redirect()->route('login');
        }

        if (strtolower($googleUser->getEmail()) !== strtolower($user->email)) {
            return redirect($target)->withErrors([
                'google' => "That Google account ({$googleUser->getEmail()}) doesn't match your CarpoolHub email ({$user->email}). Log in to Google with the same email to connect it.",
            ]);
        }

        $alreadyLinkedElsewhere = User::where('google_id', $googleUser->getId())
            ->where('id', '!=', $user->id)
            ->exists();

        if ($alreadyLinkedElsewhere) {
            return redirect($target)->withErrors([
                'google' => 'This Google account is already connected to a different CarpoolHub account.',
            ]);
        }

        $updates = ['google_id' => $googleUser->getId()];
        if (! $user->profile_photo && $googleUser->getAvatar()) {
            $updates['profile_photo'] = $googleUser->getAvatar();
        }
        if (! $user->email_verified_at) {
            $updates['email_verified_at'] = now();
        }
        $user->update($updates);

        return redirect($target)->with('status', 'Google account connected.');
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
