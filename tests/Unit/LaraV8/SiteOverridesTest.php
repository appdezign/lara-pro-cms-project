<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\SiteOverrides;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SiteOverridesTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = sys_get_temp_dir().'/v8-overrides-'.uniqid().'.php';
        File::put($this->file, "<?php\n\nreturn [\n    ['table' => 'widgets', 'where' => ['id' => 591], 'set' => ['hook' => 'home2'], 'reason' => 'test'],\n    ['table' => 'languages', 'where' => ['code' => 'en'], 'set' => ['publish' => 0]],\n];\n");
        config(['v8-migration.overrides' => $this->file]);
        SiteOverrides::flush();
    }

    protected function tearDown(): void
    {
        File::delete($this->file);
        SiteOverrides::flush();

        parent::tearDown();
    }

    public function test_an_override_sets_its_values_on_the_matching_row_of_its_table(): void
    {
        $this->assertSame(['id' => 591, 'hook' => 'home2', 'title' => 'ATEX'], SiteOverrides::apply('widgets', ['id' => 591, 'hook' => 'home1', 'title' => 'ATEX']));
        $this->assertSame(['id' => '591', 'hook' => 'home2'], SiteOverrides::apply('widgets', ['id' => '591', 'hook' => 'home1']), 'Compared as strings.');
        $this->assertSame(['code' => 'en', 'publish' => 0], SiteOverrides::apply('languages', ['code' => 'en', 'publish' => 1]));
    }

    public function test_other_rows_and_tables_are_left_alone(): void
    {
        $this->assertSame(['id' => 1, 'hook' => 'home1'], SiteOverrides::apply('widgets', ['id' => 1, 'hook' => 'home1']));
        $this->assertSame(['id' => 591, 'hook' => 'home1'], SiteOverrides::apply('sliders', ['id' => 591, 'hook' => 'home1']));
        $this->assertSame(['hook' => 'home1'], SiteOverrides::apply('widgets', ['hook' => 'home1']), 'A row without the where column does not match.');
    }

    public function test_an_empty_where_matches_nothing(): void
    {
        $this->assertFalse(SiteOverrides::matches([], ['id' => 1]));
        $this->assertTrue(SiteOverrides::matches(['id' => 1, 'language' => 'nl'], (object) ['id' => 1, 'language' => 'nl', 'title' => 'x']));
    }

    public function test_without_a_file_there_are_no_overrides(): void
    {
        config(['v8-migration.overrides' => $this->file.'.missing']);

        $this->assertSame([], SiteOverrides::all());
        $this->assertSame(['id' => 591, 'hook' => 'home1'], SiteOverrides::apply('widgets', ['id' => 591, 'hook' => 'home1']));
    }
}
