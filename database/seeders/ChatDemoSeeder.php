<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Trip;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Not part of the default db:seed chain — run explicitly with
 * `php artisan db:seed --class=ChatDemoSeeder` whenever the chat UI needs a
 * fresh, realistic set of demo conversations (e.g. after a round of chat UI
 * changes). Wipes every conversation/participant/message and rebuilds a
 * fixed set against real existing trips, chosen to span every member-count
 * the UI renders differently: 2 (1 other), 3 (2 others), 4 (3 others), 6
 * (4+ others -> the "+N" avatar cluster badge), plus a driver-started
 * private group (a distinct creation path from the public auto-sync ones).
 *
 * Goes through the real ChatService methods (syncParticipants/
 * createPrivateGroup/postMessage), not raw inserts, so the seeded data is
 * created via the exact same code path production traffic uses — including
 * the Hexa welcome message and "X joined" system messages.
 *
 * For the two already-completed trips (160, 7) the conversation/message
 * timestamps are backdated via Carbon::setTestNow() so the chat history
 * reads as something that actually happened around the trip date, not as a
 * pile of messages all timestamped the moment this seeder ran.
 */
class ChatDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('messages')->truncate();
        DB::table('conversation_participants')->truncate();
        DB::table('conversations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $chat = app(ChatService::class);

        // 2 members, completed ~3 weeks ago, unpaid — driver + one passenger.
        $this->seedPublicTrip($chat, 160, backdateTo: Trip::find(160)->trip_datetime, script: [
            [3, "Hi Ali, still on for today? I'll be at the usual spot"],
            [2, 'Yup on the way, reach in about 10 mins'],
            [3, 'Ok noted, thanks!'],
            [2, 'Reached UMPSA already, dropped you off safe 👍'],
            [3, 'Appreciate it! will transfer the RM0.50 tonight'],
        ]);

        // 3 members, upcoming tomorrow — real "now", no backdating needed.
        $this->seedPublicTrip($chat, 176, backdateTo: null, script: [
            [3, 'Morning! What time you leaving tomorrow?'],
            [2, 'Planning around 6:45am, class starts 8 right?'],
            [5, "Yup 8am class, I'll be at the usual pickup point"],
            [3, 'Same here, see you guys tomorrow'],
            [2, 'Noted, see you both at 6:45'],
        ]);

        // 4 members, upcoming tomorrow.
        $this->seedPublicTrip($chat, 177, backdateTo: null, script: [
            [6, 'Hi all, Alieff here, joining this trip too'],
            [2, 'Welcome Alieff! Pickup same spot as usual ya'],
            [5, 'Hi Alieff 👋'],
            [3, 'Hey! anyone know if parking near Fakulti Komputeran is ok in the morning?'],
            [2, "Should be fine before 8, I'll drop everyone right at the entrance"],
            [6, 'Sounds good, thanks!'],
        ]);

        // 6 members (biggest — triggers the "+N" cluster badge), upcoming tomorrow.
        $this->seedPublicTrip($chat, 178, backdateTo: null, script: [
            [7, 'Wah ramai jugak this trip'],
            [8, 'Haha yeah, first time carpooling with all of you'],
            [2, "Welcome everyone! I'll do 2 pickup points since ramai ni"],
            [3, "Noted, I'm at the usual spot"],
            [5, 'Same, usual spot'],
            [6, "I'll wait near the guard house instead, easier for pickup"],
            [8, 'Got it, thanks for organizing Ali!'],
        ]);

        // Private group (driver-started, not auto-synced) — 3 members.
        $this->seedPrivateGroup($chat, tripId: 7, connectionUserIds: [4, 7], backdateTo: Trip::find(7)->trip_datetime, script: [
            [4, 'Eh Faiq lapar la, jom singgah Tealive kejap'],
            [5, 'Boleh, singgah sekejap then terus jalan'],
            [7, 'confirm! nasi kukus pun best kat situ'],
            [5, 'haha ok noted, singgah dua-dua tempat'],
        ]);

        $this->command?->info('Seeded 5 demo conversations (member counts: 2, 3, 4, 6, private-3).');
    }

    private function seedPublicTrip(ChatService $chat, int $tripId, ?Carbon $backdateTo, array $script): void
    {
        $trip = Trip::findOrFail($tripId);

        $this->withFakeNow($backdateTo?->clone()->subDays(2), function () use ($chat, $trip) {
            $chat->syncParticipants($trip);
        });

        $conversation = Conversation::where('trip_id', $tripId)->firstOrFail();

        $this->playScript($chat, $conversation, $script, $backdateTo);
    }

    private function seedPrivateGroup(ChatService $chat, int $tripId, array $connectionUserIds, ?Carbon $backdateTo, array $script): void
    {
        $trip = Trip::findOrFail($tripId);
        $driver = User::findOrFail($trip->driver_id);

        $conversation = null;
        $this->withFakeNow($backdateTo?->clone()->subDays(2), function () use ($chat, $trip, $driver, $connectionUserIds, &$conversation) {
            $conversation = $chat->createPrivateGroup($trip, $driver, $connectionUserIds);
        });

        $this->playScript($chat, $conversation, $script, $backdateTo);
    }

    private function playScript(ChatService $chat, Conversation $conversation, array $script, ?Carbon $backdateTo): void
    {
        $cursor = $backdateTo?->clone()->subMinutes(30);

        foreach ($script as [$userId, $body]) {
            $this->withFakeNow($cursor, function () use ($chat, $conversation, $userId, $body) {
                $chat->postMessage($conversation, User::findOrFail($userId), $body);
            });
            $cursor = $cursor?->clone()->addMinutes(random_int(8, 20));
        }
    }

    /**
     * Runs $callback with Carbon::setTestNow() pinned to $fakeNow (so every
     * now()/Model::create() timestamp inside it lands on that instant), then
     * always clears it afterwards — a stuck fake "now" would corrupt
     * everything else in the app, so the reset has to run even on exception.
     * A null $fakeNow just runs $callback against the real clock.
     */
    private function withFakeNow(?Carbon $fakeNow, callable $callback): void
    {
        if (! $fakeNow) {
            $callback();

            return;
        }

        Carbon::setTestNow($fakeNow);

        try {
            $callback();
        } finally {
            Carbon::setTestNow();
        }
    }
}
