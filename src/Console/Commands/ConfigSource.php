<?php

namespace PleskExtLaravel\Console\Commands;

use Illuminate\Console\Command;

class ConfigSource extends Command
{
    protected $signature = 'plesk-ext-laravel:config-source';

    protected $description = 'Display the current configuration source for integration with Plesk Laravel Toolkit extension';

    public function handle()
    {
        $this->line(config('plesk-ext-laravel.config-source'));
    }
}
