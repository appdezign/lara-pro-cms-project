<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * The standard content types (V8Map::STANDARD_ENTITIES: blog, doc, gallery) that the v8 site
 * lacks: every migrated site keeps them (user, 2026-11-26), so lara:v8:prepare-target keeps
 * the v10 demo version instead of removing it, without the demo records.
 *
 * A type the v8 site has is removed as before and built from the v8 configuration by
 * lara:v8:import-entities.
 *
 * What "keeping" covers: the type's code in laracms/app (files()), and its definition in the
 * database, which migrate:fresh empties: read from the demo install first (capture()), then
 * written back into the essential base (restore()): the content table (the type's own app
 * migrations), the lara_resource_entities row, its custom fields, views and relations among
 * the kept types, the role permissions and the lara-app translations.
 */
final class DemoStandardTypes
{
    private const ACTIONS = ['view_any', 'delete_any', 'view', 'create', 'update', 'delete'];

    /**
     * The standard content types the v8 site does not have.
     *
     * @return list<string> entity keys
     */
    public static function keys(V8Source $source): array
    {
        $v8 = $source->allEntities()->pluck('entity_key')->all();

        return array_values(array_diff(V8Map::STANDARD_ENTITIES, $v8));
    }

    /**
     * The entity keys of the captured types.
     *
     * @param  array{entities: list<array<string, mixed>>}  $captured
     * @return list<string>
     */
    public static function keysOf(array $captured): array
    {
        $slugs = array_map(fn (array $entity): string => (string) $entity['resource_slug'], $captured['entities']);

        return array_values(array_filter(V8Map::STANDARD_ENTITIES, fn (string $key): bool => in_array(V8Map::resourceSlug($key), $slugs, true)));
    }

    /**
     * The demo files (relative to $appPath, as DemoCode::files() lists them) of these types.
     * The demo record seeders are not part of a type: they stay demo code.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function files(string $appPath, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $patterns = [];

        foreach ($keys as $key) {
            $model = Str::studly($key);
            $slug = V8Map::resourceSlug($key);
            $plural = Str::studly($slug);
            $patterns = [...$patterns,
                '#^Models/'.$model.'\.php$#',
                '#^Entities/'.$plural.'Entity\.php$#',
                '#^Policies/'.$model.'Policy\.php$#',
                '#^Filament/Resources/'.$plural.'/#',
                '#^Http/Controllers/Front/Entity/'.$plural.'Controller\.php$#',
                '#^Database/Factories/'.$model.'Factory\.php$#',
                '#^Database/Migrations/[0-9_]+_(create|add_foreign_keys_to)_lara_content_'.$slug.'_table\.php$#',
            ];
        }

        return array_values(array_filter(
            DemoCode::files($appPath),
            fn (string $file): bool => collect($patterns)->contains(fn (string $pattern): bool => preg_match($pattern, $file) === 1),
        ));
    }

    /**
     * The definition of these types in the current database (the demo install), to write
     * back after the rebuild. A type without an entity row is missing (not a demo install).
     *
     * @param  list<string>  $keys
     * @return array{entities: list<array<string, mixed>>, fields: list<array<string, mixed>>, views: list<array<string, mixed>>, relations: list<array<string, mixed>>, permissions: array<string, list<string>>, translations: list<array<string, mixed>>, missing: list<string>}
     */
    public static function capture(array $keys): array
    {
        $slugs = array_map(fn (string $key): string => V8Map::resourceSlug($key), $keys);
        $entities = DB::table('lara_resource_entities')->whereIn('resource_slug', $slugs)->orderBy('id')->get();
        $ids = $entities->pluck('id')->all();
        $found = $entities->pluck('resource_slug')->all();

        $rows = fn ($query): array => $query->get()->map(fn (object $row): array => (array) $row)->all();
        $tables = config('permission.table_names');
        $subjects = array_map(fn (string $key): string => strtolower(Str::studly($key)), $keys);

        $permissions = [];
        $grants = DB::table($tables['role_has_permissions'].' as rp')
            ->join($tables['permissions'].' as p', 'p.id', '=', 'rp.permission_id')
            ->join($tables['roles'].' as r', 'r.id', '=', 'rp.role_id')
            ->orderBy('p.name')
            ->get(['r.name as role', 'p.name as permission']);

        foreach ($grants as $grant) {
            if (in_array(self::subject($grant->permission), $subjects, true)) {
                $permissions[$grant->role][] = $grant->permission;
            }
        }

        return [
            'entities' => $entities->map(fn (object $row): array => (array) $row)->all(),
            'fields' => $ids === [] ? [] : $rows(DB::table('lara_resource_entity_custom_fields')->whereIn('entity_id', $ids)->orderBy('id')),
            'views' => $ids === [] ? [] : $rows(DB::table('lara_resource_entity_views')->whereIn('entity_id', $ids)->orderBy('id')),
            'relations' => $ids === [] ? [] : $rows(DB::table('lara_resource_entity_relations')->whereIn('entity_id', $ids)->whereIn('related_entity_id', $ids)->orderBy('id')),
            'permissions' => $permissions,
            'translations' => $found === [] ? [] : $rows(DB::table('lara_sys_translations')->where('module', 'lara-app')->whereIn('resource', $found)->orderBy('id')),
            'missing' => array_values(array_filter($keys, fn (string $key): bool => ! in_array(V8Map::resourceSlug($key), $found, true))),
        ];
    }

