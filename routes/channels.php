<?php

use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Private channel per conversation. Only a still-active participant
| (left_at null) may subscribe — someone who cancelled/was removed loses
| live updates the same moment they lose composer access.
|
| Laravel's stock /broadcasting/auth endpoint (registered by
| BroadcastServiceProvider::boot() -> Broadcast::routes()) speaks a
| Pusher-shaped protocol (channel_name/socket_id in, {auth, channel_data}
| out) that Ably's PHP broadcaster only supports for Echo-style clients.
| This app has no npm/build step to load Laravel Echo's Ably fork, so the
| actual runtime auth path is ChatController::ablyToken() — a small
| endpoint that issues an Ably token scoped to exactly one conversation
| channel via the plain ably-php SDK, enforcing the identical rule below.
| This definition stays as the canonical statement of that rule (and
| because BroadcastServiceProvider::boot() requires this file to exist).
*/
Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {
    return ConversationParticipant::query()
        ->where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->whereNull('left_at')
        ->exists();
});
