<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\ContentImporter;
use App\Console\Commands\LaraV8\Support\EntityImporter;
use App\Console\Commands\LaraV8\Support\UserImporter;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityRelation;
use Lara\Common\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Throwable;

/**
 * The real imports (lara:v8:import-entities, lara:v8:import-users), on throwaway databases:
 * - source lara_v8_fixture: the v8 fixture (tests/Fixtures/LaraV8/v8_fixture.sql),
 * - target lara_v8_build_test: an essential base (core migrations + essential seed).
 * The site database and the shared test database are not touched. The builder writes
 * code into laracms/app for the fixture content types (v8fx…), which is removed afterwards.
 * Every import runs twice; the second run must change nothing.
 *
 * Skipped when the MySQL user cannot create databases.
 */
class FixtureImportTest extends TestCase
{
    private const SOURCE = 'lara_v8_fixture';

    private const TARGET = 'lara_v8_build_test';

    private const LABELS = ['v8fxbook', 'v8fxevent', 'v8fxform'];

    private static bool $built = false;

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $report = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $rerun = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $userReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $userRerun = [];

    /** @var list<string> the webmaster permissions of the essential seed, before the user import */
    private static array $webmasterSeeded = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $contentReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $contentRerun = [];

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

        self::$webmasterSeeded = Role::findByName('webmaster', 'web')->permissions()->pluck('name')->all();
        self::$userReport = (new UserImporter(new V8Source))->run();
        self::$userRerun = (new UserImporter(new V8Source))->run();

        self::$contentReport = (new ContentImporter(new V8Source))->run(fresh: true);
        self::$contentRerun = (new ContentImporter(new V8Source))->run();
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

    public function test_the_user_import_has_no_errors_and_a_second_run_changes_nothing(): void
    {
        $errors = collect(self::$userReport)->where('level', 'error');
        $changes = collect(self::$userRerun)->whereNotIn('level', ['same', 'info', 'warning']);

        $this->assertEmpty($errors, $errors->pluck('message')->implode("\n"));
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }

    public function test_users_keep_their_v8_id_and_password_hash(): void
    {
        $users = DB::table('lara_auth_users')->orderBy('id')->get()->keyBy('id');

        $this->assertSame([1, 2, 3, 4, 5], $users->keys()->all(), 'The essential superadmin (id 3) is replaced by the v8 user 3.');

        $this->assertSame(['admin', 'Fixture Beheer', 'admin@fixture.test', 'nl'], [$users[2]->name, $users[2]->displayname, $users[2]->email, $users[2]->locale]);
        $this->assertSame(['webmaster', 'de', config('app.locale')], [$users[3]->name, $users[3]->middlename, $users[3]->locale]);
        $this->assertSame('en', $users[4]->locale);
        $this->assertSame(['API', 'fixture-api-token', ''], [$users[1]->name, $users[1]->api_token, $users[1]->email]);

        $this->assertSame('$2y$10$0gsJGtpSCdYXR/oc9XoLvukGkXnU6pt323pLJA4V1uyCWtl45et1G', $users[2]->password);
        $this->assertTrue(password_verify('fixture', $users[3]->password));
    }

    public function test_the_v8_admin_only_gets_superadmin_and_the_others_their_v8_role(): void
    {
        $roles = fn (int $id): array => User::findOrFail($id)->roles()->pluck('name')->all();

        $this->assertSame(['superadmin'], $roles(2));
        $this->assertSame(['webmaster'], $roles(3));
        $this->assertSame(['member'], $roles(4));
        $this->assertSame(['clerk'], $roles(5));
        $this->assertSame([], $roles(1));
    }

    public function test_roles_are_created_for_used_v8_roles_only(): void
    {
        $clerk = Role::where('name', 'clerk')->firstOrFail();

        $this->assertSame([1, 40], [(int) $clerk->has_panel_access, (int) $clerk->level]);
        $this->assertNull(Role::where('name', 'editor')->first(), 'A v8 role without users is skipped.');
    }

    public function test_role_permissions_follow_v8_for_shared_subjects_and_keep_v10_for_the_rest(): void
    {
        $webmaster = Role::findByName('webmaster', 'web')->permissions()->pluck('name');
        $clerk = Role::findByName('clerk', 'web')->permissions()->pluck('name');

        $this->assertSame(['update_v8fxbook', 'view_any_v8fxbook', 'view_v8fxbook'], $webmaster->filter(fn (string $p): bool => str_ends_with($p, '_v8fxbook'))->sort()->values()->all());
        $this->assertSame(['view_any_page', 'view_page'], $webmaster->filter(fn (string $p): bool => str_ends_with($p, '_page'))->sort()->values()->all());
        $this->assertSame([], $webmaster->filter(fn (string $p): bool => str_ends_with($p, '_setting'))->values()->all(), 'v8 knows setting and did not give it to the webmaster.');

        // a subject v8 never mentions keeps the permissions of the essential seed
        $untouched = fn (array $permissions): array => array_values(array_filter($permissions, fn (string $p): bool => str_ends_with($p, '_translation')));
        $this->assertSame($untouched(self::$webmasterSeeded), $untouched($webmaster->all()));

        $this->assertSame(['view_any_v8fxbook', 'view_v8fxbook'], $clerk->sort()->values()->all());
    }

