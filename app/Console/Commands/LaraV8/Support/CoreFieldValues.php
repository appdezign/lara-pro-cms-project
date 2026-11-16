<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Collection;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityCustomField;

/**
 * Core content types keep their v10 fields, so their v8 data has to fit the v10 options.
 *
 * Per option field of a v10 core entity, this compares the values the v8 data uses
 * (translated with V8Map::coreValue()) with the v10 options:
 * - values used by a live record (published, not trashed) that v10 lacks are added to the
 *   options by lara:v8:import-entities, so the webmaster can still select them;
 * - values only used by unpublished or trashed records are not added (user decision
 *   2026-11-16); the content import keeps them unchanged and reports them.
 * Dynamic options ("get_…", e.g. widget templates) come from the theme and are only listed.
 */
class CoreFieldValues
{
    public function __construct(private readonly V8Source $source) {}

    /**
     * @return list<array{entity_key: string, field: EntityCustomField, v8_column: string, dynamic: bool, live: array<string, int>, other: array<string, int>, add: list<string>, not_added: list<string>}>
     */
    public function fields(): array
    {
        $result = [];

        foreach (V8Map::CORE_ENTITIES as $key => $core) {
            $v8Entity = $this->source->contentEntities()->firstWhere('entity_key', $key);
            $v8Table = $v8Entity ? $this->source->contentTable($key, $v8Entity->group_key) : null;
            $v10Entity = Entity::where('resource_slug', $core['resource_slug'])->first();

            if ($v8Table === null || ! $this->source->hasTable($v8Table) || $v10Entity === null) {
                continue;
            }

            $v8Columns = $this->source->columns($v8Table);
            $renames = array_flip(array_filter($core['columns']));

            foreach ($v10Entity->customfields()->whereNotNull('field_options')->orderBy('field_name')->get() as $field) {
                $v8Column = $renames[$field->field_name] ?? $field->field_name;

                if (! in_array($v8Column, $v8Columns, true)) {
                    continue;
                }

                $options = array_map('strval', (array) $field->field_options);
                $dynamic = count($options) === 1 && str_starts_with($options[0], 'get_');
                $live = $this->valueCounts($key, $v8Table, $v8Columns, $v8Column, live: true);
                $other = $this->valueCounts($key, $v8Table, $v8Columns, $v8Column, live: false);
                $add = $dynamic ? [] : array_values(array_diff(array_keys($live), $options));

                $result[] = [
                    'entity_key' => $key,
                    'field' => $field,
                    'v8_column' => $v8Column,
                    'dynamic' => $dynamic,
                    'live' => $live,
                    'other' => $other,
                    'add' => $add,
                    'not_added' => $dynamic ? [] : array_values(array_diff(array_keys($other), $options, $add)),
                ];
            }
        }

        return $result;
    }

    /**
     * Translated value => number of records, for live records (published, not trashed) or the others.
     *
     * @param  list<string>  $columns
     * @return array<string, int>
     */
    private function valueCounts(string $key, string $table, array $columns, string $column, bool $live): array
    {
        $query = $this->source->connection()->table($table);
        $hasPublish = in_array('publish', $columns, true);
        $hasTrash = in_array('deleted_at', $columns, true);

        if ($live) {
            $query->when($hasPublish, fn ($q) => $q->where('publish', 1))
                ->when($hasTrash, fn ($q) => $q->whereNull('deleted_at'));
        } else {
            if (! $hasPublish && ! $hasTrash) {
                return [];
            }

            $query->where(function ($q) use ($hasPublish, $hasTrash): void {
                $q->when($hasPublish, fn ($q) => $q->orWhere('publish', '!=', 1))
                    ->when($hasTrash, fn ($q) => $q->orWhereNotNull('deleted_at'));
            });
        }

        return Collection::make($query->pluck($column))
            ->reject(fn ($value): bool => V8Source::isEmpty($value))
            ->map(fn ($value): string => (string) V8Map::coreValue($key, $column, $value))
            ->countBy()
            ->sortKeys()
            ->all();
    }
}
