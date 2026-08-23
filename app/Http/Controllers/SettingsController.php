<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    public function index(Request $request): View
    {
        return view('settings.index', [
            'user' => $request->user(),
            'telegramConfigured' => ! empty(config('services.telegram.bot_token')),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse|JsonResponse
    {
        $this->settingsService->updateProfile($request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Profile updated successfully.',
            ]);
        }

        return redirect()
            ->route('profile.index')
            ->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        try {
            $this->settingsService->updatePassword($request->user(), $request->validated());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        // Sign out every other device after a password change, so a stolen or
        // shared session cannot outlive the new password. The current device
        // stays signed in. Uses the database session store directly (no extra
        // middleware); a no-op on any other session driver.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        // SettingsService just cycled remember_token, which invalidates every
        // outstanding "remember me" cookie — including this device's. Re-issue
        // one here (and only here) so the person who just changed their own
        // password keeps the "stay signed in" they had opted into, while every
        // other device's cookie stays dead.
        if ($request->cookies->has(Auth::guard()->getRecallerName())) {
            Auth::login($request->user(), true);
        }

        return redirect()
            ->route('profile.index')
            ->with('status', 'Password updated successfully.');
    }
}
