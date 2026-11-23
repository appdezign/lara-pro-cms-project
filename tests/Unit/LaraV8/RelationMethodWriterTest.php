<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\RelationMethodWriter;
use App\Console\Commands\LaraV8\Support\V8Map;
use PHPUnit\Framework\TestCase;

class RelationMethodWriterTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = sys_get_temp_dir().'/lara-v8-model-'.uniqid().'.php';
        file_put_contents($this->file, <<<'PHP'
<?php

namespace Lara\App\Models;

use Lara\Common\Models\BaseModel;

class Event extends BaseModel
{
    protected $table = 'lara_content_events';
}

PHP);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);

        parent::tearDown();
    }

    public function test_only_generated_site_models_have_a_file(): void
    {
        $this->assertSame('/site/laracms/app/Models/Event.php', RelationMethodWriter::modelFile('Lara\\App\\Models\\Event', '/site/laracms/app/'));
        $this->assertNull(RelationMethodWriter::modelFile('Lara\\Common\\Models\\Page', '/site/laracms/app'));
    }

    public function test_a_belongs_to_method_is_added_inside_the_class(): void
    {
        $this->assertTrue(RelationMethodWriter::add($this->file, 'location', 'belongsTo', 'Lara\\App\\Models\\Location', 'location_id'));

        $code = file_get_contents($this->file);

        $this->assertTrue(RelationMethodWriter::hasMethod($this->file, 'location'));
        $this->assertStringContainsString('public function location(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo', $code);
        $this->assertStringContainsString("return \$this->belongsTo(\\Lara\\App\\Models\\Location::class, 'location_id');", $code);
        $this->assertStringEndsWith("}\n", $code);
        $this->assertSame(1, preg_match('/protected \$table = .*?;\s*\/\*\*.*public function location\(\).*\}\s*\}\s*$/s', $code));

        exec('php -l '.escapeshellarg($this->file), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));
    }

    public function test_an_existing_method_is_never_touched(): void
    {
        RelationMethodWriter::add($this->file, 'location', 'belongsTo', 'Lara\\App\\Models\\Location', 'location_id');
        $code = file_get_contents($this->file);

        $this->assertFalse(RelationMethodWriter::add($this->file, 'location', 'belongsTo', 'Lara\\App\\Models\\Other', 'other_id'));
        $this->assertSame($code, file_get_contents($this->file));
    }

    public function test_unknown_types_and_missing_files_are_refused(): void
    {
        $this->assertFalse(RelationMethodWriter::add($this->file, 'tags', 'belongsToMany', 'Lara\\App\\Models\\Tag', 'tag_id'));
        $this->assertFalse(RelationMethodWriter::add($this->file.'.missing', 'location', 'belongsTo', 'Lara\\App\\Models\\Location', 'location_id'));
        $this->assertFalse(RelationMethodWriter::hasMethod($this->file, 'tags'));
    }

    public function test_the_stock_v8_event_front_scope_is_added_once(): void
    {
        [$field, $condition] = V8Map::FRONT_SCOPES['event'];

        $this->assertSame('startdate', $field);
        $this->assertTrue(RelationMethodWriter::addFrontScope($this->file, 'event', $condition));

        $code = file_get_contents($this->file);
        $this->assertStringContainsString('public function scopeFront(\\Illuminate\\Database\\Eloquent\\Builder $query): \\Illuminate\\Database\\Eloquent\\Builder', $code);
        $this->assertStringContainsString("return \$query->where('startdate', '>=', \\Carbon\\Carbon::today());", $code);

        exec('php -l '.escapeshellarg($this->file), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        // a model that already has a scopeFront() (added before, or written by hand) is left alone
        $this->assertFalse(RelationMethodWriter::addFrontScope($this->file, 'event', $condition));
        $this->assertSame($code, file_get_contents($this->file));
    }
}