    /**
     * Write a captured definition into the (rebuilt) database: the content tables first (the
     * kept app migrations in $migrationsPath), then the rows. An entity keeps its id when it
     * is free; fields, views and relations get new ids.
     *
     * @param  array{entities: list<array<string, mixed>>, fields: list<array<string, mixed>>, views: list<array<string, mixed>>, relations: list<array<string, mixed>>, permissions: array<string, list<string>>, translations: list<array<string, mixed>>, missing: list<string>}  $captured
     * @return list<string> what was written, one line per type
     */
    public static function restore(array $captured, string $migrationsPath): array
    {
        $lines = [];

        foreach ($captured['entities'] as $entity) {
            $slug = (string) $entity['resource_slug'];
            $migrations = glob(rtrim($migrationsPath, '/').'/*_lara_content_'.$slug.'_table.php') ?: [];
            sort($migrations);

            foreach ($migrations as $migration) {
                Artisan::call('migrate', ['--force' => true, '--realpath' => true, '--path' => $migration]);
            }

            $oldId = (int) $entity['id'];

            if (DB::table('lara_resource_entities')->where('id', $oldId)->exists()) {
                unset($entity['id']);
                $newId = (int) DB::table('lara_resource_entities')->insertGetId($entity);
            } else {
                DB::table('lara_resource_entities')->insert($entity);
                $newId = $oldId;
            }

            $entityIds[$oldId] = $newId;
            $lines[$slug] = [count($migrations), 0, 0];
        }

        $entityIds ??= [];
        $slugOf = collect($captured['entities'])->pluck('resource_slug', 'id')->all();

        foreach (['fields' => 'lara_resource_entity_custom_fields', 'views' => 'lara_resource_entity_views'] as $part => $table) {
            foreach ($captured[$part] as $row) {
                $slug = $slugOf[$row['entity_id']];
                unset($row['id']);
                $row['entity_id'] = $entityIds[$row['entity_id']];
                DB::table($table)->insert($row);
                $lines[$slug][$part === 'fields' ? 1 : 2]++;
            }
        }

        foreach ($captured['relations'] as $row) {
            unset($row['id']);
            $row['entity_id'] = $entityIds[$row['entity_id']];
            $row['related_entity_id'] = $entityIds[$row['related_entity_id']];
            DB::table('lara_resource_entity_relations')->insert($row);
        }

        $permissionModel = config('permission.models.permission');
        $roleModel = config('permission.models.role');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($captured['permissions'] as $roleName => $names) {
            foreach ($names as $name) {
                $permissionModel::findOrCreate($name, 'web');
            }

            $role = $roleModel::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role !== null) {
                $role->givePermissionTo($names);
            }
        }

        foreach ($captured['translations'] as $row) {
            unset($row['id']);
            $key = ['language' => $row['language'], 'module' => $row['module'], 'resource' => $row['resource'], 'tag' => $row['tag'], 'key' => $row['key']];

            if (! DB::table('lara_sys_translations')->where($key)->exists()) {
                DB::table('lara_sys_translations')->insert($row);
            }
        }

        $translations = collect($captured['translations'])->countBy('resource')->all();

        return array_map(
            fn (string $slug, array $counts): string => "{$slug}: table ({$counts[0]} migration(s)), {$counts[1]} custom field(s), {$counts[2]} view(s), ".($translations[$slug] ?? 0).' translation(s)',
            array_keys($lines),
            $lines,
        );
    }

    private static function subject(string $permission): ?string
    {
        foreach (self::ACTIONS as $action) {
            if (str_starts_with($permission, $action.'_')) {
                return substr($permission, strlen($action) + 1);
            }
        }

        return null;
    }
}
