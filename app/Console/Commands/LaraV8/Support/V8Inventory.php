<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Collection;
use Lara\Admin\Enums\EntityViewListTypes;
use Lara\Admin\Enums\EntityViewTags;

/**
 * Describes a v8 site before it is migrated: its content types, custom fields,
 * relations, views, media and other data, and everything that has no v10
 * mapping (V8Map), changes behaviour in v10 or is inconsistent (the "findings").
 *
 * Read-only: it only queries the v8 source.
 */
class V8Inventory
{
    /**
     * The relation types of v10 (EntityRelationForm).
     */
    private const V10_RELATION_TYPES = ['belongsTo', 'hasMany'];

    /** @var list<string> */
    private array $findings = [];

    public function __construct(private readonly V8Source $source) {}

    /**
     * Every section of the inventory, with the findings last.
     *
     * @return list<array{title: string, headers: list<string>, rows: list<list<string|int>>}>
     */
    public function sections(): array
    {
        $this->findings = [];

        $sections = [
            $this->contentTypes(),
            $this->skipped(),
            $this->systemEntities(),
            $this->fieldTypes(),
            $this->fieldHooksAndStates(),
            $this->relations(),
            $this->objectRelations(),
            $this->coreFieldValues(),
            $this->views(),
            $this->media(),
            $this->mediaFiles(),
            $this->otherData(),
        ];

        $this->checkCustomFields();
        $this->coreCustomFields();
        $this->columnShortcodes();

        $sections[] = [
            'title' => 'Findings (no v10 mapping, a change in behaviour, or inconsistent v8 data)',
            'headers' => ['#', 'Finding'],
            'rows' => $this->findings === []
                ? [['', 'none']]
                : array_map(fn (string $finding, int $i): array => [$i + 1, $finding], $this->findings, array_keys($this->findings)),
        ];

        return $sections;
    }

    /**
     * @return list<string>
     */
    public function findings(): array
    {
        return $this->findings;
    }

    private function contentTypes(): array
    {
        $languages = $this->source->connection()->table('lara_sys_languages')->orderBy('position')->pluck('code')->all();
        $fieldCounts = $this->countBy('lara_ent_entity_custom_columns', 'entity_id');
        $relationCounts = $this->countBy('lara_ent_entity_relations', 'entity_id');

        $rows = [];

        foreach ($this->source->contentEntities() as $entity) {
            $table = $this->source->contentTable($entity->entity_key, $entity->group_key);
            $total = $trashed = '—';
            $perLanguage = '—';

            if ($table !== null && $this->source->hasTable($table)) {
                $columns = $this->source->columns($table);
                $query = fn () => $this->source->connection()->table($table);

                $total = $query()->count();
                $trashed = in_array('deleted_at', $columns, true) ? $query()->whereNotNull('deleted_at')->count() : '—';

                if (in_array('language', $columns, true)) {
                    $counts = $query()->selectRaw('language, count(*) as n')->groupBy('language')->pluck('n', 'language');
                    $perLanguage = $counts->map(fn ($n, $language): string => ($language ?? 'null').' '.$n)->implode(', ') ?: '0';

                    foreach ($counts->keys()->diff($languages) as $unknown) {
                        $this->findings[] = "{$table}: {$counts[$unknown]} row(s) in language '".($unknown ?? 'null')."', which is not in lara_sys_languages.";
                    }
                }
            } else {
                $this->findings[] = "Content type '{$entity->entity_key}' has no table ".($table ?? '(unknown group)').'.';
            }

            $rows[] = [
                $entity->entity_key,
                $entity->title,
                $entity->group_key,
                $entity->entity_model_class,
                $table ?? '—',
                $total,
                $perLanguage,
                $trashed,
                $fieldCounts[$entity->id] ?? 0,
                $relationCounts[$entity->id] ?? 0,
            ];
        }

        return [
            'title' => 'Content types',
            'headers' => ['Key', 'Title', 'Group', 'v8 model', 'Table', 'Rows', 'Per language', 'Trashed', 'Fields', 'Relations'],
            'rows' => $rows,
        ];
    }

