<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records every billed Anthropic call (chat, fare-advice, recommend-route)
 * so cost and reliability — token spend, JSON-parse retry rate — can be
 * queried later instead of only appearing as scattered storage/logs lines.
 */
class AiUsageLogger
{
    public function record(
        User $user,
        string $endpoint,
        string $model,
        ?int $inputTokens,
        ?int $outputTokens,
        bool $success,
        bool $isRetry = false,
        ?string $errorType = null,
    ): void {
        try {
            AiUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => $endpoint,
                'model' => $model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'success' => $success,
                'is_retry' => $isRetry,
                'error_type' => $errorType,
            ]);
        } catch (Throwable $e) {
            // Usage logging must never break the actual AI response.
            Log::warning('AI usage log write failed: ' . $e->getMessage());
        }
    }
}
