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
