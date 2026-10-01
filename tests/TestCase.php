<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * A cached config silently overrides everything in phpunit.xml's <php><env>
     * block, so the suite would run as APP_ENV=local against the file cache
     * driver with CSRF enabled, instead of the isolated testing environment.
     * That is hard to spot from a failure message, so fail loudly instead.
     */
    protected function setUp(): void
    {
        // resolved without the container: this runs before the app is created
        if (file_exists(__DIR__.'/../bootstrap/cache/config.php')) {
            $this->fail(
                'The configuration is cached, so phpunit.xml environment settings are '
                .'being ignored and these tests would run against the local environment. '
                .'Run "php artisan config:clear" before running the test suite.'
            );
        }

        parent::setUp();

        // route() builds its URLs on APP_URL; without a host (e.g. "https:site.test") every
        // request to such a URL lands on the wrong route, which fails as a puzzling 302, 404 or 405
        if (parse_url((string) config('app.url'), PHP_URL_HOST) === null) {
            $this->fail('APP_URL "'.config('app.url').'" has no host. Fix it in .env, e.g. "https://site.test".');
        }
    }
}
