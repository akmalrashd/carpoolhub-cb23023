<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | Chat realtime delivery uses Ably (hosted) — this app runs on Hostinger
    | shared hosting (proc_open/symlink disabled, no queue worker, cron-only
    | scheduling), so a self-hosted WebSocket server (Reverb) can't run here.
    | Ably needs no persistent process on our end: Laravel makes one HTTP
    | call per broadcast, and the browser talks to Ably's cloud directly.
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'log'),

    'connections' => [

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
