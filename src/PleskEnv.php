<?php

namespace PleskExtLaravel;

use Dotenv\Dotenv;

/**
 * Reads Plesk queue configuration directly from the .env.plesk file.
 *
 * Values are read from the environment at runtime (not via config()) on
 * purpose: Plesk mutates .env.plesk dynamically, while Laravel's config can be
 * cached (config:cache), which would freeze env() values and make the cached
 * configuration go stale.
 */
class PleskEnv
{
    public const ENV_FILE = '.env.plesk';

    public const LIST_VAR = 'PLESK_EXT_LARAVEL_QUEUE_LIST';

    /**
     * Per-queue env suffix => queue:work flag name.
     *
     * "stop-when-empty" is a boolean flag rendered as "--stop-when-empty"
     * (no value); the rest are rendered as "--flag=value".
     */
    public const QUEUE_FLAGS = [
        'STOP_WHEN_EMPTY' => 'stop-when-empty',
        'TIMEOUT' => 'timeout',
        'MAX_JOBS' => 'max-jobs',
        'MAX_TIME' => 'max-time',
    ];

    /** Per-queue suffixes that are not queue:work flags. */
    public const SUFFIX_ENABLED = 'ENABLED';
    public const SUFFIX_COUNT = 'COUNT';

    private const BOOLEAN_FLAGS = ['stop-when-empty'];

    /** Legacy single-worker "enabled" variable. */
    public const LEGACY_ENABLED = 'PLESK_EXT_LARAVEL_QUEUE_WORKER_ENABLED';

    /** Legacy single-worker variable => queue:work flag name. */
    private const LEGACY_FLAGS = [
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_STOP_WHEN_EMPTY' => 'stop-when-empty',
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_TIMEOUT' => 'timeout',
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_JOBS' => 'max-jobs',
        'PLESK_EXT_LARAVEL_QUEUE_WORKER_MAX_TIME' => 'max-time',
    ];

    /** Queue configuration is present (in .env.plesk or the environment). */
    public const SOURCE_PLESK_ENV = 'plesk-environment';

    /** Nothing is configured. */
    public const SOURCE_DEFAULT = 'default';

    private static bool $loaded = false;

    /**
     * Load .env.plesk once per process. Uses an immutable, safe loader so it
     * never overrides already-defined variables and never throws when the file
     * is missing.
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $basePath = function_exists('base_path') ? base_path() : getcwd();
        Dotenv::createImmutable($basePath, self::ENV_FILE)->safeLoad();

        self::$loaded = true;
    }

    /**
     * Read a raw environment value, or null when it is unset/empty.
     */
    public static function get(string $name): ?string
    {
        self::load();

        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if ($value === false || $value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * Whether the multi-queue scheme is configured (the queue list is present).
     * When false, the legacy single-worker variables should be used instead.
     */
    public static function isMultiQueue(): bool
    {
        return self::get(self::LIST_VAR) !== null;
    }

    /**
     * Where the queue configuration comes from. Reads .env.plesk directly, so
     * the result is either "plesk-environment" (any queue setting present) or
     * "default" (nothing configured).
     */
    public static function configSource(): string
    {
        if (self::isMultiQueue() || self::get(self::LEGACY_ENABLED) !== null) {
            return self::SOURCE_PLESK_ENV;
        }

        return self::SOURCE_DEFAULT;
    }

    /**
     * The list of queue names from PLESK_EXT_LARAVEL_QUEUE_LIST.
     * Trimmed, de-duplicated, empty entries removed. Original names are kept
     * (used as-is for --queue=).
     *
     * @return string[]
     */
    public static function queues(): array
    {
        $raw = self::get(self::LIST_VAR) ?? '';

        $queues = array_filter(
            array_map('trim', explode(',', $raw)),
            static fn (string $queue): bool => $queue !== ''
        );

        return array_values(array_unique($queues));
    }

    /**
     * Build the env variable name for a queue: the queue name is normalized
     * (non-alphanumeric chars -> "_", uppercased) so names like "email-high"
     * map to a valid variable while "--queue=email-high" keeps the original.
     */
    public static function key(string $queue, string $suffix): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '_', $queue));

        return "PLESK_EXT_LARAVEL_QUEUE_{$normalized}_{$suffix}";
    }

    /**
     * Whether the queue should be scheduled. Being present in the list implies
     * enabled unless explicitly disabled via ..._ENABLED=false.
     */
    public static function isEnabled(string $queue): bool
    {
        return self::toBool(self::get(self::key($queue, self::SUFFIX_ENABLED)), true);
    }

    /**
     * Number of parallel worker processes to run for the queue (>= 1).
     */
    public static function count(string $queue): int
    {
        $value = self::get(self::key($queue, self::SUFFIX_COUNT));

        return $value === null ? 1 : max(1, (int) $value);
    }

    /**
     * Per-queue suffixes in display order: the non-flag ones (ENABLED, COUNT)
     * followed by the queue:work flag suffixes.
     *
     * @return string[]
     */
    public static function queueSuffixes(): array
    {
        return array_merge(
            [self::SUFFIX_ENABLED, self::SUFFIX_COUNT],
            array_keys(self::QUEUE_FLAGS)
        );
    }

    /**
     * queue:work flag fragments for the queue (e.g. ["--timeout=120",
     * "--stop-when-empty"]). Unset params are skipped, falling back to Laravel
     * defaults.
     *
     * @return string[]
     */
    public static function flags(string $queue): array
    {
        $map = [];
        foreach (self::QUEUE_FLAGS as $suffix => $flag) {
            $map[self::key($queue, $suffix)] = $flag;
        }

        return self::renderFlags($map);
    }

    /**
     * Whether the legacy single worker is enabled (default: false, as before).
     */
    public static function legacyEnabled(): bool
    {
        return self::toBool(self::get(self::LEGACY_ENABLED), false);
    }

    /**
     * queue:work flag fragments for the legacy single worker.
     *
     * @return string[]
     */
    public static function legacyFlags(): array
    {
        return self::renderFlags(self::LEGACY_FLAGS);
    }

    /**
     * All env variable names of the legacy single worker, in display order.
     *
     * @return string[]
     */
    public static function legacyParameterNames(): array
    {
        return array_merge([self::LEGACY_ENABLED], array_keys(self::LEGACY_FLAGS));
    }

    /**
     * Render "--flag" / "--flag=value" fragments from a map of env variable
     * name => flag name. Unset variables are skipped; boolean flags are emitted
     * without a value only when truthy.
     *
     * @param array<string, string> $map
     * @return string[]
     */
    private static function renderFlags(array $map): array
    {
        $flags = [];

        foreach ($map as $name => $flag) {
            $value = self::get($name);

            if ($value === null) {
                continue;
            }

            if (in_array($flag, self::BOOLEAN_FLAGS, true)) {
                if (self::toBool($value, false)) {
                    $flags[] = "--{$flag}";
                }
                continue;
            }

            $flags[] = "--{$flag}={$value}";
        }

        return $flags;
    }

    private static function toBool(?string $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
    }
}
