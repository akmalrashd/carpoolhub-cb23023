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
        $request->validate([
            'filter' => ['nullable', 'in:all,unread,trip,payment,connection,system'],
        ]);

        $filter      = $request->input('filter', 'all');
        $tabCounts   = $this->notificationService->tabCountsForUser($request->user());
        $notifications = $this->notificationService->paginateForUser($request->user(), 15, $filter);
        $unreadCount = $tabCounts['unread'];

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter', 'tabCounts'));
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
