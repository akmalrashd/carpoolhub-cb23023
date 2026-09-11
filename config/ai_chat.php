<?php

return [
    'api_key'    => env('ANTHROPIC_API_KEY', ''),
    'model'      => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    'max_tokens' => 4096,
    'timeout'    => 15,

    // The fare endpoints allow longer than the chat timeout: they are fired in
    // the background while the driver keeps filling the trip form, so a slower
    // answer is preferable to no answer. Was hardcoded at two call sites.
    'fare_timeout' => 30,

    // Max conversation turns kept in session (each turn = 1 user + 1 assistant msg)
    'history_turns' => 4,

    // Per-user daily cap shared across /ai/chat, /ai/fare-advice and
    // /ai/recommend-route (see the 'ai-spend' rate limiter) — every one of
    // these bills a real Anthropic call, so this is a spend ceiling, not
    // just an abuse guard.
    'daily_limit' => (int) env('AI_CHAT_DAILY_LIMIT', 150),

    // Platform-wide daily ceiling across ALL users combined, checked by the
    // same 'ai-spend' rate limiter — the per-user cap above bounds one
    // account, but not total spend if the user base (or fake accounts) grows.
    // Starting value is a rough placeholder; retune it once real traffic is
    // visible via the admin Reports page (ReportService::aiUsageSummary()).
    'global_daily_limit' => (int) env('AI_CHAT_GLOBAL_DAILY_LIMIT', 3000),
];
