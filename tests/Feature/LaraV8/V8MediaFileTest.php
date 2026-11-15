<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * v8 keeps files that live records can still refer to in {entity}/_archive;
 * files in {entity}/_trash belong to deleted media and are never used.
 */
class V8MediaFileTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/v8-media-'.uniqid());
        File::ensureDirectoryExists("{$this->root}/doc/_archive");
        File::ensureDirectoryExists("{$this->root}/doc/_trash");
        File::put("{$this->root}/doc/current.pdf", 'current');
        File::put("{$this->root}/doc/_archive/archived.pdf", 'archived');
        File::put("{$this->root}/doc/_trash/trashed.pdf", 'trashed');

        config(['v8-migration.media_path' => $this->root.'/']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_a_file_in_the_entity_folder_is_found(): void
    {
        $this->assertSame("{$this->root}/doc/current.pdf", (new V8Source)->mediaFile('doc', 'current.pdf'));
    }

    public function test_a_file_only_in_archive_is_found_there(): void
    {
        $this->assertSame("{$this->root}/doc/_archive/archived.pdf", (new V8Source)->mediaFile('doc', 'archived.pdf'));
    }

    public function test_a_file_only_in_trash_is_not_used(): void
    {
        $this->assertNull((new V8Source)->mediaFile('doc', 'trashed.pdf'));
    }

    public function test_a_missing_file_gives_null(): void
    {
        $this->assertNull((new V8Source)->mediaFile('doc', 'nowhere.pdf'));
        $this->assertNull((new V8Source)->mediaFile('blog', 'current.pdf'));
    }
}
