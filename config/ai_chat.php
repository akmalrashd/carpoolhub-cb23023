<?php

return [
    'api_key'    => env('ANTHROPIC_API_KEY', ''),
    'model'      => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    'max_tokens' => 4096,
    'timeout'    => 15,

    // Max conversation turns kept in session (each turn = 1 user + 1 assistant msg)
    'history_turns' => 4,
];