    private function systemEntities(): array
    {
        $rows = $this->source->entities()
            ->reject(fn (object $entity): bool => $this->source->isContentGroup($entity->group_key))
            ->map(fn (object $entity): array => [$entity->entity_key, $entity->title, $entity->group_key ?? '—', $entity->entity_model_class])
            ->values()
            ->all();

        return [
            'title' => 'System screens (not entities in v10; their data is imported by the data commands)',
            'headers' => ['Key', 'Title', 'Group', 'v8 model'],
            'rows' => $rows,
        ];
    }

    private function fieldTypes(): array
    {
        $rows = [];

        foreach ($this->customFields()->groupBy('fieldtype')->sortByDesc(fn (Collection $fields): int => $fields->count()) as $type => $fields) {
            // core content types keep their v10 fields, so only the site's own fields are mapped
            $siteFields = $fields->reject(fn (object $field): bool => V8Map::isCoreEntity($field->entity_key));
            $v10Types = $siteFields
                ->map(fn (object $field): string => V8Map::fieldType($type, $field->group_key === 'form') ?? 'NONE')
                ->unique();
            $names = fn (Collection $fields): string => $fields->map(fn (object $field): string => $field->entity_key.'.'.$field->fieldname)->implode(', ');

            $unmapped = $siteFields->filter(fn (object $field): bool => V8Map::fieldType($type, $field->group_key === 'form') === null);

            if ($unmapped->isNotEmpty()) {
                $this->findings[] = "Field type '{$type}' has no v10 mapping ({$unmapped->count()}x: {$names($unmapped)}).";
            }

            // fields with a site setting (v8-fields.php) get their own type or are left out
            $unset = $siteFields->reject(fn (object $field): bool => SiteFields::get($field->entity_key, $field->fieldname) !== null);

            if ($unset->isNotEmpty() && ($change = V8Map::fieldTypeChange($type)) !== null) {
                $this->findings[] = "Field type '{$type}': {$change} ({$names($unset)}).";
            }

            $rows[] = [
                $type,
                $fields->count(),
                $siteFields->isEmpty() ? 'core only' : $v10Types->implode(', '),
                $fields->pluck('entity_key')->unique()->implode(', '),
            ];
        }

        $configured = $this->customFields()->filter(fn (object $field): bool => SiteFields::get($field->entity_key, $field->fieldname) !== null);

        if ($configured->isNotEmpty()) {
            $this->findings[] = 'Site field settings ('.basename(SiteFields::file()).'): '.$configured->map(function (object $field): string {
                $setting = SiteFields::get($field->entity_key, $field->fieldname);
                $what = ($setting['skip'] ?? false) ? 'left out' : ($setting['type'] ?? $field->fieldtype).(($setting['image'] ?? false) ? ' (v8 image id → v10 media id)' : '');

                return "{$field->entity_key}.{$field->fieldname} {$what}";
            })->implode(', ').'.';
        }

        $byEntity = $this->customFields()->groupBy('entity_key')->map(fn (Collection $fields): array => $fields->pluck('fieldname')->all())->all();

        foreach (SiteFields::problems($byEntity) as $problem) {
            $this->findings[] = $problem;
        }

        return [
            'title' => 'Custom field types (core content types keep their v10 fields)',
            'headers' => ['v8 type', 'Fields', 'v10 type', 'Used by'],
            'rows' => $rows,
        ];
    }

    private function fieldHooksAndStates(): array
    {
        $fields = $this->customFields()->reject(fn (object $field): bool => V8Map::isCoreEntity($field->entity_key));
        $rows = [];

        foreach ($fields->countBy('fieldhook')->sortDesc() as $hook => $count) {
            $v10 = V8Map::hook($hook);
            $rows[] = ['hook', $hook, $count, $v10 ?? 'NONE'];

            if ($v10 === null) {
                $this->findings[] = "Field hook '{$hook}' has no v10 mapping ({$count} fields).";
            }
        }

        foreach ($fields->countBy('fieldstate')->sortDesc() as $state => $count) {
            $v10 = V8Map::ruleState($state);
            $rows[] = ['state', $state, $count, $v10 ?? 'NONE'];

            if ($v10 === null) {
                $this->findings[] = "Field state '{$state}' has no v10 mapping ({$count} fields).";
            }
        }

        foreach ($fields->filter(fn (object $field): bool => V8Map::hasMultipleRuleValues($field->condition_value)) as $field) {
            $this->findings[] = "Custom field {$field->entity_key}.{$field->fieldname} is enabled for several values ('{$field->condition_value}'); v10 compares one value.";
        }

        return [
            'title' => 'Custom field hooks and states (site content types)',
            'headers' => ['Kind', 'v8 value', 'Fields', 'v10 value'],
            'rows' => $rows,
        ];
    }

