{{-- The swappable half of the chat split view — everything that depends on
     which specific conversation is open. Rendered two ways by
     ChatController::show(): as part of the full chats/show.blade.php page
     on a direct URL load, or alone (JSON-wrapped) when
     chat-thread-controller.js's mount() fetches it after a chat-row click,
     to swap in without a page reload (see that file's header comment).

     Everything conversation-agnostic — the shared modals (Trip Details,
     Rate Trip, Manage/My Request, Invite Connections) and the photo
     lightbox, plus their JS — deliberately live in the page SHELL instead
     (chats/index.blade.php / chats/show.blade.php), loaded once, not in
     here: they already delegate their trigger bindings on `document` and
     read everything from the clicked trigger's data-* attributes (same
     pattern as trips/index.blade.php), so re-including their markup+script
     on every swap would only pile up duplicate hidden modals and duplicate
     listeners for no benefit. This partial keeps only what's genuinely
     per-conversation: the header, banner, message list, and composer, plus
     the one script (chats-show.js) that actually needs a fresh run per
     conversation (polling/Ably/composer are conversation-scoped state). --}}
@php
    $me = $me ?? auth()->user();
    $others = $conversation->participants->filter(fn ($p) => $p->user_id !== $me->id)->values();
    $memberCount = $conversation->participants->count();
    $othersExtra = max(0, $others->count() - 3);
    $driverVehicle = array_filter([$conversation->driver?->vehicle_plate, $conversation->driver?->vehicle_model]);
    $titleParts = $conversation->is_circle
        ? array_filter([$conversation->name ?: 'Circle chat'])
        : array_filter([
            $conversation->trip_ref_snapshot,
            $conversation->route_snapshot ?: 'Trip chat',
        ]);
    $subParts = array_filter([
        $conversation->driver ? 'Driver: '.$conversation->driver->name : null,
        $driverVehicle ? implode(' · ', $driverVehicle) : null,
        $memberCount.' '.\Illuminate\Support\Str::plural('member', $memberCount),
    ]);
    // Conversation-scoped, not trip-scoped — a circle outlives whichever
    // trip it's currently linked to, so these can't depend on trip_id.
    $pickerOptionsUrl = route('chats.picker-options', $conversation);
    $inviteUrl = route('chats.invite', $conversation);

    $chatTz = \App\Models\Trip::TIMEZONE;
    $deletionIsEstimate = false;
    if ($conversation->is_circle) {
        $deletionAt = null;
    } elseif ($conversation->scheduled_purge_at) {
        $deletionAt = $conversation->scheduled_purge_at->clone()->setTimezone($chatTz);
    } elseif ($conversation->trip_datetime_snapshot) {
        $retentionDays = (int) (\App\Models\SystemSetting::get('chat_retention_days_after') ?? 30);
        $deletionAt = $conversation->trip_datetime_snapshot->clone()->setTimezone($chatTz)->addDays($retentionDays);
        $deletionIsEstimate = true;
    } else {
        $deletionAt = null;
    }
    $circleMessageRetentionDays = (int) (\App\Models\SystemSetting::get('chat_circle_message_retention_days') ?? 60);
