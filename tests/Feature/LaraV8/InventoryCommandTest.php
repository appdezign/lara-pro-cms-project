<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\V8Inventory;
use App\Console\Commands\LaraV8\Support\V8Source;
use Tests\TestCase;
use Throwable;

/**
 * Runs the inventory against the configured v8 source (skipped without one).
 * The assertions hold for any v8.2.46 site, not only the site being migrated.
 */
class InventoryCommandTest extends TestCase
{
    private V8Source $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = new V8Source;

        try {
            $this->source->connection()->getPdo();
        } catch (Throwable $e) {
            $this->markTestSkipped('No v8 source database configured: '.$e->getMessage());
        }
    }

    public function test_the_command_runs_in_both_formats(): void
    {
        $this->artisan('lara:v8:inventory')->assertSuccessful();

        $this->artisan('lara:v8:inventory', ['--markdown' => true])
            ->expectsOutputToContain('### Content types')
            ->expectsOutputToContain('### Findings')
            ->assertSuccessful();
    }

    public function test_every_content_type_is_listed_with_its_row_count(): void
    {
        $section = $this->section('Content types');
        $rows = collect($section['rows'])->keyBy(0);

        foreach ($this->source->contentEntities() as $entity) {
            $this->assertTrue($rows->has($entity->entity_key), "Content type {$entity->entity_key} is missing.");

            $table = $this->source->contentTable($entity->entity_key, $entity->group_key);

            if ($this->source->hasTable($table)) {
                $this->assertSame($this->source->connection()->table($table)->count(), $rows[$entity->entity_key][5]);
            }
        }
    }

    public function test_system_screens_are_not_listed_as_content_types(): void
    {
        $contentKeys = collect($this->section('Content types')['rows'])->pluck(0);
        $systemKeys = collect($this->section('System screens')['rows'])->pluck(0);

        $this->assertEmpty($contentKeys->intersect($systemKeys));
        $this->assertSame($this->source->entities()->count(), $contentKeys->count() + $systemKeys->count());
    }

    public function test_a_field_type_is_only_reported_when_v10_has_no_type_with_that_name(): void
    {
        $inventory = new V8Inventory($this->source);
        $inventory->sections();

        $reported = collect($inventory->findings())
            ->map(fn (string $finding): ?string => preg_match("/^Field type '([^']+)'/", $finding, $m) ? $m[1] : null)
            ->filter();

        $this->assertNotContains('string', $reported);
        $this->assertNotContains('date', $reported);
        $this->assertNotContains('email', $reported);
    }

    /**
     * @return array{title: string, headers: list<string>, rows: list<list<string|int>>}
     */
    private function section(string $titleStart): array
    {
        $section = collect((new V8Inventory($this->source))->sections())
            ->first(fn (array $section): bool => str_starts_with($section['title'], $titleStart));

        $this->assertNotNull($section, "Section '{$titleStart}' is missing.");

        return $section;
    }
}