    private function relations(): array
    {
        $keys = $this->source->entities()->pluck('entity_key', 'id');

        $rows = $this->source->connection()->table('lara_ent_entity_relations')->orderBy('id')->get()
            ->map(function (object $relation) use ($keys): array {
                $inV10 = in_array($relation->type, self::V10_RELATION_TYPES, true);

                if (! $inV10) {
                    $this->findings[] = "Relation type '{$relation->type}' ({$keys[$relation->entity_id]}) has no v10 counterpart.";
                }

                return [
                    $keys[$relation->entity_id] ?? '?',
                    $relation->type,
                    $keys[$relation->related_entity_id] ?? '?',
                    $relation->foreign_key,
                    $relation->is_filter ? 'yes' : 'no',
                    $inV10 ? 'yes' : 'NO',
                ];
            })
            ->all();

        return [
            'title' => 'Relations',
            'headers' => ['Entity', 'Type', 'Related', 'Foreign key', 'Filter', 'Same type in v10'],
            'rows' => $rows,
        ];
    }

    private function objectRelations(): array
    {
        $settings = $this->source->connection()->table('lara_ent_entity_object_relations')->get()->keyBy('entity_id');
        $rows = [];

        foreach ($this->source->contentEntities() as $entity) {
            $o = $settings[$entity->id] ?? null;

            if ($o === null) {
                $rows[] = [$entity->entity_key, '—', '—', '—', '—', '—'];

                continue;
            }

            $flags = collect(['seo' => $o->has_seo, 'opengraph' => $o->has_opengraph, 'layout' => $o->has_layout, 'sync' => $o->has_sync])
                ->filter()->keys()->implode(' ');
            $related = collect(['related' => $o->has_related, 'relatable' => $o->is_relatable])->filter()->keys()->implode(' ');
            $tags = $o->has_tags ? 'yes'.($o->tag_default ? " (default: {$o->tag_default})" : '') : '';
            $media = collect([
                'images' => [$o->has_images, $o->max_images],
                'videos' => [$o->has_videos, $o->max_videos],
                'videofiles' => [$o->has_videofiles, $o->max_videofiles],
                'files' => [$o->has_files, $o->max_files],
            ])->filter(fn (array $media): bool => (bool) $media[0])
                ->map(fn (array $media, string $kind): string => "{$kind} {$media[1]}")
                ->implode(', ');
            $disks = collect([$o->disk_images, $o->disk_videos, $o->disk_files])->filter()->unique()->implode(', ');

            foreach (collect([$o->disk_images, $o->disk_videos, $o->disk_files])->filter()->unique()->reject(fn (string $disk): bool => $disk === 'localdisk') as $disk) {
                $this->findings[] = "Content type '{$entity->entity_key}' uses media disk '{$disk}' (only 'localdisk' is expected).";
            }

            $rows[] = [$entity->entity_key, $flags, $related, $tags, $media, $disks];
        }

        return [
            'title' => 'Object relations and media settings',
            'headers' => ['Entity', 'Has', 'Related', 'Tags', 'Media (max)', 'Disks'],
            'rows' => $rows,
        ];
    }

    private function views(): array
    {
        $keys = $this->source->entities()->pluck('entity_key', 'id');

        $rows = $this->source->connection()->table('lara_ent_entity_views')->orderBy('entity_id')->orderBy('method')->get()
            ->map(function (object $view) use ($keys): array {
                $entity = $keys[$view->entity_id] ?? '?';

                if ($view->method === 'index' && EntityViewListTypes::tryFrom($view->type) === null) {
                    $this->findings[] = "View {$entity}.index has list type '{$view->type}', which v10 does not have.";
                }

                if ($view->showtags !== null && $view->showtags !== 'none' && EntityViewTags::tryFrom($view->showtags) === null) {
                    $this->findings[] = "View {$entity}.{$view->method} has showtags '{$view->showtags}', which v10 does not have.";
                }

                return [$entity, $view->method, $view->filename, $view->type, $view->showtags, $view->paginate, $view->publish ? 'yes' : 'no'];
            })
            ->all();

        return [
            'title' => 'Front views',
            'headers' => ['Entity', 'Method', 'File', 'Type', 'Show tags', 'Paginate', 'Published'],
            'rows' => $rows,
        ];
    }

