<?php

return [
    'queue-worker' => [
        'enabled' => env('PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED', false),
        'params' => [
            // Stop when the queue is empty. Default: false
            'stop-when-empty' => env('PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY'),
            // The number of seconds a child process can run. Default: 60
            'timeout' => env('PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT'),
            // The number of jobs to process before stopping. Default: 0 (unlimited)
            'max-jobs' => env('PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS'),
            // The maximum number of seconds the worker should run. Default: 0 (unlimited)
            'max-time' => env('PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME'),
        ],
    ],
];
