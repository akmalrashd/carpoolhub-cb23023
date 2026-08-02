<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RefreshController;
use App\Http\Controllers\SavedRouteController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripJoinRequestController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::redirect('/dashboard', '/home')->name('dashboard');

    Route::resource('trips', TripController::class);
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore.index');
    Route::get('/explore/search', [ExploreController::class, 'search'])->name('explore.search');
    Route::get('/explore/{trip}', [ExploreController::class, 'show'])->name('explore.show');
    Route::post('/explore/{trip}/request-join', [ExploreController::class, 'requestJoin'])->name('explore.request-join');
    Route::patch('/explore/join-requests/{joinRequest}/cancel', [ExploreController::class, 'cancelRequest'])->name('explore.join-requests.cancel');

    Route::get('/trips/{trip}/requests', [TripJoinRequestController::class, 'index'])->name('trips.requests.index');
    Route::patch('/trips/{trip}/request-open', [TripJoinRequestController::class, 'toggleOpen'])->name('trips.requests.toggle-open');
    Route::patch('/trip-join-requests/{joinRequest}/respond', [TripJoinRequestController::class, 'respond'])->name('trips.join-requests.respond');
    Route::get('/connections', [ConnectionController::class, 'index'])->name('connections.index');
    Route::post('/connections/requests', [ConnectionController::class, 'store'])->name('connections.requests.store');
    Route::patch('/connections/{connection}/respond', [ConnectionController::class, 'respond'])->name('connections.respond');
    Route::delete('/connections/{user}/remove', [ConnectionController::class, 'remove'])->name('connections.remove');
    Route::resource('saved-routes', SavedRouteController::class)->except(['show']);
    Route::patch('/saved-routes/{savedRoute}/status', [SavedRouteController::class, 'toggleStatus'])->name('saved-routes.toggle-status');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::patch('/payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::patch('/payments/{payment}/confirm-paid', [PaymentController::class, 'confirmPaid'])->name('payments.confirm-paid');
    Route::patch('/payments/approve-all-pending', [PaymentController::class, 'approveAllPending'])->name('payments.approve-all-pending');
    Route::patch('/payments/{payment}/reject-paid', [PaymentController::class, 'rejectPaid'])->name('payments.reject-paid');
    Route::post('/payments/{payment}/send-reminder', [PaymentController::class, 'sendReminder'])->name('payments.send-reminder');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/clear-read', [NotificationController::class, 'clearRead'])->name('notifications.clear-read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/push/vapid-key', [PushController::class, 'vapidPublicKey'])->name('push.vapid-key');
    Route::post('/push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/profile', [SettingsController::class, 'index'])->name('profile.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])->middleware('throttle:6,1')->name('settings.password.update');

    Route::prefix('/ai')->group(function (): void {
        Route::post('/chat', [AiChatController::class, 'chat'])->name('ai.chat');
        Route::delete('/chat/history', [AiChatController::class, 'clearHistory'])->name('ai.chat.clear');
        Route::post('/fare-reason', [AiChatController::class, 'fareReason'])->name('ai.fare-reason');
        Route::post('/fare-advice', [AiChatController::class, 'fareAdvice'])->name('ai.fare-advice');
    });

    Route::prefix('/refresh')->group(function (): void {
        Route::get('/notifications/latest', [RefreshController::class, 'notificationsLatest'])->name('refresh.notifications.latest');
        Route::get('/trips/{trip}/requests', [RefreshController::class, 'tripRequests'])->name('refresh.trips.requests');
        Route::get('/trips/{trip}/status', [RefreshController::class, 'tripStatus'])->name('refresh.trips.status');
        Route::get('/payments/summary', [RefreshController::class, 'paymentsSummary'])->name('refresh.payments.summary');
    });

    Route::prefix('/admin')->middleware('role:admin')->group(function (): void {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/reports/export/csv', [AdminReportController::class, 'exportCsv'])->name('admin.reports.export.csv');
        Route::get('/reports/export/pdf', [AdminReportController::class, 'exportPdfView'])->name('admin.reports.export.pdf');
        Route::view('/system-settings', 'modules.coming-soon', ['module' => 'System Settings'])->name('admin.system-settings.index');
    });
});