    private function media(): array
    {
        $tables = ['images' => 'lara_object_images', 'files' => 'lara_object_files', 'videos' => 'lara_object_videos', 'videofiles' => 'lara_object_videofiles'];
        $entitiesByModel = $this->source->allEntities()->keyBy('entity_model_class');

        $counts = [];

        foreach ($tables as $kind => $table) {
            foreach ($this->countBy($table, 'entity_type') as $type => $count) {
                $counts[$type][$kind] = $count;
            }

            foreach ($this->countOrphans($table, $entitiesByModel) as $type => $orphans) {
                $this->findings[] = "{$table}: {$orphans} row(s) of {$type} belong to a record that no longer exists.";
            }
        }

        ksort($counts);
        $rows = [];

        foreach ($counts as $type => $kinds) {
            if (! $entitiesByModel->has($type)) {
                $this->findings[] = "Media rows of '{$type}', which is not the model of any v8 entity.";
            }

            $rows[] = [
                $type,
                $entitiesByModel[$type]->entity_key ?? '?',
                $kinds['images'] ?? 0,
                $kinds['files'] ?? 0,
                $kinds['videos'] ?? 0,
                $kinds['videofiles'] ?? 0,
            ];
        }

        return [
            'title' => 'Media rows per model',
            'headers' => ['v8 model', 'Entity', 'Images', 'Files', 'Videos', 'Videofiles'],
            'rows' => $rows,
        ];
    }

    /**
     * Per folder of the v8 media path: how many media rows point into it, how many of
     * those files are only found in _archive or not at all, and how many files in the
     * folder itself no media row refers to. _trash and _archive are not listed as subfolders.
     */
    private function mediaFiles(): array
    {
        $section = [
            'title' => 'Media files on disk ('.(config('v8-migration.media_path') ?: 'LARA_V8_MEDIA_PATH not set').')',
            'headers' => ['Folder', 'Content type', 'Rows', 'Only in _archive', 'Missing', 'Files in folder', 'Not referenced', 'Subfolders'],
            'rows' => [],
        ];

        $root = rtrim((string) config('v8-migration.media_path'), '/');

        if ($root === '' || ! is_dir($root)) {
            $this->findings[] = 'The v8 media path is not set or does not exist, so the media files were not checked.';

            return $section;
        }

        $entitiesByModel = $this->source->entities()->keyBy('entity_model_class');
        $contentKeys = $this->source->contentEntities()->pluck('entity_key')->all();
        $referenced = [];
        $archived = [];
        $missing = [];

        foreach (['lara_object_images', 'lara_object_files', 'lara_object_videofiles'] as $table) {
            foreach ($this->source->connection()->table($table)->get(['entity_type', 'filename']) as $media) {
                $folder = $entitiesByModel[$media->entity_type]->entity_key ?? null;

                if ($folder === null || V8Source::isEmpty($media->filename) || $this->source->isSkipped($folder)) {
                    continue;
                }

                $referenced[$folder][$media->filename] = true;
                $file = $this->source->mediaFile($folder, $media->filename);

                if ($file === null) {
                    $missing[$folder] = ($missing[$folder] ?? 0) + 1;
                } elseif (str_contains($file, '/_archive/')) {
                    $archived[$folder] = ($archived[$folder] ?? 0) + 1;
                }
            }
        }

        $folders = array_unique([...array_keys($referenced), ...$this->subfolders($root)]);
        sort($folders);

        foreach ($folders as $folder) {
            $path = "{$root}/{$folder}";
            $files = is_dir($path) ? $this->files($path) : [];
            $unreferenced = count(array_diff($files, array_keys($referenced[$folder] ?? [])));
            $subfolders = is_dir($path) ? array_diff($this->subfolders($path), ['_trash', '_archive']) : [];

            if (($missing[$folder] ?? 0) > 0) {
                $this->findings[] = "{$missing[$folder]} media row(s) of '{$folder}' point to a file that is missing (also not in _archive): {$path}.";
            }

            if (($archived[$folder] ?? 0) > 0) {
                $this->findings[] = "{$archived[$folder]} media row(s) of '{$folder}' point to a file that is only in {$folder}/_archive (the import takes it from there).";
            }

            $section['rows'][] = [
                $folder,
                in_array($folder, $contentKeys, true) ? 'yes' : '—',
                count($referenced[$folder] ?? []),
                $archived[$folder] ?? 0,
                $missing[$folder] ?? 0,
                count($files),
                $unreferenced,
                implode(', ', $subfolders),
            ];
        }

        return $section;
    }

