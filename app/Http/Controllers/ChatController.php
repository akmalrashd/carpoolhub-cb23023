<?php

namespace App\Http\Controllers;

use Ably\AblyRest;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Trip;
use App\Models\User;
use App\Services\ChatService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function index(Request $request): View
    {
        return view('chats.index', $this->buildConversationsListData($request->user()));
    }

    /**
     * The chats.partials.list pane — same data shape whether it's rendering
     * full-width (chats/index) or as the desktop split-view's left column
     * (chats/show, see the "list on the left, thread on the right" layout).
     */
    private function buildConversationsListData(User $user): array
    {
        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id)->whereNull('left_at'))
            ->with([
                'driver',
                'participants' => fn ($query) => $query->whereNull('left_at')->with('user'),
                'messages' => fn ($query) => $query->latest('id')->limit(1)->with('sender'),
            ])
            ->orderByDesc(
                Message::query()->select('created_at')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest('id')->limit(1)
            )
            ->get();

        $unreadByConversation = ConversationParticipant::query()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->get()
            ->keyBy('conversation_id');

        return [
            'conversations' => $conversations,
            'unreadByConversation' => $unreadByConversation,
        ];
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $participant = $this->activeParticipantOrFail($request, $conversation);

        $conversation->load(['participants' => fn ($query) => $query->whereNull('left_at')->with('user'), 'trip']);

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->orderBy('id')
            ->get();

        $this->markRead($request, $conversation);

        return view('chats.show', array_merge(
            $this->buildConversationsListData($request->user()),
            [
                'conversation' => $conversation,
                'messages' => $messages,
                'isChatAdmin' => (bool) $participant->is_chat_admin,
                'isOpen' => ! $conversation->opens_at || now()->gte($conversation->opens_at),
                // Feeds the shared "Trip Details" popup (trips/partials/trip-details-modal
                // + public/js/trip-details-modal.js) — null once the trip itself has been
                // hard-deleted (cancelled), since there's nothing left to show.
                'tripModalData' => $conversation->trip ? $this->buildTripModalData($conversation->trip, $request->user()) : null,
            ]
        ));
    }

    /**
     * Keeps the header's "Trip Details" trigger button quietly up to date
     * while the thread stays open — its data-* attributes are only ever
     * read at the moment someone clicks it, so refreshing them in the
     * background (chats-show.js polls this) is enough to make both that
     * popup and "Manage requests" reflect whatever changed elsewhere
     * (a new join request, an approval, an edited trip) without the
     * viewer having to reload the page first.
     */
    public function tripModalRefresh(Request $request, Conversation $conversation): JsonResponse
    {
        $this->activeParticipantOrFail($request, $conversation);
        $conversation->load('trip');

        return response()->json([
            'trip_modal_data' => $conversation->trip ? $this->buildTripModalData($conversation->trip, $request->user()) : null,
        ]);
    }

    /**
     * Mirrors the per-row @php block in trips/index.blade.php that feeds the
     * same modal there — kept separate rather than extracted into a shared
     * service, since that page's own two copies of this logic (mobile card +
     * desktop table) are pre-existing and out of scope to touch here.
     */
    private function buildTripModalData(Trip $trip, User $viewer): array
    {
        $trip->loadMissing([
            'driver', 'savedRoute', 'returnTrip', 'participants.user', 'passengerRoutePoints.user',
            'joinRequests.user.riskProfile', 'joinRequests.routePoint', 'conversation',
        ]);

        $hasReturn = (bool) $trip->returnTrip;
        $pickupName = $trip->pickup_name ?? 'Pickup';
        $destinationName = $trip->destination_name ?? 'Destination';
        $directionText = $pickupName.' → '.$destinationName;
        $routeName = $trip->savedRoute?->route_name ?: $directionText;
        $combinedFare = (float) $trip->fare_total + (float) ($trip->returnTrip?->fare_total ?? 0);
        $tripRef = $trip->trip_ref ?: 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT);

        $participantPayload = $trip->participants
            ->map(fn ($participant) => [
                'user_id' => $participant->user_id,
                'name' => $participant->user?->name ?? '-',
                'email' => $participant->user?->email ?? '',
                'photo_url' => $participant->user?->profile_photo_url ?? null,
                'is_driver' => (bool) $participant->is_driver,
            ])
            ->values();

        $routePointPayload = $trip->passengerRoutePoints
            ->filter(fn ($point) => in_array((string) $point->status, ['accepted', 'approved'], true))
            ->filter(fn ($point) => ! $point->uses_default_pickup || ! $point->uses_default_dropoff)
            ->map(fn ($point) => [
                'name' => $point->user?->name ?? 'Passenger',
                'pickup' => $point->uses_default_pickup ? null : [
                    'lat' => $point->pickup_latitude !== null ? (float) $point->pickup_latitude : null,
                    'lng' => $point->pickup_longitude !== null ? (float) $point->pickup_longitude : null,
                    'label' => ($point->user?->name ?? 'Passenger').' pickup',
                ],
                'dropoff' => $point->uses_default_dropoff ? null : [
                    'lat' => $point->dropoff_latitude !== null ? (float) $point->dropoff_latitude : null,
                    'lng' => $point->dropoff_longitude !== null ? (float) $point->dropoff_longitude : null,
                    'label' => ($point->user?->name ?? 'Passenger').' drop-off',
                ],
            ])
            ->values();

        $passengerCount = (int) $trip->participants->where('is_driver', false)->count();
        $driverIncludedInSplit = ((int) $trip->participant_count) > $passengerCount;
        $splitType = $driverIncludedInSplit ? 'Driver Included in Fare Split' : 'Driver Excluded from Fare Split';

        $statusSlug = strtolower((string) $trip->status);
        $statusLabel = match ($statusSlug) {
            'scheduled' => 'Scheduled',
            'recorded' => 'Recorded',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'draft' => 'Draft',
            default => Str::headline($statusSlug),
        };

        $isAdmin = $viewer->role === 'admin';
        $canManage = $isAdmin || $viewer->id === $trip->driver_id;
        $canDelete = $isAdmin || ! in_array($trip->status, ['cancelled'], true);

        // Feeds the "Manage requests" trigger inside this same modal — same
        // gating and payload shape as the per-row @php block in
        // trips/index.blade.php that drives its own "Requests" button.
        $canManageTripPayment = in_array($viewer->role, ['admin', 'driver'], true)
            && ($isAdmin || $viewer->id === $trip->driver_id);
        $canManageRequests = $canManageTripPayment
            && ($trip->visibility ?? 'private') === 'public'
            && in_array($statusSlug, ['scheduled', 'recorded'], true);

        $requestsExtra = [
            'canManageRequests' => $canManageRequests ? '1' : '0',
        ];

        if ($canManageRequests) {
            $seatsTaken = $passengerCount;
            $seatsAvailable = $trip->seat_limit !== null
                ? ((int) $trip->seat_limit + ($driverIncludedInSplit ? 1 : 0))
                : ($trip->available_seats ?? '-');
            $seatsTakenDisplay = $seatsTaken + ($driverIncludedInSplit ? 1 : 0);
            $pendingRequestCount = (int) ($trip->joinRequests?->where('status', 'pending')->count() ?? 0);

            $requestPayload = $trip->joinRequests
                ->filter(fn ($joinRequest) => in_array((string) $joinRequest->status, ['pending', 'approved'], true))
                ->map(function ($joinRequest) use ($trip, $tripRef) {
                    $routePoint = $joinRequest->routePoint;
                    $participant = $trip->participants->firstWhere('user_id', $joinRequest->user_id);

                    return [
                        'id' => $joinRequest->id,
                        'passenger' => $joinRequest->user?->name ?: 'Passenger',
                        'initials' => collect(explode(' ', $joinRequest->user?->name ?: 'P'))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode(''),
                        'status' => (string) $joinRequest->status,
                        'requested_at' => $joinRequest->created_at?->diffForHumans() ?: '-',
                        'note' => $joinRequest->request_note ?: '',
                        'pickup' => $routePoint ? ($routePoint->uses_default_pickup ? 'Default pickup' : ($routePoint->pickup_name ?: 'Custom pickup')) : 'Default pickup',
                        'dropoff' => $routePoint ? ($routePoint->uses_default_dropoff ? 'Default drop-off' : ($routePoint->dropoff_name ?: 'Custom drop-off')) : 'Default drop-off',
                        'pickup_meta' => $routePoint && ! $routePoint->uses_default_pickup
                            ? trim(collect([
                                $routePoint->pickup_distance_km !== null ? number_format((float) $routePoint->pickup_distance_km, 2).' km from route' : null,
                                $routePoint->requested_pickup_time?->format('d M Y, H:i'),
                            ])->filter()->implode(' · '))
                            : "Uses driver's route starting point",
                        'dropoff_meta' => $routePoint && ! $routePoint->uses_default_dropoff
                            ? trim(collect([
                                $routePoint->dropoff_distance_km !== null ? number_format((float) $routePoint->dropoff_distance_km, 2).' km from route' : null,
                                $routePoint->detour_distance_km !== null ? 'Detour '.number_format((float) $routePoint->detour_distance_km, 2).' km' : null,
                            ])->filter()->implode(' · '))
                            : "Uses driver's route ending point",
                        'fare' => $routePoint?->extra_fee_amount !== null ? number_format((float) $routePoint->extra_fee_amount, 2) : null,
                        'detour_km' => $routePoint?->detour_distance_km !== null ? (float) $routePoint->detour_distance_km : null,
                        'detour_min' => $routePoint?->detour_duration_minutes !== null ? (int) $routePoint->detour_duration_minutes : null,
                        'deviationKm' => $routePoint?->detour_distance_km !== null ? (float) $routePoint->detour_distance_km : 0,
                        'name' => $joinRequest->user?->name ?: 'Passenger',
                        'pickup_point' => [
                            'lat' => $routePoint && ! $routePoint->uses_default_pickup && $routePoint->pickup_latitude !== null ? (float) $routePoint->pickup_latitude : null,
                            'lng' => $routePoint && ! $routePoint->uses_default_pickup && $routePoint->pickup_longitude !== null ? (float) $routePoint->pickup_longitude : null,
                            'label' => $routePoint && ! $routePoint->uses_default_pickup ? (($joinRequest->user?->name ?: 'Passenger').' pickup') : null,
                        ],
                        'dropoff_point' => [
                            'lat' => $routePoint && ! $routePoint->uses_default_dropoff && $routePoint->dropoff_latitude !== null ? (float) $routePoint->dropoff_latitude : null,
                            'lng' => $routePoint && ! $routePoint->uses_default_dropoff && $routePoint->dropoff_longitude !== null ? (float) $routePoint->dropoff_longitude : null,
                            'label' => $routePoint && ! $routePoint->uses_default_dropoff ? (($joinRequest->user?->name ?: 'Passenger').' drop-off') : null,
                        ],
                        'pickup_lat' => $routePoint?->pickup_latitude !== null ? (float) $routePoint->pickup_latitude : null,
                        'pickup_lng' => $routePoint?->pickup_longitude !== null ? (float) $routePoint->pickup_longitude : null,
                        'dropoff_lat' => $routePoint?->dropoff_latitude !== null ? (float) $routePoint->dropoff_latitude : null,
                        'dropoff_lng' => $routePoint?->dropoff_longitude !== null ? (float) $routePoint->dropoff_longitude : null,
                        'fit' => $routePoint?->route_fit_score !== null ? ((int) $routePoint->route_fit_score.'%') : null,
                        'fit_label' => $routePoint?->route_fit_label ?: 'Driver review',
                        'respond_url' => route('trips.join-requests.respond', $joinRequest),
                        'trip' => $tripRef,
                        'risk_score' => $joinRequest->user?->riskProfile?->risk_score ?? 70,
                        'risk_level' => $joinRequest->user?->riskProfile?->risk_level ?? 'Moderate Risk',
                        'risk_reliability' => $joinRequest->user?->riskProfile?->payment_reliability_score ?? 5.0,
                        'risk_cancelled' => $joinRequest->user?->riskProfile?->cancelled_request_count ?? 0,
                        'risk_absent' => $joinRequest->user?->riskProfile?->attendance_absent_count ?? 0,
                        'risk_unpaid' => $joinRequest->user?->riskProfile?->overdue_case_count ?? 0,
                        'attendance_status' => $participant?->attendance_status,
                        'attendance_note' => $participant?->attendance_note,
                        'remove_url' => route('trips.join-requests.remove', $joinRequest),
                        'absence_url' => route('trips.join-requests.mark-absent', $joinRequest),
                        'absence_available' => (bool) ($trip->trip_datetime && now()->gte($trip->trip_datetime->clone()->subMinutes(\App\Services\TripJoinRequestService::ABSENCE_WINDOW_MINUTES))),
                    ];
                })
                ->values();

            $requestsExtra['requestsB64'] = base64_encode($requestPayload->toJson());
            $requestsExtra['requestsSeats'] = is_numeric($seatsAvailable) ? max(0, $seatsAvailable - $seatsTakenDisplay) : '-';
            $requestsExtra['requestsIsOpenForRequest'] = $trip->is_open_for_request ? '1' : '0';
            $requestsExtra['requestsToggleUrl'] = route('trips.requests.toggle-open', $trip);
            $requestsExtra['requestsPendingCount'] = $pendingRequestCount;
        }

        return array_merge($requestsExtra, [
            'tripId' => $trip->id,
            'tripRef' => $tripRef,
            'routeName' => $routeName,
            'driverId' => $trip->driver_id,
            'driverName' => $trip->driver?->name ?: '-',
            'driverPhoto' => $trip->driver?->profile_photo_url,
            'driverEmail' => $trip->driver?->email ?: '',
            'driverWhatsappUrl' => $trip->driver?->whatsapp_url ?: '',
            'driverPhone' => $trip->driver?->whatsapp_digits ?: '',
            'visibility' => $trip->visibility ?? 'private',
            'chatUrl' => $trip->conversation ? route('chats.show', $trip->conversation) : '',
            'mode' => $hasReturn ? 'Two-Way' : 'One-Way',
            'status' => $statusLabel,
            'outboundDatetime' => $trip->trip_datetime?->format('Y-m-d H:i') ?: '-',
            'fareLabel' => 'Total Fare',
            'fareDisplay' => 'RM '.number_format($combinedFare, 2),
            'pickupName' => $pickupName,
            'pickupLat' => $trip->pickup_latitude ?? '',
            'pickupLng' => $trip->pickup_longitude ?? '',
            'destinationName' => $destinationName,
            'destinationLat' => $trip->destination_latitude ?? '',
            'destinationLng' => $trip->destination_longitude ?? '',
            'totalPassengers' => $passengerCount,
            'splitType' => $splitType,
            'participantsB64' => base64_encode($participantPayload->toJson()),
            'routePointsB64' => base64_encode($routePointPayload->toJson()),
            'canManage' => $canManage ? '1' : '0',
            'canDelete' => $canDelete ? '1' : '0',
            'editUrl' => route('trips.edit', $trip),
            'deleteUrl' => route('trips.destroy', $trip),
        ]);
    }

    /**
     * Returns the created message as JSON (not a redirect) — the composer
     * sends this via fetch and uses the response to reconcile its own
     * optimistic bubble, since the Ably echo of the same message (or the
     * polling fallback) would otherwise render it a second time. Both paths
     * key off the same message id, so whichever arrives first wins and the
     * other is a no-op.
     */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->activeParticipantOrFail($request, $conversation);

        $validated = $request->validate([
            'type' => ['nullable', 'in:text,image'],
            // The 400KB image ceiling really lives in ChatService::postMessage
            // (it needs the exact same check as the type-agnostic fallback
            // path) — this max:2000 only bites for plain text.
            'body' => ['required', 'string', Rule::when(($request->input('type') ?? 'text') !== 'image', ['max:2000'])],
        ]);

        try {
            $message = $this->chatService->postMessage(
                $conversation,
                $request->user(),
                $validated['body'],
                $validated['type'] ?? Message::TYPE_TEXT
            );
        } catch (ValidationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => [
            'id' => $message->id,
            'type' => $message->type,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'created_at' => $message->created_at?->toIso8601String(),
        ]]);
    }

    /**
     * Scopes an Ably token to exactly this conversation's channel —
     * see routes/channels.php for why this replaces Laravel's stock
     * /broadcasting/auth for this app.
     */
    /**
     * Scoped either to one conversation (the thread page passes
     * conversation_id) or to every conversation the caller is currently an
     * active participant of (the chat list page omits it, since it needs
     * one token covering all of its rows rather than one round-trip per row).
     */
    public function ablyToken(Request $request): JsonResponse
    {
        $conversationIdParam = $request->query('conversation_id');

        if ($conversationIdParam) {
            $conversation = Conversation::query()->findOrFail((int) $conversationIdParam);
            $this->activeParticipantOrFail($request, $conversation);
            $capability = ['private:conversation.'.$conversation->id => ['subscribe']];
        } else {
            $conversationIds = ConversationParticipant::query()
                ->where('user_id', $request->user()->id)
                ->whereNull('left_at')
                ->pluck('conversation_id');

            if ($conversationIds->isEmpty()) {
                return response()->json(['error' => 'No active conversations.'], 404);
            }

            $capability = [];
            foreach ($conversationIds as $id) {
                $capability['private:conversation.'.$id] = ['subscribe'];
            }
        }

        $key = config('broadcasting.connections.ably.key');
        if (! $key) {
            // Not configured yet (ABLY_KEY unset / BROADCAST_CONNECTION != ably)
            // — the composer/poll fallback still works without this, so fail
            // quietly rather than filling the log with a stack trace on every
            // chat page load.
            return response()->json(['error' => 'Realtime chat is not configured.'], 503);
        }

        $ably = new AblyRest(['key' => $key]);
        $tokenRequest = $ably->auth->createTokenRequest([
            'capability' => json_encode($capability),
            'clientId' => (string) $request->user()->id,
        ]);

        return response()->json($tokenRequest->toArray());
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse|null
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('left_at')
            ->first();

        if (! $participant) {
            return $request->wantsJson() ? response()->json(['ok' => false], 404) : null;
        }

        $latestId = Message::query()->where('conversation_id', $conversation->id)->max('id');
        if ($latestId) {
            $participant->update(['last_read_message_id' => $latestId]);
        }

        return $request->wantsJson() ? response()->json(['ok' => true]) : null;
    }

    /**
     * A plain-text export — one line per message in WhatsApp's own
     * "date, time - Sender: body" convention, ahead of a short metadata
     * header identifying exactly which conversation/trip it came from.
     * This is what the in-thread notice points users to before a
     * conversation is purged: something they can hand to admin that
     * still means something once the live chat itself is gone.
     */
    public function export(Request $request, Conversation $conversation): Response
    {
        $this->activeParticipantOrFail($request, $conversation);

        $conversation->load(['driver', 'participants.user']);
        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->orderBy('id')
            ->get();

        $tz = Trip::TIMEZONE;
        $fmt = fn (?Carbon $date) => $date ? $date->clone()->setTimezone($tz)->format('d/m/Y, g:i A') : '-';

        $members = $conversation->participants
            ->map(fn ($participant) => trim(
                ($participant->user?->name ?: 'Deleted user')
                .' ('.($participant->user?->email ?: '-').')'
                .($participant->left_at ? ' [left]' : '')
            ))
            ->implode('; ');

        $lines = [
            'CarpoolHub Chat Export',
            str_repeat('=', 40),
            'Conversation ID: '.$conversation->id,
            'Trip Reference: '.($conversation->trip_ref_snapshot ?: '-'),
            'Route: '.($conversation->route_snapshot ?: '-'),
            'Trip Date: '.$fmt($conversation->trip_datetime_snapshot),
            'Driver: '.($conversation->driver?->name ?: '-').' ('.($conversation->driver?->email ?: '-').')',
            'Members ('.$conversation->participants->count().'): '.$members,
            'Chat opened: '.$fmt($conversation->opens_at),
            'Scheduled deletion: '.($conversation->scheduled_purge_at ? $fmt($conversation->scheduled_purge_at) : 'Not yet scheduled'),
            'Exported by: '.$request->user()->name.' ('.$request->user()->email.') on '.$fmt(now()),
            str_repeat('=', 40),
            '',
        ];

        foreach ($messages as $message) {
            $timestamp = $fmt($message->created_at);

            if ($message->isSystem()) {
                $lines[] = "{$timestamp} - System: {$message->body}";

                continue;
            }

            $sender = $message->sender?->name ?? 'Deleted user';
            $body = $message->type === Message::TYPE_IMAGE ? '<Photo omitted>' : $message->body;
            $lines[] = "{$timestamp} - {$sender}: {$body}";
        }

        $filename = 'CarpoolHub Chat - '.preg_replace('/[^A-Za-z0-9 _-]/', '-', $conversation->trip_ref_snapshot ?: (string) $conversation->id).'.txt';

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
        ]);
    }

    private function activeParticipantOrFail(Request $request, Conversation $conversation): ConversationParticipant
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('left_at')
            ->first();

        if (! $participant) {
            abort(403);
        }

        return $participant;
    }
}
