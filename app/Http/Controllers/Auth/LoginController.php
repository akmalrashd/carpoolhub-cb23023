<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Throttle brute-force by email+IP. A legitimate login is untouched;
        // only the 6th+ failed attempt in a minute is blocked.
        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        // The email+IP key alone does not stop credential stuffing: one attempt
        // against each of a thousand different emails never trips it. This
        // second, per-IP counter does. The ceiling is deliberately high so a
        // shared NAT (campus, office) never sees it, and it is intentionally
        // NOT cleared on success — otherwise an attacker resets it at will by
        // logging into an account they own.
        $ipThrottleKey = 'login-ip|'.$request->ip();

        foreach ([$throttleKey => 5, $ipThrottleKey => 20] as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);

                return back()->withErrors([
                    'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ])->onlyInput('email');
            }
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);
            RateLimiter::hit($ipThrottleKey, 60);

            return back()->withErrors([
                'email' => 'Invalid credentials.',
            ])->onlyInput('email');
        }

        // Correct credentials — reset the counter.
        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        if (! $user->is_active) {
            if ($user->isDriverAwaitingSelfService()) {
                $request->session()->regenerate();

                return redirect()->route('settings.index')->with('status', $this->inactiveMessage($user));
            }

            Auth::logout();
            $request->session()->invalidate();

            return back()->withErrors(['email' => $this->inactiveMessage($user)])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function inactiveMessage(User $user): string
    {
        if ($user->role !== 'driver') {
            $reason = trim((string) $user->deactivation_reason);

            return $reason !== ''
                ? "Your account has been suspended. Please contact support. Reason: {$reason}"
                : 'Your account has been suspended. Please contact support.';
        }

        $driverReason = trim((string) $user->deactivation_reason);

        return match ($user->driver_verification_status) {
            'rejected' => 'Your driver application was rejected. Check your notifications, update your documents in Settings, and resubmit.',
            'approved' => $driverReason !== ''
                ? "Your driver account has been suspended. Please contact support. Reason: {$driverReason}"
                : 'Your driver account has been suspended. Please contact support.',
            default => 'Your driver account is pending admin approval.',
        };
    }
}
