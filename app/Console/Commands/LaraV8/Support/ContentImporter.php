<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityRelation;
use Lara\Common\Models\EntityView;
use Throwable;

/**
 * Imports the records of every v8 content type (pages, entities, blocks, forms) into the
 * table of its v10 entity, keeping the v8 ids (so language_parent, relations and the later
 * object, menu and media imports stay valid).
 *
 * - Columns: V8Map::contentColumn() (core renames, page lead → body2, geolocation parts → geo_*).
 * - Values: core values translated (V8Map::coreValue()), '' → null in custom fields,
 *   record locks dropped, a user_id of a missing user → the v8 admin.
 * - Rerunnable: only rows that differ are written. v10 rows that v8 does not have are
 *   reported, and removed with $fresh (e.g. the pages of the essential seed).
 *
 * Run lara:v8:import-entities and lara:v8:import-users first.
 */
class ContentImporter
{
    private const LOCK_COLUMNS = ['locked_at', 'locked_by'];

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    public function __construct(private readonly V8Source $source) {}

    /**
     * @param  list<string>  $only  v8 entity keys; empty for every content type
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(array $only = [], bool $dryRun = false, bool $fresh = false): array
    {
        $this->report = [];
        $users = DB::table('lara_auth_users')->pluck('id')->all();
        $fallbackUser = $this->source->connection()->table('lara_auth_users')->where('is_admin', 1)->orderBy('id')->value('id') ?? min($users ?: [null]);

        // the rows come from a consistent v8 database and keep their ids; foreign keys are
        // checked afterwards and reported, so the order of the tables does not matter
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->source->contentEntities() as $v8) {
                if ($only !== [] && ! in_array($v8->entity_key, $only, true)) {
                    continue;
                }

                $entity = Entity::where('resource_slug', V8Map::resourceSlug($v8->entity_key))->first();

                if ($entity === null) {
                    $this->add('error', $v8->entity_key, 'No v10 entity '.V8Map::resourceSlug($v8->entity_key).'; run lara:v8:import-entities first.');

                    continue;
                }

                $this->importTable($v8, $entity, $users, $fallbackUser, $dryRun, $fresh);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if (! $dryRun) {
            foreach ($this->source->contentEntities() as $v8) {
                if ($only === [] || in_array($v8->entity_key, $only, true)) {
                    $this->checkForeignKeys($v8->entity_key);
                }
            }
        }

        return $this->report;
    }

    /**
     * @param  list<int>  $users  ids of the v10 users
     */
    private function importTable(object $v8, Entity $entity, array $users, ?int $fallbackUser, bool $dryRun, bool $fresh): void
    {
        $key = $v8->entity_key;
        $v8Table = $this->source->contentTable($key, $v8->group_key);
        $v10Table = $entity->model_class::getTableName();

        if ($v8Table === null || ! $this->source->hasTable($v8Table)) {
            $this->add('error', $key, "No v8 table {$v8Table}.");

            return;
        }

        $v8Columns = $this->source->columns($v8Table);
        $v10Columns = Schema::getColumnListing($v10Table);
        $hasGeolocation = $this->source->connection()->table('lara_ent_entity_custom_columns')
            ->where('entity_id', $v8->id)->where('fieldtype', 'geolocation')->exists();

        $map = [];

        foreach ($v8Columns as $column) {
            $target = V8Map::contentColumn($key, $column, $hasGeolocation);

            if ($target !== null && in_array($target, $v10Columns, true)) {
                $map[$column] = $target;
            } elseif (! in_array($column, self::LOCK_COLUMNS, true)) {
                $withData = $this->source->connection()->table($v8Table)->whereNotNull($column)->where($column, '!=', '')->count();

                if ($withData > 0) {
                    $this->add('warning', $key, "Column {$v8Table}.{$column} has no v10 column; {$withData} value(s) not imported.");
                }
            }
        }

        $customColumns = [...$entity->customfields()->pluck('field_name')->all(), ...array_values(V8Map::GEO_FIELDS), 'geo_location'];

        // v8 NULL in a v10 column that is NOT NULL with a default (e.g. position): the default
        $defaults = collect(DB::select(
            'select column_name as name, column_default as value from information_schema.columns where table_schema = database() and table_name = ? and is_nullable = ? and column_default is not null',
            [$v10Table, 'NO'],
        ))->pluck('value', 'name')->all();
        $existing = DB::table($v10Table)->get()->keyBy('id');
        $v8Ids = [];
        $counts = ['create' => 0, 'update' => 0, 'same' => 0];
        $reassigned = 0;
        $inserts = [];

        foreach ($this->source->connection()->table($v8Table)->orderBy('id')->cursor() as $v8Row) {
            $row = [];

            foreach ($map as $v8Column => $v10Column) {
                $value = $v8Row->{$v8Column};

                if (in_array($v10Column, self::LOCK_COLUMNS, true)) {
                    $value = null;
                } elseif (V8Map::isCoreEntity($key)) {
                    $value = V8Map::coreValue($key, $v8Column, $value);
                }

                if ($value === '' && in_array($v10Column, $customColumns, true)) {
                    $value = null;
                }

                if ($value === null && array_key_exists($v10Column, $defaults)) {
                    $value = $defaults[$v10Column];
                }

                $row[$v10Column] = $value;
            }

            if (array_key_exists('user_id', $row) && ! in_array((int) $row['user_id'], $users, true)) {
                $row['user_id'] = $fallbackUser;
                $reassigned++;
            }

            $id = (int) $row['id'];
            $v8Ids[] = $id;
            $current = $existing[$id] ?? null;

            if ($current === null) {
                $counts['create']++;
                $inserts[] = $row;
            } elseif ($this->differs($current, $row)) {
                $counts['update']++;

                if (! $dryRun) {
                    DB::table($v10Table)->where('id', $id)->update($row);
                }
            } else {
                $counts['same']++;
            }

            if (count($inserts) >= 100 && ! $dryRun) {
                DB::table($v10Table)->insert($inserts);
                $inserts = [];
            }
        }

        if ($inserts !== [] && ! $dryRun) {
            DB::table($v10Table)->insert($inserts);
        }

        $level = $counts['create'] + $counts['update'] > 0 ? ($counts['update'] > 0 ? 'update' : 'create') : 'same';
        $verb = $dryRun ? 'would be ' : '';
        $this->add($level, $key, "{$v8Table} → {$v10Table}: ".count($v8Ids)." v8 row(s); {$counts['create']} {$verb}created, {$counts['update']} {$verb}updated, {$counts['same']} unchanged.");

        if ($reassigned > 0) {
            $this->add('warning', $key, "{$reassigned} row(s) belonged to a v8 user that no longer exists; assigned to user {$fallbackUser}.");
        }

        $extra = $existing->keys()->map(fn ($id): int => (int) $id)->diff($v8Ids)->values();

        if ($extra->isNotEmpty()) {
            if ($fresh && ! $dryRun) {
                DB::table($v10Table)->whereIn('id', $extra->all())->delete();
                $this->add('update', $key, "{$v10Table}: removed {$extra->count()} row(s) that v8 does not have (ids ".$extra->implode(', ').').');
            } else {
                $this->add('differs', $key, "{$v10Table}: {$extra->count()} row(s) that v8 does not have (ids ".$extra->implode(', ').'); kept (remove with --fresh).');
            }
        }

        if ($key === 'page') {
            $this->checkPageBody2($v8Table);
        }
    }

