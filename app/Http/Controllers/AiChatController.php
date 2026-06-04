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

        $history[] = ['role' => 'user',      'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        session(['ai_chat_history' => \array_slice($history, -$maxMessages)]);

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
        ]);

        if (empty(config('ai_chat.api_key'))) {
            return response()->json(['reason' => null]);
        }

        $language    = session('ai_chat_language', 'en');
        $distanceKm  = round((float) $request->input('distance_km'), 1);
        $durationMin = (int) round((float) $request->input('duration_min'));
        $fare        = number_format((float) $request->input('fare'), 2);
        $roads       = (string) ($request->input('roads') ?? '');
        $langInstr   = $language === 'ms' ? 'Bahasa Malaysia ringkas' : 'brief English';

        $prompt = "Malaysian carpooling context. Route: {$distanceKm}km, {$durationMin}min" .
            ($roads ? ", via {$roads}" : '') .
            ". Suggested fare: RM{$fare}. " .
            "Write ONE short sentence ({$langInstr}, max 20 words) explaining why this fare is reasonable. " .
            "Focus on distance as main factor. Plain text only, no JSON.";

        try {
            $http = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.anthropic.com',
                'timeout'  => 8,
                'headers'  => [
                    'x-api-key'         => config('ai_chat.api_key'),
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
            ]);

            $response = $http->post('/v1/messages', [
                'json' => [
                    'model'      => config('ai_chat.model', 'claude-haiku-4-5-20251001'),
                    'max_tokens' => 60,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ],
            ]);

            $body   = json_decode((string) $response->getBody(), true);
            $reason = trim((string) ($body['content'][0]['text'] ?? ''));

            return response()->json(['reason' => $reason ?: null]);

        } catch (\Throwable) {
            return response()->json(['reason' => null]);
        }
    }

    public function clearHistory(): JsonResponse
    {
        session()->forget(['ai_chat_history', 'ai_chat_language', 'ai_chat_pending_trip']);

        return response()->json(['ok' => true]);
    }
}
