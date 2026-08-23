<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiUsageLogger;
use App\Services\Ai\ChatbotService;
use App\Services\Ai\FareReferenceDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(
        private readonly ChatbotService $chatbotService,
        private readonly FareReferenceDataService $fareData,
        private readonly AiUsageLogger $usage,
    ) {
    }

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message'  => ['required', 'string', 'max:500'],
            'language' => ['nullable', 'in:ms,en'],
        ]);

        if (empty(config('ai_chat.api_key'))) {
            return response()->json([
                'intent' => 'error',
                'reply'  => 'AI Chat belum dikonfigurasi. Sila hubungi admin.',
            ]);
        }

        $user     = $request->user();
        $message  = $request->string('message')->trim()->toString();
        $language = $request->input('language', session('ai_chat_language', 'ms'));

        session(['ai_chat_language' => $language]);

        $maxMessages    = (int) config('ai_chat.history_turns', 4) * 2;
        $history        = \array_slice((array) session('ai_chat_history', []), -$maxMessages);
        $pendingContext = (string) session('ai_chat_pending_trip', '');

        $result = $this->chatbotService->chat($user, $message, $history, $language, $pendingContext);

        // Save pending trip context when route is missing — so AI remembers after user registers route
        if (($result['intent'] ?? '') === 'no_route') {
            // Only save if it looks like a trip request (preserve the original user message)
            session(['ai_chat_pending_trip' => $message]);
        }

        // Clear pending context once a trip draft is successfully produced
        if (($result['intent'] ?? '') === 'trip_draft') {
            session()->forget('ai_chat_pending_trip');
        }

        if (($result['intent'] ?? '') !== 'error') {
            $history[] = ['role' => 'user',      'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $result['reply']];
            session(['ai_chat_history' => \array_slice($history, -$maxMessages)]);
        }

        if (($result['intent'] ?? '') === 'navigate' && ! empty($result['route'])) {
            try {
                $result['url'] = route($result['route'], $result['params'] ?? []);
            } catch (\Throwable) {
                $result['intent'] = 'general';
                unset($result['route'], $result['url']);
            }
        }
        unset($result['params']);

        return response()->json($result);
    }

    public function clearHistory(): JsonResponse
    {
        session()->forget(['ai_chat_history', 'ai_chat_language', 'ai_chat_pending_trip']);

        return response()->json(['ok' => true]);
    }

    /**
     * Picks which already-computed route option should be pre-selected for
     * the driver, and explains why — a genuine multi-factor trade-off
     * (cost vs travel time), not a fixed "always cheapest" rule.
     */
    public function recommendRoute(Request $request): JsonResponse
    {
        $request->validate([
            'options'                => ['required', 'array', 'min:1', 'max:5'],
            'options.*.distance_km'  => ['required', 'numeric', 'min:0'],
            'options.*.duration_min' => ['required', 'numeric', 'min:0'],
            'options.*.toll_cost'    => ['required', 'numeric', 'min:0'],
            'options.*.total_cost'   => ['required', 'numeric', 'min:0'],
        ]);

        $options  = $request->input('options');
        $language = session('ai_chat_language', 'en');
        $fallback = $this->cheapestFirstRecommendation($options, $language);

        if (count($options) < 2 || empty(config('ai_chat.api_key'))) {
            return response()->json($fallback);
        }

        $langInstr = $language === 'ms' ? 'Bahasa Malaysia ringkas' : 'brief English';
        $lines = [];
        foreach ($options as $i => $opt) {
            // Shown to the AI (and echoed back in its reason text) as 1-based,
            // matching the "Option 1 / Option 2" labels the driver actually
            // sees on screen — recommended_index below stays 0-based since
            // that's an array position the frontend indexes with directly.
            $lines[] = sprintf(
                'Option %d: %.1f km, %d min, toll RM%.2f, total estimated cost RM%.2f',
                $i + 1,
                (float) $opt['distance_km'],
                (int) round((float) $opt['duration_min']),
                (float) $opt['toll_cost'],
                (float) $opt['total_cost'],
            );
        }

        $prompt = "You are picking the best default route option for a Malaysian carpool driver from these real, already-calculated route choices (numbered from 1, as the driver sees them on screen):\n\n" .
            implode("\n", $lines) . "\n\n" .
            "Pick whichever option is the most sensible default for a typical carpool trip — weigh total cost against travel time together, don't blindly pick only the cheapest or only the fastest. " .
            "In your reason text, refer to options as \"Option 1\", \"Option 2\" etc. exactly as numbered above — never \"Option 0\" or any other numbering. " .
            "Return JSON only: {\"recommended_index\": <int, the ZERO-BASED array position matching your pick — Option 1 above = 0, Option 2 = 1, up to " . (count($options) - 1) . ">, \"reason\": \"<{$langInstr}, 15-25 words, name the specific trade-off, referring to the option number as shown above>\"}";

        try {
            $response = $this->anthropic()->post('/v1/messages', [
                'json' => [
                    'model'      => trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                    'max_tokens' => 120,
                    'thinking'   => ['type' => 'disabled'],
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ],
            ]);

            $body    = json_decode((string) $response->getBody(), true);
            $raw     = $this->extractAnthropicText($body);
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $cleaned = preg_replace('/\s*```$/', '', $cleaned ?? $raw);
            $decoded = json_decode(trim($cleaned ?? $raw), true);

            $this->usage->record(
                $request->user(),
                'recommend-route',
                trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                $body['usage']['input_tokens'] ?? null,
                $body['usage']['output_tokens'] ?? null,
                success: is_array($decoded) && isset($decoded['recommended_index']),
            );

            if (! is_array($decoded) || ! isset($decoded['recommended_index'])) {
                return response()->json($fallback);
            }

            $index = (int) $decoded['recommended_index'];
            if ($index < 0 || $index >= count($options)) {
                return response()->json($fallback);
            }

            return response()->json([
                'recommended_index' => $index,
                'reason' => trim((string) ($decoded['reason'] ?? '')) ?: $fallback['reason'],
            ]);
        } catch (\Throwable) {
            $this->usage->record(
                $request->user(),
                'recommend-route',
                trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                null,
                null,
                success: false,
                errorType: 'http_error',
            );

            return response()->json($fallback);
        }
    }

    private function cheapestFirstRecommendation(array $options, string $language): array
    {
        $bestIndex = 0;
        foreach ($options as $i => $opt) {
            $cost     = (float) $opt['total_cost'];
            $bestCost = (float) $options[$bestIndex]['total_cost'];

            if ($cost < $bestCost - 0.01) {
                $bestIndex = $i;
            } elseif (abs($cost - $bestCost) <= 0.01 && (float) $opt['duration_min'] < (float) $options[$bestIndex]['duration_min']) {
                $bestIndex = $i;
            }
        }

        $reason = $language === 'ms'
            ? 'Dipilih automatik: gabungan kos terendah dan masa perjalanan terpendek antara pilihan yang ada.'
            : 'Auto-selected: the best balance of lowest cost and shortest travel time among the options found.';

        return ['recommended_index' => $bestIndex, 'reason' => $reason];
    }

    public function fareAdvice(Request $request): JsonResponse
    {
        $request->validate([
            'distance_km'  => ['required', 'numeric', 'min:0', 'max:1000'],
            'duration_min' => ['required', 'numeric', 'min:0', 'max:900'],
            'roads'        => ['nullable', 'string', 'max:500'],
            'vehicle'      => ['nullable', 'string', 'max:120'],
            'fuel_type'    => ['nullable', 'string', 'in:RON95,RON97,Diesel'],
        ]);

        $distanceKm  = round((float) $request->input('distance_km'), 1);
        $durationMin = (int) round((float) $request->input('duration_min'));
        $roads       = trim((string) ($request->input('roads') ?? ''));
        $vehicle     = trim((string) ($request->input('vehicle') ?? ''));

        // When the driver has already picked a fuel type in the form, that choice
        // is ground truth — the AI only fills in km/L and toll for it, it never
        // gets to override what the driver selected.
        $confirmedFuelType = $request->input('fuel_type');
        $confirmedFuelType = in_array($confirmedFuelType, ['RON95', 'RON97', 'Diesel'], true) ? $confirmedFuelType : null;

        $language = session('ai_chat_language', 'en');

        // Toll detection runs unconditionally — a sourced highway match always
        // wins over whatever the AI/heuristic path below would have guessed.
        $tollMatch = $this->fareData->detectTolls($roads, $distanceKm);

        // A recognised vehicle model has real sourced km/L figures — build the
        // whole response from that and skip the AI call entirely.
        $vehicleMatch = $this->fareData->lookupVehicleConsumption($vehicle);
        if ($vehicleMatch !== null) {
            return response()->json($this->tableFareAdvice($vehicleMatch, $tollMatch, $distanceKm, $durationMin, $confirmedFuelType, $language));
        }

        $fallback = $this->fallbackFareAdvice($vehicle, $distanceKm, $durationMin, $tollMatch, $confirmedFuelType);

        if (empty(config('ai_chat.api_key'))) {
            return response()->json($fallback);
        }

        $langInstr = $language === 'ms' ? 'Bahasa Malaysia ringkas' : 'brief English';

        $fuelInstruction = $confirmedFuelType
            ? "The driver has already confirmed the fuel type is exactly \"{$confirmedFuelType}\" — return this same value in fuel_type, do not change it. Only estimate km/L and toll for this confirmed fuel type."
            : 'Infer fuel_type from the vehicle model.';

        $prompt = "You are a Malaysian carpool fare advisor. Estimate practical cost inputs, not the final fare.\n\n" .
            "Vehicle: " . ($vehicle ?: 'unknown') . "\n" .
            "Route distance: {$distanceKm} km\n" .
            "Route duration: {$durationMin} min\n" .
            "Road names/context: " . ($roads ?: 'unknown') . "\n\n" .
            "Return JSON only with this shape:\n" .
            "{\"fuel_type\":\"RON95|RON97|Diesel|Unknown\",\"estimated_km_per_liter\":number,\"has_toll\":boolean,\"toll_roads\":[\"road\"],\"estimated_toll_cost\":number,\"confidence\":\"low|medium|high\",\"reason\":\"{$langInstr}, 20-35 words\"}\n\n" .
            "Rules: {$fuelInstruction} Use real-world km/L for Malaysian mixed driving. Toll estimate may be approximate based on toll highway names only. Use 0 toll if unsure.";

        try {
            $response = $this->anthropic()->post('/v1/messages', [
                'json' => [
                    'model'      => trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                    'max_tokens' => 180,
                    'thinking'   => ['type' => 'disabled'],
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $raw = $this->extractAnthropicText($body);
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $cleaned = preg_replace('/\s*```$/', '', $cleaned ?? $raw);
            $decoded = json_decode(trim($cleaned ?? $raw), true);

            $this->usage->record(
                $request->user(),
                'fare-advice',
                trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                $body['usage']['input_tokens'] ?? null,
                $body['usage']['output_tokens'] ?? null,
                success: is_array($decoded),
            );

            if (! is_array($decoded)) {
                return response()->json($fallback);
            }

            $fuelType = $confirmedFuelType ?? $this->validFuelType($decoded['fuel_type'] ?? null, $fallback['fuel_type']);

            return response()->json([
                'fuel_type' => $fuelType,
                'estimated_km_per_liter' => $this->boundedFloat($decoded['estimated_km_per_liter'] ?? null, 4, 35, $fallback['estimated_km_per_liter']),
                // A sourced toll match always overrides the AI's own toll guess.
                'has_toll' => $tollMatch['matched'] ? true : (bool) ($decoded['has_toll'] ?? $fallback['has_toll']),
                'toll_roads' => $tollMatch['matched'] ? $tollMatch['toll_roads'] : array_values(array_slice(array_filter((array) ($decoded['toll_roads'] ?? $fallback['toll_roads'])), 0, 4)),
                'estimated_toll_cost' => $tollMatch['matched'] ? $tollMatch['estimated_toll_cost'] : $this->boundedFloat($decoded['estimated_toll_cost'] ?? null, 0, 80, $fallback['estimated_toll_cost']),
                'confidence' => in_array(($decoded['confidence'] ?? ''), ['low', 'medium', 'high'], true) ? $decoded['confidence'] : $fallback['confidence'],
                'reason' => trim((string) ($decoded['reason'] ?? $fallback['reason'])) ?: $fallback['reason'],
            ]);
        } catch (\Throwable) {
            $this->usage->record(
                $request->user(),
                'fare-advice',
                trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                null,
                null,
                success: false,
                errorType: 'http_error',
            );

            return response()->json($fallback);
        }
    }

    /**
     * Vehicle model matched config/vehicle_fuel_consumption.php — build the
     * whole response from sourced real-world data, no AI call needed.
     */
    private function tableFareAdvice(array $vehicleMatch, array $tollMatch, float $distanceKm, int $durationMin, ?string $confirmedFuelType, string $language): array
    {
        $fuelType = $confirmedFuelType ?? $vehicleMatch['fuel_type'];
        $kmPerLiter = $this->fareData->pickKmPerLiter($vehicleMatch, $distanceKm, $durationMin);

        $speedKmh = $durationMin > 0 ? $distanceKm / ($durationMin / 60) : 0;
        $roadContext = $vehicleMatch['combined_only'] ? '' : ($speedKmh > 40
            ? ($language === 'ms' ? ' untuk lebuhraya' : ' on highway-dominant routes')
            : ($language === 'ms' ? ' untuk jalan bandar' : ' in mixed city driving'));

        $reason = $language === 'ms'
            ? sprintf(
                '%s purata %s km/L%s (data sebenar Malaysia, disahkan). %s',
                $vehicleMatch['label'],
                number_format($kmPerLiter, 1),
                $roadContext,
                $tollMatch['matched'] ? 'Tol: ' . implode(', ', $tollMatch['toll_roads']) . '.' : 'Tiada lebuhraya bertol dikesan pada laluan ini.'
            )
            : sprintf(
                '%s averages %s km/L%s (verified real-world Malaysian data). %s',
                $vehicleMatch['label'],
                number_format($kmPerLiter, 1),
                $roadContext,
                $tollMatch['matched'] ? 'Toll roads: ' . implode(', ', $tollMatch['toll_roads']) . '.' : 'No known toll road detected on this route.'
            );

        return [
            'fuel_type' => $fuelType,
            'estimated_km_per_liter' => round($kmPerLiter, 1),
            'has_toll' => $tollMatch['has_toll'],
            'toll_roads' => $tollMatch['toll_roads'],
            'estimated_toll_cost' => $tollMatch['estimated_toll_cost'],
            'confidence' => 'high',
            'reason' => $reason,
        ];
    }

    private function fallbackFareAdvice(string $vehicle, float $distanceKm, int $durationMin, array $tollMatch, ?string $confirmedFuelType = null): array
    {
        $vehicleLower = strtolower($vehicle);
        $fuelType = 'RON95';
        $kmPerLiter = 11.5;

        if (preg_match('/diesel|hilux|triton|d-max|dmax|navara|fortuner|transit|van|lorry|truck|pickup/i', $vehicle) === 1) {
            $fuelType = 'Diesel';
            $kmPerLiter = 9.5;
        } elseif (preg_match('/bmw|mercedes|audi|volvo|lexus|mazda cx|civic type r|turbo/i', $vehicleLower) === 1) {
            $fuelType = 'RON97';
            $kmPerLiter = 10.5;
        } elseif (preg_match('/myvi|axia|bezza|iriz|saga|persona|city|vios|almera|jazz|yaris/i', $vehicleLower) === 1) {
            $kmPerLiter = 13.0;
        } elseif (preg_match('/alza|exora|avanza|innova|xpander|serena|estima|vellfire|alphard|mpv|suv/i', $vehicleLower) === 1) {
            $kmPerLiter = 9.0;
        }

        if ($confirmedFuelType !== null && $confirmedFuelType !== $fuelType) {
            $fuelType = $confirmedFuelType;
            $kmPerLiter = match ($confirmedFuelType) {
                'Diesel' => 9.5,
                'RON97'  => 10.5,
                default  => $kmPerLiter,
            };
        }

        $speed = $durationMin > 0 ? $distanceKm / ($durationMin / 60) : 0;
        if ($kmPerLiter > 0 && $speed > 55) {
            $kmPerLiter += 1.0;
        } elseif ($kmPerLiter > 0 && $speed > 0 && $speed < 25) {
            $kmPerLiter -= 1.0;
        }

        return [
            'fuel_type' => $fuelType,
            'estimated_km_per_liter' => round(max(0, $kmPerLiter), 1),
            'has_toll' => $tollMatch['has_toll'],
            'toll_roads' => $tollMatch['toll_roads'],
            'estimated_toll_cost' => $tollMatch['estimated_toll_cost'],
            'confidence' => $vehicle ? 'medium' : 'low',
            'reason' => $vehicle
                ? 'Estimated from vehicle type, route distance, travel time, and detected road names. Driver should verify fuel and toll values.'
                : 'Vehicle model is missing, so a conservative default efficiency is used. Driver should verify fuel and toll values.',
        ];
    }

    /**
     * One place that builds the Anthropic client. The two fare endpoints each
     * constructed their own identical copy with the timeout hardcoded, so the
     * configured value was ignored and a change had to be made twice.
     */
    private function anthropic(): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client([
            'base_uri' => 'https://api.anthropic.com',
            'timeout'  => (int) config('ai_chat.fare_timeout', 30),
            'headers'  => [
                'x-api-key'         => config('ai_chat.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
        ]);
    }

    /**
     * The text block from an Anthropic /v1/messages response. Was the same
     * seven-line extraction copied into fareReason() and fareAdvice().
     */
    private function extractAnthropicText(mixed $body): string
    {
        $text = '';
        if (isset($body['content']) && is_array($body['content'])) {
            foreach ($body['content'] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $text = $block['text'] ?? '';
                    break;
                }
            }
        }

        return trim((string) $text);
    }

    private function validFuelType(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, ['RON95', 'RON97', 'Diesel', 'Unknown'], true) ? $value : $fallback;
    }

    private function boundedFloat(mixed $value, float $min, float $max, float $fallback): float
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return round(min($max, max($min, (float) $value)), 2);
    }
}
