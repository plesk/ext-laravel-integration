<?php

namespace PleskExtLaravel\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use PleskExtLaravel\Migration\EnvMigrator;
use Throwable;

/**
 * Runs the .env.plesk migration automatically after the package is installed or
 * updated in the host application.
 *
 * Requires the host application to allow this plugin:
 *
 *     "config": {
 *         "allow-plugins": {
 *             "plesk/ext-laravel-integration": true
 *         }
 *     }
 *
 * When the plugin is not allowed (e.g. non-interactive Plesk environments),
 * Composer skips it silently; the migration can then be run manually with
 * "php artisan plesk-ext-laravel:migrate-env". Both paths call EnvMigrator.
 */
class MigrationPlugin implements PluginInterface, EventSubscriberInterface
{
    private ?Composer $composer = null;

    private ?IOInterface $io = null;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostUpdate',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdate',
        ];
    }

    public function onPostUpdate(): void
    {
        $path = $this->projectRoot() . DIRECTORY_SEPARATOR . EnvMigrator::ENV_FILE;

        try {
            $result = (new EnvMigrator($path))->migrate();
        } catch (Throwable $e) {
            $this->write('<warning>[plesk-ext-laravel] Skipped .env.plesk migration: ' . $e->getMessage() . '</warning>');

            return;
        }

        if (($result['status'] ?? null) === EnvMigrator::STATUS_MIGRATED) {
            $this->write('<info>[plesk-ext-laravel] Migrated legacy queue settings in .env.plesk to the multi-queue format.</info>');
        }
    }

    private function projectRoot(): string
    {
        // vendor-dir is an absolute path; its parent is the project root.
        $vendorDir = (string) $this->composer->getConfig()->get('vendor-dir');

        return dirname($vendorDir);
    }

    private function write(string $message): void
    {
        if ($this->io !== null) {
            $this->io->writeError($message);
        }
    }
}
