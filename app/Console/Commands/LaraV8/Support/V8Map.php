<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Str;
use Lara\Common\Models\Cta;
use Lara\Common\Models\LaraWidget;
use Lara\Common\Models\Page;
use Lara\Common\Models\Slider;

/**
 * Translates Lara CMS v8.2.46 configuration values into their v10 counterparts.
 *
 * Pure: no database access. The v8 values are the complete lists of the v8 core
 * (laracms/core/config/lara-admin.php: fieldTypes, fieldFormTypes, fieldHooks,
 * fieldStates), so the map covers every v8.2.46 site, not only the ones migrated so far.
 *
 * The import commands use this for the configuration; a value without a mapping
 * returns null, and the caller reports it.
 */
final class V8Map
{
    /**
     * v8 field type (content types) => v10 CustomFieldType value.
     */
    public const FIELD_TYPES = [
        'string' => 'string',
        'text' => 'textarea',           // v8 renders a textarea; v10 'text' is a single line
        'mcefull' => 'richeditor',
        'mcemin' => 'richeditormin',
        'email' => 'email',
        'datetime' => 'datetime',
        'date' => 'date',
        'time' => 'time',
        'integer' => 'number',
        'intunsigned' => 'number',
        'decimal101' => 'decimal_10_1',
        'decimal142' => 'decimal_14_2',
        'decimal164' => 'decimal_16_4',
        'decimal108' => 'latitude_10_8',
        'decimal118' => 'longitude_11_8',
        'geolocation' => 'geolocation',
        'boolean' => 'toggle',
        'yesno' => 'toggle',
        'video' => 'string',
        'selectone' => 'select',
        'selectone2one' => 'select',
        'radio' => 'radio',
        'color' => 'colorpicker',
        'custom' => 'textarea',
    ];

    /**
     * v8 form field type => v10 FormFieldType value.
     */
    public const FORM_FIELD_TYPES = [
        'string' => 'string',
        'text' => 'textarea',
        'email' => 'email',
        'integer' => 'number',
        'date' => 'date',
        'yesno' => 'toggle',
        'boolean' => 'toggle',
        'selectone' => 'select',
        'radio' => 'radio',
    ];

    /**
     * v8 field types whose behaviour changes in v10: the import keeps the data and reports these.
     */
    public const FIELD_TYPE_CHANGES = [
        'intunsigned' => 'the column becomes a signed integer',
        'yesno' => 'the yes/no choice becomes a toggle',
        'selectone2one' => 'v10 does not enforce that each option is used by one record only',
        'video' => 'the video field becomes a plain text field (the stored value is kept)',
        'custom' => 'the site-specific v8 field becomes a textarea; check how the site uses it',
    ];

    /**
     * v8 field hook => v10 EntityHook value. v8 renders: before, title, slug, relations,
     * between, lead, body, after; forms only use default.
     */
    public const HOOKS = [
        'before' => 'before-title',
        'between' => 'after-slug',
        'after' => 'after-last',
        'default' => 'default',
    ];

    /**
     * v8 field state => v10 rule_state (the same four values).
     */
    public const RULE_STATES = [
        'enabled' => 'enabled',
        'enabledif' => 'enabledif',
        'hidden' => 'hidden',
        'disabled' => 'disabled',
    ];

    /**
     * v8 condition operator => v10 rule_operator.
     */
    public const RULE_OPERATORS = [
        'isequal' => 'isequal',
        'isnotequal' => 'isnotequal',
    ];

    /**
     * v8 media disk => v10 media disk.
     */
    public const DISKS = [
        'localdisk' => 'public',
    ];

    /**
     * v8 entity group => v10 cgroup, for the content groups.
     */
    public const CGROUPS = [
        'page' => 'page',
        'block' => 'block',
        'form' => 'form',
        'entity' => 'entity',
    ];

    /**
     * v8 menu_parent => v10 NavGroup value.
     */
    public const NAV_GROUPS = [
        'root' => 'root',
        'menu' => 'menu',
        'modules' => 'modules',
        'blocks' => 'blocks',
        'forms' => 'forms',
        'tools' => 'tools',
        'seo' => 'seo',
        'users' => 'users',
        'builder' => 'builder',
    ];

