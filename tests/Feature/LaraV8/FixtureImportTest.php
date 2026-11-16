<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\ContentImporter;
use App\Console\Commands\LaraV8\Support\EntityImporter;
use App\Console\Commands\LaraV8\Support\MenuImporter;
use App\Console\Commands\LaraV8\Support\ObjectImporter;
use App\Console\Commands\LaraV8\Support\SystemImporter;
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

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $objectReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $objectRerun = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $menuReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $menuRerun = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $systemReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $systemRerun = [];

    private static ?string $langPath = null;

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

        if (self::$langPath !== null) {
            exec('rm -rf '.escapeshellarg(self::$langPath));
        }

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
        // what v10 does when a record is saved (e.g. by the Pages list): only updated_at changes
        DB::table('lara_content_v8fxbooks')->where('id', 1)->update(['updated_at' => now()]);
        self::$contentRerun = (new ContentImporter(new V8Source))->run();

        self::$objectReport = (new ObjectImporter(new V8Source))->run(fresh: true);
        self::$objectRerun = (new ObjectImporter(new V8Source))->run();

        self::$menuReport = (new MenuImporter(new V8Source))->run(fresh: true);
        // what lara:menu:refresh-routenames does: v10 names menu routes after their URL
        DB::table('lara_menu_menu_items')->where('id', 2)->update(['routename' => 'entitytag.v8fxbooks.boeken.index']);
        self::$menuRerun = (new MenuImporter(new V8Source))->run();

        // the translation export writes language files: into a temporary folder, never the site's lang/vendor
        self::$langPath = sys_get_temp_dir().'/lara-v8-lang-'.uniqid();
        File::ensureDirectoryExists(self::$langPath);
        $sitePath = app()->langPath();
        app()->useLangPath(self::$langPath);
        self::$systemReport = (new SystemImporter(new V8Source))->run(fresh: true);
        self::$systemRerun = (new SystemImporter(new V8Source))->run();
        app()->useLangPath($sitePath);
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

        $this->assertSame([19, 1125, 2001], $pages->keys()->all(), 'v8 pages replace the essential pages (--fresh).');
        $this->assertSame('v8fxbooks-index-module-nl', $pages[2001]->slug, 'v10 finds a module page by the resource slug in its slug.');
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

    public function test_the_object_import_has_no_errors_and_a_second_run_changes_nothing(): void
    {
        $errors = collect(self::$objectReport)->where('level', 'error');
        $changes = collect(self::$objectRerun)->whereNotIn('level', ['same', 'info', 'warning']);

        $this->assertEmpty($errors, $errors->pluck('message')->implode("\n"));
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }

    public function test_tags_get_a_resource_slug_and_keep_their_tree(): void
    {
        $tags = DB::table('lara_object_tags')->orderBy('id')->get()->keyBy('id');

        $this->assertSame([11, 12, 13], $tags->keys()->all());
        $this->assertSame(['v8fxbooks', 0], [$tags[11]->resource_slug, (int) $tags[11]->depth], 'A NULL depth gets the column default.');
        $this->assertSame(['v8fxbooks', 11, 2, 3, 1], [$tags[12]->resource_slug, (int) $tags[12]->parent_id, (int) $tags[12]->lft, (int) $tags[12]->rgt, (int) $tags[12]->depth]);
        $this->assertSame('widgets', $tags[13]->resource_slug);
    }

    public function test_links_get_the_v10_model_class(): void
    {
        $taggables = DB::table('lara_object_taggables')->orderBy('id')->get();

        $this->assertSame([1, 2], $taggables->pluck('id')->map(fn ($id) => (int) $id)->all(), 'A link to a record that does not exist is skipped.');
        $this->assertSame(['Lara\\App\\Models\\V8fxbook'], $taggables->pluck('entity_type')->unique()->values()->all());

        $pageable = DB::table('lara_object_pageables')->first();
        $this->assertSame([19, 'Lara\\Common\\Models\\Cta', 99], [(int) $pageable->page_id, $pageable->entity_type, (int) $pageable->entity_id]);
    }

    public function test_seo_and_opengraph_become_one_filarank_row(): void
    {
        $seo = DB::table('lara_object_seo')->get();

        $this->assertCount(1, $seo, 'Empty SEO rows and an og title without SEO are not imported.');

        $book = $seo->first();
        $this->assertSame(['Lara\\App\\Models\\V8fxbook', 1, 'Boek één | Fixture', 'Over het boek', 'boek', 'nl', 'og-boek.jpg'], [$book->model_type, (int) $book->model_id, $book->title, $book->description, $book->focus_keyword, $book->locale, $book->og_image]);
        $this->assertSame(['boeken', 'lezen', 'romans'], json_decode($book->additional_keywords, true));
    }

    public function test_layout_settings_become_one_row_per_record(): void
    {
        $layout = DB::table('lara_object_layout')->get();

        $this->assertCount(1, $layout);
        $this->assertSame(['Lara\\Common\\Models\\Page', 19, 'hero_slider_70', 'hidden', 'no_such_layout', null], [$layout[0]->entity_type, (int) $layout[0]->entity_id, $layout[0]->hero, $layout[0]->pagetitle, $layout[0]->content, $layout[0]->footer]);
    }

    public function test_object_problems_are_reported(): void
    {
        $warnings = collect(self::$objectReport)->where('level', 'warning')->pluck('message');

        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 v8 tag(s) have a publish_from/publish_to date')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 v8 taggables row(s) point to a record')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 record(s) have a v8 opengraph title or description')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, "'no_such_layout' is not a content option of the active theme")));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 v8 related objects (lara_object_related) are not imported')));
    }

    public function test_the_menu_import_has_no_errors_and_a_second_run_keeps_the_v10_route_names(): void
    {
        $errors = collect(self::$menuReport)->where('level', 'error');
        $changes = collect(self::$menuRerun)->whereNotIn('level', ['same', 'info', 'warning']);

        $this->assertEmpty($errors, $errors->pluck('message')->implode("\n"));
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
        $this->assertSame('entitytag.v8fxbooks.boeken.index', DB::table('lara_menu_menu_items')->where('id', 2)->value('routename'), 'A rerun keeps the route name v10 rebuilt.');
    }

    public function test_the_v8_menu_trees_are_flattened(): void
    {
        $items = DB::table('lara_menu_menu_items')->orderBy('menu_id')->orderBy('lft')->get()->keyBy('id');

        $this->assertSame([1, 2, 3, 4, 6], $items->keys()->sort()->values()->all(), 'The footer root item and the essential items are gone.');

        $shape = fn (int $id): array => [(int) $items[$id]->is_home, $items[$id]->parent_id === null ? null : (int) $items[$id]->parent_id, (int) $items[$id]->depth, (int) $items[$id]->lft, (int) $items[$id]->rgt, (int) $items[$id]->position];

        $this->assertSame([1, null, 0, 1, 2, 1001], $shape(1), 'The v8 home root is the first top-level item.');
        $this->assertSame([0, null, 0, 3, 6, 1002], $shape(2), 'Its children move up to the top level.');
        $this->assertSame([0, 2, 1, 4, 5, 1003], $shape(3));
        $this->assertSame([0, null, 0, 7, 8, 1004], $shape(4));
        $this->assertSame([0, null, 0, 1, 2, 1001], $shape(6), 'The children of a footer root item are the top level.');
        $this->assertNull($items[1]->route, 'The home page has no route.');
    }

    public function test_menu_items_point_to_the_v10_entities_and_views(): void
    {
        $items = DB::table('lara_menu_menu_items')->get()->keyBy('id');
        $entity = fn (string $slug): int => (int) Entity::where('resource_slug', $slug)->value('id');
        $view = fn (string $slug, string $method): int => (int) DB::table('lara_resource_entity_views')->where('entity_id', $entity($slug))->where('method', $method)->orderBy('id')->value('id');

        $this->assertSame([$entity('v8fxbooks'), $view('v8fxbooks', 'index'), 12], [(int) $items[3]->entity_id, (int) $items[3]->entity_view_id, (int) $items[3]->tag_id]);
        $this->assertSame([$entity('v8fxforms'), $view('v8fxforms', 'form')], [(int) $items[4]->entity_id, (int) $items[4]->entity_view_id]);

        $standard = (int) DB::table('lara_resource_entity_views')->where('entity_id', $entity('pages'))->where('template', 'standard')->value('id');
        $this->assertSame([$entity('pages'), $standard, 19], [(int) $items[1]->entity_id, (int) $items[1]->entity_view_id, (int) $items[1]->object_id], 'A page item gets the show view of the page template.');
        $this->assertSame(0, (int) $items[6]->publish);
    }

    public function test_menus_and_redirects_are_imported(): void
    {
        $this->assertSame(['main', 'footer'], DB::table('lara_menu_menus')->orderBy('id')->pluck('slug')->all());

        $redirects = DB::table('seo_redirects')->orderBy('id')->get();
        $this->assertSame(['/oude-pagina', '/nl/boeken', 301, 1], [$redirects[0]->from_path, $redirects[0]->to_path, (int) $redirects[0]->status, (int) $redirects[0]->active]);
        $this->assertSame(['/tijdelijk', 302, 0], [$redirects[1]->from_path, (int) $redirects[1]->status, (int) $redirects[1]->active]);
    }

    public function test_dropped_root_items_are_reported(): void
    {
        $this->assertTrue(collect(self::$menuReport)->contains(fn (array $line): bool => str_contains($line['message'], 'Menu 2 (nl): 1 v8 root item(s) dropped')));
        $this->assertTrue(collect(self::$menuReport)->contains(fn (array $line): bool => str_contains($line['message'], "Menu item 7 'Weg' points to page 9999, which does not exist; skipped.")));
    }

    public function test_the_settings_import_has_no_errors_and_a_second_run_changes_nothing(): void
    {
        $errors = collect(self::$systemReport)->where('level', 'error');
        $changes = collect(self::$systemRerun)->whereNotIn('level', ['same', 'info', 'warning']);

        $this->assertEmpty($errors, $errors->pluck('message')->implode("\n"));
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }

    public function test_the_site_settings_replace_the_seed_and_system_values_stay(): void
    {
        $settings = DB::table('lara_sys_settings')->get()->keyBy(fn (object $row): string => $row->cgroup.'.'.$row->key);

        $this->assertSame('Fixture BV', $settings['company.company_name']->value);
        $this->assertSame('+31201234567', $settings['company.company_telephone_clean']->value, 'Of two v8 rows the one with a value wins.');
        $this->assertSame('020 1234567', $settings['company.company_phone']->value, 'A v8-only key is added.');
        $this->assertSame('12', $settings['general.google_maps_zoom']->value);
        $this->assertNotSame('8.2.46', $settings['system.lara_db_version']->value ?? null, 'System values belong to v10.');
        $this->assertFalse($settings->has('opengraph.og_site_name'), 'opengraph is handled by filarank.');
        $this->assertTrue($settings->has('company.company_2fa_app_name'), 'A v10-only setting stays.');
        $this->assertSame(1, $settings->where('key', 'company_telephone_clean')->count());
    }

    public function test_the_blacklist_is_imported(): void
    {
        $this->assertSame(['192.0.2.66', '198.51.100.7'], DB::table('lara_sys_blacklist')->orderBy('id')->pluck('ipaddress')->all());
    }

    public function test_site_labels_become_lara_app_translations(): void
    {
        $value = fn (string $resource, string $tag, string $key): ?string => DB::table('lara_sys_translations')
            ->where(['language' => 'nl', 'module' => 'lara-app', 'resource' => $resource, 'tag' => $tag, 'key' => $key])->value('value');

        $this->assertSame('boekenkast', $value('v8fxbooks', 'entity', 'title'));
        $this->assertSame('boekenkast', $value('v8fxbooks', 'navigation', 'label'));
        $this->assertSame('boek', $value('v8fxbooks', 'model', 'label_single'));
        $this->assertSame('boeken', $value('v8fxbooks', 'model', 'label_plural'));
        $this->assertSame('ondertitel', $value('v8fxbooks', 'column', 'subtitle'));
        $this->assertSame('adres', $value('v8fxbooks', 'column', 'geo_address'), 'A geolocation part label follows its column.');
        $this->assertNotSame('_kind', $value('v8fxbooks', 'column', 'kind'), 'A v8 placeholder is not imported.');
        $this->assertSame('naam (beheer)', $value('v8fxforms', 'column', 'name'), 'A v8 column label wins over the form field label.');
        $this->assertSame('opmerking', $value('v8fxforms', 'column', 'comment'), 'A form field label becomes the column label.');
        $this->assertSame('versturen', $value('v8fxforms', 'button', 'submit'));
        $this->assertNull($value('gones', 'column', 'title'), 'Labels of an entity that is no content type are skipped.');
    }

    public function test_front_texts_only_for_known_keys_and_no_admin_texts(): void
    {
        $front = fn (string $key): ?string => DB::table('lara_sys_translations')
            ->where(['language' => 'nl', 'module' => 'lara-front', 'resource' => 'default', 'tag' => 'button', 'key' => $key])->value('value');

        $this->assertSame('terug naar het overzicht', $front('go_back'));
        $this->assertNull($front('v8_only'));
        $this->assertNotSame('opslaan', DB::table('lara_sys_translations')->where(['language' => 'nl', 'module' => 'lara-admin', 'resource' => 'default', 'tag' => 'button', 'key' => 'save'])->value('value'));
    }

    public function test_the_translations_are_exported_to_the_temporary_lang_folder(): void
    {
        $file = self::$langPath.'/vendor/lara-app/nl/v8fxbooks.php';

        $this->assertFileExists($file);
        $this->assertSame('boek', (require $file)['model']['label_single']);
        $this->assertFileDoesNotExist(base_path('lang/vendor/lara-app/nl/v8fxbooks.php'), 'The site lang folder is not touched.');
    }

    public function test_header_tags_are_reported(): void
    {
        $this->assertTrue(collect(self::$systemReport)->contains(fn (array $line): bool => str_contains($line['message'], '1 v8 header tag setting(s)')));
    }
}
