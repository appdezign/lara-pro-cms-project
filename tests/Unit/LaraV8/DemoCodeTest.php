<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\DemoCode;
use PHPUnit\Framework\TestCase;

class DemoCodeTest extends TestCase
{
    private string $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = sys_get_temp_dir().'/lara-v8-democode-'.uniqid();

        foreach ([
            'Models/Blog.php',
            'Entities/BlogsEntity.php',
            'Filament/Resources/Blogs/Pages/ListBlogs.php',
            'Http/Controllers/Front/Form/ContactformsController.php',
            'Http/Controllers/Front/Page/PagesController.php',
            'Database/Seeders/DatabaseDemoSeeder.php',
            'Legacy/Models/CustomBlog.php',
            'Providers/LaraAppServiceProvider.php',
            'Filament/Components/CustomBlocks/Hero2Block.php',
        ] as $file) {
            @mkdir(dirname("{$this->app}/{$file}"), 0777, true);
            file_put_contents("{$this->app}/{$file}", '<?php');
        }
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->app));

        parent::tearDown();
    }

    public function test_only_the_demo_content_type_code_is_listed(): void
    {
        $this->assertSame([
            'Database/Seeders/DatabaseDemoSeeder.php',
            'Entities/BlogsEntity.php',
            'Filament/Resources/Blogs/Pages/ListBlogs.php',
            'Http/Controllers/Front/Form/ContactformsController.php',
            'Models/Blog.php',
        ], DemoCode::files($this->app));
    }

    public function test_removing_keeps_the_folders_and_everything_else(): void
    {
        $this->assertSame(5, DemoCode::remove($this->app));

        $this->assertSame([], DemoCode::files($this->app));
        $this->assertDirectoryExists("{$this->app}/Models");
        $this->assertDirectoryExists("{$this->app}/Filament/Resources");

        $this->assertFileExists("{$this->app}/Legacy/Models/CustomBlog.php");
        $this->assertFileExists("{$this->app}/Providers/LaraAppServiceProvider.php");
        $this->assertFileExists("{$this->app}/Http/Controllers/Front/Page/PagesController.php");
        $this->assertFileExists("{$this->app}/Filament/Components/CustomBlocks/Hero2Block.php");
    }

    public function test_kept_files_stay_and_emptied_subfolders_go(): void
    {
        @mkdir("{$this->app}/Filament/Resources/Teams/Pages", 0777, true);
        file_put_contents("{$this->app}/Filament/Resources/Teams/Pages/ListTeams.php", '<?php');

        $this->assertSame(4, DemoCode::remove($this->app, ['Models/Blog.php', 'Filament/Resources/Blogs/Pages/ListBlogs.php']));

        $this->assertSame(['Filament/Resources/Blogs/Pages/ListBlogs.php', 'Models/Blog.php'], DemoCode::files($this->app));
        $this->assertDirectoryDoesNotExist("{$this->app}/Filament/Resources/Teams");
        $this->assertDirectoryExists("{$this->app}/Entities", 'A demo folder stays, also when it is empty.');
        $this->assertFileExists("{$this->app}/Legacy/Models/CustomBlog.php");
    }

    public function test_a_missing_folder_is_no_problem(): void
    {
        $this->assertSame([], DemoCode::files($this->app.'/does-not-exist'));
        $this->assertSame(0, DemoCode::remove($this->app.'/does-not-exist'));
    }
}
