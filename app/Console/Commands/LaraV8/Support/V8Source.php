<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Read access to a Lara CMS v8.2.46 database (the lara_v8 connection).
 *
 * Holds the v8 conventions the lara:v8:* commands share: which entity groups
 * are content types, and in which table a content type keeps its records.
 */
class V8Source
{
    /**
     * v8 entity groups (lara_ent_entity_groups.key) that hold content types.
     * The other groups (menu, tag, auth, builder, tools, seo) are system screens,
     * which v10 does not configure as entities.
     */
    public const CONTENT_GROUPS = ['page', 'block', 'form', 'entity'];

    /**
     * Table prefix per content group; the table is the prefix plus the plural entity key.
     */
    private const TABLE_PREFIXES = [
        'page' => 'lara_content_',
        'entity' => 'lara_content_',
        'block' => 'lara_blocks_',
        'form' => 'lara_form_',
    ];

    public function connection(): Connection
    {
        return DB::connection(config('v8-migration.connection'));
    }

    /**
     * Every v8 entity with its group key, ordered by id, without the skipped content types
     * (see skippedKeys()): for the imports they do not exist.
     *
     * @return Collection<int, object>
     */
    public function entities(): Collection
    {
        return $this->allEntities()
            ->reject(fn (object $entity): bool => $this->isSkipped($entity->entity_key))
            ->values();
    }

    /**
     * Every v8 entity with its group key, ordered by id, including the skipped ones.
     *
     * @return Collection<int, object>
     */
    public function allEntities(): Collection
    {
        return $this->connection()
            ->table('lara_ent_entities as e')
            ->leftJoin('lara_ent_entity_groups as g', 'g.id', '=', 'e.group_id')
            ->orderBy('e.id')
            ->get(['e.*', 'g.key as group_key']);
    }

    /**
     * The v8 content types the site leaves out of the migration (v8-migration.skip_entities,
     * e.g. demo leftovers): not built, their records, media, tags and other objects not imported.
     * Core content types (page, larawidget, cta, slider) and the standard content types
     * (blog, doc, gallery: V8Map::STANDARD_ENTITIES) cannot be skipped.
     *
     * @return list<string>
     */
    public function skippedKeys(): array
    {
        return array_values(array_filter(
            (array) config('v8-migration.skip_entities', []),
            fn (mixed $key): bool => is_string($key) && $key !== '' && V8Map::canBeSkipped($key),
        ));
    }

    public function isSkipped(?string $entityKey): bool
    {
        return $entityKey !== null && in_array($entityKey, $this->skippedKeys(), true);
    }

    /**
     * The skipped v8 entities that exist in the v8 database.
     *
     * @return Collection<int, object>
     */
    public function skippedEntities(): Collection
    {
        return $this->skippedKeys() === []
            ? collect()
            : $this->allEntities()->filter(fn (object $entity): bool => $this->isSkipped($entity->entity_key))->values();
    }

    /**
     * The skipped content types by their v8 model class, e.g. ['Eve\\Models\\City' => 'city'].
     *
     * @return array<string, string>
     */
    public function skippedClasses(): array
    {
        return $this->skippedEntities()->pluck('entity_key', 'entity_model_class')->all();
    }

    /**
     * The v8 entities that are content types (pages, blocks, forms and entities),
     * without the skipped ones.
     *
     * @return Collection<int, object>
     */
    public function contentEntities(): Collection
    {
        return $this->entities()
            ->filter(fn (object $entity): bool => $this->isContentGroup($entity->group_key))
            ->values();
    }

    public function isContentGroup(?string $groupKey): bool
    {
        return in_array($groupKey, self::CONTENT_GROUPS, true);
    }

    /**
     * The table of a content type, e.g. lara_content_blogs, lara_blocks_larawidgets,
     * lara_form_contactforms; null when the group has no content table.
     */
    public function contentTable(string $entityKey, ?string $groupKey): ?string
    {
        $prefix = self::TABLE_PREFIXES[$groupKey] ?? null;

        return $prefix === null ? null : $prefix.Str::plural($entityKey);
    }

    /**
     * v8 often stores "no value" as the string 'NULL' (e.g. condition_field)
     * or as an empty string, next to real NULLs.
     */
    public static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === 'NULL';
    }

    /**
     * The v8 media file of a media row: {media_path}/{entity_key}/{filename}, or the
     * same name in that folder's _archive, where v8 keeps files that live records can
     * still refer to. Null when neither exists. Files in _trash are never used.
     */
    public function mediaFile(string $entityKey, string $filename): ?string
    {
        $folder = rtrim((string) config('v8-migration.media_path'), '/').'/'.$entityKey;

        foreach (["{$folder}/{$filename}", "{$folder}/_archive/{$filename}"] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public function hasTable(string $table): bool
    {
        return $this->connection()->getSchemaBuilder()->hasTable($table);
    }

    /**
     * @return list<string>
     */
    public function columns(string $table): array
    {
        return $this->connection()->getSchemaBuilder()->getColumnListing($table);
    }
}
