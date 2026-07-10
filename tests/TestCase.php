<?php

namespace PleskExtLaravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use PleskExtLaravel\Providers\ConsoleServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearPleskEnv();
    }

    protected function tearDown(): void
    {
        $this->clearPleskEnv();
        parent::tearDown();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ConsoleServiceProvider::class];
    }

    /**
     * Set Plesk queue environment variables for the current test. The commands
     * read them through PleskEnv, which reads $_ENV directly.
     *
     * @param array<string, string> $variables
     */
    protected function setPleskEnv(array $variables): void
    {
        foreach ($variables as $key => $value) {
            $_ENV[$key] = $value;
        }
    }

    /**
     * Remove every Plesk queue variable so tests do not leak into each other.
     */
    protected function clearPleskEnv(): void
    {
        foreach (array_keys($_ENV) as $key) {
            if (str_starts_with($key, 'PLESK_EXT_LARAVEL_QUEUE')) {
                unset($_ENV[$key]);
            }
        }

        foreach (array_keys($_SERVER) as $key) {
            if (str_starts_with($key, 'PLESK_EXT_LARAVEL_QUEUE')) {
                unset($_SERVER[$key]);
            }
        }
    }
}
