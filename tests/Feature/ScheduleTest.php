<?php

namespace PleskExtLaravel\Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use PleskExtLaravel\Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function testMultiQueueTranslatesEveryParameterToFlags(): void
    {
        $this->setPleskEnv([
            'PLESK_EXT_LARAVEL_QUEUE_LIST' => 'mail',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_STOP_WHEN_EMPTY' => 'true',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_TIMEOUT' => '120',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_MAX_JOBS' => '1000',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_MAX_TIME' => '3600',
        ]);

        $commands = $this->queueWorkerCommands();

        // COUNT defaults to 1 -> a single worker.
        $this->assertCount(1, $commands);

        $command = $commands[0];
        $this->assertStringContainsString('--queue=mail', $command);
        $this->assertStringContainsString('--name=queue-worker-mail-0', $command);
        $this->assertStringContainsString('--stop-when-empty', $command);
        $this->assertStringContainsString('--timeout=120', $command);
        $this->assertStringContainsString('--max-jobs=1000', $command);
        $this->assertStringContainsString('--max-time=3600', $command);
    }

    public function testMultiQueueOmitsStopWhenEmptyFlagWhenFalse(): void
    {
        $this->setPleskEnv([
            'PLESK_EXT_LARAVEL_QUEUE_LIST' => 'mail',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_STOP_WHEN_EMPTY' => 'false',
        ]);

        $commands = $this->queueWorkerCommands();

        $this->assertCount(1, $commands);
        $this->assertStringNotContainsString('--stop-when-empty', $commands[0]);
    }

    public function testMultiQueueSchedulesParallelWorkersForEnabledQueuesOnly(): void
    {
        $this->setPleskEnv([
            'PLESK_EXT_LARAVEL_QUEUE_LIST' => 'mail,notifications',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_COUNT' => '2',
            'PLESK_EXT_LARAVEL_QUEUE_NOTIFICATIONS_ENABLED' => 'false',
        ]);

        $commands = $this->queueWorkerCommands();

        // notifications is disabled -> only the two mail workers are scheduled.
        $this->assertCount(2, $commands);

        foreach ($commands as $command) {
            $this->assertStringContainsString('--queue=mail', $command);
        }

        $joined = implode("\n", $commands);
        $this->assertStringContainsString('--name=queue-worker-mail-0', $joined);
        $this->assertStringContainsString('--name=queue-worker-mail-1', $joined);
        $this->assertStringNotContainsString('--queue=notifications', $joined);
    }

    public function testLegacyWorkerTranslatesEveryParameterToFlags(): void
    {
        $this->setPleskEnv([
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED' => 'true',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY' => 'true',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT' => '1',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS' => '2',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME' => '3',
        ]);

        $commands = $this->queueWorkerCommands();

        $this->assertCount(1, $commands);

        $command = $commands[0];
        // Legacy worker runs the default queue, so no --queue option.
        $this->assertStringNotContainsString('--queue=', $command);
        $this->assertStringContainsString('--stop-when-empty', $command);
        $this->assertStringContainsString('--timeout=1', $command);
        $this->assertStringContainsString('--max-jobs=2', $command);
        $this->assertStringContainsString('--max-time=3', $command);
    }

    public function testNothingIsScheduledWhenLegacyWorkerDisabled(): void
    {
        // No PLESK_EXT_LARAVEL_QUEUE_* variables set at all.
        $this->assertCount(0, $this->queueWorkerCommands());
    }

    /**
     * The "queue:work" command strings registered on the scheduler by the
     * package (this is what "schedule:list" renders).
     *
     * @return string[]
     */
    private function queueWorkerCommands(): array
    {
        $schedule = $this->app->make(Schedule::class);

        $commands = [];
        foreach ($schedule->events() as $event) {
            if (is_string($event->command) && str_contains($event->command, 'queue:work')) {
                $commands[] = $event->command;
            }
        }

        return $commands;
    }
}