@endphp
<div class="chat-split-thread">
<div class="chat-thread-page">
<div class="chat-thread-freeze">
<div class="chat-thread-header" id="chatThreadHead">
    <a href="{{ route('chats.index') }}" class="chat-thread-back" aria-label="Back to chats">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
    <button
        type="button"
        class="chat-thread-info {{ $tripModalData ? 'open-trip-modal-btn' : '' }}"
        @if(! $tripModalData) disabled @endif
        @if($tripModalData)
            data-trip-id="{{ $tripModalData['tripId'] }}"
            data-trip-ref="{{ $tripModalData['tripRef'] }}"
            data-route-name="{{ $tripModalData['routeName'] }}"
            data-driver-name="{{ $tripModalData['driverName'] }}"
            data-driver-id="{{ $tripModalData['driverId'] }}"
            data-driver-photo="{{ $tripModalData['driverPhoto'] }}"
            data-driver-email="{{ $tripModalData['driverEmail'] }}"
            data-driver-whatsapp-url="{{ $tripModalData['driverWhatsappUrl'] }}"
            data-visibility="{{ $tripModalData['visibility'] }}"
            data-chat-url="{{ $tripModalData['chatUrl'] }}"
            data-driver-phone="{{ $tripModalData['driverPhone'] }}"
            data-mode="{{ $tripModalData['mode'] }}"
            data-status="{{ $tripModalData['status'] }}"
            data-outbound-datetime="{{ $tripModalData['outboundDatetime'] }}"
            data-fare-label="{{ $tripModalData['fareLabel'] }}"
            data-fare-display="{{ $tripModalData['fareDisplay'] }}"
            data-pickup-name="{{ $tripModalData['pickupName'] }}"
            data-pickup-lat="{{ $tripModalData['pickupLat'] }}"
            data-pickup-lng="{{ $tripModalData['pickupLng'] }}"
            data-destination-name="{{ $tripModalData['destinationName'] }}"
            data-destination-lat="{{ $tripModalData['destinationLat'] }}"
            data-destination-lng="{{ $tripModalData['destinationLng'] }}"
            data-total-passengers="{{ $tripModalData['totalPassengers'] }}"
            data-split-type="{{ $tripModalData['splitType'] }}"
            data-participants-b64="{{ $tripModalData['participantsB64'] }}"
            data-route-points-b64="{{ $tripModalData['routePointsB64'] }}"
            data-can-manage="{{ $tripModalData['canManage'] }}"
            data-can-delete="{{ $tripModalData['canDelete'] }}"
            data-edit-url="{{ $tripModalData['editUrl'] }}"
            data-delete-url="{{ $tripModalData['deleteUrl'] }}"
            data-can-manage-requests="{{ $tripModalData['canManageRequests'] }}"
            @if($tripModalData['canManageRequests'] === '1')
                data-requests-b64="{{ $tripModalData['requestsB64'] }}"
                data-requests-seats="{{ $tripModalData['requestsSeats'] }}"
                data-requests-is-open-for-request="{{ $tripModalData['requestsIsOpenForRequest'] }}"
                data-requests-toggle-url="{{ $tripModalData['requestsToggleUrl'] }}"
                data-requests-pending-count="{{ $tripModalData['requestsPendingCount'] }}"
            @endif
        @endif
    >
        <span class="chat-thread-avatars">
            @forelse($others->take(3) as $participant)
                <x-avatar :user="$participant->user" size="sm" class="chat-thread-avatar" />
            @empty
                <x-avatar :user="$conversation->driver" size="sm" class="chat-thread-avatar" />
            @endforelse
            @if($othersExtra > 0)
                <span class="chat-thread-avatar chat-thread-avatar-badge">+{{ $othersExtra }}</span>
            @endif
        </span>
        <span class="chat-thread-headtext">
            <span class="chat-thread-title">{{ implode(' · ', $titleParts) }}</span>
            <span class="chat-thread-sub">{{ implode(' · ', $subParts) }}</span>
        </span>
    </button>
    @if($isChatAdmin && $conversation->isPrivate())
        <button type="button" class="chat-thread-invite-btn" id="chatInviteBtn" aria-label="Invite connections" title="Invite connections" data-picker-options-url="{{ $pickerOptionsUrl }}" data-invite-url="{{ $inviteUrl }}">
            <i class="fa-solid fa-user-plus"></i>
        </button>
    @endif
    @if($isChatAdmin && $conversation->is_circle)
        <form method="POST" action="{{ route('chats.circle.destroy', $conversation) }}" onsubmit="return confirm('Retire this circle? Its messages and membership will be permanently deleted, and you\'ll need to start a new one for future trips with this group.');" style="margin:0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="chat-thread-invite-btn" aria-label="Retire circle" title="Retire circle">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </form>
    @endif
