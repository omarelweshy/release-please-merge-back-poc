<?php

use App\Logging\UseJsonFormatter;
use Monolog\Handler\StreamHandler;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'json')),
            'ignore_exceptions' => false,
        ],

        /*
        | One JSON object per line, stamped with the request id and the build.
        | This is the channel deployed environments should use.
        */
        'json' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => ['stream' => 'php://stderr'],
            'tap' => [UseJsonFormatter::class],
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        /*
        | Plain text, for a human reading the file locally. Deliberately not the
        | default: what is pleasant to read by eye is not what a shipper parses.
        */
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

    ],

];
