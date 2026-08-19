<?php

namespace App\Services\Ai;

use App\Models\Connection;
use App\Models\SavedRoute;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => 'https://api.anthropic.com',
            'timeout'  => (int) config('ai_chat.timeout', 15),
            'headers'  => [
                'x-api-key'         => config('ai_chat.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
        ]);
    }

    /**
     * @param  array<array{role:string,content:string}>  $history
     * @return array{intent:string,reply:string,data?:array,route?:string}
     */
    public function chat(User $user, string $message, array $history = [], string $language = 'ms', string $pendingContext = ''): array
    {
        $messages   = $history;
        $messages[] = ['role' => 'user', 'content' => trim($message)];

        try {
            $response = $this->http->post('/v1/messages', [
                'json' => [
                    'model'      => trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                    'max_tokens' => (int) config('ai_chat.max_tokens', 600),
                    'system'     => $this->buildSystemPrompt($user, $language, $pendingContext),
                    'messages'   => $messages,
                ],
            ]);

            $bodyStr = (string) $response->getBody();
            $body    = json_decode($bodyStr, true);
            
            $text = '';
            if (isset($body['content']) && is_array($body['content'])) {
                foreach ($body['content'] as $block) {
                    if (($block['type'] ?? '') === 'text') {
                        $text = $block['text'] ?? '';
                        break;
                    }
                }
            }
            $content = trim((string) $text);

            if ($content === '') {
                // Was returning 'DEBUG EMPTY CONTENT. Body: ...' straight to the
                // browser, exposing the raw upstream API response. Log it for
                // diagnosis; show the user the same friendly copy as any other
                // AI failure.
                Log::warning('AI Chat returned empty content.', [
                    'body' => substr($bodyStr, 0, 500),
                ]);

                return ['intent' => 'error', 'reply' => $this->unavailableMessage($language)];
            }

            return $this->parseResponse($content, $user, $language);

        } catch (GuzzleException $e) {
            Log::error('AI Chat Error: ' . $e->getMessage(), [
                'response' => $e instanceof RequestException && $e->hasResponse() ? (string) $e->getResponse()->getBody() : null
            ]);
            // The exception message used to be appended to the Malay copy, which
            // put upstream API errors (and the request URL) in front of the end
            // user. It is already logged above, which is where it belongs.
            return ['intent' => 'error', 'reply' => $this->unavailableMessage($language)];
        }
    }

    private function unavailableMessage(string $language): string
    {
        return $language === 'en'
            ? 'Sorry, AI is unavailable right now. Please try again.'
            : 'Maaf, sistem AI tidak dapat dihubungi sekarang. Cuba lagi sebentar.';
    }

    private function buildSystemPrompt(User $user, string $language, string $pendingContext = ''): string
    {
        $now   = Carbon::now()->format('d M Y, H:i');
        $isBm  = $language !== 'en';
        $role  = (string) $user->role;

        $roleContext = $isBm
            ? match ($role) {
                'driver'    => 'PEMANDU — boleh buat trip baru.',
                'passenger' => 'PENUMPANG — boleh cari trip dan semak bayaran.',
                'admin'     => 'ADMIN — boleh akses semua bahagian.',
                default     => 'pengguna biasa.',
            }
            : match ($role) {
                'driver'    => 'DRIVER — can create new trips.',
                'passenger' => 'PASSENGER — can search trips and check payments.',
                'admin'     => 'ADMIN — can access all sections.',
                default     => 'regular user.',
            };

        $isDriver = \in_array($role, ['driver', 'admin'], true);

        // Only send route + connection data to drivers — passengers don't need it
        $contextBlock = $isDriver
            ? $this->formatSavedRoutes($user, $isBm) . "\n" . $this->formatConnections($user, $isBm)
            : ($isBm ? 'User adalah penumpang — tiada route tersimpan diperlukan.' : 'User is a passenger — no saved routes needed.');

        $langInstr = $isBm
            ? 'Balas dalam BM santai (mixed ok). Ringkas dan mesra.'
            : 'Reply in casual English. Brief and friendly.';

        $clarifyInstr = $isBm
            ? 'Kalau info tak cukup, TANYA dulu — jangan teka.'
            : 'If info is insufficient, ASK first — never guess.';

        // Inject pending trip context if user previously had a failed trip request
        $pendingBlock = '';
        if ($pendingContext !== '') {
            $pendingBlock = $isBm
                ? "\n⚠️ KONTEKS TERTANGGUH: User sebelum ni cuba buat trip: \"{$pendingContext}\" — route belum wujud masa tu. Route mungkin dah didaftarkan sekarang. Kalau user tanya trip tanpa bagi butiran semula, guna konteks ini."
                : "\n⚠️ PENDING CONTEXT: User previously requested: \"{$pendingContext}\" — route didn't exist then. Route may now be registered. If user asks about a trip without repeating full details, use this context.";
        }

        $driverInstructions = $isDriver ? ($isBm
            ? 'ROUTE MATCHING (WAJIB IKUT):
- Semak saved routes di atas. Cuba padankan destinasi/tempat pickup user dengan point_a atau point_b dalam senarai.
- Kalau ada padanan jelas → gunakan route tu, isi saved_route_id, route_name, pickup_name, destination_name.
- Kalau user ada 1 route sahaja dan tak sebut route lain → assume route tu.
- KALAU TIADA PADANAN LANGSUNG → JANGAN return trip_draft. Return intent "no_route" dengan mesej BM yang beritahu user tempat tu tiada dalam saved routes mereka, dan minta mereka tambah route dulu atau pilih route yang sedia ada.
- Jangan SEKALI-KALI reka atau teka nama tempat. Ambil terus dari senarai.
- outbound_pickup_key & outbound_destination_key mesti berbeza (point_a/point_b).
- participant_ids: isi HANYA kalau user sebut nama penumpang.'
            : 'ROUTE MATCHING (MUST FOLLOW):
- Check saved routes above. Try to match user\'s destination/pickup with point_a or point_b in the list.
- If clear match found → use that route, fill saved_route_id, route_name, pickup_name, destination_name.
- If user has only 1 route and mentions no other → assume that route.
- IF NO MATCH AT ALL → DO NOT return trip_draft. Return intent "no_route" with a message telling user that place is not in their saved routes, and ask them to add a route first or pick an existing one.
- NEVER invent or guess place names. Take exact values from the list.
- outbound_pickup_key & outbound_destination_key must differ (point_a/point_b).
- participant_ids: fill ONLY if user mentions passenger names.'
        ) : '';

        $tripDraftSchema = $isDriver ? '
1. TRIP DRAFT (driver creating a trip):
{"intent":"trip_draft","reply":"<msg>","data":{"saved_route_id":<n|null>,"route_name":"<exact|null>","pickup_name":"<exact|null>","destination_name":"<exact|null>","outbound_pickup_key":"<point_a|point_b>","outbound_destination_key":"<point_a|point_b>","trip_datetime":"<YYYY-MM-DD HH:mm|null>","trip_type":"<one_way|two_way>","visibility":"<public|private>","seat_limit":<n|null>,"note":"<str|null>","participant_ids":[],"participant_names":[]}}

2. ROUTE DRAFT (driver wants to create a NEW saved route — place not in existing routes):
{"intent":"route_draft","reply":"<msg>","data":{"route_name":"<suggested name>","point_a_name":"<pickup place name in Malaysia>","point_a_lat":<lat 7dp>,"point_a_lng":<lng 7dp>,"point_b_name":"<destination place name in Malaysia>","point_b_lat":<lat 7dp>,"point_b_lng":<lng 7dp>,"default_fare":<suggested fare RM based on distance, number>,"distance_km":<approx km, number>}}'
        : '';

        return <<<PROMPT
You are CarpoolHub AI Assistant for a Malaysian carpooling app.
{$langInstr}
Now: {$now} | User: {$user->name} | {$roleContext}

{$contextBlock}
{$driverInstructions}{$pendingBlock}

RESPOND IN VALID JSON ONLY (no markdown):{$tripDraftSchema}

2. NAVIGATE: {"intent":"navigate","reply":"<msg>","route":"<trips.index|trips.create|payments.index|explore.index|connections.index|saved-routes.index|settings.index|notifications.index>"}

3. GENERAL: {"intent":"general","reply":"<answer>"}

{$clarifyInstr}
PROMPT;
    }

    private function formatSavedRoutes(User $user, bool $isBm): string
    {
        $routes = SavedRoute::query()
            ->where('user_id', (int) $user->id)
            ->where('is_active', true)
            ->orderBy('route_name')
            ->limit(10)
            ->get(['id', 'route_name', 'point_a_name', 'point_b_name', 'default_fare']);

        $header = $isBm ? 'ROUTE TERSIMPAN PENGGUNA' : 'USER SAVED ROUTES';

        if ($routes->isEmpty()) {
            return $isBm
                ? "{$header}: Tiada route aktif. Minta user buat saved route dulu."
                : "{$header}: No active routes. Ask user to create a saved route first.";
        }

        $lines = $routes->map(fn ($r) => \sprintf(
            '  - ID %d | Route: "%s" | Point A: "%s" | Point B: "%s" | Fare: RM%.2f',
            (int) $r->id,
            (string) ($r->route_name ?? 'Route ' . (int) $r->id),
            (string) $r->point_a_name,
            (string) $r->point_b_name,
            (float) $r->default_fare,
        ))->implode("\n");

        return "{$header}:\n{$lines}";
    }

    private function formatConnections(User $user, bool $isBm): string
    {
        $connections = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($user): void {
                $q->where('requester_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->with(['requester:id,name', 'receiver:id,name'])
            ->limit(20)
            ->get();

        $header = $isBm ? 'CONNECTIONS PENGGUNA (untuk passenger)' : 'USER CONNECTIONS (for passengers)';

        if ($connections->isEmpty()) {
            return $isBm
                ? "{$header}: Tiada connections."
                : "{$header}: No connections.";
        }

        $lines = $connections->map(function ($c) use ($user) {
            $contact = (int) $c->requester_id === (int) $user->id ? $c->receiver : $c->requester;

            return \sprintf('  - ID %d | Name: "%s"', (int) $contact->id, (string) $contact->name);
        })->implode("\n");

        return "{$header}:\n{$lines}";
    }

    private function parseResponse(string $raw, User $user, string $language): array
    {
        $raw = trim($raw);
        $jsonStr = $raw;

        // Try to robustly extract the JSON object in case Claude added conversational text
        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $jsonStr = $matches[0];
        }

        $decoded = json_decode($jsonStr, true);

        $fallback = $language === 'en'
            ? 'Sorry, I could not process that response.'
            : 'Maaf, respons tidak dapat diproses.';

        if (! \is_array($decoded) || empty($decoded['intent'])) {
            // Include JSON error for debugging if needed (but hide from user)
            \Illuminate\Support\Facades\Log::warning('AI Chat JSON Decode Failed: ' . json_last_error_msg(), ['raw' => $raw]);
            return ['intent' => 'general', 'reply' => $fallback];
        }

        $intent = (string) $decoded['intent'];
        $reply  = (string) ($decoded['reply'] ?? '');

        // no_route — route not in saved routes, guide user to create one
        if ($intent === 'no_route') {
            return [
                'intent'    => 'no_route',
                'reply'     => $reply,
                'route_url' => route('saved-routes.index'),
            ];
        }

        // route_draft — AI suggests a new saved route with coordinates
        if ($intent === 'route_draft') {
            if (! \in_array((string) $user->role, ['driver', 'admin'], true)) {
                return ['intent' => 'general', 'reply' => $language === 'en'
                    ? 'Only drivers can create saved routes.'
                    : 'Hanya pemandu boleh buat saved route.'];
            }

            return [
                'intent' => 'route_draft',
                'reply'  => $reply,
                'data'   => (array) ($decoded['data'] ?? []),
                'url'    => route('saved-routes.create'),
            ];
        }

        if ($intent === 'trip_draft') {
            if (! \in_array((string) $user->role, ['driver', 'admin'], true)) {
                return [
                    'intent' => 'general',
                    'reply'  => $language === 'en'
                        ? 'Only drivers can create trips. Use Explore to find a ride.'
                        : 'Hanya pemandu boleh buat trip. Guna Explore untuk cari trip.',
                ];
            }

            $data = (array) ($decoded['data'] ?? []);

            // Safety: if Claude returns trip_draft but no route — convert to no_route
            if (empty($data['saved_route_id'])) {
                return [
                    'intent'    => 'no_route',
                    'reply'     => $language === 'en'
                        ? "That place isn't in your saved routes. Want me to draft a new saved route for it, or add one manually?"
                        : "Tempat tu tiada dalam saved routes kau. Nak AI draftkan route baru tu, atau tambah sendiri?",
                    'route_url' => route('saved-routes.create'),
                ];
            }

            return ['intent' => 'trip_draft', 'reply' => $reply, 'data' => $data];
        }

        if ($intent === 'navigate') {
            $allowed = [
                'trips.index', 'trips.create', 'payments.index', 'explore.index',
                'connections.index', 'saved-routes.index', 'settings.index', 'notifications.index',
            ];
            $route = (string) ($decoded['route'] ?? '');

            if (\in_array($route, $allowed, true)) {
                return ['intent' => 'navigate', 'reply' => $reply, 'route' => $route];
            }

            return ['intent' => 'general', 'reply' => $reply];
        }

        return ['intent' => 'general', 'reply' => $reply];
    }
}
