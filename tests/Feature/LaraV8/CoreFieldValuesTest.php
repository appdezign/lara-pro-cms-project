<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\CoreFieldValues;
use App\Console\Commands\LaraV8\Support\V8Map;
use App\Console\Commands\LaraV8\Support\V8Source;
use Tests\TestCase;
use Throwable;

/**
 * The v8 values of core content type fields against the v10 options (skipped without a
 * v8 source). Read-only. The rules hold for any site: only values of live records are
 * added, values are translated first, and dynamic options are never extended.
 */
class CoreFieldValuesTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private array $fields;

    protected function setUp(): void
    {
        parent::setUp();

        $source = new V8Source;

        try {
            $source->connection()->getPdo();
        } catch (Throwable $e) {
            $this->markTestSkipped('No v8 source database configured: '.$e->getMessage());
        }

        $this->fields = (new CoreFieldValues($source))->fields();
    }

    public function test_only_values_of_live_records_that_v10_lacks_are_added(): void
    {
        // A site can have nothing to add (doormode): the rules below then have nothing to check.
        $this->assertIsList($this->fields);

        foreach ($this->fields as $row) {
            $options = array_map('strval', (array) $row['field']->field_options);

            foreach ($row['add'] as $value) {
                $this->assertArrayHasKey($value, $row['live']);
                $this->assertNotContains($value, $options);
            }

            foreach ($row['not_added'] as $value) {
                $this->assertArrayNotHasKey($value, $row['live']);
                $this->assertNotContains($value, $options);
            }
        }
    }

    public function test_values_are_translated_before_they_are_compared(): void
    {
        foreach ($this->fields as $row) {
            foreach (array_keys(V8Map::CORE_VALUES[$row['entity_key']][$row['v8_column']] ?? []) as $v8Value) {
                $this->assertArrayNotHasKey($v8Value, $row['live']);
                $this->assertArrayNotHasKey($v8Value, $row['other']);
            }
        }
    }

    public function test_dynamic_options_are_never_extended(): void
    {
        foreach (array_filter($this->fields, fn (array $row): bool => $row['dynamic']) as $row) {
            $this->assertSame([], $row['add']);
            $this->assertSame([], $row['not_added']);
        }
    }

    public function test_only_core_content_types_are_checked(): void
    {
        foreach ($this->fields as $row) {
            $this->assertTrue(V8Map::isCoreEntity($row['entity_key']));
        }
    }
}
