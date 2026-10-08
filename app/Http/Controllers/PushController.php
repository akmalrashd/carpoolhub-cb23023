<?php

namespace App\Http\Controllers;

use App\Http\Requests\Push\StorePushSubscriptionRequest;
use App\Models\UserNotification;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function __construct(private readonly PushService $pushService) {}

    public function subscribe(StorePushSubscriptionRequest $request): JsonResponse
    {
        $this->pushService->saveSubscription(
            $request->user(),
            $request->validated(),
            $request->userAgent()
        );

        // Created through the model rather than a bulk insert so that
        // UserNotificationObserver fires and actually delivers this. The
        // notifyParticipants() docblock in TripService explains why that
        // matters.
        UserNotification::query()->create([
            'user_id'      => $request->user()->id,
            'type'         => 'system',
            'title'        => 'Browser Push Enabled',
            'message'      => 'Browser push notifications are now on for this device. Alerts will appear even while CarpoolHub is closed.',
            'related_type' => 'settings',
            'related_id'   => null,
            'is_read'      => false,
        ]);

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $this->pushService->deleteSubscription($request->user(), $data['endpoint']);

        // Deleted above first, so the observer's own push attempt for this
        // notification finds no subscription left and simply skips it.
        UserNotification::query()->create([
            'user_id'      => $request->user()->id,
            'type'         => 'system',
            'title'        => 'Browser Push Disabled',
            'message'      => "Browser push notifications are now off for this device. You'll still get in-app alerts, and Telegram if it's connected.",
            'related_type' => 'settings',
            'related_id'   => null,
            'is_read'      => false,
        ]);

        return response()->json(['success' => true]);
    }

    public function vapidPublicKey(): JsonResponse
    {
        return response()->json(['key' => config('app.vapid_public_key')]);
    }
}
