<?php

namespace App\Http\Controllers;

use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function __construct(private readonly PushService $pushService) {}

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'      => ['required', 'string'],
            'keys.p256dh'   => ['required', 'string'],
            'keys.auth'     => ['required', 'string'],
        ]);

        $this->pushService->saveSubscription(
            $request->user(),
            $data,
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