    /**
     * @return list<string>
     */
    private function files(string $path): array
    {
        return array_values(array_filter(
            scandir($path) ?: [],
            fn (string $name): bool => ! str_starts_with($name, '.') && is_file("{$path}/{$name}"),
        ));
    }

    /**
     * @return list<string>
     */
    private function subfolders(string $path): array
    {
        return array_values(array_filter(
            scandir($path) ?: [],
            fn (string $name): bool => ! str_starts_with($name, '.') && is_dir("{$path}/{$name}"),
        ));
    }

    /**
     * The content types left out of the migration (v8-migration.skip_entities) and what is
     * dropped with them. A configured key that is not a v8 site content type is a finding.
     */
    private function skipped(): array
    {
        $section = [
            'title' => 'Skipped content types (v8-migration.skip_entities; not built, data not imported)',
            'headers' => ['Key', 'Title', 'Records', 'Media rows', 'Tags', 'Taggables', 'SEO rows', 'Related rows', 'Menu items'],
            'rows' => [],
        ];

        $db = $this->source->connection();
        $configured = (array) config('v8-migration.skip_entities', []);
        $known = $this->source->skippedEntities()->pluck('entity_key')->all();

        foreach (array_diff($configured, $known) as $key) {
            $this->findings[] = match (true) {
                V8Map::isCoreEntity((string) $key) => "v8-migration.skip_entities: '{$key}' is a core content type, which cannot be skipped; it is imported.",
                V8Map::isStandardEntity((string) $key) => "v8-migration.skip_entities: '{$key}' is a standard content type (blog, doc, gallery), which cannot be skipped; it is imported.",
                default => "v8-migration.skip_entities: '{$key}' is not a v8 content type.",
            };
        }

        foreach ($this->source->skippedEntities() as $entity) {
            $key = $entity->entity_key;
            $class = $entity->entity_model_class;
            $table = $this->source->contentTable($key, $entity->group_key);
            $records = $table !== null && $this->source->hasTable($table) ? $db->table($table)->count() : 0;
            $byClass = fn (string $objectTable): int => $db->table($objectTable)->where('entity_type', $class)->count();
            $media = $byClass('lara_object_images') + $byClass('lara_object_files') + $byClass('lara_object_videos') + $byClass('lara_object_videofiles');
            $related = $db->table('lara_object_related')->where('entity_key', $key)->orWhere('related_entity_key', $key)->count();
            $menuItems = $db->table('lara_menu_menu_items')->where('entity_id', $entity->id)->count();

            if ($records > 0 || $media > 0 || $menuItems > 0) {
                $this->findings[] = "Content type '{$key}' is skipped: {$records} record(s), {$media} media row(s) and {$menuItems} menu item(s) are not imported.";
            }

            $section['rows'][] = [
                $key,
                $entity->title,
                $records,
                $media,
                $db->table('lara_object_tags')->where('entity_key', $key)->count(),
                $byClass('lara_object_taggables'),
                $byClass('lara_object_seo'),
                $related,
                $menuItems,
            ];
        }

        if ($section['rows'] === []) {
            $section['rows'][] = ['none', '', '', '', '', '', '', '', ''];
        }

        return $section;
    }

