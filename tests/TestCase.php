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
        if (file_exists(__DIR__ . '/../bootstrap/cache/config.php')) {
            $this->fail(
                'The configuration is cached, so phpunit.xml environment settings are '
                . 'being ignored and these tests would run against the local environment. '
                . 'Run "php artisan config:clear" before running the test suite.'
            );
        }

        parent::setUp();
    }
}
