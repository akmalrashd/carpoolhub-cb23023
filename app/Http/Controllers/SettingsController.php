<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return redirect()
            ->route('profile.index')
            ->with('status', 'Password updated successfully.');
    }
}