    /**
     * v8 content types that are part of the v10 core. They keep their v10 configuration
     * (fields, views, code); only their data is imported, with these column renames
     * (v8 column => v10 column, null = not imported).
     */
    public const CORE_ENTITIES = [
        'page' => [
            'resource_slug' => 'pages',
            'model_class' => Page::class,
            'table' => 'lara_content_pages',
            'columns' => ['show_in_app' => null, 'lead' => 'body2'],
        ],
        'larawidget' => [
            'resource_slug' => 'widgets',
            'model_class' => LaraWidget::class,
            'table' => 'lara_blocks_widgets',
            'columns' => [
                'relentkey' => 'resource_slug',
                'term' => 'resource_term',
                'imgreq' => 'image_required',
                'maxitems' => 'max_items',
                'isglobal' => 'is_global',
                'customtags' => null,
            ],
        ],
        'cta' => [
            'resource_slug' => 'ctas',
            'model_class' => Cta::class,
            'table' => 'lara_blocks_ctas',
            'columns' => [],
        ],
        'slider' => [
            'resource_slug' => 'sliders',
            'model_class' => Slider::class,
            'table' => 'lara_blocks_sliders',
            'columns' => [],
        ],
    ];

    /**
     * v8 values of core content type fields that v10 names differently
     * (v8 entity key => v8 column => v8 value => v10 value). v8 themes are Bootstrap
     * ("primary"); the v10 themes call the main colour "brand" (sliders: brand1–3).
     */
    public const CORE_VALUES = [
        'cta' => ['overlaycolor' => ['primary' => 'brand']],
        'slider' => ['overlaycolor' => ['primary' => 'brand1']],
    ];

    /**
     * The v10 value of a v8 core field value; unchanged when v10 does not rename it.
     */
    public static function coreValue(string $entityKey, string $v8Column, mixed $value): mixed
    {
        // a v8 widget names its content type by entity key ("blog"), v10 by resource slug ("blogs")
        if ($entityKey === 'larawidget' && $v8Column === 'relentkey' && ! V8Source::isEmpty($value)) {
            return self::resourceSlug((string) $value);
        }

        return self::CORE_VALUES[$entityKey][$v8Column][$value] ?? $value;
    }

    /**
     * A module page (the intro/SEO page of an index or form) is found by its slug:
     * v8 "{entity key}-{method}-module-{language}", v10 "{resource slug}-{method}-module-{language}"
     * (FrontObjectRepository::getModulePageBySlug()). Other slugs are unchanged.
     *
     * @param  list<string>  $contentKeys  the v8 content entity keys
     */
    public static function modulePageSlug(string $slug, array $contentKeys): string
    {
        if (preg_match('/^([a-z][a-z0-9]*)-([a-z]+)-module-([a-z]{2,5})$/', $slug, $m) !== 1 || ! in_array($m[1], $contentKeys, true)) {
            return $slug;
        }

        return self::resourceSlug($m[1])."-{$m[2]}-module-{$m[3]}";
    }

    /**
     * The v10 column of a v8 content column: the core renames (V8Map::column(), e.g. page
     * lead → body2), and for a content type with a geolocation field its v8 parts (address,
     * latitude, …) → geo_* and the geolocation mode (auto/manual) → geo_location.
     * Null when the column is not imported.
     */
    public static function contentColumn(string $entityKey, string $v8Column, bool $hasGeolocation): ?string
    {
        if ($hasGeolocation && isset(self::GEO_FIELDS[$v8Column])) {
            return self::GEO_FIELDS[$v8Column];
        }

        if ($hasGeolocation && $v8Column === 'geolocation') {
            return 'geo_location';
        }

        return self::column($entityKey, $v8Column);
    }

    /**
     * When a v8 content type has a geolocation field, these v8 fields are parts of it:
     * v10 keeps them in the geo_* columns of its geolocation field (v8 field => v10 column).
     */
    public const GEO_FIELDS = [
        'address' => 'geo_address',
        'pcode' => 'geo_pcode',
        'city' => 'geo_city',
        'country' => 'geo_country',
        'latitude' => 'geo_latitude',
        'longitude' => 'geo_longitude',
    ];