    /**
     * Core content types keep their v10 fields, so their v8 data must fit the v10 options
     * (read from the target site, not changed). See CoreFieldValues for the rules.
     */
    private function coreFieldValues(): array
    {
        $section = [
            'title' => 'Core field values (v8 data, translated, against the v10 options of the target site)',
            'headers' => ['Entity', 'v10 field', 'v8 column', 'Live records', 'Other records', 'Added to options by import-entities'],
            'rows' => [],
        ];

        $list = fn (array $counts): string => collect($counts)->map(fn (int $n, string $value): string => "{$value} ({$n})")->implode(', ') ?: '—';

        foreach ((new CoreFieldValues($this->source))->fields() as $row) {
            $field = $row['field'];
            $key = $row['entity_key'];

            foreach ($row['add'] as $value) {
                $this->findings[] = "{$key}.{$row['v8_column']}: '{$value}' is used by {$row['live'][$value]} live record(s) and is not a v10 option; import-entities adds it to {$field->entity->resource_slug}.{$field->field_name}.";
            }

            foreach ($row['not_added'] as $value) {
                $this->findings[] = "{$key}.{$row['v8_column']}: '{$value}' is only used by unpublished or trashed records ({$row['other'][$value]}x); not added as an option, the value is imported unchanged.";
            }

            $section['rows'][] = [
                $key,
                $field->field_name,
                $row['v8_column'],
                $list($row['live']),
                $list($row['other']),
                $row['dynamic'] ? 'dynamic ('.$field->field_options[0].'): check the theme' : (implode(', ', $row['add']) ?: '—'),
            ];
        }

        return $section;
    }

    private function otherData(): array
    {
        $db = $this->source->connection();
        $summary = fn (Collection $counts): string => $counts->map(fn ($n, $key): string => ($key ?? 'null').' '.$n)->implode(', ');

        $rows = [
            ['Users', $db->table('lara_auth_users')->count(), ''],
            ['Roles', $db->table('lara_auth_roles')->count(), $db->table('lara_auth_roles')->orderBy('level')->pluck('name')->implode(', ')],
            ['Abilities', $db->table('lara_auth_abilities')->count(), ''],
            ['Languages', $db->table('lara_sys_languages')->count(), $db->table('lara_sys_languages')->orderBy('position')->pluck('code')->implode(', ')],
            ['Menus', $db->table('lara_menu_menus')->count(), ''],
            ['Menu items', $db->table('lara_menu_menu_items')->count(), $summary($this->countBy('lara_menu_menu_items', 'type'))],
            ['Redirects', $db->table('lara_menu_redirects')->count(), ''],
            ['Taxonomies', $db->table('lara_object_taxonomies')->count(), $db->table('lara_object_taxonomies')->orderBy('position')->pluck('slug')->implode(', ')],
            ['Tags', $db->table('lara_object_tags')->count(), $summary($this->countBy('lara_object_tags', 'entity_key'))],
            ['Taggables', $db->table('lara_object_taggables')->count(), ''],
            ['SEO rows', $db->table('lara_object_seo')->count(), ''],
            ['Opengraph rows', $db->table('lara_object_opengraph')->count(), ''],
            ['Layout rows', $db->table('lara_object_layout')->count(), ''],
            ['Related rows', $db->table('lara_object_related')->count(), ''],
            ['Pageables', $db->table('lara_object_pageables')->count(), ''],
            ['Sync rows', $db->table('lara_object_sync')->count(), ''],
            ['Settings', $db->table('lara_sys_settings')->count(), $summary($this->countBy('lara_sys_settings', 'cgroup'))],
            ['Translations', $db->table('lara_sys_translations')->count(), $summary($this->countBy('lara_sys_translations', 'module'))],
            ['Header tags', $db->table('lara_sys_headertags')->count(), ''],
            ['Blacklist', $db->table('lara_sys_blacklist')->count(), ''],
        ];

        return [
            'title' => 'Other data',
            'headers' => ['What', 'Rows', 'Detail'],
            'rows' => $rows,
        ];
    }

    /**
     * The site's own v8 custom fields on core content types (CoreCustomFields).
     */
    private function coreCustomFields(): void
    {
        foreach ((new CoreCustomFields($this->source))->candidates() as $candidate) {
            $field = $candidate['v8_field'];
            $name = "{$candidate['entity_key']}.{$field->fieldname} ({$field->fieldtype})";

            $this->findings[] = $candidate['live'] > 0
                ? "Core content type field {$name} is the site's own and v10 lacks it; used by {$candidate['live']} live record(s), so import-entities adds it as a custom field."
                : "Core content type field {$name} is the site's own and v10 lacks it; no live record uses it, so it is not added and its values are not imported.";
        }
    }

