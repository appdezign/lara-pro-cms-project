<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\ContentImporter;
use App\Console\Commands\LaraV8\Support\EntityImporter;
use App\Console\Commands\LaraV8\Support\SiteFields;
use App\Console\Commands\LaraV8\Support\SiteOverrides;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityView;
use Tests\TestCase;
use Throwable;

/**
 * A site whose v8 theme shows the page lead (gain): v10 pages have no lead column, so the lead
 * goes into its own custom field (v8-fields.php 'page.lead' => ['field' => 'intro', …]) instead
 * of body2. Runs the page parts of the imports on the v8 fixture, on throwaway databases (the
 * site database and the shared test database are not touched).
 *
 * Skipped when the MySQL user cannot create databases.
 */
class PageLeadFieldTest extends TestCase
{
    private const SOURCE = 'lara_v8_fixture_pagelead';

    private const TARGET = 'lara_v8_build_pagelead';

    private static bool $built = false;

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $entityReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $entityRerun = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $contentReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $contentRerun = [];

    private static ?string $fieldsPath = null;

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

        // the fixture's field settings; the site's own overrides never apply (an absent file)
        self::$fieldsPath ??= sys_get_temp_dir().'/lara-v8-fields-pagelead-'.uniqid().'.php';
        File::put(self::$fieldsPath, "<?php\n\nreturn [\n    'page.lead' => ['field' => 'intro', 'title' => 'Intro', 'type' => 'richeditor', 'hook' => 'after-slug', 'reason' => 'test'],\n];\n");
        config([
            'v8-migration.fields' => self::$fieldsPath,
            'v8-migration.overrides' => self::$fieldsPath.'.overrides',
            'v8-migration.skip_entities' => ['v8fxleftover'],
        ]);
        SiteFields::flush();
        SiteOverrides::flush();

        if (! self::$built) {
            self::$built = true;
            $this->build();
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$fieldsPath !== null) {
            @unlink(self::$fieldsPath);
        }

        SiteFields::flush();

        parent::tearDownAfterClass();
    }

    private function build(): void
    {
        DB::connection('lara_v8')->statement('DROP DATABASE `'.self::SOURCE.'`');
        DB::connection('lara_v8')->statement('CREATE DATABASE `'.self::SOURCE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::purge('lara_v8');
        DB::connection('lara_v8')->unprepared(File::get(base_path('tests/Fixtures/LaraV8/v8_fixture.sql')));

        Artisan::call('migrate:fresh', ['--force' => true, '--path' => 'laracms/core/src/common/Database/Migrations']);
        Artisan::call('db:seed', ['--force' => true, '--class' => '\\Lara\\Common\\Database\\Seeders\\DatabaseCommonSeeder']);

        self::$entityReport = (new EntityImporter(new V8Source))->run(only: ['page']);
        self::$entityRerun = (new EntityImporter(new V8Source))->run(only: ['page']);
        self::$contentReport = (new ContentImporter(new V8Source))->run(only: ['page'], fresh: true);
        self::$contentRerun = (new ContentImporter(new V8Source))->run(only: ['page']);
    }

    public function test_the_page_lead_becomes_a_custom_field_of_the_core_page_type(): void
    {
        $report = collect(self::$entityReport);
        $this->assertEmpty($report->where('level', 'error'), $report->pluck('message')->implode("\n"));

        $field = Entity::where('resource_slug', 'pages')->firstOrFail()->customfields()->where('field_name', 'intro')->first();

        $this->assertNotNull($field);
        $this->assertSame(['Intro', 'richeditor', 'after-slug'], [$field->title, $field->field_type, $field->field_hook]);
        $this->assertTrue(Schema::hasColumn('lara_content_pages', 'intro'));
        $this->assertTrue($report->contains(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], "v8 column 'lead' becomes the custom field 'intro'")));

        $changes = collect(self::$entityRerun)->where('entity', 'page')->whereIn('level', ['create', 'update', 'differs', 'error']);
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }

    public function test_a_page_template_needs_no_extra_body_fields_for_the_lead(): void
    {
        $pages = Entity::where('resource_slug', 'pages')->firstOrFail();

        $this->assertSame(0, (int) EntityView::where('entity_id', $pages->id)->where('template', 'fxwallpaper')->value('template_extra_fields'));
    }

    public function test_the_page_lead_is_imported_into_the_field_and_not_into_body2(): void
    {
        $page = DB::table('lara_content_pages')->where('id', 1125)->first(['intro', 'body2', 'body']);

        $this->assertSame(['<p>Intro</p>', null, '<p>Tekst</p>'], [$page->intro, $page->body2, $page->body]);
        $this->assertSame('<p>Korte intro</p>', DB::table('lara_content_pages')->where('id', 1126)->value('intro'));
        $this->assertFalse(collect(self::$contentReport)->contains(fn (array $l): bool => str_contains($l['message'], 'the v8 lead is in body2')));

        $changes = collect(self::$contentRerun)->whereNotIn('level', ['same', 'info', 'warning']);
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }
}
