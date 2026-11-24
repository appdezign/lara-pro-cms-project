<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\ContentImporter;
use App\Console\Commands\LaraV8\Support\CoreCustomFields;
use App\Console\Commands\LaraV8\Support\EntityImporter;
use App\Console\Commands\LaraV8\Support\MenuImporter;
use App\Console\Commands\LaraV8\Support\SiteOverrides;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityView;
use Tests\TestCase;
use Throwable;

/**
 * An older v8 pages table (inventiverepair): no template and no lead column, but a pageid
 * (an outline number such as 02.01.03 that no v8 code reads). The page imports must not
 * read the missing columns. Runs the page parts of the imports on the v8 fixture with that
 * table, on throwaway databases (the site database and the shared test database are not touched).
 *
 * Skipped when the MySQL user cannot create databases.
 */
class OlderPagesSchemaTest extends TestCase
{
    private const SOURCE = 'lara_v8_fixture_oldpages';

    private const TARGET = 'lara_v8_build_oldpages';

    private static bool $built = false;

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $entityReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $contentReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $menuReport = [];

    private static int $pageViewsBefore = 0;

    private static int $pageViewsAfter = 0;

    private static ?string $overridesPath = null;

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
        ]);
        DB::purge($default);
        DB::purge('lara_v8');

        // the site's own overrides (v8-overrides.php) never apply to the fixture
        self::$overridesPath ??= sys_get_temp_dir().'/lara-v8-overrides-'.uniqid().'.php';
        config(['v8-migration.overrides' => self::$overridesPath, 'v8-migration.skip_entities' => ['v8fxleftover']]);
        SiteOverrides::flush();

        if (! self::$built) {
            self::$built = true;
            $this->build();
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$overridesPath !== null) {
            @unlink(self::$overridesPath);
        }

        parent::tearDownAfterClass();
    }

    private function build(): void
    {
        DB::connection('lara_v8')->statement('DROP DATABASE `'.self::SOURCE.'`');
        DB::connection('lara_v8')->statement('CREATE DATABASE `'.self::SOURCE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::purge('lara_v8');
        DB::connection('lara_v8')->unprepared(File::get(base_path('tests/Fixtures/LaraV8/v8_fixture.sql')));

        // the older pages table
        DB::connection('lara_v8')->statement('ALTER TABLE `lara_content_pages` DROP COLUMN `template`, DROP COLUMN `lead`, ADD COLUMN `pageid` varchar(255) NULL AFTER `title`');
        DB::connection('lara_v8')->table('lara_content_pages')->where('id', 1125)->update(['pageid' => '02.01.03']);
        // inventiverepair also defines pageid as a custom field of the page type (in the v8 page admin)
        DB::connection('lara_v8')->table('lara_ent_entity_custom_columns')->insert([
            'entity_id' => 1, 'fieldtitle' => 'Pageid', 'fieldname' => 'pageid', 'fieldtype' => 'string', 'fieldhook' => 'after',
            'fielddata' => '', 'primary' => 0, 'required' => 0, 'fieldstate' => 'enabled', 'position' => 9,
        ]);

        Artisan::call('migrate:fresh', ['--force' => true, '--path' => 'laracms/core/src/common/Database/Migrations']);
        Artisan::call('db:seed', ['--force' => true, '--class' => '\\Lara\\Common\\Database\\Seeders\\DatabaseCommonSeeder']);

        $pages = Entity::where('resource_slug', 'pages')->firstOrFail();
        self::$pageViewsBefore = EntityView::where('entity_id', $pages->id)->count();
        self::$entityReport = (new EntityImporter(new V8Source))->run(only: ['page']);
        self::$pageViewsAfter = EntityView::where('entity_id', $pages->id)->count();

        self::$contentReport = (new ContentImporter(new V8Source))->run(only: ['page'], fresh: true);
        self::$menuReport = (new MenuImporter(new V8Source))->run(dryRun: true);
    }

    public function test_page_views_are_left_alone_and_the_missing_column_is_reported(): void
    {
        $this->assertEmpty(collect(self::$entityReport)->where('level', 'error'), collect(self::$entityReport)->pluck('message')->implode("\n"));
        $this->assertSame(self::$pageViewsBefore, self::$pageViewsAfter);
        $this->assertTrue(
            collect(self::$entityReport)->contains(fn (array $line): bool => $line['level'] === 'info' && str_contains($line['message'], 'Page template column missing')),
            'The missing template column is reported.'
        );
    }

    public function test_the_pages_are_imported_and_pageid_is_reported_as_not_imported(): void
    {
        $report = collect(self::$contentReport);

        $this->assertEmpty($report->where('level', 'error'), $report->pluck('message')->implode("\n"));
        $this->assertSame('Arbeidshygiëne', DB::table('lara_content_pages')->where('id', 1125)->value('title'));
        $this->assertNull(DB::table('lara_content_pages')->where('id', 1125)->value('body2'));
        $this->assertSame([], DB::table('lara_content_pages')->where(fn ($q) => $q->whereNull('template')->orWhere('template', ''))->pluck('id')->all(), 'Every page gets a template (v10 requires one).');
        $this->assertSame('standard', DB::table('lara_content_pages')->where('id', 1125)->value('template'));
        $this->assertTrue(
            $report->contains(fn (array $line): bool => $line['level'] === 'warning' && str_contains($line['message'], 'lara_content_pages.pageid')),
            'The v8 pageid values are reported as not imported.'
        );
        $this->assertFalse($report->contains(fn (array $line): bool => str_contains($line['message'], 'the v8 lead is in body2')));
    }

    public function test_pageid_is_not_built_as_a_core_custom_field(): void
    {
        $this->assertNotContains('pageid', collect((new CoreCustomFields(new V8Source))->candidates())->pluck('v8_field.fieldname')->all());
        $this->assertFalse(collect(self::$entityReport)->contains(fn (array $line): bool => str_contains($line['message'], 'pageid')));
        $this->assertNotContains('pageid', DB::getSchemaBuilder()->getColumnListing('lara_content_pages'));
    }

    public function test_the_menu_import_does_not_need_page_templates(): void
    {
        $this->assertNotEmpty(self::$menuReport);
        $this->assertEmpty(collect(self::$menuReport)->where('level', 'error'), collect(self::$menuReport)->pluck('message')->implode("\n"));
    }
}
