# Plesk Laravel Toolkit integration with Laravel applications.

[![Apache 2](http://img.shields.io/badge/license-Apache%202-blue.svg)](http://www.apache.org/licenses/LICENSE-2.0)

This package adds Laravel Queues to the Laravel Toolkit extension by Plesk. With the help of this package, one can enable, disable, and configure the Laravel Queue Worker directly from the Plesk UI without having to access the Laravel Application using an SSH console.

### Installation and Configuration

Before you start, take into account the following:

-  Plesk Laravel Toolkit integration package works with Laravel version 7.0.0 and later.
-  Laravel Toolkit integration package supports auto package discovery for Laravel version 7.0 and later. In this case, registration of the service provider is not required.


Here’s how to add Laravel Queues to Plesk Laravel Toolkit:

1. [Integrate the Queue Laravel package into Plesk](https://support.plesk.com/hc/en-us/articles/9574602107410)
2. [Enable the Scheduled Tasks](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/laravel-toolkit.80010/#viewing-your-application-s-scheduled-tasks).
3. Enable Queues in Laravel Toolkit. To do so, go to ***Websites & Domains** > your domain > **Manage Laravel Application**, and then on the "Dashboard" tab, click the **Queues** toggle button so that it shows "Enabled".

### Multiple queues

You can run workers for several queues at once. The configuration lives in `.env.plesk` as flat variables:

```
# Comma-separated list of queues to process
PLESK_EXT_LARAVEL_QUEUE_LIST=mail,simple,calc

# Per-queue settings, namespaced by the (upper-cased) queue name
PLESK_EXT_LARAVEL_QUEUE_MAIL_ENABLED=true
PLESK_EXT_LARAVEL_QUEUE_MAIL_COUNT=3
PLESK_EXT_LARAVEL_QUEUE_MAIL_TIMEOUT=120
PLESK_EXT_LARAVEL_QUEUE_MAIL_MAX_JOBS=1000
PLESK_EXT_LARAVEL_QUEUE_MAIL_MAX_TIME=3600
PLESK_EXT_LARAVEL_QUEUE_MAIL_STOP_WHEN_EMPTY=false
```

For each enabled queue the package schedules `COUNT` parallel `queue:work --queue=<name>` workers. Supported per-queue parameters: `ENABLED`, `COUNT`, `STOP_WHEN_EMPTY`, `TIMEOUT`, `MAX_JOBS`, `MAX_TIME`. Run `php artisan plesk-ext-laravel:list-env` to see the effective variables.

### Migrating from the legacy single-worker configuration

Earlier versions used a single set of `PLESK_EXT_LARAVEL_QUEUE_WORKER_*` variables. These are automatically converted to the multi-queue format (a `default` queue) after the package is installed or updated.

The automatic migration runs as a Composer plugin, which Composer only executes if the host application allows it. Add the following to the application's `composer.json`:

```json
{
    "config": {
        "allow-plugins": {
            "plesk/ext-laravel-integration": true
        }
    }
}
```

If the plugin is not allowed (Composer skips disallowed plugins silently), run the migration manually at any time:

```
php artisan plesk-ext-laravel:migrate-env
```

Both paths perform the exact same, idempotent migration: it does nothing once `PLESK_EXT_LARAVEL_QUEUE_LIST` is present.