</div>

<div class="chat-thread-banner">
    <div class="chat-thread-banner-head">
        <i class="fa-solid fa-shield-halved chat-thread-banner-icon"></i>
        <p class="chat-thread-banner-headline">
            @if($conversation->is_circle)
                This is a persistent circle chat and won't be deleted — messages older than {{ $circleMessageRetentionDays }} days are cleared automatically.
            @elseif($deletionAt)
                This chat {{ $deletionIsEstimate ? 'will be deleted around' : 'will be deleted on' }}
                <strong>{{ $deletionAt->format('d M Y, g:i A') }}</strong>.
            @else
                This chat will be deleted a few days after the trip ends.
            @endif
        </p>
    </div>
    <div class="chat-thread-banner-foot">
        <details class="chat-thread-banner-details">
            <summary>Why is this chat monitored?</summary>
            <p>CarpoolHub admin can view this chat to help prevent scams. Please do all trip related communication here, not on other apps such as WhatsApp. Messages sent outside this chat cannot be used as evidence.</p>
            @if($conversation->is_circle)
                <p>Export this chat and report any issue to admin any time — messages older than {{ $circleMessageRetentionDays }} days are cleared automatically, so export regularly if you need a full record.</p>
            @else
                <p>Export this chat and report any issue to admin before the date above. Once the chat is deleted, admin has nothing left to check, so reports made after that date cannot be investigated.</p>
            @endif
        </details>
        <a href="{{ route('chats.export', $conversation) }}" class="chat-thread-export-btn">
            <i class="fa-solid fa-file-export"></i> Export Chat
        </a>
    </div>
</div>
</div>

