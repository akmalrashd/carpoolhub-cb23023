<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\GoogleRegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\FuelPriceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RefreshController;
use App\Http\Controllers\SavedRouteController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripJoinRequestController;
use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

// Local-only: open in a browser to see an email's actual rendered HTML
// without triggering the real flow that sends it. Returning a Mailable
// straight from a route is a stock Laravel dev trick — the framework
// renders it as if it were a view.
if (app()->environment('local')) {
    Route::get('/dev/preview/reset-password-email', function () {
        $user = User::first() ?? new User(['name' => 'Preview User', 'email' => 'preview@example.com']);

        return new ResetPasswordMail('preview-token', $user);
    })->name('dev.preview.reset-password-email');

    Route::get('/dev/preview/verify-email', function () {
        $user = User::first() ?? new User(['name' => 'Preview User', 'email' => 'preview@example.com']);

        return new VerifyEmailMail($user);
    })->name('dev.preview.verify-email');

    // Live sandbox for tuning the ambient honeycomb background (bg-pattern.css)
    // without touching CSS by hand — adjusts size/opacity/speed/animation style
    // via CSS custom properties on the same partial every real page includes,
    // so what looks right here is exactly what ships once the values are
    // copied into layouts/partials/bg-pattern.blade.php and bg-pattern.css.
    Route::view('/dev/bg-playground', 'dev.bg-playground')->name('dev.bg-playground');
}

// Public — no auth required, since a prospective user needs to read this
// before registering (linked from the sign-up form's consent text).
Route::view('/legal/terms', 'legal.terms')->name('legal.terms');

// Public — called by Telegram's servers, not the browser. No session to
// authenticate against; the X-Telegram-Bot-Api-Secret-Token header (checked
// inside the controller) is what verifies the caller instead. Excluded from
// CSRF verification in bootstrap/app.php for the same reason.
Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
    Route::post('/telegram/miniapp-auth', [TelegramController::class, 'miniAppAuth'])->middleware('throttle:20,1')->name('telegram.miniapp-auth');

    // Reached only via the Google callback below, for a brand-new email —
    // Google can't tell us role or (for a driver) vehicle/license, and role
    // has no edit path once an account exists, so this collects the rest
    // before the User row is actually created.
    Route::get('/register/complete', [GoogleRegisterController::class, 'show'])->name('register.complete');
    Route::post('/register/complete', [GoogleRegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.complete.store');
});

// Deliberately NOT inside the 'guest' group above: settings' "Connect
// Google" button sends an already-authenticated user through this exact
// same redirect/callback pair (with ?purpose=link) to link Google onto
// their existing account, so a logged-in visitor has to be able to reach it
// too — not just someone signing in or registering.
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Needs 'auth' (there must be a logged-in user to check/mark verified) but
// deliberately NOT 'active' or 'verified' — this IS the escape hatch an
// unverified user is sent to, so it can't itself require being verified.
Route::middleware('auth')->group(function (): void {
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('verification.send');
});

