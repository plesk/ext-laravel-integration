<?php

namespace PleskExtLaravel\Tests\Feature;

use PleskExtLaravel\Tests\TestCase;

class ConfigSourceCommandTest extends TestCase
{
    public function testReportsPleskEnvironmentForMultiQueue(): void
    {
        $this->setPleskEnv(['PLESK_EXT_LARAVEL_QUEUE_LIST' => 'mail']);

        $this->artisan('plesk-ext-laravel:config-source')
            ->expectsOutput('plesk-environment')
            ->assertExitCode(0);
    }

    public function testReportsPleskEnvironmentForLegacyWorker(): void
    {
        $this->setPleskEnv(['PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED' => 'true']);

        $this->artisan('plesk-ext-laravel:config-source')
            ->expectsOutput('plesk-environment')
            ->assertExitCode(0);
    }

    public function testReportsDefaultWhenNothingConfigured(): void
    {
        $this->artisan('plesk-ext-laravel:config-source')
            ->expectsOutput('default')
            ->assertExitCode(0);
    }
}
