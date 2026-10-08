<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SystemSetting;
use App\Models\Trip;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The group chat on a private trip is started by the driver rather than
 * created automatically, which is handled by ChatService::createCircle() and
 * linkCircleToTrip(). Public trips never reach this controller, because they
 * get their conversation from ChatService::syncParticipants() as passengers
 * are approved.
 *
 * Once a conversation exists, inviting, removing members, loading the picker
 * options and managing the circle all live on ChatController instead, where
 * the route is keyed by conversation. A circle outlives whichever trip it is
 * linked to at the moment, so those actions cannot rely on a trip id in the
 * URL still being the right one.
 */
class PrivateChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function create(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureDriver($request, $trip);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $conversation = $this->chatService->createCircle(
                $trip,
                $request->user(),
                $validated['name'] ?? null
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('chats.show', $conversation);
    }

    /**
     * Feeds the "start group chat" chooser: 0 circles → skip straight to a
     * one-tap create; ≥1 → let the driver reuse one instead of starting from
     * scratch with the same people all over again.
     */
    public function circleOptions(Request $request, Trip $trip): JsonResponse
    {
        $this->ensureDriver($request, $trip);

        $driver = $request->user();
        $maxCircles = (int) (SystemSetting::get('chat_max_circles_per_driver') ?? 5);

        $circles = Conversation::query()
            ->where('driver_id', $driver->id)
            ->where('is_circle', true)
            ->withCount(['participants' => fn ($query) => $query->whereNull('left_at')])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'circles' => $circles->map(fn (Conversation $circle) => [
                'id' => $circle->public_id,
                'name' => $circle->name ?: ($circle->route_snapshot ?: 'Circle chat'),
                'member_count' => $circle->participants_count,
                'linked_trip_label' => $circle->trip_id ? $circle->trip_ref_snapshot : 'Not linked to a trip',
            ])->values(),
            'at_cap' => $circles->count() >= $maxCircles,
            'cap_limit' => $maxCircles,
        ]);
    }

    public function linkCircle(Request $request, Trip $trip, Conversation $conversation): RedirectResponse
    {
        $this->ensureDriver($request, $trip);

        try {
            $this->chatService->linkCircleToTrip($conversation, $trip, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('chats.show', $conversation);
    }

    private function ensureDriver(Request $request, Trip $trip): void
    {
        if ((int) $trip->driver_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($trip->visibility !== 'private') {
            abort(403, 'Group chat creation here is only for private trips.');
        }
    }
}
