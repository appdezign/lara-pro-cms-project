<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\EntityImporter;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityRelation;
use Tests\TestCase;
use Throwable;

/**
 * The real build of lara:v8:import-entities, on throwaway databases:
 * - source lara_v8_fixture: the v8 fixture (tests/Fixtures/LaraV8/v8_fixture.sql),
 * - target lara_v8_build_test: an essential base (core migrations + essential seed).
 * The site database and the shared test database are not touched. The builder writes
 * code into laracms/app for the fixture content types (v8fx…), which is removed afterwards.
 *
 * Skipped when the MySQL user cannot create databases.
 */
class ImportEntitiesBuildTest extends TestCase
{
    private const SOURCE = 'lara_v8_fixture';

    private const TARGET = 'lara_v8_build_test';

    private const LABELS = ['v8fxbook', 'v8fxevent', 'v8fxform'];

    private static bool $built = false;

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
        ]);
        DB::purge($default);
        DB::purge('lara_v8');

        if (! self::$built) {
            self::$built = true;
            $this->build();
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::removeGeneratedCode(dirname(__DIR__, 3).'/laracms/app');

        parent::tearDownAfterClass();
    }

    private function build(): void
    {
        self::removeGeneratedCode(base_path('laracms/app'));

        DB::connection('lara_v8')->statement('DROP DATABASE `'.self::SOURCE.'`');
        DB::connection('lara_v8')->statement('CREATE DATABASE `'.self::SOURCE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::purge('lara_v8');
        DB::connection('lara_v8')->unprepared(File::get(base_path('tests/Fixtures/LaraV8/v8_fixture.sql')));

        Artisan::call('migrate:fresh', ['--force' => true, '--path' => 'laracms/core/src/common/Database/Migrations']);
        Artisan::call('db:seed', ['--force' => true, '--class' => '\\Lara\\Common\\Database\\Seeders\\DatabaseCommonSeeder']);

        self::$report = (new EntityImporter(new V8Source))->run();
        self::$rerun = (new EntityImporter(new V8Source))->run();
    }

    /**
     * The files createEntity() writes for the fixture labels (the conventions of getGeneratedPaths()).
     */
    private static function removeGeneratedCode(string $appPath): void
    {
        foreach (self::LABELS as $label) {
            $single = ucfirst($label);
            $plural = ucfirst(Str::plural($label));

            foreach ([
                "Models/{$single}.php",
                "Entities/{$plural}Entity.php",
                "Policies/{$single}Policy.php",
                "Http/Controllers/Front/Entity/{$plural}Controller.php",
                "Http/Controllers/Front/Form/{$plural}Controller.php",
                "Database/Factories/{$single}Factory.php",
            ] as $file) {
                @unlink("{$appPath}/{$file}");
            }

            File::deleteDirectory("{$appPath}/Filament/Resources/{$plural}");
        }
    }

    public function test_the_build_has_no_errors_or_todos(): void
    {
        $problems = collect(self::$report)->whereIn('level', ['error', 'todo', 'builder']);

        $this->assertEmpty($problems, $problems->pluck('message')->implode("\n"));
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $changes = collect(self::$rerun)->whereNotIn('level', ['same', 'info', 'warning']);

        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }

    public function test_only_site_content_types_are_built_with_their_v8_settings(): void
    {
        $book = Entity::where('resource_slug', 'v8fxbooks')->firstOrFail();
        $form = Entity::where('resource_slug', 'v8fxforms')->firstOrFail();

        $this->assertNull(Entity::where('resource_slug', 'v8fxmenus')->first(), 'A system screen is not a content type.');

        $this->assertSame('Fixture Books', $book->title);
        $this->assertSame('Lara\\App\\Models\\V8fxbook', $book->model_class);
        $this->assertSame(['entity', 'modules', 901], [$book->cgroup, $book->nav_group, (int) $book->position]);
        $this->assertSame([1, 1, 1, 1, 1], [(int) $book->show_view_action, (int) $book->show_edit_action, (int) $book->show_delete_action, (int) $book->show_restore_action, (int) $book->filter_by_trashed]);
        $this->assertSame([1, 1, 12, 3], [(int) $book->media_has_featured, (int) $book->media_has_gallery, (int) $book->media_max_gallery, (int) $book->media_max_files]);
        $this->assertSame([1, 1, 0], [(int) $book->filter_by_tag, (int) $book->show_rich_body, (int) $book->show_rich_lead]);
        $this->assertSame(['created_at', null], [$book->sort_primary_field, $book->sort_secondary_field]);
        $this->assertSame('public', $book->media_disk_images);

        $this->assertSame(['form', 'forms'], [$form->cgroup, $form->nav_group]);
        $this->assertSame([1, 0, 1, 0], [(int) $form->show_view_action, (int) $form->show_edit_action, (int) $form->show_delete_action, (int) $form->filter_by_trashed]);
    }

    public function test_fields_are_mapped_and_geolocation_parts_folded_in(): void
    {
        $book = Entity::where('resource_slug', 'v8fxbooks')->firstOrFail();
        $fields = $book->customfields()->get()->keyBy('field_name');

        $this->assertSame(['geolocation', 'kind', 'note', 'pages', 'sticky', 'subtitle'], $fields->keys()->sort()->values()->all());

        $this->assertSame(['string', 'before-title', 1], [$fields['subtitle']->field_type, $fields['subtitle']->field_hook, (int) $fields['subtitle']->show_in_list]);
        $this->assertSame(['toggle', 'after-slug'], [$fields['sticky']->field_type, $fields['sticky']->field_hook]);
        $this->assertSame(['select', ['novel', 'poetry'], 1], [$fields['kind']->field_type, $fields['kind']->field_options, (int) $fields['kind']->is_required]);
        $this->assertSame(['textarea', 'enabledif', 'kind', 'isequal', 'poetry', 1], [$fields['note']->field_type, $fields['note']->rule_state, $fields['note']->rule_field, $fields['note']->rule_operator, $fields['note']->rule_value, (int) $fields['note']->conditional]);
        $this->assertSame('number', $fields['pages']->field_type);

        $columns = Schema::getColumnListing('lara_content_v8fxbooks');

        foreach (['subtitle', 'sticky', 'kind', 'note', 'pages', 'geo_address', 'geo_city', 'geo_latitude', 'geo_longitude'] as $column) {
            $this->assertContains($column, $columns);
        }

        foreach (['address', 'city', 'latitude'] as $column) {
            $this->assertNotContains($column, $columns, "{$column} belongs in the geolocation field.");
        }
    }

    public function test_the_form_gets_form_field_types_and_a_form_front_controller(): void
    {
        $form = Entity::where('resource_slug', 'v8fxforms')->firstOrFail();
        $fields = $form->customfields()->get()->keyBy('field_name');

        $this->assertSame(['textarea', 'default'], [$fields['comment']->field_type, $fields['comment']->field_hook]);
        $this->assertSame('toggle', $fields['agree']->field_type);

        $controller = base_path('laracms/app/Http/Controllers/Front/Form/V8fxformsController.php');
        $this->assertFileExists($controller);
        $this->assertStringContainsString('namespace Lara\\App\\Http\\Controllers\\Front\\Form;', File::get($controller));
    }

    public function test_the_relation_is_built_on_both_sides_with_its_model_methods(): void
    {
        $book = Entity::where('resource_slug', 'v8fxbooks')->firstOrFail();
        $event = Entity::where('resource_slug', 'v8fxevents')->firstOrFail();

        $hasMany = EntityRelation::where('entity_id', $book->id)->where('related_entity_id', $event->id)->firstOrFail();
        $belongsTo = EntityRelation::where('entity_id', $event->id)->where('related_entity_id', $book->id)->firstOrFail();

        $this->assertSame(['hasMany', 'v8fxbook_id', 0], [$hasMany->type, $hasMany->foreign_key, (int) $hasMany->is_filter]);
        $this->assertSame(['belongsTo', 'v8fxbook_id', 1], [$belongsTo->type, $belongsTo->foreign_key, (int) $belongsTo->is_filter]);
        $this->assertContains('v8fxbook_id', Schema::getColumnListing('lara_content_v8fxevents'));

        $this->assertStringContainsString('public function v8fxevents()', File::get(base_path('laracms/app/Models/V8fxbook.php')));
        $this->assertStringContainsString('public function v8fxbook()', File::get(base_path('laracms/app/Models/V8fxevent.php')));
    }

    public function test_front_views_are_imported(): void
    {
        $book = Entity::where('resource_slug', 'v8fxbooks')->firstOrFail();
        $form = Entity::where('resource_slug', 'v8fxforms')->firstOrFail();
        $views = $book->views()->get()->keyBy('method');

        $this->assertSame(['grid3', 12, '_sortbytaxonomy', 0], [$views['index']->list_type, (int) $views['index']->paginate, $views['index']->showtags, (int) $views['index']->is_single]);
        $this->assertSame(['_single', null, 1, 1], [$views['show']->list_type, $views['show']->showtags, (int) $views['show']->prevnext, (int) $views['show']->is_single]);
        $this->assertNull($form->views()->where('method', 'form')->firstOrFail()->list_type);
    }

    public function test_duplicates_and_changed_field_types_are_reported(): void
    {
        $warnings = collect(self::$report)->where('level', 'warning')->pluck('message');

        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, "'latitude' is defined more than once")));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, "'pages' is defined more than once")));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, "'agree' (yesno)")));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, "'pages' (intunsigned)")));
    }
}