    /**
     * v8 ability => v10 permission actions; the permission is "{action}_{entity_key}".
     */
    public const PERMISSION_ACTIONS = [
        'view' => ['view', 'view_any'],
        'create' => ['create'],
        'update' => ['update'],
        'delete' => ['delete', 'delete_any'],
    ];

    public static function fieldType(?string $v8Type, bool $isForm = false): ?string
    {
        return ($isForm ? self::FORM_FIELD_TYPES : self::FIELD_TYPES)[$v8Type] ?? null;
    }

    /**
     * What changes in v10 for this v8 field type, or null when nothing does.
     */
    public static function fieldTypeChange(?string $v8Type): ?string
    {
        return self::FIELD_TYPE_CHANGES[$v8Type] ?? null;
    }

    /**
     * The v10 hook; an empty v8 hook is 'default'.
     */
    public static function hook(?string $v8Hook): ?string
    {
        return V8Source::isEmpty($v8Hook) ? 'default' : (self::HOOKS[$v8Hook] ?? null);
    }

    /**
     * The v10 rule state; an empty v8 state is 'enabled'.
     */
    public static function ruleState(?string $v8State): ?string
    {
        return V8Source::isEmpty($v8State) ? 'enabled' : (self::RULE_STATES[$v8State] ?? null);
    }

    /**
     * v8 options are stored as a JSON object {"0":"one","1":"two"}; v10 keeps a list ["one","two"].
     *
     * @return list<string>|null null when the field has no options
     */
    public static function options(?string $fielddata): ?array
    {
        if (V8Source::isEmpty($fielddata)) {
            return null;
        }

        $decoded = json_decode($fielddata, true);

        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        return array_values(array_map(fn ($option): string => (string) $option, $decoded));
    }

    /**
     * True when a v8 rule value holds several values ("feature || featuresmall"),
     * which v10 compares as one literal value.
     */
    public static function hasMultipleRuleValues(?string $v8Value): bool
    {
        return $v8Value !== null && str_contains($v8Value, '||');
    }

    /**
     * The v10 lara_resource_entity_custom_fields attributes of a v8 custom column
     * (lara_ent_entity_custom_columns row), without entity_id. Null values for
     * field_type, field_hook or rule_state mean the v8 value has no mapping.
     *
     * @return array<string, mixed>
     */
    public static function customField(object $v8Field, bool $isForm = false): array
    {
        $ruleState = self::ruleState($v8Field->fieldstate ?? null);
        $isConditional = $ruleState === 'enabledif' && ! V8Source::isEmpty($v8Field->condition_field ?? null);

        return [
            'title' => $v8Field->fieldtitle ?: Str::headline($v8Field->fieldname),
            'field_name' => $v8Field->fieldname,
            'field_type' => self::fieldType($v8Field->fieldtype, $isForm),
            'field_hook' => $isForm ? 'default' : self::hook($v8Field->fieldhook ?? null),
            'field_options' => self::options($v8Field->fielddata ?? null),
            'is_required' => (int) (($v8Field->required ?? 0) == 1),
            'is_filter' => 0,
            'show_in_list' => (int) (($v8Field->primary ?? 0) == 1),
            'conditional' => (int) ($ruleState !== 'enabled'),
            'rule_state' => $ruleState,
            'rule_field' => $isConditional ? $v8Field->condition_field : null,
            'rule_operator' => $isConditional ? (self::RULE_OPERATORS[$v8Field->condition_operator] ?? null) : null,
            'rule_value' => $isConditional ? $v8Field->condition_value : null,
            'sort_order' => $v8Field->position ?? null,
        ];
    }

    public static function isCoreEntity(string $entityKey): bool
    {
        return isset(self::CORE_ENTITIES[$entityKey]);
    }

    public static function resourceSlug(string $entityKey): string
    {
        return self::CORE_ENTITIES[$entityKey]['resource_slug'] ?? Str::plural($entityKey);
    }

