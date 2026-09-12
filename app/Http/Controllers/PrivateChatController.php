<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Conversation;
use App\Models\Trip;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Private-trip group chat is driver-initiated, not automatic — see
 * ChatService::createPrivateGroup(). Public trips never touch this
 * controller; they get a conversation automatically via ChatService::
 * syncParticipants() as passengers are approved.
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
            'connection_user_ids' => ['nullable', 'array'],
            'connection_user_ids.*' => ['integer'],
        ]);

        try {
            $conversation = $this->chatService->createPrivateGroup(
                $trip,
                $request->user(),
                $validated['connection_user_ids'] ?? []
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('chats.show', $conversation);
    }

    public function invite(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureDriver($request, $trip);
        $conversation = $this->conversationForTripOrFail($trip);

        $validated = $request->validate([
            'connection_user_ids' => ['required', 'array', 'min:1'],
            'connection_user_ids.*' => ['integer'],
        ]);

        try {
            $this->chatService->addToPrivateGroup($conversation, $request->user(), $validated['connection_user_ids']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('chats.show', $conversation);
    }

    public function remove(Request $request, Trip $trip, int $user): RedirectResponse
    {
        $this->ensureDriver($request, $trip);
        $conversation = $this->conversationForTripOrFail($trip);

        $this->chatService->removeFromPrivateGroup($conversation, $request->user(), $user);

        return redirect()->route('chats.show', $conversation);
    }

    /**
     * Given how selectable connections for a private trip are already
     * scoped to the driver's accepted connections elsewhere (TripService::
     * getSelectableParticipants), this reuses that same list for the "who
     * can I invite" picker instead of re-deriving it.
     */
    public function pickerOptions(Request $request, Trip $trip): JsonResponse
    {
        $this->ensureDriver($request, $trip);

        $connections = Connection::acceptedUserIdsFor($request->user());

        return response()->json([
            'connections' => User::query()
                ->whereIn('id', $connections)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])
                ->values(),
        ]);
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

    private function conversationForTripOrFail(Trip $trip): Conversation
    {
        $conversation = Conversation::query()->where('trip_id', $trip->id)->first();

        if (! $conversation) {
            abort(404);
        }

        return $conversation;
    }
}
