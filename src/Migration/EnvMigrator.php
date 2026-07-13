<?php

namespace PleskExtLaravel\Migration;

/**
 * Migrates legacy single-worker settings in a .env.plesk file to the
 * multi-queue format, in place.
 *
 * This class is intentionally free of any Laravel/Dotenv dependency so it can
 * run both inside the Laravel application (via the artisan command) and inside
 * the Composer runtime (via the Composer plugin on package update), where the
 * framework is not booted.
 *
 * Legacy:
 *     PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED=true
 *     PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT=120
 *
 * Result (the legacy worker had no --queue, i.e. the "default" queue):
 *     PLESK_EXT_LARAVEL_QUEUE_LIST=default
 *     PLESK_EXT_LARAVEL_QUEUE_DEFAULT_ENABLED=true
 *     PLESK_EXT_LARAVEL_QUEUE_DEFAULT_TIMEOUT=120
 *     PLESK_EXT_LARAVEL_QUEUE_DEFAULT_COUNT=1
 */
class EnvMigrator
{
    public const ENV_FILE = '.env.plesk';

    public const LIST_VAR = 'PLESK_EXT_LARAVEL_QUEUE_LIST';

    /** The queue the legacy worker effectively processed. */
    public const DEFAULT_QUEUE = 'default';

    public const STATUS_MIGRATED = 'migrated';
    public const STATUS_SKIPPED = 'skipped';

    /** Legacy variable name => per-queue suffix in the new scheme. */
    private const LEGACY_MAP = [
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED' => 'ENABLED',
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY' => 'STOP_WHEN_EMPTY',
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT' => 'TIMEOUT',
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS' => 'MAX_JOBS',
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME' => 'MAX_TIME',
    ];

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Perform the migration.
     *
     * Idempotent: does nothing if the file is missing, already migrated
     * (LIST present), or has no legacy variables.
     *
     * @return array{status: string, reason?: string, queue?: string, added?: string[]}
     */
    public function migrate(): array
    {
        if (!is_file($this->path)) {
            return ['status' => self::STATUS_SKIPPED, 'reason' => 'file-not-found'];
        }

        $content = (string) file_get_contents($this->path);
        $values = $this->parse($content);

        if (array_key_exists(self::LIST_VAR, $values)) {
            return ['status' => self::STATUS_SKIPPED, 'reason' => 'already-migrated'];
        }

        if (empty(array_intersect_key($values, self::LEGACY_MAP))) {
            return ['status' => self::STATUS_SKIPPED, 'reason' => 'nothing-to-migrate'];
        }

        $queue = self::DEFAULT_QUEUE;
        $prefix = 'PLESK_EXT_LARAVEL_QUEUE_' . strtoupper($queue) . '_';

        $additions = [self::LIST_VAR => $queue];
        foreach (self::LEGACY_MAP as $legacyName => $suffix) {
            if (array_key_exists($legacyName, $values)) {
                $additions[$prefix . $suffix] = $values[$legacyName];
            }
        }
        if (!array_key_exists($prefix . 'COUNT', $additions)) {
            $additions[$prefix . 'COUNT'] = '1';
        }

        $migrated = $this->rewrite($content, array_keys(self::LEGACY_MAP), $additions);

        if (file_put_contents($this->path, $migrated) === false) {
            return ['status' => self::STATUS_SKIPPED, 'reason' => 'write-failed'];
        }

        return [
            'status' => self::STATUS_MIGRATED,
            'queue' => $queue,
            'added' => array_keys($additions),
        ];
    }

    /**
     * Parse simple KEY=VALUE lines into an associative array.
     *
     * @return array<string, string>
     */
    private function parse(string $content): array
    {
        $values = [];
        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            if (preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=(.*)$/', $line, $matches)) {
                $values[$matches[1]] = trim($matches[2]);
            }
        }

        return $values;
    }

    /**
     * Drop the given keys and append the new KEY=VALUE lines, preserving the
     * rest of the file (comments, blank lines, unrelated variables).
     *
     * @param string[] $removeKeys
     * @param array<string, string> $additions
     */
    private function rewrite(string $content, array $removeKeys, array $additions): string
    {
        $eol = strpos($content, "\r\n") !== false ? "\r\n" : "\n";

        $kept = [];
        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            if (
                preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=/', $line, $matches)
                && in_array($matches[1], $removeKeys, true)
            ) {
                continue;
            }
            $kept[] = $line;
        }

        while (!empty($kept) && trim((string) end($kept)) === '') {
            array_pop($kept);
        }

        foreach ($additions as $key => $value) {
            $kept[] = "{$key}={$value}";
        }

        return implode($eol, $kept) . $eol;
    }
}