    /**
     * The v10 model: a core model, or the generated site model Lara\App\Models\{Key}.
     */
    public static function modelClass(string $entityKey): string
    {
        return self::CORE_ENTITIES[$entityKey]['model_class'] ?? 'Lara\\App\\Models\\'.Str::studly($entityKey);
    }

    /**
     * The v10 column of a v8 content column; null when the column is not imported.
     */
    public static function column(string $entityKey, string $v8Column): ?string
    {
        $renames = self::CORE_ENTITIES[$entityKey]['columns'] ?? [];

        return array_key_exists($v8Column, $renames) ? $renames[$v8Column] : $v8Column;
    }

    /**
     * The v10 lara_resource_entities attributes of a v8 content type, from its rows in
     * lara_ent_entities (with group_key), lara_ent_entity_columns, lara_ent_entity_panels
     * and lara_ent_entity_object_relations. Only attributes with a v8 source are returned;
     * the others keep their v10 value or default.
     *
     * @return array<string, mixed>
     */
    public static function entity(object $entity, ?object $columns, ?object $panels, ?object $objectRelations): array
    {
        $attributes = [
            'title' => $entity->title,
            'resource_slug' => self::resourceSlug($entity->entity_key),
            'label_single' => $entity->entity_key,
            'model_class' => self::modelClass($entity->entity_key),
            'controller' => $entity->entity_controller,
            'nav_group' => self::NAV_GROUPS[$entity->menu_parent ?? ''] ?? null,
            'has_front_auth' => (int) ($entity->has_front_auth ?? 0),
            'cgroup' => self::CGROUPS[$entity->group_key ?? ''] ?? null,
            'position' => $entity->menu_position ?? null,
        ];

        // v8 always offered these in the admin (a show page, edit, delete and a trash bin);
        // in v10 they are settings that default to off. A v10 form only lists, views and deletes.
        $isForm = ($entity->group_key ?? null) === 'form';
        $attributes += [
            'show_view_action' => 1,
            'show_edit_action' => (int) ! $isForm,
            'show_delete_action' => 1,
            'show_restore_action' => (int) ! $isForm,
            'filter_by_trashed' => (int) ! $isForm,
        ];

        if ($columns !== null) {
            $hasFilters = (bool) ($panels->has_filters ?? false);

            $attributes += [
                'col_has_lead' => (int) $columns->has_lead,
                'col_has_body' => (int) $columns->has_body,
                'col_has_status' => (int) $columns->has_status,
                'col_has_expiration' => (int) $columns->has_expiration,
                'col_has_hideinlist' => (int) $columns->has_hideinlist,
                'sort_is_sortable' => (int) $columns->is_sortable,
                'sort_primary_field' => V8Source::isEmpty($columns->sort_field) ? null : $columns->sort_field,
                'sort_primary_order' => V8Source::isEmpty($columns->sort_order) ? null : $columns->sort_order,
                'sort_secondary_field' => V8Source::isEmpty($columns->sort2_field) ? null : $columns->sort2_field,
                'sort_secondary_order' => V8Source::isEmpty($columns->sort2_order) ? null : $columns->sort2_order,
                'objrel_has_groups' => (int) $columns->has_groups,
                'objrel_group_values' => self::groupValues($columns->group_values),
                'filter_by_group' => (int) ($hasFilters && $columns->has_groups),
                'filter_by_status' => (int) ($hasFilters && $columns->has_status),
            ];
        }

        if ($panels !== null) {
            $attributes += [
                'show_search' => (int) $panels->has_search,
                'show_batch' => (int) $panels->has_batch,
                'show_author' => (int) $panels->show_author,
                'show_status' => (int) $panels->show_status,
                'show_rich_lead' => (int) $panels->has_tiny_lead,
                'show_rich_body' => (int) $panels->has_tiny_body,
            ];
        }

        if ($objectRelations !== null) {
            $o = $objectRelations;
            $hasFilters = (bool) ($panels->has_filters ?? false);

            $attributes += [
                'show_seo' => (int) $o->has_seo,
                'show_opengraph' => (int) $o->has_opengraph,
                'show_sync' => (int) $o->has_sync,
                'objrel_has_terms' => (int) $o->has_tags,
                'objrel_has_related' => (int) $o->has_related,
                'objrel_is_relatable' => (int) $o->is_relatable,
                'filter_by_category' => (int) ($hasFilters && $o->has_tags),
                'filter_by_tag' => (int) ($hasFilters && $o->has_tags),
                'media_has_featured' => (int) $o->has_images,
                'media_has_gallery' => (int) ($o->has_images && $o->max_images > 1),
                'media_max_gallery' => $o->has_images && $o->max_images > 1 ? (int) $o->max_images : 0,
                'media_has_videos' => (int) $o->has_videos,
                'media_max_videos' => $o->has_videos ? (int) $o->max_videos : 0,
                'media_has_videofiles' => (int) $o->has_videofiles,
                'media_max_videofiles' => $o->has_videofiles ? (int) $o->max_videofiles : 0,
                'media_has_files' => (int) $o->has_files,
                'media_max_files' => $o->has_files ? (int) $o->max_files : 0,
                'media_disk_images' => self::DISKS[$o->disk_images] ?? null,
                'media_disk_videos' => self::DISKS[$o->disk_videos] ?? null,
                'media_disk_files' => self::DISKS[$o->disk_files] ?? null,
            ];
        }

        return $attributes;
    }

