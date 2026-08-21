<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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
            'filter' => ['nullable', 'in:all,unread,trip,payment,connection,system,route'],
        ]);

        $filter        = $request->input('filter', 'all');
        $tabCounts     = $this->notificationService->tabCountsForUser($request->user());
        $notifications = $this->notificationService->paginateForUser($request->user(), 15, $filter);
        $unreadCount   = $tabCounts['unread'];

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter', 'tabCounts'));
    }

    public function markRead(Request $request, UserNotification $notification): RedirectResponse|JsonResponse
    {
        $this->notificationService->markAsRead($request->user(), $notification);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->notificationService->markAsRead($request->user(), $notification);

        return redirect()->to($notification->target_url);
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'All notifications marked as read.');
    }

    public function destroy(Request $request, UserNotification $notification): RedirectResponse|JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);
        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Notification deleted.');
    }

    public function clearRead(Request $request): RedirectResponse|JsonResponse
    {
        $this->notificationService->clearReadNotifications($request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Read notifications cleared.');
    }

    public function deleteAll(Request $request): RedirectResponse|JsonResponse
    {
        $this->notificationService->deleteAllNotifications($request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'All notifications deleted.');
    }
}
