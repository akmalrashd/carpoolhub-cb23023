<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | Live chat delivery uses Ably, which is hosted for us. This app runs on
    | shared hosting where proc_open and symlink are disabled, there is no
    | queue worker and scheduling is cron only, so running a WebSocket server
    | such as Reverb here is not possible.
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
