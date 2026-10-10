<?php

use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Private channel per conversation. Only a still-active participant
| (left_at is null) may subscribe, so someone who cancelled or was removed
| stops getting live updates at the same moment they lose the composer.
|
| Laravel's stock /broadcasting/auth endpoint (registered by
| BroadcastServiceProvider::boot() -> Broadcast::routes()) speaks a
| Pusher-shaped protocol (channel_name/socket_id in, {auth, channel_data}
| out) that Ably's PHP broadcaster only supports for Echo-style clients.
| This app has no npm/build step to load Laravel Echo's Ably fork, so the
| actual path used at runtime is ChatController::ablyToken(), a small
| endpoint that issues an Ably token limited to one conversation channel
| using the plain ably-php SDK, applying the same rule written below.
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
