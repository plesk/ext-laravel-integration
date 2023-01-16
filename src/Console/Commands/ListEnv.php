<?php

namespace PleskExtLaravel\Console\Commands;

use Illuminate\Console\Command;

class ListEnv extends Command
{
    private const CONFIG_PARAMETERS_MAP = [
        'plesk-ext-laravel.queue-worker.enabled' => 'PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED',
        'plesk-ext-laravel.queue-worker.params.stop-when-empty' => 'PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY',
        'plesk-ext-laravel.queue-worker.params.timeout' => 'PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT',
        'plesk-ext-laravel.queue-worker.params.max-jobs' => 'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS',
        'plesk-ext-laravel.queue-worker.params.max-time' => 'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME',
    ];

    protected $signature = 'plesk-ext-laravel:list-env';

    protected $description = 'Display environment variables setted for Plesk Laravel Toolkit extension integration.';

    public function handle()
    {
        $parameters = [];
        foreach (self::CONFIG_PARAMETERS_MAP as $key => $name) {
            $value = config($key);

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $parameters[] = [
                'parameter' => $name,
                'value' => $value,
            ];
        }
        $this->table(['Parameter', 'Value'], $parameters);
    }
}
