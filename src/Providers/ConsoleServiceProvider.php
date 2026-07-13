<?php

namespace PleskExtLaravel\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use PleskExtLaravel\Console\Commands\ConfigSource;
use PleskExtLaravel\Console\Commands\ListEnv;
use PleskExtLaravel\PleskEnv;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            ListEnv::class,
            ConfigSource::class,
        ]);
    }

    public function boot()
    {
        $this->app->resolving(Schedule::class, function ($schedule) {
            $this->schedule($schedule);
        });
    }

    /**
     * Register the queue workers, supporting both the multi-queue scheme
     * (PLESK_EXT_LARAVEL_QUEUE_LIST) and the legacy single-worker variables.
     */
    public function schedule(Schedule $schedule)
    {
        if (PleskEnv::isMultiQueue()) {
            $this->scheduleQueues($schedule);

            return;
        }

        $this->scheduleLegacyWorker($schedule);
    }

    /**
     * Schedule one "queue:work" per parallel worker for every enabled queue.
     *
     * Each worker gets a unique --name so its command string (and therefore its
     * withoutOverlapping mutex) is distinct, allowing COUNT workers to run in
     * parallel. runInBackground() lets a single schedule:run tick launch them
     * all without running them sequentially.
     */
    private function scheduleQueues(Schedule $schedule): void
    {
        foreach (PleskEnv::queues() as $queue) {
            if (!PleskEnv::isEnabled($queue)) {
                continue;
            }

            $flags = PleskEnv::flags($queue);
            $count = PleskEnv::count($queue);

            for ($i = 0; $i < $count; $i++) {
                $command = array_merge(
                    ['queue:work'],
                    $flags,
                    ["--queue={$queue}", "--name=queue-worker-{$queue}-{$i}"]
                );

                $schedule->command(implode(' ', $command))
                    ->withoutOverlapping()
                    ->runInBackground()
                    ->everyMinute();
            }
        }
    }

    /**
     * Schedule the legacy single worker (no --queue, i.e. the default queue),
     * matching the behaviour of previous versions.
     */
    private function scheduleLegacyWorker(Schedule $schedule): void
    {
        if (!PleskEnv::legacyEnabled()) {
            return;
        }

        $command = array_merge(['queue:work'], PleskEnv::legacyFlags());

        $schedule->command(implode(' ', $command))
            ->withoutOverlapping()
            ->everyMinute();
    }
}
