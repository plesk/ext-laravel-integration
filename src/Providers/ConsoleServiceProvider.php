<?php

namespace PleskExtLaravel\Providers;

use Dotenv\Dotenv;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use PleskExtLaravel\Console\Commands\ConfigSource;
use PleskExtLaravel\Console\Commands\ListEnv;
use Psr\Container\ContainerExceptionInterface;

class ConsoleServiceProvider extends ServiceProvider
{
    private const PLESK_EXT_LARAVEL_CONFIG_PATH = __DIR__ . '/../../config/plesk-ext-laravel.php';
    private const PLESK_ENV_FILE_NAME = '.env.plesk';
    private const ENV_SOURCE_LARAVEL_APP = 'laravel-app';
    private const ENV_SOURCE_LARAVEL_ENV = 'laravel-environment';
    private const ENV_SOURCE_PLESK_ENV = 'plesk-environment';
    private const ENV_SOURCE_DEFAULT = 'default';

    public function register()
    {
        $configSource = $this->ensureConfigSource();
        $this->mergeConfigFrom(self::PLESK_EXT_LARAVEL_CONFIG_PATH, 'plesk-ext-laravel');
        config()->set('plesk-ext-laravel.config-source', $configSource);

        $this->commands([
            ListEnv::class,
            ConfigSource::class,
        ]);
    }

    public function boot()
    {
        $this->app->resolving(Schedule::class, function ($schedule) {
            if (config('plesk-ext-laravel.queue-worker.enabled', false)) {
                $this->schedule($schedule);
            }
        });
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function schedule(Schedule $schedule)
    {
        $schedule->command(join(' ', ['queue:work', $this->getQueueScheduleParams()]))
            ->withoutOverlapping()
            ->everyMinute();
    }

    private function ensureConfigSource(): string
    {
        if (config('plesk-ext-laravel.queue-worker.enabled') !== null) {
            return self::ENV_SOURCE_LARAVEL_APP;
        }

        if (env('PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED') !== null) {
            return self::ENV_SOURCE_LARAVEL_ENV;
        }

        try {
            Dotenv::createImmutable(base_path(), self::PLESK_ENV_FILE_NAME)->load();
            if (env('PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED') !== null) {
                return self::ENV_SOURCE_PLESK_ENV;
            }
        } catch (InvalidArgumentException $ignore) {
            // do nothing
        }

        return self::ENV_SOURCE_DEFAULT;
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function getQueueScheduleParams(): string
    {
        $params = array_filter(config()->get('plesk-ext-laravel.queue-worker.params'));

        return join(' ', array_map(
            fn ($paramValue, $paramName) => is_bool($paramValue)
                ? "--$paramName"
                : "--$paramName=$paramValue",
            $params,
            array_keys($params)
        ));
    }
}
