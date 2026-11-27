<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\Concerns\SyncsRows;
use App\Console\Commands\LaraV8\Support\ContentImporter;
use App\Console\Commands\LaraV8\Support\EntityImporter;
use App\Console\Commands\LaraV8\Support\MediaImporter;
use App\Console\Commands\LaraV8\Support\MenuImporter;
use App\Console\Commands\LaraV8\Support\ObjectImporter;
use App\Console\Commands\LaraV8\Support\SiteFields;
use App\Console\Commands\LaraV8\Support\SiteOverrides;
use App\Console\Commands\LaraV8\Support\SystemImporter;
use App\Console\Commands\LaraV8\Support\UrlRewriter;
use App\Console\Commands\LaraV8\Support\UserImporter;
use App\Console\Commands\LaraV8\Support\V8Inventory;
use App\Console\Commands\LaraV8\Support\V8Source;
use App\Console\Commands\LaraV8\Support\Verifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityRelation;
use Lara\Common\Models\EntityView;
use Lara\Common\Models\User;
use ReflectionMethod;
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

    private static ?string $mediaPath = null;

    private static ?string $diskPath = null;

    /** A per-test overrides file (absent unless a test writes it). */
    private static ?string $overridesPath = null;

    /** The site field settings of the fixture (v8-fields.php, gain). */
    private static ?string $fieldsPath = null;

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $mediaReport = [];

    /** @var list<array{level: string, entity: string, message: string}> the content import once the media exist */
    private static array $contentAfterMedia = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $mediaRerun = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $urlReport = [];

    /** @var list<array{level: string, entity: string, message: string}> */
    private static array $urlRerun = [];

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

        // media: read from a temporary v8 media path, written to a temporary public disk, never the site's storage
        self::$mediaPath ??= sys_get_temp_dir().'/lara-v8-media-'.uniqid();
        self::$diskPath ??= sys_get_temp_dir().'/lara-v8-disk-'.uniqid();
        config(['v8-migration.media_path' => self::$mediaPath, 'filesystems.disks.public.root' => self::$diskPath, 'v8-migration.site_hosts' => ['fixture.test']]);
        Storage::forgetDisk('public');

        // the site's own overrides (v8-overrides.php) never apply to the fixture
        self::$overridesPath ??= sys_get_temp_dir().'/lara-v8-overrides-'.uniqid().'.php';
        config(['v8-migration.overrides' => self::$overridesPath]);
        SiteOverrides::flush();

        // the fixture's site field settings (v8-fields.php), as gain has; the site's own file never applies
        self::$fieldsPath ??= sys_get_temp_dir().'/lara-v8-fields-'.uniqid().'.php';
        config(['v8-migration.fields' => self::$fieldsPath]);
        SiteFields::flush();

        // the fixture's demo leftover is skipped, as a site does with v8-migration.skip_entities
        config(['v8-migration.skip_entities' => ['v8fxleftover']]);

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

        foreach ([self::$mediaPath, self::$diskPath, self::$overridesPath, self::$fieldsPath] as $path) {
            if ($path !== null) {
                exec('rm -rf '.escapeshellarg($path));
            }
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

        // the v8 media files of the fixture (the test media path is a temporary folder)
        File::ensureDirectoryExists(self::$mediaPath.'/v8fxbook/_archive');
        $jpeg = imagecreatetruecolor(40, 30);
        imagejpeg($jpeg, self::$mediaPath.'/v8fxbook/cover.jpg');
        $png = imagecreatetruecolor(20, 10);
        imagepng($png, self::$mediaPath.'/v8fxbook/inside.png');
        File::put(self::$mediaPath.'/v8fxbook/_archive/sample.pdf', '%PDF-1.4 fixture');
        // an editor upload, only referenced in the HTML
        File::ensureDirectoryExists(self::$mediaPath.'/images/shares');
        File::put(self::$mediaPath.'/images/shares/Mijn logo.png', 'png');
        // an upload whose name the v8 editor wrote with HTML entities in the URL (Co&ouml;rdinator)
        File::put(self::$mediaPath.'/images/shares/Coördinator Fryslân.png', 'png');

        File::put(self::$fieldsPath, "<?php\n\nreturn [\n    'v8fxbook.introtext' => ['type' => 'richeditor', 'reason' => 'test'],\n    'v8fxbook.secretkey' => ['skip' => true, 'reason' => 'test'],\n    'v8fxbook.picture' => ['type' => 'select', 'options' => ['get_record_images'], 'image' => true, 'reason' => 'test'],\n];\n");
        SiteFields::flush();

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

        self::$mediaReport = (new MediaImporter(new V8Source))->run(fresh: true);
        self::$mediaRerun = (new MediaImporter(new V8Source))->run();
        self::$contentAfterMedia = (new ContentImporter(new V8Source))->run();

        self::$urlReport = (new UrlRewriter(new V8Source))->run();
        self::$urlRerun = (new UrlRewriter(new V8Source))->run();
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

    public function test_v8_page_templates_v10_lacks_become_page_views(): void
    {
        $pages = Entity::where('resource_slug', 'pages')->firstOrFail();
        $views = EntityView::where('entity_id', $pages->id)->get()->keyBy('template');

        // fxwallpaper: a page with a lead (body2), so the admin shows the extra body field
        $this->assertSame(['show', 'show', 1, 1, '_single', 1], [$views['fxwallpaper']->method, $views['fxwallpaper']->filename, (int) $views['fxwallpaper']->template_extra_fields, (int) $views['fxwallpaper']->is_single, $views['fxwallpaper']->list_type, (int) $views['fxwallpaper']->publish]);
        // fxlanding: only an unpublished page without a lead; still added (the admin form needs it)
        $this->assertSame(0, (int) $views['fxlanding']->template_extra_fields);
        $this->assertSame(1, $views->where('template', 'standard')->count(), 'An existing template is not added twice.');

        $created = collect(self::$report)->where('level', 'create')->pluck('message');
        $this->assertTrue($created->contains("Page template 'fxwallpaper' (1 page(s), extra body fields 1) added as a page view."));
        $this->assertTrue(collect(self::$rerun)->contains(fn (array $line): bool => $line['level'] === 'same' && $line['message'] === "Page template 'fxwallpaper' exists."));

        // the content import no longer warns about the lead of a page with such a template
        $warnings = collect(self::$contentReport)->where('level', 'warning')->pluck('message');
        $this->assertFalse($warnings->contains(fn (string $m): bool => str_contains($m, "Page 1126 'Behang'")));
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
        $this->assertSame(1, (int) EntityView::where('entity_id', $book->id)->where('method', 'index')->value('image_required'), 'v8 lists only books with media in its grid index.');
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

        $this->assertSame(['geolocation', 'introtext', 'kind', 'note', 'pages', 'picture', 'sticky', 'subtitle'], $fields->keys()->sort()->values()->all());

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

        $this->assertSame([19, 1125, 1126, 1127, 2001, 2002], $pages->keys()->all(), 'v8 pages replace the essential pages (--fresh).');
        $this->assertSame('v8fxbooks-index-module-nl', $pages[2001]->slug, 'v10 finds a module page by the resource slug in its slug.');
        $this->assertSame('v8fxforms-email-nl', $pages[2002]->slug, 'v10 finds a form\'s mail page by the resource slug, and creates an empty one otherwise.');
        $this->assertSame('standard', $pages[2002]->template, 'A page without a v8 template gets the standard one (v10\'s page form requires one).');
        $this->assertSame('fxwallpaper', $pages[1126]->template, 'A v8 template is kept.');
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

    public function test_tags_get_a_resource_slug_and_keep_their_tree_without_the_v8_root(): void
    {
        $tags = DB::table('lara_object_tags')->orderBy('id')->get()->keyBy('id');
        $node = fn (int $id): array => [$tags[$id]->parent_id === null ? null : (int) $tags[$id]->parent_id, (int) $tags[$id]->lft, (int) $tags[$id]->rgt, (int) $tags[$id]->depth];

        $this->assertSame([12, 13, 15, 16, 17], $tags->keys()->all(), 'The v8 roots 11 (root) and 14 ([EN] root) are not imported.');
        $this->assertSame('v8fxbooks', $tags[12]->resource_slug);
        $this->assertSame([null, 1, 2, 0], $node(12), 'A child of the root becomes a top-level tag.');
        $this->assertSame([null, 1, 2, 0], $node(13), 'A top-level tag that is not a root is kept.');
        $this->assertSame('widgets', $tags[13]->resource_slug);
        $this->assertSame([null, 1, 4, 0], $node(15));
        $this->assertSame([15, 2, 3, 1], $node(16), 'Deeper tags move up one level.');
        $this->assertSame([null, 1, 2, 0], $node(17), 'A root with objects is kept as a top-level tag.');
    }

    public function test_dropped_and_kept_root_tags_are_reported(): void
    {
        $report = collect(self::$objectReport);

        $this->assertTrue($report->contains(fn (array $line): bool => $line['level'] === 'info' && str_contains($line['message'], '2 v8 root tag(s) not imported')));
        $this->assertTrue($report->contains(fn (array $line): bool => $line['level'] === 'warning' && str_contains($line['message'], 'v8 root tag #17 has objects or menu items')));
    }

    public function test_links_get_the_v10_model_class(): void
    {
        $taggables = DB::table('lara_object_taggables')->orderBy('id')->get();

        $this->assertSame([1, 2, 4], $taggables->pluck('id')->map(fn ($id) => (int) $id)->all(), 'A link to a record that does not exist is skipped.');
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
        $this->assertSame(['Lara\\Common\\Models\\Page', 19, 'hero_slider_70', 'hidden', 'no_such_layout', null, null], [$layout[0]->entity_type, (int) $layout[0]->entity_id, $layout[0]->hero, $layout[0]->pagetitle, $layout[0]->content, $layout[0]->footer, $layout[0]->cta]);
    }

    public function test_empty_layout_values_are_left_to_the_theme_default_without_a_warning(): void
    {
        $warnings = collect(self::$objectReport)->where('level', 'warning')->pluck('message');

        $this->assertFalse($warnings->contains(fn (string $m): bool => str_contains($m, 'is not a footer option') || str_contains($m, 'is not a cta option')));
    }

    public function test_object_problems_are_reported(): void
    {
        $warnings = collect(self::$objectReport)->where('level', 'warning')->pluck('message');

        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 v8 tag(s) have a publish_from/publish_to date')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 v8 taggables row(s) point to a record')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 record(s) have a v8 opengraph title or description')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, "'no_such_layout' is not a content option of the active theme")));
        $this->assertFalse($warnings->contains(fn (string $m): bool => str_contains($m, 'lara_object_related) are not imported')), 'Related objects are converted now.');
    }

    public function test_related_objects_become_one_row_per_record_with_three_lists(): void
    {
        $related = DB::table('lara_object_related')->get()->keyBy(fn (object $row): string => class_basename($row->entity_type).' '.$row->entity_id);
        $lists = fn (string $key): array => collect(['related_page_objects', 'related_entity_objects', 'related_entities'])
            ->mapWithKeys(fn (string $column): array => [$column => json_decode($related[$key]->{$column}, true)])->all();

        $this->assertSame(['Page 19', 'V8fxbook 1'], $related->keys()->sort()->values()->all(), 'One row per record; no row for the missing book or the skipped leftover.');
        $this->assertSame(Entity::where('resource_slug', 'v8fxbooks')->value('model_class'), $related['V8fxbook 1']->entity_type);
        $this->assertSame([
            'related_page_objects' => [['page_object_id' => 1125], ['page_object_id' => 19]],
            'related_entity_objects' => [['object_id' => 1, 'resource_slug' => 'v8fxevents']],
            'related_entities' => [['module_page_menu_id' => 2]],
        ], $lists('V8fxbook 1'), 'In v8 front order (related_object_id descending, row 10 before row 3); the duplicate page link is written once; the menu root and the missing event are left out.');
        $this->assertSame([
            'related_page_objects' => [['page_object_id' => 1125]],
            'related_entity_objects' => [],
            'related_entities' => [],
        ], $lists('Page 19'), 'Every list is present, also when empty (the front loops over all three).');
    }

    public function test_related_problems_are_reported_and_a_rerun_keeps_v10_empty_rows(): void
    {
        $warnings = collect(self::$objectReport)->where('level', 'warning')->pluck('message');

        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, 'Related #5: v8fxbook 1 → menuitem 5, which does not exist in v10 (or is a v8 menu root)')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, 'Related #6: v8fxbook 1 → v8fxevent 999, which does not exist in v10')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 v8 related row(s) belong to a record or content type that does not exist in v10')));
        $this->assertTrue(collect(self::$objectReport)->contains(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], '1 related')), 'The leftover link counts as skipped.');
        $this->assertTrue(collect(self::$objectRerun)->contains(fn (array $l): bool => $l['entity'] === 'related' && $l['level'] === 'same'), 'A rerun changes nothing (MySQL reorders the JSON keys).');

        // what v10 does when a record without related objects is saved in the admin: an empty row
        $empty = ['entity_type' => 'Lara\\Common\\Models\\Page', 'entity_id' => 1125, 'related_page_objects' => '[]', 'related_entity_objects' => '[]', 'related_entities' => '[]'];
        DB::table('lara_object_related')->insert($empty);

        try {
            $rerun = collect((new ObjectImporter(new V8Source))->run(fresh: true))->where('entity', 'related');
            $this->assertTrue(DB::table('lara_object_related')->where('entity_id', 1125)->where('entity_type', $empty['entity_type'])->exists(), 'A v10 empty row is kept, also with --fresh.');
            $this->assertSame(['same'], $rerun->whereNotIn('level', ['warning'])->pluck('level')->unique()->values()->all(), 'and is not reported as a row v8 does not have.');
        } finally {
            DB::table('lara_object_related')->where('entity_id', 1125)->where('entity_type', $empty['entity_type'])->delete();
        }
    }

    public function test_verify_checks_the_related_targets_and_the_entity_setting(): void
    {
        $this->assertSame([], collect((new Verifier(new V8Source))->run(['related']))->whereIn('level', ['error', 'warning'])->values()->all());

        DB::table('lara_menu_menu_items')->where('id', 2)->update(['id' => 902]);
        $pages = Entity::where('resource_slug', 'pages')->firstOrFail();
        $setting = $pages->objrel_has_related;
        $pages->forceFill(['objrel_has_related' => 0])->saveQuietly();

        try {
            $report = collect((new Verifier(new V8Source))->run(['related']));
        } finally {
            DB::table('lara_menu_menu_items')->where('id', 902)->update(['id' => 2]);
            $pages->forceFill(['objrel_has_related' => $setting])->saveQuietly();
        }

        $this->assertTrue($report->contains(fn (array $l): bool => $l['level'] === 'error' && str_contains($l['message'], 'V8fxbook 1 → menu item 2')));
        $this->assertTrue($report->contains(fn (array $l): bool => $l['level'] === 'warning' && str_contains($l['message'], 'pages has related objects, but objrel_has_related is off')));
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

    public function test_menu_items_of_a_skipped_content_type_are_not_imported(): void
    {
        // footer item 8 links the skipped v8fxleftover; item 9 hangs below it
        $ids = DB::table('lara_menu_menu_items')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $this->assertNotContains(8, $ids);
        $this->assertNotContains(9, $ids);
        $this->assertTrue(collect(self::$menuReport)->contains(fn (array $line): bool => $line['level'] === 'info' && str_contains($line['message'], "Menu item 8 'Restjes' links the skipped content type 'v8fxleftover'; not imported, with 1 item(s) below it.")));
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

    public function test_front_texts_for_known_or_used_keys_and_no_admin_texts(): void
    {
        $front = fn (string $key, string $tag = 'button'): ?string => DB::table('lara_sys_translations')
            ->where(['language' => 'nl', 'module' => 'lara-front', 'resource' => 'default', 'tag' => $tag, 'key' => $key])->value('value');
        $messages = collect(self::$systemReport)->where('entity', 'translations')->pluck('message');

        $this->assertSame('terug naar het overzicht', $front('go_back'));
        $this->assertSame('vink aan dat u geen robot bent', $front('error_recaptcha_not_checked', 'form'), 'A key the v10 views use is imported even without a v10 seed row.');
        $this->assertNull($front('v8_only'));
        $this->assertTrue($messages->contains(fn (string $m): bool => str_contains($m, 'not imported: default.button.v8_only')), 'Skipped front texts are named.');
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

    public function test_the_media_import_has_no_errors_and_a_second_run_copies_and_changes_nothing(): void
    {
        $errors = collect(self::$mediaReport)->where('level', 'error');
        $changes = collect(self::$mediaRerun)->whereNotIn('level', ['same', 'info', 'warning']);

        $this->assertEmpty($errors, $errors->pluck('message')->implode("\n"));
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
        $this->assertTrue(collect(self::$mediaRerun)->contains('message', 'Copied 0 file(s) to the v10 disks.'));
    }

    public function test_images_become_curator_items_with_their_v8_name(): void
    {
        $items = DB::table('curator')->orderBy('path')->get()->keyBy('path');

        $this->assertSame(['v8fxbooks/cover.jpg', 'v8fxbooks/inside.png'], $items->keys()->all(), 'A missing file and a missing record are skipped.');

        $cover = $items['v8fxbooks/cover.jpg'];
        $this->assertSame(['public', 'v8fxbooks', 'cover', 'jpg', 'image/jpeg', 40, 30], [$cover->disk, $cover->directory, $cover->name, $cover->ext, $cover->type, (int) $cover->width, (int) $cover->height]);
        $this->assertSame(['Omslag van boek één', 'Omslag', 'Het omslag', 1], [$cover->alt, $cover->title, $cover->caption, (int) $cover->in_use]);

        Storage::disk('public')->assertExists(['v8fxbooks/cover.jpg', 'v8fxbooks/inside.png']);
        $this->assertSame(filesize(self::$mediaPath.'/v8fxbook/cover.jpg'), Storage::disk('public')->size('v8fxbooks/cover.jpg'));
    }

    public function test_image_links_get_their_type_and_order(): void
    {
        $links = DB::table('lara_object_images as i')
            ->join('curator as c', 'c.id', '=', 'i.media_id')
            ->orderBy('i.type')->orderBy('i.order')
            ->get(['i.mediable_type', 'i.mediable_id', 'i.type', 'i.order', 'c.path']);

        $this->assertSame([
            ['Lara\\App\\Models\\V8fxbook', 1, 'featured', 1, 'v8fxbooks/cover.jpg'],
            ['Lara\\App\\Models\\V8fxbook', 1, 'gallery', 1, 'v8fxbooks/inside.png'],
        ], $links->map(fn (object $link): array => [$link->mediable_type, (int) $link->mediable_id, $link->type, (int) $link->order, $link->path])->all());
    }

    public function test_files_and_videos_become_json_lists(): void
    {
        $files = json_decode(DB::table('lara_object_files')->where('entity_type', 'Lara\\App\\Models\\V8fxbook')->where('entity_id', 1)->value('entity_files'), true);
        $videos = json_decode(DB::table('lara_object_videos')->where('entity_type', 'Lara\\App\\Models\\V8fxbook')->where('entity_id', 1)->value('entity_videos'), true);

        $this->assertSame([['doc_date' => '2024-05-01 00:00', 'doc_filename' => 'v8fxbooks/file/sample.pdf', 'doc_original' => 'Leesproef']], $files);
        Storage::disk('public')->assertExists('v8fxbooks/file/sample.pdf');

        $this->assertSame([['video_title' => 'Trailer', 'youtubecode' => 'dQw4w9WgXcQ']], $videos);
    }

    public function test_the_image_types_follow_the_v8_flags(): void
    {
        $image = fn (array $flags): object => (object) [...['featured' => 0, 'ishero' => 0, 'isicon' => 0, 'hide_in_gallery' => 1], ...$flags];

        $this->assertSame(['featured'], MediaImporter::imageTypes($image(['featured' => 1])));
        $this->assertSame(['featured', 'gallery'], MediaImporter::imageTypes($image(['featured' => 1, 'hide_in_gallery' => 0])));
        $this->assertSame(['featured', 'hero'], MediaImporter::imageTypes($image(['featured' => 1, 'ishero' => 1])));
        $this->assertSame(['icon'], MediaImporter::imageTypes($image(['isicon' => 1])));
        $this->assertSame(['gallery'], MediaImporter::imageTypes($image([])));
    }

    public function test_media_problems_are_reported_and_the_site_storage_is_not_touched(): void
    {
        $warnings = collect(self::$mediaReport)->where('level', 'warning')->pluck('message');

        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, '1 v8 media row(s) point to a file that is missing')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, 'Media of v8fxbook records that do not exist in v10')));
        $this->assertDirectoryDoesNotExist(storage_path('app/public/v8fxbooks'));
    }

    public function test_v8_media_urls_in_the_html_point_to_the_v10_storage(): void
    {
        $body = (string) DB::table('lara_content_v8fxbooks')->where('id', 2)->value('body');

        $this->assertStringContainsString('src="/storage/v8fxbooks/cover.jpg"', $body, 'A content type file: where the media import put it.');
        $this->assertStringContainsString('src="/storage/images/shares/Mijn%20logo.png"', $body, 'An editor upload: copied to the same path.');
        $this->assertStringContainsString('href="/storage/v8fxbooks/inside.png"', $body, 'An absolute URL to the site itself.');
        $this->assertStringContainsString('src="/assets/media/images/shares/nope.png"', $body, 'A missing file: left as it is.');
        $this->assertStringContainsString('src="/storage/images/shares/Co%C3%B6rdinator%20Frysl%C3%A2n.png"', $body, 'HTML entities in the URL are decoded, as a browser does.');
        $this->assertStringContainsString('href="https://elders.test/assets/media/x.jpg"', $body, 'Another site: left as it is.');
        $this->assertStringContainsString('href="/storage/v8fxbooks/file/sample.pdf"', $body, 'A document of a record: in file/, where the media import puts it, also though the content import runs first.');
        Storage::disk('public')->assertMissing('v8fxbooks/sample.pdf');

        Storage::disk('public')->assertExists('images/shares/Mijn logo.png');
    }

    public function test_the_url_rewrite_reports_problems_and_a_second_run_changes_nothing(): void
    {
        $warnings = collect(self::$urlReport)->where('level', 'warning')->pluck('message');

        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, 'Media URL /assets/media/images/shares/nope.png has no file')));
        $this->assertTrue($warnings->contains(fn (string $m): bool => str_contains($m, 'Internal link /v8-fixture-missing/?page=1 does not reach a page')));
        $this->assertFalse($warnings->contains(fn (string $m): bool => str_contains($m, '/robots.txt')), 'A file in public/ is reached.');
        // the content import already rewrote the media URLs; rewrite-urls only finds the missing file
        $this->assertTrue(collect(self::$urlReport)->contains(fn (array $line): bool => str_starts_with($line['message'], '0 v8 media URL(s) in 0 row(s) rewritten')));
        $this->assertTrue(collect(self::$contentRerun)->doesntContain(fn (array $line): bool => $line['entity'] === 'v8fxbook' && $line['level'] === 'update'), 'A content rerun keeps the rewritten URLs.');
        $this->assertTrue(collect(self::$urlRerun)->contains(fn (array $line): bool => $line['level'] === 'same' && str_starts_with($line['message'], '0 v8 media URL(s)')));
    }

    public function test_column_shortcodes_become_rich_editor_grids(): void
    {
        $body = (string) DB::table('lara_content_pages')->where('id', 19)->value('body');

        $this->assertStringNotContainsString('[kolom_', $body);
        $this->assertStringContainsString('<div class="grid-layout" data-cols="2" data-from-breakpoint="md"', $body);
        $this->assertSame(2, substr_count($body, '<div class="grid-layout-col" data-col-span="1"'));
        $this->assertStringContainsString("<p>Rechts</p>\n</div></div>", $body, 'The marker after an &nbsp; loses its paragraph too.');

        $this->assertTrue(collect(self::$contentReport)->contains(fn (array $line): bool => $line['entity'] === 'page' && $line['level'] === 'info' && str_contains($line['message'], 'shortcodes converted to rich editor grids (row.column): 19.body')));
        $this->assertTrue(collect(self::$contentRerun)->doesntContain(fn (array $line): bool => $line['entity'] === 'page' && $line['level'] === 'update'), 'A content rerun keeps the grids.');
    }

    public function test_plain_v8_divs_become_paragraphs(): void
    {
        $body = (string) DB::table('lara_content_pages')->where('id', 1127)->value('body');

        $this->assertSame("<p>Tekst</p>\n<p><strong>Gastheer<br /></strong>Jan Pit<br />\nTelefoon: 06-12345678&nbsp;</p>\n", $body);
        $this->assertStringContainsString('<div class="grid-layout"', (string) DB::table('lara_content_pages')->where('id', 19)->value('body'), 'The rich editor grids stay divs.');

        $this->assertTrue(collect(self::$contentReport)->contains(fn (array $line): bool => $line['entity'] === 'page' && $line['level'] === 'info' && str_contains($line['message'], 'v8 <div> blocks converted to paragraphs (row.column): 1127.body')));
        $this->assertTrue(collect(self::$contentRerun)->doesntContain(fn (array $line): bool => $line['entity'] === 'page' && $line['level'] === 'update'), 'A content rerun keeps the paragraphs.');
    }

    public function test_verify_finds_no_errors_after_the_import(): void
    {
        $report = (new Verifier(new V8Source))->run();
        $errors = collect($report)->where('level', 'error')->pluck('message');

        $this->assertSame([], $errors->all());
        $this->assertTrue(collect($report)->contains(fn (array $line): bool => $line['entity'] === 'v8fxbook' && $line['level'] === 'ok' && str_contains($line['message'], 'as v8')));
        $this->assertTrue(collect($report)->contains(fn (array $line): bool => $line['entity'] === 'curator' && $line['level'] === 'ok'));
        $this->assertTrue(collect($report)->contains(fn (array $line): bool => $line['entity'] === 'sync: content' && $line['level'] === 'ok'));
        $this->assertTrue(collect($report)->contains(fn (array $line): bool => $line['entity'] === 'tags' && $line['level'] === 'ok' && str_contains($line['message'], 'valid nested set')));
        $this->assertTrue(collect($report)->contains(fn (array $line): bool => $line['entity'] === 'tags' && $line['level'] === 'warning' && str_contains($line['message'], '1 v8 root tag(s) in v10 (#17)')), 'The kept root with objects is reported.');
    }

    public function test_verify_finds_a_missing_row_a_missing_file_and_a_changed_row(): void
    {
        $book = (array) DB::table('lara_content_v8fxbooks')->where('id', 1)->first();
        $image = DB::table('curator')->orderBy('id')->first();
        $disk = Storage::disk($image->disk);
        $contents = $disk->get($image->path);

        try {
            DB::table('lara_content_v8fxbooks')->where('id', 1)->delete();
            $disk->delete($image->path);
            DB::table('lara_content_v8fxbooks')->where('id', 2)->update(['title' => 'Changed in v10']);

            $report = collect((new Verifier(new V8Source))->run());
        } finally {
            DB::table('lara_content_v8fxbooks')->insert($book);
            $disk->put($image->path, $contents);
            (new ContentImporter(new V8Source))->run(['v8fxbook']);
        }

        $this->assertTrue($report->contains(fn (array $line): bool => $line['entity'] === 'v8fxbook' && $line['level'] === 'error' && str_contains($line['message'], 'v8 has')), 'A row fewer than v8 is an error.');
        $this->assertTrue($report->contains(fn (array $line): bool => $line['entity'] === 'curator' && $line['level'] === 'error' && str_contains($line['message'], $image->path)), 'A missing media file is an error.');
        $this->assertTrue($report->contains(fn (array $line): bool => $line['entity'] === 'sync: content' && $line['level'] === 'warning' && str_contains($line['message'], 'would be updated')), 'A row changed in v10 is out of sync.');

        // restored: verify is clean again
        $this->assertSame([], collect((new Verifier(new V8Source))->run(['counts', 'media']))->where('level', 'error')->all());
    }

    public function test_a_site_override_is_kept_by_the_import_and_checked_by_verify(): void
    {
        File::put(self::$overridesPath, "<?php\n\nreturn [\n    ['table' => 'lara_content_v8fxbooks', 'where' => ['id' => 1], 'set' => ['title' => 'Changed for the site'], 'reason' => 'test'],\n    ['table' => 'lara_content_v8fxbooks', 'where' => ['id' => 999], 'set' => ['title' => 'x'], 'reason' => 'stale'],\n];\n");
        SiteOverrides::flush();

        try {
            $first = (new ContentImporter(new V8Source))->run(['v8fxbook']);
            $second = (new ContentImporter(new V8Source))->run(['v8fxbook'], true);
            $title = DB::table('lara_content_v8fxbooks')->where('id', 1)->value('title');
            $report = collect((new Verifier(new V8Source))->run(['sync']));
        } finally {
            File::delete(self::$overridesPath);
            SiteOverrides::flush();
            (new ContentImporter(new V8Source))->run(['v8fxbook']);
        }

        $this->assertSame('Changed for the site', $title);
        $this->assertTrue(collect($first)->contains(fn (array $line): bool => $line['entity'] === 'v8fxbook' && $line['level'] === 'update'));
        $this->assertTrue(collect($second)->contains(fn (array $line): bool => $line['entity'] === 'v8fxbook' && $line['level'] === 'same'), 'A rerun keeps the override.');
        $this->assertTrue($report->contains(fn (array $line): bool => $line['entity'] === 'sync: content' && $line['level'] === 'ok'), 'The override counts as in sync.');
        $this->assertTrue($report->contains(fn (array $line): bool => $line['entity'] === 'overrides' && $line['level'] === 'ok' && str_starts_with($line['message'], '1 of 2')));
        $this->assertTrue($report->contains(fn (array $line): bool => $line['entity'] === 'overrides' && $line['level'] === 'warning' && str_contains($line['message'], 'no such row')), 'A stale override is reported.');
        $this->assertNotSame('Changed for the site', DB::table('lara_content_v8fxbooks')->where('id', 1)->value('title'), 'Without the override the v8 title is back.');
    }

    public function test_the_sync_dry_run_runs_every_step_and_changes_nothing(): void
    {
        $before = DB::table('lara_content_v8fxbooks')->orderBy('id')->get()->toJson();

        $exit = Artisan::call('lara:v8:sync', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit, $output);
        foreach (['import-entities', 'import-users', 'import-content', 'import-objects', 'import-menus', 'import-settings', 'import-media', 'rewrite-urls', 'verify'] as $step) {
            $this->assertStringContainsString("▶ lara:v8:{$step}", $output);
        }
        $this->assertStringContainsString('Dry run finished: nothing changed.', $output);
        $this->assertSame($before, DB::table('lara_content_v8fxbooks')->orderBy('id')->get()->toJson());
    }

    public function test_a_rerun_takes_the_image_dimensions_from_curator_while_the_file_is_unchanged(): void
    {
        $row = DB::table('curator')->where('path', 'v8fxbooks/cover.jpg')->first();
        $curatorLine = fn (array $report): string => (string) collect($report)->first(fn (array $line): bool => $line['entity'] === 'curator')['message'];

        try {
            DB::table('curator')->where('id', $row->id)->update(['width' => 1]);
            $unchanged = (new MediaImporter(new V8Source))->run(dryRun: true);

            DB::table('curator')->where('id', $row->id)->update(['size' => $row->size + 1]);
            $changed = (new MediaImporter(new V8Source))->run(dryRun: true);
        } finally {
            DB::table('curator')->where('id', $row->id)->update(['width' => $row->width, 'size' => $row->size]);
        }

        $this->assertStringContainsString('0 would be updated', $curatorLine($unchanged), 'Same file size: the stored width is used, the image is not read again.');
        $this->assertStringContainsString('1 would be updated', $curatorLine($changed), 'Another file size: the image is read again.');
    }

    public function test_rows_with_the_same_key_are_written_once_and_fresh_removes_rows_by_their_own_values(): void
    {
        Schema::dropIfExists('v8fx_sync_test');
        Schema::create('v8fx_sync_test', function ($table): void {
            $table->id();
            $table->string('path')->nullable();
            $table->string('note')->nullable();
        });

        $syncer = new class
        {
            use SyncsRows;

            /** @var list<array{level: string, entity: string, message: string}> */
            public array $report = [];

            public function write(Collection $rows, bool $fresh = false): void
            {
                $this->sync('test', 'v8fx_sync_test', ['path'], $rows, false, $fresh);
            }

            private function add(string $level, string $entity, string $message): void
            {
                $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
            }
        };

        $syncer->write(collect([['path' => 'a#1.jpg', 'note' => 'first'], ['path' => 'a#1.jpg', 'note' => 'second'], ['path' => 'b.jpg', 'note' => 'b']]));

        $this->assertSame(['first'], DB::table('v8fx_sync_test')->where('path', 'a#1.jpg')->pluck('note')->all(), 'Two rows with one key: written once, the first wins.');
        $this->assertTrue(collect($syncer->report)->contains(fn (array $line): bool => $line['level'] === 'warning' && str_contains($line['message'], '1 row(s) have the same key')));

        DB::table('v8fx_sync_test')->insert(['path' => null, 'note' => 'v10 only']);
        $syncer->write(collect([['path' => 'b.jpg', 'note' => 'b']]), fresh: true);

        $this->assertSame(['b.jpg'], DB::table('v8fx_sync_test')->pluck('path')->all(), 'Fresh removes a key with a # and a NULL key.');

        // JSON comes back from MySQL with spaces after the separators: no change
        DB::table('v8fx_sync_test')->insert(['path' => 'c.json', 'note' => '{"a": 1, "b": [1, 2]}']);
        $syncer->report = [];
        $syncer->write(collect([['path' => 'b.jpg', 'note' => 'b'], ['path' => 'c.json', 'note' => '{"a":1,"b":[1,2]}']]));

        $this->assertTrue(collect($syncer->report)->contains(fn (array $line): bool => str_contains($line['message'], '0 created, 0 updated, 2 unchanged')), 'Equal JSON with other spacing is unchanged.');

        Schema::drop('v8fx_sync_test');
    }

    public function test_column_defaults_are_read_as_values(): void
    {
        $parse = (new class
        {
            use SyncsRows;

            public function parse(string $sql): ?string
            {
                return self::columnDefaultValue($sql);
            }

            private function add(string $level, string $entity, string $message): void {}
        })->parse(...);

        $this->assertSame('0', $parse('0'), 'MySQL: the plain value.');
        $this->assertSame('public', $parse("'public'"), 'MariaDB: a quoted string literal.');
        $this->assertSame("it's", $parse("'it''s'"));
        $this->assertNull($parse('CURRENT_TIMESTAMP'), 'An expression default is no value.');
        $this->assertNull($parse('current_timestamp()'));
        $this->assertNull($parse('NULL'));
    }

    public function test_rows_of_a_missing_user_go_to_an_imported_admin(): void
    {
        $importer = new ContentImporter(new V8Source);
        $fallback = (new ReflectionMethod($importer, 'fallbackUser'))->invoke(...);
        $v8Admin = (int) DB::connection('lara_v8')->table('lara_auth_users')->where('is_admin', 1)->orderBy('id')->value('id');
        $users = DB::table('lara_auth_users')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $this->assertSame($v8Admin, $fallback($importer, $users), 'The v8 admin when it was imported.');

        $others = array_values(array_diff($users, [$v8Admin]));
        $chosen = $fallback($importer, $others);

        $this->assertNotSame($v8Admin, $chosen, 'Never a user that was not imported.');
        $this->assertContains($chosen, $others);
        $this->assertNull($fallback($importer, []));
    }

    public function test_the_sync_stops_after_a_failing_step(): void
    {
        $v8 = new V8Source;
        $entity = $v8->contentEntities()->firstWhere('entity_key', 'v8fxbook');
        $table = $v8->contentTable($entity->entity_key, $entity->group_key);
        DB::connection('lara_v8')->statement("RENAME TABLE `{$table}` TO `{$table}_away`");

        try {
            $exit = Artisan::call('lara:v8:sync');
            $output = Artisan::output();
        } finally {
            DB::connection('lara_v8')->statement("RENAME TABLE `{$table}_away` TO `{$table}`");
        }

        $this->assertSame(1, $exit, $output);
        $this->assertStringContainsString('Stopped after the error in', $output);
        $this->assertStringNotContainsString('▶ lara:v8:import-media', $output, 'The --fresh media step does not run after the failure.');
        $this->assertStringContainsString('▶ lara:v8:verify', $output, 'Verify still reports.');
    }

    public function test_a_skipped_content_type_is_not_built_and_reported(): void
    {
        $this->assertNull(Entity::where('resource_slug', 'v8fxleftovers')->first());
        $this->assertFileDoesNotExist(base_path('laracms/app/Models/V8fxleftover.php'));
        $this->assertFalse(Schema::hasTable('lara_content_v8fxleftovers'));

        $info = collect(self::$report)->where('level', 'info');
        $this->assertTrue($info->contains(fn (array $l): bool => $l['entity'] === 'v8fxleftover' && str_contains($l['message'], 'Skipped (v8-migration.skip_entities): not built.')));
        $this->assertTrue($info->contains(fn (array $l): bool => $l['entity'] === 'v8fxbook' && str_contains($l['message'], 'Relation hasMany v8fxleftover: v8fxleftover is skipped')), 'A relation to a skipped type is reported, not an error.');
        $this->assertSame([], collect(self::$rerun)->where('entity', 'v8fxleftover')->whereNotIn('level', ['info'])->all());
    }

    public function test_the_data_of_a_skipped_content_type_is_not_imported_and_reported(): void
    {
        $this->assertTrue(collect(self::$contentReport)->contains(fn (array $l): bool => $l['level'] === 'info' && $l['entity'] === 'v8fxleftover' && str_contains($l['message'], '1 record(s) not imported')));

        $this->assertNull(DB::table('lara_object_tags')->where('id', 18)->first(), 'Tags of a skipped type are not imported.');
        $this->assertNull(DB::table('lara_object_taggables')->where('id', 5)->first());
        $this->assertSame(0, DB::table('lara_object_seo')->where('model_type', 'like', '%V8fxleftover')->count());
        $objects = collect(self::$objectReport)->firstWhere(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], 'skipped content type (v8fxleftover)'));
        $this->assertNotNull($objects);
        $this->assertStringContainsString('1 tags', $objects['message']);
        $this->assertStringContainsString('1 taggables', $objects['message']);
        $this->assertStringContainsString('1 SEO', $objects['message']);
        $this->assertFalse(collect(self::$objectReport)->contains(fn (array $l): bool => $l['level'] === 'warning' && str_contains($l['message'], 'V8fxleftover')), 'No warning for rows of a skipped type.');

        $this->assertTrue(collect(self::$mediaReport)->contains(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], "Media of 'v8fxleftover', a skipped content type")));
        $this->assertFalse(collect(self::$mediaReport)->contains(fn (array $l): bool => str_contains($l['message'], 'V8fxleftover')), 'No "not a v8 content type" warning.');

        $this->assertTrue(collect(self::$userReport)->contains(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], 'Abilities of skipped content types (v8-migration.skip_entities) not imported: v8fxleftover.')));
        $this->assertFalse(collect(self::$userReport)->contains(fn (array $l): bool => str_contains($l['message'], "v8 subject 'v8fxleftover'")));
    }

    public function test_verify_does_not_count_a_skipped_content_type(): void
    {
        $report = collect((new Verifier(new V8Source))->run(['counts']));

        $this->assertFalse($report->contains(fn (array $l): bool => $l['entity'] === 'v8fxleftover'));
        $this->assertSame([], $report->where('level', 'error')->all());
    }

    public function test_the_inventory_lists_a_skipped_content_type_and_what_it_drops(): void
    {
        config(['v8-migration.skip_entities' => ['v8fxleftover', 'page', 'nosuchtype']]);
        $inventory = new V8Inventory(new V8Source);
        $sections = collect($inventory->sections())->keyBy('title');

        $skipped = $sections->first(fn (array $section): bool => str_starts_with($section['title'], 'Skipped content types'));
        $this->assertSame([['v8fxleftover', 'Fixture Leftovers', 1, 1, 1, 1, 1, 1, 1]], $skipped['rows'], 'The last column: footer item 8 links it.');

        $content = $sections['Content types']['rows'];
        $this->assertNotContains('v8fxleftover', array_column($content, 0));
        $this->assertContains('page', array_column($content, 0), 'A core content type is never skipped.');

        $findings = $inventory->findings();
        $this->assertContains("Content type 'v8fxleftover' is skipped: 1 record(s), 1 media row(s) and 1 menu item(s) are not imported.", $findings);
        $this->assertContains("v8-migration.skip_entities: 'page' is a core content type, which cannot be skipped; it is imported.", $findings);
        $this->assertContains("v8-migration.skip_entities: 'nosuchtype' is not a v8 content type.", $findings);
        $this->assertEmpty(array_filter($findings, fn (string $f): bool => str_contains($f, 'V8fxleftover') && str_contains($f, 'not the model')));
        $this->assertNotEmpty(array_filter($findings, fn (string $f): bool => str_starts_with($f, 'lara_content_pages.body: v8 column shortcodes ([kolom_XvanN]) in 1 row(s) (ids 19)')));
    }

    public function test_a_site_custom_field_on_a_core_type_used_by_live_records_is_added(): void
    {
        $pages = Entity::where('resource_slug', 'pages')->firstOrFail();
        $subtitle = $pages->customfields()->where('field_name', 'subtitle')->first();

        $this->assertNotNull($subtitle, 'page.subtitle is used by live page 19.');
        $this->assertSame(['string', 'after-slug'], [$subtitle->field_type, $subtitle->field_hook]);
        $this->assertContains('subtitle', Schema::getColumnListing('lara_content_pages'));
        $this->assertSame('Welkom bij de boeken', DB::table('lara_content_pages')->where('id', 19)->value('subtitle'), 'The content import fills it.');

        $this->assertNull($pages->customfields()->where('field_name', 'oldnote')->first());
        $this->assertNotContains('oldnote', Schema::getColumnListing('lara_content_pages'));

        $info = collect(self::$report)->where('level', 'info')->pluck('message');
        $this->assertTrue($info->contains(fn (string $m): bool => str_contains($m, "v8 custom field 'subtitle' of core entity 'pages' is used by 1 live record(s): added as a custom field.")));
        $this->assertTrue($info->contains(fn (string $m): bool => str_contains($m, "v8 custom field 'oldnote' of core entity 'pages' is not used by live records; not added")));
        $this->assertFalse(collect(self::$rerun)->contains(fn (array $l): bool => $l['entity'] === 'page' && str_contains($l['message'], "'subtitle'")), 'A rerun leaves the built field alone.');
        $this->assertFalse(collect(self::$rerun)->contains(fn (array $l): bool => $l['entity'] === 'page' && in_array($l['level'], ['create', 'update', 'differs', 'error'], true)), "v10's own page fields are never touched.");
    }

    public function test_site_field_settings_set_the_v10_field_type_and_leave_fields_out(): void
    {
        $fields = Entity::where('resource_slug', 'v8fxbooks')->firstOrFail()->customfields()->get()->keyBy('field_name');
        $report = collect(self::$report)->where('entity', 'v8fxbook');

        $this->assertSame('richeditor', $fields['introtext']->field_type);
        $this->assertSame('select', $fields['picture']->field_type);
        $this->assertSame(['get_record_images'], $fields['picture']->field_options);
        $this->assertArrayNotHasKey('secretkey', $fields->all(), 'A field left out is not built.');
        $this->assertFalse(Schema::hasColumn('lara_content_v8fxbooks', 'secretkey'));
        $this->assertFalse($report->contains(fn (array $l): bool => $l['level'] === 'warning' && str_contains($l['message'], "'introtext'")), 'A field with a site type is not reported as a textarea.');
        $this->assertTrue($report->contains(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], "Field 'introtext' (custom) becomes richeditor")));
        $this->assertTrue($report->contains(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], "Field 'secretkey' left out")));
    }

    public function test_site_field_values_are_imported_and_a_v8_image_id_becomes_the_v10_media_id(): void
    {
        $books = DB::table('lara_content_v8fxbooks')->orderBy('id')->get()->keyBy('id');
        $inside = DB::table('curator')->where('path', 'v8fxbooks/inside.png')->value('id');

        $this->assertNotNull($inside);
        $this->assertSame('<p>Inleiding</p>', $books[1]->introtext);
        $this->assertSame((string) $inside, (string) $books[1]->picture, 'Book 1 holds its own image 2: the v10 media id of inside.png.');
        $this->assertNull($books[2]->picture, 'Book 2 holds an image of book 1, not its own: not imported.');

        $content = collect(self::$contentReport)->where('entity', 'v8fxbook');
        $this->assertTrue($content->contains(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], 'Field secretkey left out')));
        $this->assertTrue($content->contains(fn (array $l): bool => $l['level'] === 'info' && str_contains($l['message'], '1 image field value(s) wait for their media')));
        $this->assertTrue($content->contains(fn (array $l): bool => $l['level'] === 'warning' && str_contains($l['message'], '2.picture: v8 image 1 is not an image of this record')));
        $this->assertTrue(collect(self::$mediaReport)->contains(fn (array $l): bool => $l['entity'] === 'v8fxbook' && $l['level'] === 'update' && str_contains($l['message'], 'Set 1 image field value(s)')));
        $this->assertTrue(collect(self::$mediaRerun)->contains(fn (array $l): bool => $l['entity'] === 'v8fxbook' && $l['level'] === 'same' && str_contains($l['message'], '1 image field value(s)')));

        // once the media exist, the content import writes the same media id: nothing changes
        $changes = collect(self::$contentAfterMedia)->whereNotIn('level', ['same', 'info', 'warning']);
        $this->assertEmpty($changes, $changes->pluck('message')->implode("\n"));
    }

    public function test_derived_page_columns_are_kept_on_a_rerun_and_written_with_fresh(): void
    {
        $v8 = DB::connection('lara_v8')->table('lara_content_pages')->where('id', 19)->first(['ishome', 'position', 'menuroute']);
        // what the essential seed (page 19 is its English home) or the v10 Pages list leaves behind
        DB::table('lara_content_pages')->where('id', 19)->update(['ishome' => 0, 'position' => 9001, 'menuroute' => '/v10']);

        try {
            (new ContentImporter(new V8Source))->run(['page']);
            $this->assertSame([0, 9001, '/v10'], $this->derived(19), 'A rerun keeps what v10 derived.');

            (new ContentImporter(new V8Source))->run(['page'], fresh: true);
            $this->assertSame([(int) $v8->ishome, (int) $v8->position, $v8->menuroute], $this->derived(19), 'With --fresh the v8 values are written.');
        } finally {
            (new ContentImporter(new V8Source))->run(['page'], fresh: true);
        }
    }

    /**
     * @return array{int, int, ?string}
     */
    private function derived(int $id): array
    {
        $page = DB::table('lara_content_pages')->where('id', $id)->first(['ishome', 'position', 'menuroute']);

        return [(int) $page->ishome, (int) $page->position, $page->menuroute];
    }
}
