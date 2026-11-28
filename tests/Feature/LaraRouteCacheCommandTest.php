<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Tests\TestCase;

/**
 * lara:route:cache must not cache the setup routes: before setup, the route files register
 * only those, and a cache of them keeps redirecting /admin to /setup after the setup is done.
 *
 * The cache is written to a temporary directory (APP_ROUTES_CACHE), never to bootstrap/cache.
 */
class LaraRouteCacheCommandTest extends TestCase
{
    use DatabaseTransactions;

    private string $cacheDirectory;

    /**
     * The route cache path phpunit.xml sets, restored afterwards so later tests do not
     * fall back to bootstrap/cache, which holds the routes of the development database.
     */
    private ?string $previousRoutesCachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousRoutesCachePath = $_SERVER['APP_ROUTES_CACHE'] ?? $_ENV['APP_ROUTES_CACHE'] ?? (getenv('APP_ROUTES_CACHE') ?: null);

        $this->cacheDirectory = storage_path('framework/testing/route-cache-'.getmypid());
        File::ensureDirectoryExists($this->cacheDirectory);

        $this->setRoutesCachePath($this->cacheDirectory.'/routes-v7.php');
    }

    protected function tearDown(): void
    {
        $this->setRoutesCachePath($this->previousRoutesCachePath);
        File::deleteDirectory($this->cacheDirectory);

        parent::tearDown();
    }

    public function test_clears_the_route_cache_and_builds_none_while_lara_needs_setup(): void
    {
        DB::table(config('lara-common.database.ent.entities'))->delete();

        foreach ($this->routeCacheFiles() as $path) {
            File::put($path, '<?php // stale cache');
        }

        $this->artisan('lara:route:cache')
            ->expectsOutputToContain('Lara needs setup')
            ->assertSuccessful();

        foreach ($this->routeCacheFiles() as $path) {
            $this->assertFileDoesNotExist($path);
        }
    }

    public function test_builds_the_route_cache_of_every_locale_once_lara_is_set_up(): void
    {
        $this->artisan('lara:route:cache')
            ->expectsOutputToContain('Routes cached successfully for all locales!')
            ->assertSuccessful();

        foreach ($this->routeCacheFiles() as $path) {
            $this->assertFileExists($path);
        }
    }

    /**
     * The default route cache and one per locale.
     *
     * @return list<string>
     */
    private function routeCacheFiles(): array
    {
        $files = [$this->cacheDirectory.'/routes-v7.php'];

        foreach (LaravelLocalization::getSupportedLanguagesKeys() as $locale) {
            $files[] = $this->cacheDirectory.'/routes-v7_'.$locale.'.php';
        }

        return $files;
    }

    private function setRoutesCachePath(?string $path): void
    {
        if ($path === null) {
            unset($_SERVER['APP_ROUTES_CACHE'], $_ENV['APP_ROUTES_CACHE']);
            putenv('APP_ROUTES_CACHE');

            return;
        }

        $_SERVER['APP_ROUTES_CACHE'] = $_ENV['APP_ROUTES_CACHE'] = $path;
        putenv('APP_ROUTES_CACHE='.$path);
    }
}
