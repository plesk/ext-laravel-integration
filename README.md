# Plesk Laravel Toolkit integration with Laravel applications.

[![Apache 2](http://img.shields.io/badge/license-Apache%202-blue.svg)](http://www.apache.org/licenses/LICENSE-2.0)

This package adds the ability to use a queue for your Laravel application hosted in Plesk with installed the Laravel Toolkit extension.
Schedule your queue worker without going to the console and making changes to the Laravel Application source code. Enable/disable, configure the queue worker directly from the Plesk Laravel Toolkit interface.

### Installing and configuration

1. Add Plesk Laravel Toolkit integration package

    Plesk Laravel Toolkit integration package requires Laravel v7.0.0 and above. Use composer to install package to your Laravel Application

    ```
    composer require plesk/ext-laravel-integration
    ```

    > Laravel Toolkit integration package supports auto package discovery for Laravel v7.0+, therefore service provider registration is not required.
2. [Enable Scheduled Tasks](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/laravel-toolkit.80010/#viewing-your-application-s-scheduled-tasks)
3. Enable Queue
