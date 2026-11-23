<?php

namespace Tests\Feature\LaraV8;

use App\Console\Commands\LaraV8\Support\CoreCustomFields;
use App\Console\Commands\LaraV8\Support\EntityImporter;
use App\Console\Commands\LaraV8\Support\V8Map;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Support\Str;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityCustomField;
use Tests\TestCase;
use Throwable;

/**
 * The dry run of lara:v8:import-entities against the configured v8 source (skipped
 * without one). It plans every site content type and changes nothing; the real build
 * writes code and tables, so it is not run against the shared test database.
 */
class ImportEntitiesDryRunTest extends TestCase
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

    /**
     * @return list<array{level: string, entity: string, message: string}>
     */
    private function dryRun(): array
    {
        return (new EntityImporter($this->source))->run(dryRun: true);
    }

    public function test_the_dry_run_changes_nothing(): void
    {
        $entities = Entity::count();
        $fields = EntityCustomField::count();

        $this->dryRun();

        $this->assertSame($entities, Entity::count());
        $this->assertSame($fields, EntityCustomField::count());
    }

    public function test_every_site_content_type_is_planned_and_no_core_one(): void
    {
        $report = collect($this->dryRun());
        $planned = $report->pluck('entity')->unique();

        foreach ($this->source->contentEntities() as $entity) {
            if (! V8Map::isCoreEntity($entity->entity_key)) {
                $this->assertContains($entity->entity_key, $planned, "Site content type {$entity->entity_key} is not planned.");

                continue;
            }

            // a core content type keeps its v10 configuration; at most option values are added, the
            // site's own v8 custom fields that v10 lacks (CoreCustomFields), and page views for the
            // v8 page templates v10 lacks
            $siteFields = collect((new CoreCustomFields($this->source))->candidates())
                ->where('entity_key', $entity->entity_key)
                ->map(fn (array $candidate): string => "Field '{$candidate['v8_field']->fieldname}' ")
                ->all();

            foreach ($report->where('entity', $entity->entity_key) as $line) {
                $this->assertTrue(
                    Str::startsWith($line['message'], ['Option field ', 'v8 custom field ', 'Page template ', ...$siteFields]),
                    "Core content type {$entity->entity_key} must keep its v10 configuration: {$line['message']}",
                );
            }
        }
    }

    public function test_the_plan_has_no_errors(): void
    {
        $errors = collect($this->dryRun())->where('level', 'error')->pluck('message');

        $this->assertEmpty($errors, $errors->implode("\n"));
    }

    public function test_geolocation_parts_are_not_planned_as_fields(): void
    {
        $report = collect($this->dryRun());

        // A site can have no geolocation field (macgyverbouw: location skipped): the loop below then has nothing to check.
        $this->assertIsList($report->all());

        foreach ($this->source->contentEntities() as $entity) {
            $v8Fields = $this->source->connection()->table('lara_ent_entity_custom_columns')->where('entity_id', $entity->id)->get();

            foreach (V8Map::geolocationParts($v8Fields) as $part) {
                $this->assertFalse(
                    $report->contains(fn (array $line): bool => $line['entity'] === $entity->entity_key && str_starts_with($line['message'], "Field '{$part}' (")),
                    "{$entity->entity_key}.{$part} belongs in the geolocation field.",
                );
            }
        }
    }

    public function test_the_command_dry_run_succeeds(): void
    {
        $this->artisan('lara:v8:import-entities', ['--dry-run' => true])
            ->expectsOutputToContain('(dry run)')
            ->assertSuccessful();
    }
}