    public function test_the_permissions_of_the_built_content_types_exist(): void
    {
        foreach (['view_any_v8fxbook', 'delete_any_v8fxevent', 'create_v8fxform'] as $permission) {
            $this->assertTrue(DB::table('lara_auth_permissions')->where('name', $permission)->exists(), $permission);
        }
    }

    public function test_skipped_abilities_and_orphan_assignments_are_reported(): void
    {
        $messages = collect(self::$userReport)->pluck('message');

        $this->assertTrue($messages->contains(fn (string $m): bool => str_contains($m, "v8 subject 'headertag' has no v10 policy")));
        $this->assertTrue($messages->contains(fn (string $m): bool => str_contains($m, 'for user 99, who no longer exists')));
        $this->assertTrue($messages->contains(fn (string $m): bool => str_contains($m, "Role 'editor' has no users")));
        $this->assertTrue($messages->contains(fn (string $m): bool => str_contains($m, "replaces v10 user 'superadmin'")));
    }

    public function test_the_content_import_has_no_errors_and_a_second_run_changes_nothing(): void
    {
        $errors = collect(self::$contentReport)->where('level', 'error');
        $changes = collect(self::$contentRerun)->whereNotIn('level', ['same', 'info', 'warning']);

        $this->assertEmpty($errors, $errors->pluck('message')->implode("\n"));
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }

    public function test_content_rows_keep_their_id_and_are_mapped(): void
    {
        $books = DB::table('lara_content_v8fxbooks')->orderBy('id')->get()->keyBy('id');

        $this->assertSame([1, 2], $books->keys()->all());

        $one = $books[1];
        $this->assertSame(['Boek één', 'Ondertitel', 1, '<p>Intro</p>', '<p>Tekst</p>'], [$one->title, $one->subtitle, (int) $one->sticky, $one->lead, $one->body]);
        $this->assertSame(['Grote Markt 1', 'Haarlem', 'manual', '52.38108000'], [$one->geo_address, $one->geo_city, $one->geo_location, (string) $one->geo_latitude], 'Geolocation parts go into the geo_* columns.');
        $this->assertSame(120, (int) $one->pages);
        $this->assertNull($one->kind, 'An empty custom field value becomes null.');
        $this->assertSame(0, (int) $one->position, 'A v8 NULL in a NOT NULL column gets the column default.');
        $this->assertNull($one->locked_by, 'Record locks are not imported.');
        $this->assertNull($one->locked_at);

        $two = $books[2];
        $this->assertSame([1, 'en', 'poetry', 'A note', '2024-03-01 10:00:00'], [(int) $two->language_parent, $two->language, $two->kind, $two->note, $two->deleted_at]);
        $this->assertSame(2, (int) $two->user_id, 'A row of a user that no longer exists goes to the v8 admin.');
    }

    public function test_core_content_is_translated_and_the_page_lead_goes_to_body2(): void
    {
        $ctas = DB::table('lara_blocks_ctas')->get()->keyBy('id');

        $this->assertSame(['brand', 'homeparallax1'], [$ctas[99]->overlaycolor, $ctas[99]->hook]);
        $this->assertSame(['black', 'homeparalax2'], [$ctas[98]->overlaycolor, $ctas[98]->hook], 'A value of an unpublished record is imported unchanged.');

        $pages = DB::table('lara_content_pages')->orderBy('id')->get()->keyBy('id');

        $this->assertSame([19, 1125], $pages->keys()->all(), 'v8 pages replace the essential pages (--fresh).');
        $this->assertSame('Home', $pages[19]->title, 'A v8 page with the id of an essential page replaces it.');
        $this->assertSame(['<p>Intro</p>', '<p>Tekst</p>', 'Arbeidshygiëne'], [$pages[1125]->body2, $pages[1125]->body, $pages[1125]->title]);
    }

    public function test_form_submissions_are_imported_without_title(): void
    {
        $form = DB::table('lara_form_v8fxforms')->first();

        $this->assertSame(['Jan Jansen', 'jan@fixture.test', 1, '192.0.2.1'], [$form->name, $form->email, (int) $form->agree, $form->ipaddress]);
        $this->assertNull($form->comment);
        $this->assertNotContains('title', Schema::getColumnListing('lara_form_v8fxforms'));
    }

    public function test_content_problems_are_reported(): void
    {
        $warnings = collect(self::$contentReport)->where('level', 'warning')->pluck('message');

        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, 'lara_form_v8fxforms.title has no v10 column; 1 value')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, "Page 1125 'Arbeidshygiëne': the v8 lead is in body2")));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, 'belonged to a v8 user that no longer exists')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, 'lara_content_v8fxevents.v8fxbook_id point to a v8fxbook that does not exist')));
    }
}
