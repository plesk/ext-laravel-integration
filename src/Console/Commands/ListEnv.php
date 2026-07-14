<?php

namespace PleskExtLaravel\Console\Commands;

use Illuminate\Console\Command;
use PleskExtLaravel\PleskEnv;

class ListEnv extends Command
{
    private const MULTIPLE_QUEUES_SUPPORTED = 'PLESK_EXT_LARAVEL_QUEUE_MULTIPLE_SUPPORTED';

    protected $signature = 'plesk-ext-laravel:list-env';

    protected $description = 'Display environment variables set for Plesk Laravel Toolkit extension integration.';

    public function handle()
    {
        $parameters = PleskEnv::isMultiQueue()
            ? $this->multiQueueParameters()
            : $this->legacyParameters();

        array_unshift($parameters, [
            'parameter' => self::MULTIPLE_QUEUES_SUPPORTED,
            'value' => 'true',
        ]);

        $this->table(['Parameter', 'Value'], $parameters);
    }

    /**
     * Legacy single-worker output, kept identical to previous versions.
     *
     * @return array<int, array{parameter: string, value: string}>
     */
    private function legacyParameters(): array
    {
        $parameters = [];
        foreach (PleskEnv::legacyParameterNames() as $name) {
            $value = PleskEnv::get($name);

            if ($value === null) {
                // The legacy "enabled" flag defaulted to false, so it was shown
                // as "false" when unset; other params were shown empty.
                $value = $name === PleskEnv::LEGACY_ENABLED ? 'false' : '';
            }

            $parameters[] = [
                'parameter' => $name,
                'value' => $value,
            ];
        }

        return $parameters;
    }

    /**
     * Multi-queue output: the queue list plus the per-queue variables of every
     * listed queue.
     *
     * @return array<int, array{parameter: string, value: string}>
     */
    private function multiQueueParameters(): array
    {
        $parameters = [[
            'parameter' => PleskEnv::LIST_VAR,
            'value' => PleskEnv::get(PleskEnv::LIST_VAR) ?? '',
        ]];

        foreach (PleskEnv::queues() as $queue) {
            foreach (PleskEnv::queueSuffixes() as $suffix) {
                $name = PleskEnv::key($queue, $suffix);

                // COUNT falls back to 1 when unset (same as the scheduler),
                // so the displayed value matches what actually runs.
                $value = $suffix === PleskEnv::SUFFIX_COUNT
                    ? (string) PleskEnv::count($queue)
                    : PleskEnv::get($name) ?? '';

                $parameters[] = [
                    'parameter' => $name,
                    'value' => $value,
                ];
            }
        }

        return $parameters;
    }
}
