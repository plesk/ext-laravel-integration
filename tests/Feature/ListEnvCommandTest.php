<?php

namespace PleskExtLaravel\Tests\Feature;

use PleskExtLaravel\Tests\TestCase;

class ListEnvCommandTest extends TestCase
{
    public function testLegacyOutputShowsWorkerVariables(): void
    {
        $this->setPleskEnv([
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED' => 'true',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY' => 'true',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT' => '1',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS' => '2',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME' => '3',
        ]);

        $this->artisan('plesk-ext-laravel:list-env')
            ->expectsTable(['Parameter', 'Value'], [
                ['PLESK_EXT_LARAVEL_QUEUE_MULTIPLE_SUPPORTED', 'true'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED', 'true'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY', 'true'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT', '1'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS', '2'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME', '3'],
            ])
            ->assertExitCode(0);
    }

    public function testLegacyOutputDefaultsEnabledToFalseWhenUnset(): void
    {
        // Legacy structure: a WORKER_* variable is present and there is no
        // queue-list key, so the enabled flag is unset and shown as "false".
        $this->setPleskEnv([
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT' => '5',
        ]);

        $this->artisan('plesk-ext-laravel:list-env')
            ->expectsTable(['Parameter', 'Value'], [
                ['PLESK_EXT_LARAVEL_QUEUE_MULTIPLE_SUPPORTED', 'true'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED', 'false'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT', '5'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME', ''],
            ])
            ->assertExitCode(0);
    }

    public function testEmptyEnvironmentOutputsLegacyFormat(): void
    {
        // No .env.plesk / nothing configured -> legacy format (no queue-list
        // row), so the extension treats it as the old package.
        $this->artisan('plesk-ext-laravel:list-env')
            ->expectsTable(['Parameter', 'Value'], [
                ['PLESK_EXT_LARAVEL_QUEUE_MULTIPLE_SUPPORTED', 'true'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED', 'false'],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME', ''],
            ])
            ->assertExitCode(0);
    }

    public function testExplicitEmptyQueueListOutputsMultiQueueFormat(): void
    {
        // Only "PLESK_EXT_LARAVEL_QUEUE_LIST=" present -> the new format, because
        // the queue-list key is explicitly there.
        $this->setPleskEnv([
            'PLESK_EXT_LARAVEL_QUEUE_LIST' => '',
        ]);

        $this->artisan('plesk-ext-laravel:list-env')
            ->expectsTable(['Parameter', 'Value'], [
                ['PLESK_EXT_LARAVEL_QUEUE_MULTIPLE_SUPPORTED', 'true'],
                ['PLESK_EXT_LARAVEL_QUEUE_LIST', ''],
            ])
            ->assertExitCode(0);
    }

    public function testMultiQueueOutputListsEveryQueueParameter(): void
    {
        $this->setPleskEnv([
            'PLESK_EXT_LARAVEL_QUEUE_LIST' => 'mail,notifications',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_ENABLED' => 'true',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_COUNT' => '3',
            'PLESK_EXT_LARAVEL_QUEUE_MAIL_TIMEOUT' => '120',
        ]);

        $this->artisan('plesk-ext-laravel:list-env')
            ->expectsTable(['Parameter', 'Value'], [
                ['PLESK_EXT_LARAVEL_QUEUE_MULTIPLE_SUPPORTED', 'true'],
                ['PLESK_EXT_LARAVEL_QUEUE_LIST', 'mail,notifications'],
                ['PLESK_EXT_LARAVEL_QUEUE_MAIL_ENABLED', 'true'],
                ['PLESK_EXT_LARAVEL_QUEUE_MAIL_COUNT', '3'],
                ['PLESK_EXT_LARAVEL_QUEUE_MAIL_STOP_WHEN_EMPTY', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_MAIL_TIMEOUT', '120'],
                ['PLESK_EXT_LARAVEL_QUEUE_MAIL_MAX_JOBS', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_MAIL_MAX_TIME', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_NOTIFICATIONS_ENABLED', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_NOTIFICATIONS_COUNT', '1'],
                ['PLESK_EXT_LARAVEL_QUEUE_NOTIFICATIONS_STOP_WHEN_EMPTY', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_NOTIFICATIONS_TIMEOUT', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_NOTIFICATIONS_MAX_JOBS', ''],
                ['PLESK_EXT_LARAVEL_QUEUE_NOTIFICATIONS_MAX_TIME', ''],
            ])
            ->assertExitCode(0);
    }
}