// No 'verified' here — logging out has to work from the verify-email
// prompt page too, which is exactly where 'verified' would send someone
// who isn't verified yet.
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'active', 'verified'])->group(function (): void {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::redirect('/dashboard', '/home')->name('dashboard');

    Route::delete('/trips/bulk-delete', [TripController::class, 'bulkDestroy'])->name('trips.bulk-destroy');
    Route::resource('trips', TripController::class);
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore.index');
    Route::get('/explore/search', [ExploreController::class, 'search'])->name('explore.search');
    Route::get('/explore/{trip}', [ExploreController::class, 'show'])->name('explore.show');
    Route::post('/explore/{trip}/request-join', [ExploreController::class, 'requestJoin'])->name('explore.request-join');
    Route::patch('/explore/join-requests/{joinRequest}/cancel', [ExploreController::class, 'cancelRequest'])->name('explore.join-requests.cancel');

    Route::get('/trips/{trip}/requests', [TripJoinRequestController::class, 'index'])->name('trips.requests.index');
    Route::patch('/trips/{trip}/request-open', [TripJoinRequestController::class, 'toggleOpen'])->name('trips.requests.toggle-open');
    Route::patch('/trip-join-requests/{joinRequest}/respond', [TripJoinRequestController::class, 'respond'])->name('trips.join-requests.respond');
    Route::patch('/trip-join-requests/{joinRequest}/remove', [TripJoinRequestController::class, 'remove'])->name('trips.join-requests.remove');
    Route::patch('/trip-join-requests/{joinRequest}/mark-absent', [TripJoinRequestController::class, 'markAbsent'])->name('trips.join-requests.mark-absent');
    Route::patch('/trip-join-requests/{joinRequest}/cancel', [TripJoinRequestController::class, 'cancel'])->name('trips.join-requests.cancel');
    Route::patch('/trips/{trip}/leave', [TripJoinRequestController::class, 'leave'])->name('trips.leave');
    Route::get('/connections', [ConnectionController::class, 'index'])->name('connections.index');
    Route::post('/connections/requests', [ConnectionController::class, 'store'])->name('connections.requests.store');
    Route::patch('/connections/{connection}/respond', [ConnectionController::class, 'respond'])->name('connections.respond');
    Route::delete('/connections/{connection}/cancel', [ConnectionController::class, 'cancel'])->name('connections.cancel');
    Route::delete('/connections/{user}/remove', [ConnectionController::class, 'remove'])->name('connections.remove');
    Route::resource('saved-routes', SavedRouteController::class)->except(['show']);
    Route::patch('/saved-routes/{savedRoute}/status', [SavedRouteController::class, 'toggleStatus'])->name('saved-routes.toggle-status');
    Route::post('/saved-routes/redeem', [SavedRouteController::class, 'redeem'])->name('saved-routes.redeem');
    Route::get('/fuel-prices/current', [FuelPriceController::class, 'current'])->name('fuel-prices.current');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/outstanding', [PaymentController::class, 'outstanding'])->name('payments.outstanding');
    Route::patch('/payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::patch('/payments/{payment}/confirm-paid', [PaymentController::class, 'confirmPaid'])->name('payments.confirm-paid');
    Route::patch('/payments/bulk-confirm', [PaymentController::class, 'bulkConfirm'])->name('payments.bulk-confirm');
    Route::patch('/payments/bulk-reject', [PaymentController::class, 'bulkReject'])->name('payments.bulk-reject');
    Route::patch('/payments/approve-all-pending', [PaymentController::class, 'approveAllPending'])->name('payments.approve-all-pending');
    Route::patch('/payments/{payment}/reject-paid', [PaymentController::class, 'rejectPaid'])->name('payments.reject-paid');
    Route::post('/payments/{payment}/send-reminder', [PaymentController::class, 'sendReminder'])->name('payments.send-reminder');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/clear-read', [NotificationController::class, 'clearRead'])->name('notifications.clear-read');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.delete-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/push/vapid-key', [PushController::class, 'vapidPublicKey'])->name('push.vapid-key');
    Route::post('/push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::post('/telegram/link', [TelegramController::class, 'link'])->name('telegram.link');
    Route::post('/telegram/unlink', [TelegramController::class, 'unlink'])->name('telegram.unlink');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/profile', [SettingsController::class, 'index'])->name('profile.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])->middleware('throttle:6,1')->name('settings.password.update');
    Route::post('/settings/google/unlink', [GoogleAuthController::class, 'unlink'])->name('settings.google.unlink');

    // Every route below except clearHistory bills a real Anthropic API call, so
    // they share the 'ai-spend' limiter (registered in AppServiceProvider) —
    // one bucket per user across all three routes, not one bucket each, plus
    // a daily cap. 30/min is far above interactive use (the chat box is one
    // request per typed message) but caps a scripted loop from draining the
    // API budget.
    Route::prefix('/ai')->group(function (): void {
        Route::post('/chat', [AiChatController::class, 'chat'])->middleware('throttle:ai-spend')->name('ai.chat');
        Route::delete('/chat/history', [AiChatController::class, 'clearHistory'])->name('ai.chat.clear');
        Route::post('/fare-advice', [AiChatController::class, 'fareAdvice'])->middleware('throttle:ai-spend')->name('ai.fare-advice');
        Route::post('/recommend-route', [AiChatController::class, 'recommendRoute'])->middleware('throttle:ai-spend')->name('ai.recommend-route');
    });

    // Live-polled JSON — must never be cached by the browser or an
    // intermediary (Hostinger's CDN sits in front of this app), or every
    // client polling one of these keeps getting the same stale snapshot
    // regardless of how often it actually re-requests it.
    Route::prefix('/refresh')->middleware('no-cache')->group(function (): void {
        Route::get('/notifications/latest', [RefreshController::class, 'notificationsLatest'])->name('refresh.notifications.latest');
        Route::get('/trips/{trip}/requests', [RefreshController::class, 'tripRequests'])->name('refresh.trips.requests');
        Route::get('/trips/{trip}/status', [RefreshController::class, 'tripStatus'])->name('refresh.trips.status');
        Route::get('/payments/summary', [RefreshController::class, 'paymentsSummary'])->name('refresh.payments.summary');
    });

    Route::prefix('/admin')->middleware('role:admin')->group(function (): void {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::patch('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('admin.users.approve');
        Route::patch('/users/{user}/reject', [AdminUserController::class, 'reject'])->name('admin.users.reject');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/reports/export/csv', [AdminReportController::class, 'exportCsv'])->name('admin.reports.export.csv');
        Route::get('/reports/export/pdf', [AdminReportController::class, 'exportPdfView'])->name('admin.reports.export.pdf');
        Route::view('/system-settings', 'modules.coming-soon', ['module' => 'System Settings'])->name('admin.system-settings.index');
    });
});