    /**
     * The v8 page lead is imported in body2, which only templates with extra body fields show.
     */
    private function checkPageBody2(string $v8Table): void
    {
        $pages = Entity::where('resource_slug', 'pages')->first();
        $extraFields = EntityView::where('entity_id', $pages?->id)->pluck('template_extra_fields', 'template');

        $withLead = $this->source->connection()->table($v8Table)
            ->whereNotNull('lead')->where('lead', '!=', '')
            ->get(['id', 'title', 'template']);

        foreach ($withLead as $page) {
            if ((int) ($extraFields[$page->template] ?? 0) < 1) {
                $this->add('warning', 'page', "Page {$page->id} '{$page->title}': the v8 lead is in body2, but its template '{$page->template}' shows no extra body fields; choose a template that does (e.g. landing).");
            }
        }
    }

    /**
     * The belongsTo foreign keys of the imported rows must point to an existing row.
     */
    private function checkForeignKeys(string $entityKey): void
    {
        $entity = Entity::where('resource_slug', V8Map::resourceSlug($entityKey))->first();

        if ($entity === null) {
            return;
        }

        foreach (EntityRelation::where('entity_id', $entity->id)->where('type', 'belongsTo')->get() as $relation) {
            $related = Entity::find($relation->related_entity_id);

            if ($related === null) {
                continue;
            }

            try {
                $table = $entity->model_class::getTableName();
                $relatedTable = $related->model_class::getTableName();
                $broken = DB::table($table)
                    ->whereNotNull($relation->foreign_key)
                    ->whereNotIn($relation->foreign_key, fn ($query) => $query->select('id')->from($relatedTable))
                    ->count();
            } catch (Throwable $e) {
                $this->add('warning', $entityKey, "Foreign key {$relation->foreign_key} could not be checked: ".$e->getMessage());

                continue;
            }

            if ($broken > 0) {
                $this->add('warning', $entityKey, "{$broken} row(s) of {$table}.{$relation->foreign_key} point to a {$related->label_single} that does not exist.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function differs(object $current, array $row): bool
    {
        foreach ($row as $column => $value) {
            $old = $current->{$column} ?? null;

            if (($old === null) !== ($value === null) || (string) $old !== (string) $value) {
                return true;
            }
        }

        return false;
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}