    /**
     * v8 column shortcodes ([kolom_1van2]…) in the text columns of the content tables
     * (ColumnShortcodes): import-content turns them into rich editor grids.
     */
    private function columnShortcodes(): void
    {
        foreach ($this->source->contentEntities() as $entity) {
            $table = $this->source->contentTable($entity->entity_key, $entity->group_key);

            if ($table === null || ! $this->source->hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter(
                $this->source->columns($table),
                fn (string $column): bool => in_array($column, ['body', 'lead', 'body2', 'body3', 'body4', 'body5'], true)
            ));

            foreach ($columns as $column) {
                $ids = $this->source->connection()->table($table)->where($column, 'like', '%[kolom\_%')->orderBy('id')->pluck('id');

                if ($ids->isNotEmpty()) {
                    $this->findings[] = "{$table}.{$column}: v8 column shortcodes ([kolom_XvanN]) in {$ids->count()} row(s) (ids {$ids->implode(', ')}); import-content turns them into rich editor grids (the client theme needs the .grid-layout CSS).";
                }
            }
        }
    }

    /**
     * Custom fields that are defined twice, have no column in the content table,
     * or depend on a field that does not exist.
     */
    private function checkCustomFields(): void
    {
        $entities = $this->source->contentEntities()->keyBy('id');

        foreach ($this->customFields()->groupBy('entity_id') as $entityId => $fields) {
            $entity = $entities[$entityId] ?? null;

            if ($entity === null) {
                $this->findings[] = "{$fields->count()} custom field(s) belong to entity id {$entityId}, which is not a content type.";

                continue;
            }

            foreach ($fields->countBy('fieldname')->filter(fn (int $n): bool => $n > 1) as $name => $n) {
                $this->findings[] = "Custom field {$entity->entity_key}.{$name} is defined {$n} times.";
            }

            $table = $this->source->contentTable($entity->entity_key, $entity->group_key);
            $columns = $table !== null && $this->source->hasTable($table) ? $this->source->columns($table) : [];

            foreach ($fields->pluck('fieldname')->unique() as $name) {
                if ($columns !== [] && ! in_array($name, $columns, true)) {
                    $this->findings[] = "Custom field {$entity->entity_key}.{$name} has no column in {$table}.";
                }
            }

            $names = $fields->pluck('fieldname')->all();

            foreach ($fields as $field) {
                if (V8Source::isEmpty($field->condition_field)) {
                    continue;
                }

                if (! in_array($field->condition_field, $names, true)) {
                    $this->findings[] = "Custom field {$entity->entity_key}.{$field->fieldname} depends on '{$field->condition_field}', which is not a field of {$entity->entity_key}.";
                }
            }
        }
    }

    /**
     * @return Collection<int, object>
     */
    private function customFields(): Collection
    {
        return $this->source->connection()
            ->table('lara_ent_entity_custom_columns as c')
            ->join('lara_ent_entities as e', 'e.id', '=', 'c.entity_id')
            ->leftJoin('lara_ent_entity_groups as g', 'g.id', '=', 'e.group_id')
            ->orderBy('e.entity_key')
            ->orderBy('c.position')
            ->get(['c.*', 'e.entity_key', 'g.key as group_key'])
            ->reject(fn (object $field): bool => $this->source->isSkipped($field->entity_key))
            ->values();
    }

    /**
     * @return Collection<string|int, int>
     */
    private function countBy(string $table, string $column): Collection
    {
        return $this->source->connection()->table($table)
            ->selectRaw("{$column} as k, count(*) as n")
            ->groupBy($column)
            ->orderBy($column)
            ->pluck('n', 'k')
            ->map(fn ($n): int => (int) $n);
    }

    /**
     * Per model: the media rows whose record is missing from the model's content table.
     *
     * @param  Collection<string, object>  $entitiesByModel
     * @return array<string, int>
     */
    private function countOrphans(string $table, Collection $entitiesByModel): array
    {
        $orphans = [];

        foreach ($this->countBy($table, 'entity_type')->keys() as $type) {
            $entity = $entitiesByModel[$type] ?? null;
            $contentTable = $entity ? $this->source->contentTable($entity->entity_key, $entity->group_key) : null;

            if ($contentTable === null || ! $this->source->hasTable($contentTable)) {
                continue;
            }

            $count = $this->source->connection()->table($table)
                ->where('entity_type', $type)
                ->whereNotIn('entity_id', fn ($query) => $query->select('id')->from($contentTable))
                ->count();

            if ($count > 0) {
                $orphans[$type] = $count;
            }
        }

        return $orphans;
    }
}
