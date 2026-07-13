<?php

namespace PleskExtLaravel\Console\Commands;

use Illuminate\Console\Command;
use PleskExtLaravel\Migration\EnvMigrator;

class MigrateEnv extends Command
{
    protected $signature = 'plesk-ext-laravel:migrate-env';

    protected $description = 'Migrate legacy Plesk queue worker settings in .env.plesk to the multi-queue format.';

    public function handle()
    {
        $result = (new EnvMigrator(base_path(EnvMigrator::ENV_FILE)))->migrate();

        if (($result['status'] ?? null) === EnvMigrator::STATUS_MIGRATED) {
            $this->info(sprintf(
                'Migrated legacy queue worker settings to queue "%s".',
                $result['queue']
            ));

            return 0;
        }

        $this->line(sprintf('Nothing to migrate (%s).', $result['reason'] ?? 'unknown'));

        return 0;
    }
}
