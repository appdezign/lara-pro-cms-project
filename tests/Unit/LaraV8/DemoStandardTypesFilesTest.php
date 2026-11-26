<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\DemoStandardTypes;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Which standard content types a site keeps from the demo, and which demo files belong to them.
 */
class DemoStandardTypesFilesTest extends TestCase
{
    private string $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = sys_get_temp_dir().'/lara-v8-standardtypes-'.uniqid();

        foreach ([
            'Models/Blog.php',
            'Models/Gallery.php',
            'Models/Team.php',
            'Entities/BlogsEntity.php',
            'Entities/GalleriesEntity.php',
            'Policies/BlogPolicy.php',
            'Filament/Resources/Blogs/BlogResource.php',
            'Filament/Resources/Blogs/Pages/ListBlogs.php',
            'Filament/Resources/Teams/TeamResource.php',
            'Http/Controllers/Front/Entity/BlogsController.php',
            'Http/Controllers/Front/Entity/GalleriesController.php',
            'Database/Factories/BlogFactory.php',
            'Database/Migrations/2026_09_01_120000_create_lara_content_blogs_table.php',
            'Database/Migrations/2026_09_01_120003_add_foreign_keys_to_lara_content_blogs_table.php',
            'Database/Migrations/2026_09_01_120000_create_lara_content_teams_table.php',
            'Database/Seeders/DemoLaraContentBlogsTableSeeder.php',
            'Database/Seeders/DemoLaraCustomBlogsTableSeeder.php',
            'Legacy/Models/CustomBlog.php',
        ] as $file) {
            is_dir(dirname("{$this->app}/{$file}")) || mkdir(dirname("{$this->app}/{$file}"), 0777, true);
            file_put_contents("{$this->app}/{$file}", '<?php');
        }
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->app));

        parent::tearDown();
    }

    public function test_the_files_of_a_kept_type_are_its_code_and_migrations_not_its_records(): void
    {
        $this->assertSame([
            'Database/Factories/BlogFactory.php',
            'Database/Migrations/2026_09_01_120000_create_lara_content_blogs_table.php',
            'Database/Migrations/2026_09_01_120003_add_foreign_keys_to_lara_content_blogs_table.php',
            'Entities/BlogsEntity.php',
            'Filament/Resources/Blogs/BlogResource.php',
            'Filament/Resources/Blogs/Pages/ListBlogs.php',
            'Http/Controllers/Front/Entity/BlogsController.php',
            'Models/Blog.php',
            'Policies/BlogPolicy.php',
        ], DemoStandardTypes::files($this->app, ['blog']));
    }

    public function test_the_plural_of_gallery_is_used_for_its_classes(): void
    {
        $this->assertSame([
            'Entities/GalleriesEntity.php',
            'Http/Controllers/Front/Entity/GalleriesController.php',
            'Models/Gallery.php',
        ], DemoStandardTypes::files($this->app, ['gallery']));
    }

    public function test_no_kept_type_keeps_no_file(): void
    {
        $this->assertSame([], DemoStandardTypes::files($this->app, []));
    }

    public function test_only_the_standard_types_the_v8_site_lacks_are_kept(): void
    {
        $source = new class extends V8Source
        {
            public function allEntities(): Collection
            {
                return collect([(object) ['entity_key' => 'page'], (object) ['entity_key' => 'blog'], (object) ['entity_key' => 'team']]);
            }
        };

        $this->assertSame(['doc', 'gallery'], DemoStandardTypes::keys($source));
    }
}
