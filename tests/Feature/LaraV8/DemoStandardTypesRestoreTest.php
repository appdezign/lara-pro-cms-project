<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\DemoStandardTypes;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Throwable;

/**
 * lara:v8:prepare-target keeps a standard content type the v8 site lacks: its definition is
 * read from the demo install, the database is rebuilt, and the definition is written back
 * (DemoStandardTypes::capture() / restore()). Runs on its own throwaway database.
 */
class DemoStandardTypesRestoreTest extends TestCase
{
    private const TARGET = 'lara_v8_keep_test';

    private string $migrations;

    private string $originalDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::statement('CREATE DATABASE IF NOT EXISTS `'.self::TARGET.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (Throwable $e) {
            $this->markTestSkipped('Cannot create the test database: '.$e->getMessage());
        }

        $default = config('database.default');
        $this->originalDatabase = (string) config("database.connections.{$default}.database");
        config(["database.connections.{$default}.database" => self::TARGET]);
        DB::purge($default);

        // the kept type's own app migration, as the demo ships it
        $this->migrations = sys_get_temp_dir().'/lara-v8-keep-migrations-'.uniqid();
        mkdir($this->migrations);
        file_put_contents($this->migrations.'/2026_09_01_120000_create_lara_content_blogs_table.php', <<<'PHP'
            <?php

            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('lara_content_blogs', function (Blueprint $table) {
                        $table->id();
                        $table->string('title')->nullable();
                        $table->string('source')->nullable();
                    });
                }
            };
            PHP);

        $this->essentialBase();
    }

    protected function tearDown(): void
    {
        $default = config('database.default');
        DB::statement('DROP DATABASE IF EXISTS `'.self::TARGET.'`');
        config(["database.connections.{$default}.database" => $this->originalDatabase]);
        DB::purge($default);
        exec('rm -rf '.escapeshellarg($this->migrations));

        parent::tearDown();
    }

    public function test_a_captured_type_is_written_back_after_the_rebuild(): void
    {
        // the demo install: a blogs entity with a custom field, a view, permissions for the
        // administrator and a translation
        DB::table('lara_resource_entities')->insert(['id' => 2, 'title' => 'Blogs', 'resource_slug' => 'blogs', 'model_class' => 'Lara\\App\\Models\\Blog', 'nav_group' => 'modules']);
        DB::table('lara_resource_entity_custom_fields')->insert(['entity_id' => 2, 'title' => 'source', 'field_name' => 'source', 'field_type' => 'string']);
        DB::table('lara_resource_entity_views')->insert(['entity_id' => 2, 'title' => 'Index', 'method' => 'index']);
        $permission = config('permission.models.permission');
        $role = config('permission.models.role');
        $permission::findOrCreate('view_any_blog', 'web');
        $permission::findOrCreate('update_blog', 'web');
        $role::where('name', 'administrator')->firstOrFail()->givePermissionTo(['view_any_blog', 'update_blog']);
        DB::table('lara_sys_translations')->insert(['language' => 'nl', 'module' => 'lara-app', 'resource' => 'blogs', 'tag' => 'entity', 'key' => 'entity_title', 'value' => 'Nieuws']);

        $captured = DemoStandardTypes::capture(['blog', 'gallery']);

        $this->assertSame(['gallery'], $captured['missing'], 'A type without an entity row is reported missing.');
        $this->assertSame(['blog'], DemoStandardTypes::keysOf($captured));

        $this->essentialBase();
        $this->assertFalse(DB::table('lara_resource_entities')->where('resource_slug', 'blogs')->exists(), 'The rebuild removed it.');

        $lines = DemoStandardTypes::restore($captured, $this->migrations);

        $this->assertSame(['blogs: table (1 migration(s)), 1 custom field(s), 1 view(s), 1 translation(s)'], $lines);
        $this->assertSame(2, (int) DB::table('lara_resource_entities')->where('resource_slug', 'blogs')->value('id'), 'The entity keeps its free id.');
        $this->assertTrue(Schema::hasColumn('lara_content_blogs', 'source'));
        $this->assertSame(['source'], DB::table('lara_resource_entity_custom_fields')->where('entity_id', 2)->pluck('field_name')->all());
        $this->assertSame(['index'], DB::table('lara_resource_entity_views')->where('entity_id', 2)->pluck('method')->all());
        $this->assertSame('Nieuws', DB::table('lara_sys_translations')->where(['module' => 'lara-app', 'resource' => 'blogs', 'key' => 'entity_title'])->value('value'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $administrator = $role::where('name', 'administrator')->firstOrFail();
        $this->assertTrue($administrator->hasPermissionTo('view_any_blog'));
        $this->assertTrue($administrator->hasPermissionTo('update_blog'));
        $this->assertSame(0, DB::table('lara_content_blogs')->count(), 'No records.');
    }

    public function test_nothing_captured_restores_nothing(): void
    {
        $captured = DemoStandardTypes::capture(['blog']);

        $this->assertSame(['blog'], $captured['missing']);
        $this->assertSame([], DemoStandardTypes::restore($captured, $this->migrations));
        $this->assertFalse(Schema::hasTable('lara_content_blogs'));
    }

    private function essentialBase(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true, '--path' => 'laracms/core/src/common/Database/Migrations']);
        Artisan::call('db:seed', ['--force' => true, '--class' => '\\Lara\\Common\\Database\\Seeders\\DatabaseCommonSeeder']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
