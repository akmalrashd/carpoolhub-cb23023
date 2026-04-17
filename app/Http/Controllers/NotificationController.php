<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): View
    {
        $notifications = $this->notificationService->paginateForUser($request->user());
        $unreadCount = $this->notificationService->unreadCount($request->user());

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->notificationService->markAsRead($request->user(), $notification);

        return back()->with('status', 'Notification marked as read.');
    }

    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->notificationService->markAsRead($request->user(), $notification);

        return redirect()->to($notification->target_url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return back()->with('status', 'All notifications marked as read.');
    }
}
