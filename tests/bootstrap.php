<?php

/*
|--------------------------------------------------------------------------
| PHPUnit bootstrap
|--------------------------------------------------------------------------
|
| A cached config file (bootstrap/cache/config.php) is written by
| `php artisan config:cache` and pins APP_ENV to whatever environment
| produced it. Laravel loads that cache *instead of* the .env + phpunit.xml
| variables, so the suite would boot as `local` even though phpunit.xml sets
| APP_ENV=testing. That silently re-enables the production middleware stack
| (CSRF verification included) and breaks every POST feature test with 419.
|
| Deleting the runtime cache here — before the framework boots — restores the
| documented Laravel contract that tests run in the `testing` environment.
| It only removes regenerable, gitignored runtime artifacts; no application
| middleware or security setting is weakened.
|
*/

$cacheDirectory = __DIR__.'/../bootstrap/cache';

foreach (['config.php', 'routes-v7.php', 'events.php'] as $cachedFile) {
    $path = $cacheDirectory.'/'.$cachedFile;

    if (is_file($path)) {
        @unlink($path);
    }
}

require __DIR__.'/../vendor/autoload.php';
