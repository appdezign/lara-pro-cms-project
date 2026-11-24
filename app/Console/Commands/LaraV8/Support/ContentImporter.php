<?php

namespace App\Console\Commands\LaraV8\Support;

use App\Console\Commands\LaraV8\Support\Concerns\SyncsRows;
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
 *   record locks dropped, a user_id of a missing user → the v8 admin (or, when that user was
 *   not imported, the first v10 superadmin).
 * - Rerunnable: only rows that differ are written. v10 rows that v8 does not have are
 *   reported, and removed with $fresh (e.g. the pages of the essential seed).
 *
 * Run lara:v8:import-entities and lara:v8:import-users first.
 */
class ContentImporter
{
    use SyncsRows;

    private const LOCK_COLUMNS = ['locked_at', 'locked_by'];

    /**
     * Columns v10 derives itself: imported for new rows, not overwritten on existing ones.
     * Page home flag, position and menu route follow the menu (ListPages::syncPagesWithMenu()).
     * With --fresh (the first import, the final sync) they are written from v8 too: a v8 page can
     * have the id of an essential seed page, whose values are not v10's own (seerden: v8 page 19
     * "Team" kept the seed's ishome = 1, a second home page until the Pages list was opened).
     */
    private const DERIVED_COLUMNS = [
        'page' => ['ishome', 'position', 'menuroute'],
    ];

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    private readonly UrlRewriter $urls;

    public function __construct(private readonly V8Source $source)
    {
        $this->urls = new UrlRewriter($source);
    }

    /**
     * @param  list<string>  $only  v8 entity keys; empty for every content type
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(array $only = [], bool $dryRun = false, bool $fresh = false): array
    {
        $this->report = [];
        $users = DB::table('lara_auth_users')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $fallbackUser = $this->fallbackUser($users);

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

        $this->reportSkipped($only);

        return $this->report;
    }

    /**
     * The records of skipped content types (v8-migration.skip_entities) are not imported.
     *
     * @param  list<string>  $only
     */
    private function reportSkipped(array $only): void
    {
        foreach ($this->source->skippedEntities() as $v8) {
            if ($only !== [] && ! in_array($v8->entity_key, $only, true)) {
                continue;
            }

            $table = $this->source->contentTable($v8->entity_key, $v8->group_key);
            $count = $table !== null && $this->source->hasTable($table) ? $this->source->connection()->table($table)->count() : 0;

            $this->add('info', $v8->entity_key, "Skipped (v8-migration.skip_entities): {$count} record(s) not imported.");
        }
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
        $contentKeys = $this->source->contentEntities()->pluck('entity_key')->all();
        $rows = collect();
        $reassigned = 0;
        $defaultTemplates = 0;
        $shortcodeRows = [];

        foreach ($this->source->connection()->table($v8Table)->orderBy('id')->cursor() as $v8Row) {
            $row = [];

            foreach ($map as $v8Column => $v10Column) {
                $value = $v8Row->{$v8Column};

                if (in_array($v10Column, self::LOCK_COLUMNS, true)) {
                    $value = null;
                } elseif (V8Map::isCoreEntity($key)) {
                    $value = V8Map::coreValue($key, $v8Column, $value);
                }

                // v8 media URLs in the HTML become v10 storage URLs (the same rewrite as lara:v8:rewrite-urls)
                if (is_string($value) && str_contains($value, '/assets/media/')) {
                    $value = $this->urls->rewriteMedia($value, "{$v10Table}.{$v10Column}#{$v8Row->id}", $dryRun);
                }

                // v8 column shortcodes ([kolom_1van2]…) become rich editor grids
                if (ColumnShortcodes::has($value)) {
                    [$value, $grids] = ColumnShortcodes::convert($value);
                    $shortcodeRows[] = "{$v8Row->id}.{$v10Column}".($grids === 0 ? ' (incomplete, removed)' : '');
                }

                if ($value === '' && in_array($v10Column, $customColumns, true)) {
                    $value = null;
                }

                $row[$v10Column] = $value;
            }

            if (array_key_exists('user_id', $row) && ! in_array((int) $row['user_id'], $users, true)) {
                $row['user_id'] = $fallbackUser;
                $reassigned++;
            }

            // v10 finds a module page (intro/SEO of an index or form) and a form's mail page by the resource slug in its slug
            if ($key === 'page' && in_array($row['cgroup'] ?? null, ['module', 'email'], true) && isset($row['slug'])) {
                $row['slug'] = V8Map::modulePageSlug($row['slug'], $contentKeys);
            }

            // v10's page form requires a template (V8Map::DEFAULT_PAGE_TEMPLATE)
            if ($key === 'page' && V8Source::isEmpty($row['template'] ?? null)) {
                $row['template'] = V8Map::DEFAULT_PAGE_TEMPLATE;
                $defaultTemplates++;
            }

            $rows->push($row);
        }

        if ($defaultTemplates > 0) {
            $this->add('info', $key, "{$defaultTemplates} page(s) without a v8 template get the template '".V8Map::DEFAULT_PAGE_TEMPLATE."' (v10's page form requires one).");
        }

        // column defaults for a v8 NULL, the site overrides, and only the rows that differ:
        // the shared sync (SyncsRows); an existing row keeps what v10 derives itself
        // (e.g. the page home flag and position from the menu)
        $this->sync($key, $v10Table, ['id'], $rows, $dryRun, $fresh, $fresh ? [] : (self::DERIVED_COLUMNS[$key] ?? []), from: $v8Table);

        if ($shortcodeRows !== []) {
            $this->add('info', $key, 'v8 column shortcodes converted to rich editor grids (row.column): '.implode(', ', $shortcodeRows).'.');
        }

        if ($reassigned > 0) {
            $this->add('warning', $key, "{$reassigned} row(s) belonged to a v8 user that no longer exists; assigned to user {$fallbackUser}.");
        }

        if ($key === 'page') {
            $this->checkPageBody2($v8Table);
        }
    }

    /**
     * The user for rows whose v8 user does not exist: the first v8 admin that was imported,
     * otherwise the first v10 superadmin, otherwise the first v10 user (null without users).
     *
     * @param  list<int>  $users  ids of the v10 users
     */
    private function fallbackUser(array $users): ?int
    {
        $v8Admins = $this->source->connection()->table('lara_auth_users')->where('is_admin', 1)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id);
        $admin = $v8Admins->first(fn (int $id): bool => in_array($id, $users, true));

        if ($admin !== null) {
            return $admin;
        }

        $roles = (string) config('permission.table_names.roles');
        $assigned = (string) config('permission.table_names.model_has_roles');
        $superadmin = DB::table($assigned)
            ->join($roles, "{$roles}.id", '=', "{$assigned}.role_id")
            ->where("{$roles}.name", 'superadmin')
            ->whereIn("{$assigned}.model_id", $users ?: [0])
            ->min("{$assigned}.model_id");

        $fallback = $superadmin !== null ? (int) $superadmin : ($users === [] ? null : min($users));

        if ($v8Admins->isNotEmpty()) {
            $this->add('warning', 'users', 'The v8 admin (user '.$v8Admins->first().') was not imported; rows of a missing user go to user '.($fallback ?? '(none)').'.');
        }

        return $fallback;
    }

    /**
     * The v8 page lead is imported in body2, which only templates with extra body fields show.
     */
    private function checkPageBody2(string $v8Table): void
    {
        $columns = $this->source->columns($v8Table);

        // an older v8 pages table has no lead and no template column (inventiverepair)
        if (! in_array('lead', $columns, true) || ! in_array('template', $columns, true)) {
            return;
        }

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

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}
