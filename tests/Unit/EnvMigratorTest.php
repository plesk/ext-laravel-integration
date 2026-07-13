<?php

namespace PleskExtLaravel\Tests\Unit;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use PleskExtLaravel\Migration\EnvMigrator;

class EnvMigratorTest extends TestCase
{
    /** @var string */
    private $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = tempnam(sys_get_temp_dir(), 'pleskenv');
    }

    protected function tearDown(): void
    {
        if (is_string($this->path) && is_file($this->path)) {
            unlink($this->path);
        }
        parent::tearDown();
    }

    public function testMigratesLegacyWorkerToDefaultQueue(): void
    {
        file_put_contents($this->path, implode("\n", [
            '# Plesk managed file',
            'APP_KEEP=1',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED=true',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY=false',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT=120',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS=1000',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME=3600',
        ]) . "\n");

        $result = (new EnvMigrator($this->path))->migrate();

        $this->assertSame(EnvMigrator::STATUS_MIGRATED, $result['status']);
        $this->assertSame(EnvMigrator::DEFAULT_QUEUE, $result['queue']);

        $values = $this->envValues();

        $this->assertSame('default', $values['PLESK_EXT_LARAVEL_QUEUE_LIST']);
        $this->assertSame('true', $values['PLESK_EXT_LARAVEL_QUEUE_DEFAULT_ENABLED']);
        $this->assertSame('false', $values['PLESK_EXT_LARAVEL_QUEUE_DEFAULT_STOP_WHEN_EMPTY']);
        $this->assertSame('120', $values['PLESK_EXT_LARAVEL_QUEUE_DEFAULT_TIMEOUT']);
        $this->assertSame('1000', $values['PLESK_EXT_LARAVEL_QUEUE_DEFAULT_MAX_JOBS']);
        $this->assertSame('3600', $values['PLESK_EXT_LARAVEL_QUEUE_DEFAULT_MAX_TIME']);
        $this->assertSame('1', $values['PLESK_EXT_LARAVEL_QUEUE_DEFAULT_COUNT']);
    }

    public function testRemovesLegacyKeysAndKeepsUnrelatedContent(): void
    {
        file_put_contents($this->path, implode("\n", [
            '# comment stays',
            'APP_KEEP=keep-me',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED=true',
            'PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT=60',
        ]) . "\n");

        (new EnvMigrator($this->path))->migrate();

        $raw = (string) file_get_contents($this->path);
        $values = $this->envValues();

        // Legacy keys removed.
        $this->assertArrayNotHasKey('PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED', $values);
        $this->assertArrayNotHasKey('PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT', $values);

        // Unrelated variable and comment preserved.
        $this->assertSame('keep-me', $values['APP_KEEP']);
        $this->assertStringContainsString('# comment stays', $raw);
    }

    public function testIsIdempotentWhenListAlreadyPresent(): void
    {
        $content = "PLESK_EXT_LARAVEL_QUEUE_LIST=mail\nPLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED=true\n";
        file_put_contents($this->path, $content);

        $result = (new EnvMigrator($this->path))->migrate();

        $this->assertSame(EnvMigrator::STATUS_SKIPPED, $result['status']);
        $this->assertSame('already-migrated', $result['reason']);
        $this->assertSame($content, file_get_contents($this->path));
    }

    public function testSkipsWhenNoLegacyVariables(): void
    {
        $content = "FOO=bar\n# just a comment\n";
        file_put_contents($this->path, $content);

        $result = (new EnvMigrator($this->path))->migrate();

        $this->assertSame(EnvMigrator::STATUS_SKIPPED, $result['status']);
        $this->assertSame('nothing-to-migrate', $result['reason']);
        $this->assertSame($content, file_get_contents($this->path));
    }

    public function testSkipsWhenFileIsMissing(): void
    {
        $missing = sys_get_temp_dir() . '/pleskenv_missing_' . uniqid();

        $result = (new EnvMigrator($missing))->migrate();

        $this->assertSame(EnvMigrator::STATUS_SKIPPED, $result['status']);
        $this->assertSame('file-not-found', $result['reason']);
    }

    /**
     * Parse the migrated file into a KEY => value map with phpdotenv (the same
     * parser the application uses), backed by an isolated repository so it does
     * not touch the real environment.
     *
     * @return array<string, string|null>
     */
    private function envValues(): array
    {
        return Dotenv::createArrayBacked(dirname($this->path), basename($this->path))->load();
    }
}
