<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Facades\Schema;
use Lara\Common\Models\Entity;

/**
 * Custom fields a site added in v8 to a core content type (seerden: page.subtitle).
 *
 * Core content types keep their v10 configuration, so such a field has no v10 column. It is a
 * candidate when the v10 core entity has no column of that name and V8Map::CORE_ENTITIES does
 * not rename or drop it. import-entities builds it like a site field when a live v8 record
 * (published, not trashed) uses it (user decision 2026-11-18, as for core option values);
 * otherwise it is only reported. Once built, the v10 column exists and the field is no longer
 * a candidate, so a rerun leaves it alone and v10's own core fields are never changed.
 */
class CoreCustomFields
{
    public function __construct(private readonly V8Source $source) {}

    /**
     * @return list<array{entity_key: string, entity: Entity, v8_field: object, live: int}>
     */
    public function candidates(): array
    {
        $candidates = [];

        foreach ($this->source->contentEntities() as $v8) {
            $key = $v8->entity_key;

            if (! V8Map::isCoreEntity($key)) {
                continue;
            }

            $entity = Entity::where('resource_slug', V8Map::resourceSlug($key))->first();
            $table = $this->source->contentTable($key, $v8->group_key);

            if ($entity === null || $table === null || ! $this->source->hasTable($table)) {
                continue;
            }

            $v8Columns = $this->source->columns($table);
            $v10Columns = Schema::getColumnListing($entity->model_class::getTableName());
            $mapped = V8Map::CORE_ENTITIES[$key]['columns'];

            $v8Fields = $this->source->connection()->table('lara_ent_entity_custom_columns')
                ->where('entity_id', $v8->id)
                ->orderBy('position')
                ->get()
                ->unique('fieldname');

            foreach ($v8Fields as $v8Field) {
                $name = $v8Field->fieldname;

                if (array_key_exists($name, $mapped) || in_array($name, $v10Columns, true) || ! in_array($name, $v8Columns, true)) {
                    continue;
                }

                $candidates[] = [
                    'entity_key' => $key,
                    'entity' => $entity,
                    'v8_field' => $v8Field,
                    'live' => $this->liveUse($table, $v8Columns, $name),
                ];
            }
        }

        return $candidates;
    }

    /**
     * The number of live records (published, not trashed) with a value in the column.
     *
     * @param  list<string>  $columns
     */
    private function liveUse(string $table, array $columns, string $column): int
    {
        return $this->source->connection()->table($table)
            ->when(in_array('publish', $columns, true), fn ($q) => $q->where('publish', 1))
            ->when(in_array('deleted_at', $columns, true), fn ($q) => $q->whereNull('deleted_at'))
            ->pluck($column)
            ->reject(fn ($value): bool => V8Source::isEmpty($value))
            ->count();
    }
}
