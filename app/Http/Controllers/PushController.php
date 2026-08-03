<?php

namespace App\Http\Controllers;

use App\Http\Requests\Push\StorePushSubscriptionRequest;
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

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $this->pushService->deleteSubscription($request->user(), $data['endpoint']);

        return response()->json(['success' => true]);
    }

    public function vapidPublicKey(): JsonResponse
    {
        return response()->json(['key' => config('app.vapid_public_key')]);
    }
}