    /**
     * v8 group values are a JSON list or a comma separated string; v10 keeps a list.
     *
     * @return list<string>|null
     */
    public static function groupValues(?string $v8Values): ?array
    {
        if (V8Source::isEmpty($v8Values)) {
            return null;
        }

        $decoded = json_decode($v8Values, true);
        $values = is_array($decoded) ? $decoded : explode(',', $v8Values);
        $values = array_values(array_filter(array_map(fn ($value): string => trim((string) $value), $values), fn (string $value): bool => $value !== ''));

        return $values === [] ? null : $values;
    }

    /**
     * The v8 fields of a content type that v10 keeps inside its geolocation field:
     * all GEO_FIELDS, but only when the content type has a geolocation field.
     *
     * @param  iterable<object>  $v8Fields  lara_ent_entity_custom_columns rows of one content type
     * @return list<string>
     */
    public static function geolocationParts(iterable $v8Fields): array
    {
        $fields = collect($v8Fields);

        if (! $fields->contains(fn (object $field): bool => $field->fieldtype === 'geolocation')) {
            return [];
        }

        return $fields->pluck('fieldname')->intersect(array_keys(self::GEO_FIELDS))->unique()->values()->all();
    }

    /**
     * The v10 lara_resource_entity_views attributes of a v8 view (lara_ent_entity_views row),
     * without entity_id. v8 index views keep their list type; show and form views are single.
     *
     * @return array<string, mixed>
     */
    public static function view(object $v8View, string $entityTitle): array
    {
        $isIndex = $v8View->method === 'index';

        return [
            'title' => $v8View->title ?: $entityTitle.' '.ucfirst($v8View->method),
            'method' => $v8View->method,
            'filename' => $v8View->filename,
            'template' => null,
            'template_extra_fields' => 0,
            'is_single' => (int) ! $isIndex,
            'list_type' => $isIndex ? $v8View->type : ($v8View->method === 'form' ? null : '_single'),
            'image_required' => 0,
            'showtags' => V8Source::isEmpty($v8View->showtags) || $v8View->showtags === 'none' ? null : $v8View->showtags,
            'paginate' => $v8View->paginate ? (int) $v8View->paginate : null,
            'infinite' => (int) $v8View->infinite,
            'prevnext' => (int) $v8View->prevnext,
            'publish' => (int) $v8View->publish,
        ];
    }

    /**
     * The v10 permissions of a v8 ability ("view" on "blog" => view_blog, view_any_blog).
     *
     * @return list<string>
     */
    public static function permissions(string $v8Ability, string $entityKey): array
    {
        return array_map(
            fn (string $action): string => "{$action}_{$entityKey}",
            self::PERMISSION_ACTIONS[$v8Ability] ?? [],
        );
    }
}
