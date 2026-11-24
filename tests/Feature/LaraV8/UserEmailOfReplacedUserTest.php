<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\UserImporter;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Throwable;

/**
 * A v8 user whose e-mail address belongs to the essential v10 superadmin, which another v8 user
 * with the same id (3) replaces in the same run (inventiverepair: v8 admin 2 had the address of
 * the essential superadmin). The address is free once user 3 is written, so neither the dry run
 * nor the real run reports a conflict. Runs on throwaway databases (the site database and the
 * shared test database are not touched).
 *
 * Skipped when the MySQL user cannot create databases.
 */
class UserEmailOfReplacedUserTest extends TestCase
{
    private const SOURCE = 'lara_v8_fixture_useremail';

    private const TARGET = 'lara_v8_build_useremail';

    private static bool $built = false;

    private static string $email = '';

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $dryRun = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $report = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $rerun = [];

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::statement('CREATE DATABASE IF NOT EXISTS `'.self::TARGET.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            DB::statement('CREATE DATABASE IF NOT EXISTS `'.self::SOURCE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (Throwable $e) {
            $this->markTestSkipped('Cannot create the test databases: '.$e->getMessage());
        }

        $default = config('database.default');
        config([
            "database.connections.{$default}.database" => self::TARGET,
            'database.connections.lara_v8' => [...config("database.connections.{$default}"), 'database' => self::SOURCE, 'strict' => false],
            'v8-migration.skip_entities' => ['v8fxleftover'],
        ]);
        DB::purge($default);
        DB::purge('lara_v8');

        if (! self::$built) {
            self::$built = true;
            $this->build();
        }
    }

    private function build(): void
    {
        DB::connection('lara_v8')->statement('DROP DATABASE `'.self::SOURCE.'`');
        DB::connection('lara_v8')->statement('CREATE DATABASE `'.self::SOURCE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::purge('lara_v8');
        DB::connection('lara_v8')->unprepared(File::get(base_path('tests/Fixtures/LaraV8/v8_fixture.sql')));

        Artisan::call('migrate:fresh', ['--force' => true, '--path' => 'laracms/core/src/common/Database/Migrations']);
        Artisan::call('db:seed', ['--force' => true, '--class' => '\\Lara\\Common\\Database\\Seeders\\DatabaseCommonSeeder']);

        // the v8 admin (2) has the address of the essential superadmin (3), which v8 user 3 replaces
        self::$email = (string) DB::table('lara_auth_users')->where('id', 3)->value('email');
        DB::connection('lara_v8')->table('lara_auth_users')->where('id', 2)->update(['email' => self::$email]);

        self::$dryRun = (new UserImporter(new V8Source))->run(dryRun: true);
        self::$report = (new UserImporter(new V8Source))->run();
        self::$rerun = (new UserImporter(new V8Source))->run();
    }

    public function test_the_essential_superadmin_has_an_address_to_take_over(): void
    {
        $this->assertNotSame('', self::$email);
        $this->assertNotSame(self::$email, DB::connection('lara_v8')->table('lara_auth_users')->where('id', 3)->value('email'));
    }

    public function test_neither_the_dry_run_nor_the_import_reports_a_conflict(): void
    {
        foreach ([self::$dryRun, self::$report] as $report) {
            $errors = collect($report)->where('level', 'error');
            $this->assertEmpty($errors, $errors->pluck('message')->implode("\n"));
        }

        $this->assertTrue(collect(self::$dryRun)->contains(fn (array $l): bool => $l['message'] === "User 2 'admin' would be created."));
    }

    public function test_the_v8_admin_gets_the_address_and_a_second_run_changes_nothing(): void
    {
        $this->assertSame(self::$email, DB::table('lara_auth_users')->where('id', 2)->value('email'));
        $this->assertSame('webmaster@fixture.test', DB::table('lara_auth_users')->where('id', 3)->value('email'));

        $changes = collect(self::$rerun)->whereIn('level', ['error', 'create', 'update']);
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }
}
