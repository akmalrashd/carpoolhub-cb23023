<?php

namespace App\Http\Controllers;

use App\Services\Ai\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(private readonly ChatbotService $chatbotService)
    {
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
                $result['url'] = route($result['route']);
            } catch (\Throwable) {
                $result['intent'] = 'general';
                unset($result['route'], $result['url']);
            }
        }

        return response()->json($result);
    }

    public function fareReason(Request $request): JsonResponse
    {
        $request->validate([
            'distance_km'  => ['required', 'numeric', 'min:0', 'max:1000'],
            'duration_min' => ['required', 'numeric', 'min:0', 'max:600'],
            'fare'         => ['required', 'numeric', 'min:0'],
            'roads'        => ['nullable', 'string', 'max:200'],
            'vehicle'      => ['nullable', 'string', 'max:100'],
        ]);

        if (empty(config('ai_chat.api_key'))) {
            return response()->json(['reason' => null]);
        }

        $language    = session('ai_chat_language', 'en');
        $distanceKm  = round((float) $request->input('distance_km'), 1);
        $durationMin = (int) round((float) $request->input('duration_min'));
        $fare        = number_format((float) $request->input('fare'), 2);
        $roads       = trim((string) ($request->input('roads') ?? ''));
        $vehicle     = trim((string) ($request->input('vehicle') ?? ''));
        $langInstr   = $language === 'ms' ? 'Bahasa Malaysia ringkas' : 'brief English';

        // Detect road context from road names
        $hasHighway = $roads && (
            stripos($roads, 'lebuhraya') !== false ||
            stripos($roads, 'highway')   !== false ||
            stripos($roads, 'expressway') !== false
        );
        $roadContext    = $hasHighway ? 'highway-dominant (better fuel economy per km)' : 'city/mixed roads (more stop-go, higher fuel use)';
        $distCategory   = $distanceKm < 15 ? 'short urban' : ($distanceKm < 60 ? 'medium distance' : 'long-haul');

        if ($vehicle) {
            $prompt = "Malaysian carpooling fare. Vehicle: {$vehicle}. Route: {$distanceKm}km, {$durationMin}min. Roads: {$roadContext}. Trip type: {$distCategory}. Base fare: RM{$fare}.\n\n" .
                "Adjust fare considering ALL these factors:\n" .
                "1. Vehicle class fuel cost (eco=base, mid=base, SUV/4WD=+15-30%, MPV/luxury=+25-45%)\n" .
                "2. Road type: highway improves fuel efficiency → moderate premium; city roads → higher premium\n" .
                "3. Distance: long-haul gets slight economy of scale discount vs short trips\n" .
                "4. Malaysian carpooling norms (RM0.80-1.50/km typical range)\n\n" .
                "Return JSON only: {\"fare\": <2dp>, \"reason\": \"<{$langInstr}, 25-30 words, mention vehicle + road type + key factor>\"}";
        } else {
            $prompt = "Malaysian carpooling. Route: {$distanceKm}km, {$durationMin}min. Roads: {$roadContext}. Trip type: {$distCategory}. Base fare: RM{$fare}. " .
                "Return JSON only: {\"fare\": {$fare}, \"reason\": \"<{$langInstr}, 25-30 words, mention road type, distance category, and fare justification>\"}";
        }

        try {
            $http = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.anthropic.com',
                'timeout'  => 30,
                'headers'  => [
                    'x-api-key'         => config('ai_chat.api_key'),
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
            ]);

            $response = $http->post('/v1/messages', [
                'json' => [
                    'model'      => trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                    'max_tokens' => 120,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ],
            ]);

            $body    = json_decode((string) $response->getBody(), true);
            
            $text = '';
            if (isset($body['content']) && is_array($body['content'])) {
                foreach ($body['content'] as $block) {
                    if (($block['type'] ?? '') === 'text') {
                        $text = $block['text'] ?? '';
                        break;
                    }
                }
            }
            $raw = trim((string) $text);

            $jsonStr = $raw;
            if (preg_match('/\{.*\}/s', $raw, $matches)) {
                $jsonStr = $matches[0];
            }
            $decoded = json_decode($jsonStr, true);

            $adjustedFare = isset($decoded['fare']) && is_numeric($decoded['fare'])
                ? round((float) $decoded['fare'], 2)
                : null;
            $reason = isset($decoded['reason']) ? trim((string) $decoded['reason']) : null;

            return response()->json([
                'fare'   => $adjustedFare,
                'reason' => $reason,
            ]);

        } catch (\Throwable) {
            return response()->json(['fare' => null, 'reason' => null]);
        }
    }

    public function clearHistory(): JsonResponse
    {
        session()->forget(['ai_chat_history', 'ai_chat_language', 'ai_chat_pending_trip']);

        return response()->json(['ok' => true]);
    }

    public function fareAdvice(Request $request): JsonResponse
    {
        $request->validate([
            'distance_km'  => ['required', 'numeric', 'min:0', 'max:1000'],
            'duration_min' => ['required', 'numeric', 'min:0', 'max:600'],
            'roads'        => ['nullable', 'string', 'max:500'],
            'vehicle'      => ['nullable', 'string', 'max:120'],
        ]);

        $distanceKm  = round((float) $request->input('distance_km'), 1);
        $durationMin = (int) round((float) $request->input('duration_min'));
        $roads       = trim((string) ($request->input('roads') ?? ''));
        $vehicle     = trim((string) ($request->input('vehicle') ?? ''));
        $fallback    = $this->fallbackFareAdvice($vehicle, $roads, $distanceKm, $durationMin);

        if (empty(config('ai_chat.api_key'))) {
            return response()->json($fallback);
        }

        $language  = session('ai_chat_language', 'en');
        $langInstr = $language === 'ms' ? 'Bahasa Malaysia ringkas' : 'brief English';

        $prompt = "You are a Malaysian carpool fare advisor. Estimate practical cost inputs, not the final fare.\n\n" .
            "Vehicle: " . ($vehicle ?: 'unknown') . "\n" .
            "Route distance: {$distanceKm} km\n" .
            "Route duration: {$durationMin} min\n" .
            "Road names/context: " . ($roads ?: 'unknown') . "\n\n" .
            "Return JSON only with this shape:\n" .
            "{\"fuel_type\":\"RON95|RON97|Diesel|EV|Unknown\",\"estimated_km_per_liter\":number,\"has_toll\":boolean,\"toll_roads\":[\"road\"],\"estimated_toll_cost\":number,\"confidence\":\"low|medium|high\",\"reason\":\"{$langInstr}, 20-35 words\"}\n\n" .
            "Rules: infer fuel type from vehicle model. Use real-world km/L for Malaysian mixed driving. Toll estimate may be approximate based on toll highway names only. Use 0 toll if unsure.";

        try {
            $http = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.anthropic.com',
                'timeout'  => 30,
                'headers'  => [
                    'x-api-key'         => config('ai_chat.api_key'),
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
            ]);

            $response = $http->post('/v1/messages', [
                'json' => [
                    'model'      => trim(config('ai_chat.model', 'claude-haiku-4-5-20251001')),
                    'max_tokens' => 180,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ],
            ]);

            $body    = json_decode((string) $response->getBody(), true);
            
            $text = '';
            if (isset($body['content']) && is_array($body['content'])) {
                foreach ($body['content'] as $block) {
                    if (($block['type'] ?? '') === 'text') {
                        $text = $block['text'] ?? '';
                        break;
                    }
                }
            }
            $raw = trim((string) $text);
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $cleaned = preg_replace('/\s*```$/', '', $cleaned ?? $raw);
            $decoded = json_decode(trim($cleaned ?? $raw), true);

            if (! is_array($decoded)) {
                return response()->json($fallback);
            }

            $fuelType = $this->validFuelType($decoded['fuel_type'] ?? null, $fallback['fuel_type']);

            return response()->json([
                'fuel_type' => $fuelType,
                'estimated_km_per_liter' => $fuelType === 'EV' ? 0 : $this->boundedFloat($decoded['estimated_km_per_liter'] ?? null, 4, 35, $fallback['estimated_km_per_liter']),
                'has_toll' => (bool) ($decoded['has_toll'] ?? $fallback['has_toll']),
                'toll_roads' => array_values(array_slice(array_filter((array) ($decoded['toll_roads'] ?? $fallback['toll_roads'])), 0, 4)),
                'estimated_toll_cost' => $this->boundedFloat($decoded['estimated_toll_cost'] ?? null, 0, 80, $fallback['estimated_toll_cost']),
                'confidence' => in_array(($decoded['confidence'] ?? ''), ['low', 'medium', 'high'], true) ? $decoded['confidence'] : $fallback['confidence'],
                'reason' => trim((string) ($decoded['reason'] ?? $fallback['reason'])) ?: $fallback['reason'],
            ]);
        } catch (\Throwable) {
            return response()->json($fallback);
        }
    }

    private function fallbackFareAdvice(string $vehicle, string $roads, float $distanceKm, int $durationMin): array
    {
        $vehicleLower = strtolower($vehicle);
        $roadsLower = strtolower($roads);
        $fuelType = 'RON95';
        $kmPerLiter = 11.5;

        if (preg_match('/diesel|hilux|triton|d-max|dmax|navara|fortuner|transit|van|lorry|truck|pickup/i', $vehicle) === 1) {
            $fuelType = 'Diesel';
            $kmPerLiter = 9.5;
        } elseif (preg_match('/ev|electric|tesla|byd|ora|ioniq|leaf|model 3|model y/i', $vehicle) === 1) {
            $fuelType = 'EV';
            $kmPerLiter = 0;
        } elseif (preg_match('/bmw|mercedes|audi|volvo|lexus|mazda cx|civic type r|turbo/i', $vehicleLower) === 1) {
            $fuelType = 'RON97';
            $kmPerLiter = 10.5;
        } elseif (preg_match('/myvi|axia|bezza|iriz|saga|persona|city|vios|almera|jazz|yaris/i', $vehicleLower) === 1) {
            $kmPerLiter = 13.0;
        } elseif (preg_match('/alza|exora|avanza|innova|xpander|serena|estima|vellfire|alphard|mpv|suv/i', $vehicleLower) === 1) {
            $kmPerLiter = 9.0;
        }

        $speed = $durationMin > 0 ? $distanceKm / ($durationMin / 60) : 0;
        if ($kmPerLiter > 0 && $speed > 55) {
            $kmPerLiter += 1.0;
        } elseif ($kmPerLiter > 0 && $speed > 0 && $speed < 25) {
            $kmPerLiter -= 1.0;
        }

        $knownTollRoads = ['plus', 'nkve', 'elite', 'kesas', 'ldp', 'duke', 'mex', 'akleh', 'sprint', 'npe', 'gce', 'skve', 'lekas', 'silk', 'besraya'];
        $tollRoads = [];
        foreach ($knownTollRoads as $road) {
            if (str_contains($roadsLower, $road)) {
                $tollRoads[] = strtoupper($road);
            }
        }

        $estimatedToll = count($tollRoads) > 0 ? min(25, max(2, round($distanceKm * 0.12, 2))) : 0.0;

        return [
            'fuel_type' => $fuelType,
            'estimated_km_per_liter' => round(max(0, $kmPerLiter), 1),
            'has_toll' => count($tollRoads) > 0,
            'toll_roads' => $tollRoads,
            'estimated_toll_cost' => $estimatedToll,
            'confidence' => $vehicle ? 'medium' : 'low',
            'reason' => $vehicle
                ? 'Estimated from vehicle type, route distance, travel time, and detected road names. Driver should verify fuel and toll values.'
                : 'Vehicle model is missing, so a conservative default efficiency is used. Driver should verify fuel and toll values.',
        ];
    }

    private function validFuelType(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, ['RON95', 'RON97', 'Diesel', 'EV', 'Unknown'], true) ? $value : $fallback;
    }

    private function boundedFloat(mixed $value, float $min, float $max, float $fallback): float
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return round(min($max, max($min, (float) $value)), 2);
    }
}
