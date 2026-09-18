<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Lara\Common\Models\Entity;
use Lara\Common\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Characterisation test for the shared content form and table.
 *
 * Every user-generated resource renders through one LaraBaseForm and one
 * LaraBaseTable, so a change there affects every entity at once. This captures
 * the resulting structure - form tabs and fields, table columns, filters,
 * actions - for every resource, and compares it against a committed baseline.
 *
 * It goes through Filament's public API rather than the internals, so it stays
 * valid across refactors of how that structure is assembled.
 *
 * Regenerate the baseline deliberately, never casually:
 *   LARA_REBUILD_BASELINE=1 php artisan test --filter=BaseResourceStructureTest
 */
class BaseResourceStructureTest extends TestCase
{
    private function baselinePath(): string
    {
        return __DIR__ . '/__snapshots__/base_resource_structure.json';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::where('name', 'admin')->first();

        if (!$user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);
        Filament::setCurrentPanel('admin');
    }

    /**
     * Structure of every resource that renders through the shared form/table.
     *
     * @return array<string, array<string, mixed>>
     */
    private function captureStructure(): array
    {
        $structure = [];

        $entities = Entity::whereIn('cgroup', ['entity', 'form', 'page', 'block'])
            ->orderBy('resource_slug')
            ->get();

        foreach ($entities as $entity) {
            $resource = $entity->resource;

            if (!$resource || !class_exists($resource)) {
                continue;
            }

            $pages = $resource::getPages();

            $captured = [];

            if (isset($pages['index'])) {
                $captured['table'] = $this->captureTable($pages['index']->getPage());
            }

            if (isset($pages['create'])) {
                $captured['form'] = $this->captureForm($pages['create']->getPage());
            }

            if ($captured !== []) {
                $structure[$entity->resource_slug] = $captured;
            }
        }

        return $structure;
    }

    /**
     * @param class-string $pageClass
     * @return array<string, mixed>
     */
    private function captureTable(string $pageClass): array
    {
        try {
            $instance = Livewire::test($pageClass)->instance();
            $table = $instance->getTable();
        } catch (\Throwable $e) {
            return ['error' => class_basename($e)];
        }

        return [
            'columns' => array_keys($table->getColumns()),
            'filters' => array_keys($table->getFilters()),
            'actions' => collect($table->getFlatActions())->keys()->sort()->values()->all(),
        ];
    }

    /**
     * @param class-string $pageClass
     * @return array<string, mixed>
     */
    private function captureForm(string $pageClass): array
    {
        try {
            $instance = Livewire::test($pageClass)->instance();
            $schema = $instance->getSchema('form');
        } catch (\Throwable $e) {
            return ['error' => class_basename($e)];
        }

        if (!$schema) {
            return ['error' => 'no form schema'];
        }

        return ['fields' => collect($schema->getFlatFields(withHidden: true))->keys()->sort()->values()->all()];
    }

    public function test_the_shared_form_and_table_structure_is_unchanged(): void
    {
        $structure = $this->captureStructure();

        $this->assertNotEmpty($structure, 'Captured no resource structure at all.');

        $path = $this->baselinePath();

        if (getenv('LARA_REBUILD_BASELINE') || !File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

            $this->addToAssertionCount(1);

            fwrite(STDERR, "\n  baseline written: " . count($structure) . " resources\n");

            return;
        }

        $baseline = json_decode(File::get($path), true);

        $this->assertSame(
            $baseline,
            $structure,
            'The shared content form or table changed shape. If that was intended, regenerate '
            . 'the baseline with LARA_REBUILD_BASELINE=1.'
        );
    }
}