<div class="chat-thread-messages" id="chatMessages">
    {{-- margin-top:auto on this inner wrapper (not justify-content:flex-end
         on the scroller above) is what pins a short conversation to the
         bottom — flex-end + overflow is a known trap: once content
         overflows, the start-side overflow lands in negative scroll
         space browsers won't let you scroll into, permanently hiding
         the earliest messages. auto margins collapse to 0 instead, so a
         long conversation just scrolls normally from the very top. --}}
    <div class="chat-thread-messages-inner" id="chatMessagesInner">
    @php
        $tz = \App\Models\Trip::TIMEZONE;
        $lastDateKey = null;
        $unreadSeparatorShown = false;
    @endphp
    @foreach($messages as $message)
        @php
            $isOwn = $message->sender_id === $me->id;
            $localCreatedAt = $message->created_at?->clone()->setTimezone($tz);
            $dateKey = $localCreatedAt?->format('Y-m-d');
            $showDateSeparator = $dateKey && $dateKey !== $lastDateKey;
            if ($showDateSeparator) {
                $lastDateKey = $dateKey;
                $dateSeparatorLabel = \App\Support\ChatDateLabel::forDate($localCreatedAt);
            }
            // Marks the first message that was still unread the moment
            // this thread was opened (captured in ChatController::show()
            // before its own markRead() call moves the watermark) — null
            // only on a participant's very first-ever visit, where
            // there's no "catching up" concept yet. chats-show.js scrolls
            // here instead of the very bottom, WhatsApp-style.
            $showUnreadSeparator = ! $unreadSeparatorShown
                && $priorLastReadMessageId !== null
                && $message->id > $priorLastReadMessageId;
            if ($showUnreadSeparator) {
                $unreadSeparatorShown = true;
            }
        @endphp
        @if($showDateSeparator)
            <div class="chat-date-separator" data-date-key="{{ $dateKey }}"><span>{{ $dateSeparatorLabel }}</span></div>
        @endif
        @if($showUnreadSeparator)
            <div class="chat-unread-separator" id="chatUnreadSeparator"><span>Unread messages</span></div>
        @endif
        @if($message->isSystem())
            <div class="chat-bubble-row is-system">
                <span class="chat-bubble-system">{{ $message->body }}</span>
            </div>
        @elseif($message->isFromHexa())
            @php
                // Hexa's body is stored as plain text (bullets marked with
                // "• ", same as any other message — keeps chat export
                // readable and the messages table free of HTML) — grouped
                // here into paragraphs/lists purely for display, so wrapped
                // bullet lines hang-indent under their own text instead of
                // under the bullet. The 🚗 stays in the stored text (reads
                // fine in a plain-text export) but renders as the same
                // Font Awesome icon shown next to the driver's name, so
                // what Hexa describes visually matches what's on screen.
                $hexaBlocks = [];
                $hexaBullets = [];
                foreach (preg_split('/\n/', trim($message->body)) as $hexaLine) {
                    $hexaLine = trim($hexaLine);
                    if ($hexaLine === '') continue;
                    if (str_starts_with($hexaLine, '• ')) {
                        $hexaBullets[] = mb_substr($hexaLine, 2);
                        continue;
                    }
                    if ($hexaBullets) {
                        $hexaBlocks[] = ['type' => 'list', 'items' => $hexaBullets];
                        $hexaBullets = [];
                    }
                    $hexaBlocks[] = ['type' => 'p', 'text' => $hexaLine];
                }
                if ($hexaBullets) {
                    $hexaBlocks[] = ['type' => 'list', 'items' => $hexaBullets];
                }
                $hexaIconify = fn (string $text) => str_replace('🚗', '<i class="fa-solid fa-car chat-bubble-hexa-icon"></i>', e($text));
            @endphp
            <div class="chat-bubble-row">
                <span class="chat-bubble-avatar chat-bubble-avatar-hexa"><x-mascot size="20" /></span>
                <div class="chat-bubble-col">
                    <span class="chat-bubble-sender chat-bubble-sender-hexa">Hexa</span>
                    <div class="chat-bubble chat-bubble-hexa">
                        @foreach($hexaBlocks as $hexaBlock)
                            @if($hexaBlock['type'] === 'list')
                                <ul class="chat-bubble-hexa-list">
                                    @foreach($hexaBlock['items'] as $hexaItem)
                                        <li>{!! $hexaIconify($hexaItem) !!}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p>{!! $hexaIconify($hexaBlock['text']) !!}</p>
                            @endif
                        @endforeach
                    </div>
                    @if($message->type === \App\Models\Message::TYPE_PAYMENT_REMINDER && $conversation->trip_id)
                        <a href="{{ route('payments.index', ['trip_id' => $conversation->trip_id, 'pay' => 1]) }}" class="chat-bubble-hexa-cta">
                            <i class="fa-solid fa-wallet"></i> Pay Now <i class="fa-solid fa-arrow-up-right-from-square chat-bubble-hexa-cta-arrow"></i>
                        </a>
                    @elseif($message->type === \App\Models\Message::TYPE_RATING_INVITE && $conversation->trip && (int) $conversation->driver_id !== (int) $me->id)
                        {{-- Gated on driver_id !== viewer so the trip's own driver never
                             sees a self-rate prompt — unlike Pay Now above (generic and
                             safe for anyone), the server would correctly reject this one. --}}
                        <a href="#" class="chat-bubble-hexa-cta is-rating open-rate-trip-modal-btn"
                           data-trip-id="{{ $conversation->trip->id }}"
                           data-trip-ref="{{ $conversation->trip_ref_snapshot }}"
                           data-rate-url="{{ route('driver-ratings.store', $conversation->trip) }}"
                           data-driver-name="{{ $conversation->driver?->name ?: 'your driver' }}"
                           data-route-name="{{ $conversation->route_snapshot }}">
                            <i class="fa-solid fa-star"></i> Rate Trip <i class="fa-solid fa-arrow-up-right-from-square chat-bubble-hexa-cta-arrow"></i>
                        </a>
                    @endif
                    <span class="chat-bubble-time">{{ $localCreatedAt?->format('g:i A') }}</span>
                </div>
            </div>
        @else
            <div class="chat-bubble-row {{ $isOwn ? 'is-own' : '' }}">
                @unless($isOwn)
                    <x-avatar :user="$message->sender" size="sm" class="chat-bubble-avatar" />
                @endunless
                <div class="chat-bubble-col">
                    @unless($isOwn)
                        <span class="chat-bubble-sender">
                            {{ $message->sender?->name ?? 'Deleted user' }}
                            @if($message->sender_id === $conversation->driver_id)
                                <i class="fa-solid fa-car chat-bubble-driver-icon" title="Driver"></i>
                            @endif
                        </span>
                    @endunless
                    @if($message->type === 'image')
                        <button type="button" class="chat-bubble-image-link" data-lightbox-src="{{ $message->body }}">
                            <img src="{{ $message->body }}" alt="Photo" class="chat-bubble-image">
                        </button>
                    @else
                        <div class="chat-bubble">{{ $message->body }}</div>
                    @endif
                    <span class="chat-bubble-time">{{ $localCreatedAt?->format('g:i A') }}</span>
                </div>
            </div>
        @endif
    @endforeach
    </div>
