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