</div>

@if($isOpen)
    <form id="chatComposerForm" class="chat-composer">
        <button type="button" class="chat-composer-attach" id="chatAttachBtn" aria-label="Send a photo">
            <i class="fa-solid fa-camera"></i>
        </button>
        <input type="file" id="chatAttachInput" accept="image/*" capture="environment" hidden>
        <textarea id="chatComposerInput" class="chat-composer-input" rows="1" maxlength="2000" placeholder="Type a message..." required></textarea>
        <button type="submit" class="chat-composer-send" id="chatComposerSend" aria-label="Send">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </form>
@else
    <div class="chat-composer-locked">
        <i class="fa-regular fa-clock"></i>
        This chat opens {{ $conversation->opens_at?->diffForHumans() }}.
    </div>
@endif
</div>
</div>
</div>

<script>window.CH_CHAT = {
    csrf: @json(csrf_token()),
    conversationId: @json($conversation->id),
    myUserId: @json($me->id),
    lastMessageId: @json($messages->last()?->id ?? 0),
    lastDateKey: @json($lastDateKey ?? null),
    sendUrl: @json(route('chats.messages.store', $conversation)),
    pollUrl: @json(route('refresh.chats.messages', $conversation)),
    ablyTokenUrl: @json(route('chats.ably-token')),
    readUrl: @json(route('chats.read', $conversation)),
    tripModalRefreshUrl: @json($tripModalData ? route('refresh.chats.trip-modal', $conversation) : null),
};</script>
@if($conversation->trip && (int) $conversation->driver_id !== (int) $me->id)
    {{-- Trips-list "Rate" action icon links here with ?open_rate=1 so the
         modal opens immediately on arrival, even when the in-chat Hexa
         CTA bubble (TYPE_RATING_INVITE) hasn't been posted to this
         thread yet — CarpoolRateTrip.openFor() builds its own throwaway
         trigger rather than depending on one already being in the DOM.
         Server-side eligibility (already rated, window closed, etc.) is
         still the real gate — a real submit is rejected with the usual
         error toast either way. Re-runs on every AJAX-mounted thread just
         like chats-show.js below, so this still fires when arriving via a
         chat-row click, not only on a direct/full-page load. --}}
    <script>
        (() => {
            const params = new URLSearchParams(window.location.search);
            if (params.get('open_rate') !== '1') return;
            window.CarpoolRateTrip?.openFor({
                tripId: @json($conversation->trip->id),
                tripRef: @json($conversation->trip_ref_snapshot),
                rateUrl: @json(route('driver-ratings.store', $conversation->trip)),
                driverName: @json($conversation->driver?->name ?: 'your driver'),
                routeName: @json($conversation->route_snapshot),
            });
        })();
    </script>
@endif
<script src="{{ asset('js/chats-show.js') }}?v={{ filemtime(public_path('js/chats-show.js')) }}"></script>
