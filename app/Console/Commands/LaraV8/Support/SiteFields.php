<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lara\Admin\Enums\CustomFieldType;
use Lara\Admin\Enums\EntityHook;
use Lara\Common\Models\Entity;

/**
 * Per-site settings for single v8 custom fields (found on gain: interview paragraphs with
 * quotes and images, a docpro share key). The file (config v8-migration.fields, default
 * v8-fields.php in the project root) is site data and returns:
 *
 *     'interview.paragraph1' => ['type' => 'richeditor', 'reason' => '...'],
 *     'interview.par1quotepos' => ['type' => 'select', 'options' => ['left', 'right'], 'reason' => '...'],
 *     'interview.par1img' => ['type' => 'select', 'options' => ['get_record_images'], 'image' => true, 'reason' => '...'],
 *     'docpro.secretkey' => ['skip' => true, 'reason' => '...'],
 *     'page.lead' => ['field' => 'intro', 'type' => 'richeditor', 'hook' => 'after-slug', 'reason' => '...'],
 *
 * `type`/`options` replace the mapped v10 field type (V8Map::FIELD_TYPES) and options; `skip`
 * leaves the field out (not built, values not imported); `image` marks a value that is the id
 * of one of the record's own v8 images, imported as the v10 media id (curator) of that image.
 *
 * `field` is for a v8 content column that is no custom field (gain: the page lead, which v10
 * pages lack and would otherwise import into body2): it becomes a v10 custom field with that
 * name (`type`, `options`, `hook`, `title`), and the column's values are imported into it.
 */
class SiteFields
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $fields = null;

    private static ?string $loadedFrom = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $file = self::file();

        if (self::$fields === null || self::$loadedFrom !== $file) {
            self::$loadedFrom = $file;
            self::$fields = is_file($file) ? (array) require $file : [];
        }

        return self::$fields;
    }

    public static function file(): string
    {
        return (string) (config('v8-migration.fields') ?: base_path('v8-fields.php'));
    }

    /**
     * Forget the loaded file (tests, or after the file changed).
     */
    public static function flush(): void
    {
        self::$fields = null;
        self::$loadedFrom = null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $entityKey, string $fieldName): ?array
    {
        $setting = self::all()["{$entityKey}.{$fieldName}"] ?? null;

        return is_array($setting) ? $setting : null;
    }

    public static function isSkipped(string $entityKey, string $fieldName): bool
    {
        return (bool) (self::get($entityKey, $fieldName)['skip'] ?? false);
    }

    public static function isImage(string $entityKey, string $fieldName): bool
    {
        return (bool) (self::get($entityKey, $fieldName)['image'] ?? false);
    }

    /**
     * The setting of a v8 content column that becomes a custom field (`field`), or null.
     *
     * @return array<string, mixed>|null
     */
    public static function columnField(string $entityKey, string $v8Column): ?array
    {
        $setting = self::get($entityKey, $v8Column);

        return isset($setting['field']) ? $setting : null;
    }

    /**
     * The v10 column of a v8 content column that becomes a custom field, or null.
     */
    public static function columnTarget(string $entityKey, string $v8Column): ?string
    {
        $setting = self::columnField($entityKey, $v8Column);

        return $setting === null ? null : (string) $setting['field'];
    }

    /**
     * The settings for v8 content columns of an entity (v8 column => setting).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function columnFields(string $entityKey): array
    {
        $fields = [];

        foreach (self::all() as $key => $setting) {
            [$entity, $column] = array_pad(explode('.', (string) $key, 2), 2, null);

            if ($entity === $entityKey && $column !== null && is_array($setting) && isset($setting['field'])) {
                $fields[$column] = $setting;
            }
        }

        return $fields;
    }

    /**
     * The v10 custom field attributes (without entity_id) for a v8 content column with a
     * `field` setting, or null with a problem.
     *
     * @param  array<string, mixed>  $setting
     * @return array{0: array<string, mixed>|null, 1: string|null}
     */
    public static function columnFieldAttributes(array $setting, int $sortOrder = 0): array
    {
        $type = CustomFieldType::tryFrom((string) ($setting['type'] ?? 'textarea'));
        $hook = EntityHook::tryFrom((string) ($setting['hook'] ?? EntityHook::Default->value));
        $file = basename(self::file());

        if ($type === null) {
            return [null, "the field type '{$setting['type']}' in {$file} is not a v10 field type"];
        }

        if ($hook === null) {
            return [null, "the hook '{$setting['hook']}' in {$file} is not a v10 field hook"];
        }

        $options = $setting['options'] ?? null;

        if ($type->hasOptions() && (! is_array($options) || $options === [])) {
            return [null, "the field type '{$type->value}' in {$file} needs options"];
        }

        return [[
            'title' => (string) ($setting['title'] ?? Str::headline((string) $setting['field'])),
            'field_name' => (string) $setting['field'],
            'field_type' => $type->value,
            'field_hook' => $hook->value,
            'field_options' => $type->hasOptions() ? array_values(array_map('strval', $options)) : null,
            'is_required' => 0,
            'is_filter' => 0,
            'show_in_list' => 0,
            'conditional' => 0,
            'rule_state' => 'enabled',
            'rule_field' => null,
            'rule_operator' => null,
            'rule_value' => null,
            'sort_order' => $sortOrder,
        ], null];
    }

    /**
     * The field names of an entity whose value is a v8 image id.
     *
     * @return list<string>
     */
    public static function imageFields(string $entityKey): array
    {
        $names = [];

        foreach (self::all() as $key => $setting) {
            [$entity, $field] = array_pad(explode('.', (string) $key, 2), 2, null);

            if ($entity === $entityKey && $field !== null && is_array($setting) && ($setting['image'] ?? false)) {
                $names[] = $field;
            }
        }

        return $names;
    }

    /**
     * The v10 custom field attributes with the site setting applied (type, options), or the
     * attributes unchanged without one. Null with a problem when the setting is invalid.
     *
     * @param  array<string, mixed>  $attributes  V8Map::customField()
     * @return array{0: array<string, mixed>|null, 1: string|null}
     */
    public static function applyTo(string $entityKey, array $attributes): array
    {
        $setting = self::get($entityKey, $attributes['field_name']);

        if ($setting === null || ! isset($setting['type'])) {
            return [$attributes, null];
        }

        $type = CustomFieldType::tryFrom((string) $setting['type']);

        if ($type === null) {
            return [null, "the field type '{$setting['type']}' in ".basename(self::file()).' is not a v10 field type'];
        }

        $options = $setting['options'] ?? null;

        if ($type->hasOptions() && (! is_array($options) || $options === [])) {
            return [null, "the field type '{$type->value}' in ".basename(self::file()).' needs options'];
        }

        $attributes['field_type'] = $type->value;
        $attributes['field_options'] = $type->hasOptions() ? array_values(array_map('strval', $options)) : null;

        return [$attributes, null];
    }

    /**
     * Problems with the settings: keys that are no v8 custom field (or, with `field`, no v8
     * content column) of a content type the site imports. Checked by the inventory.
     *
     * @param  array<string, list<string>>  $v8Fields  entity key => its v8 custom field names
     * @param  array<string, list<string>>  $v8Columns  entity key => its v8 content columns
     * @return list<string>
     */
    public static function problems(array $v8Fields, array $v8Columns = []): array
    {
        $problems = [];

        foreach (self::all() as $key => $setting) {
            [$entity, $field] = array_pad(explode('.', (string) $key, 2), 2, null);

            if ($field !== null && is_array($setting) && isset($setting['field'])) {
                if (! in_array($field, $v8Columns[$entity] ?? [], true)) {
                    $problems[] = basename(self::file())." '{$key}' is not a v8 content column of an imported content type.";
                }

                continue;
            }

            if ($field === null || ! in_array($field, $v8Fields[$entity] ?? [], true)) {
                $problems[] = basename(self::file())." '{$key}' is not a v8 custom field of an imported content type.";
            }
        }

        return $problems;
    }

    /**
     * The v10 media id (curator) of a v8 image id held by a field of a record: the image must be
     * one of the record's own v8 images, and its media must exist in v10 (import-media).
     *
     * @return array{0: int|null, 1: string|null} the media id, or null with the reason
     */
    public static function mediaId(V8Source $source, object $v8Entity, Entity $entity, int $recordId, mixed $value): array
    {
        if (V8Source::isEmpty($value)) {
            return [null, null];
        }

        if (! is_numeric($value)) {
            return [null, "'{$value}' is not a v8 image id"];
        }

        $image = $source->connection()->table('lara_object_images')->where('id', (int) $value)->first();

        if ($image === null || $image->entity_type !== $v8Entity->entity_model_class || (int) $image->entity_id !== $recordId) {
            return [null, "v8 image {$value} is not an image of this record"];
        }

        $disk = $entity->media_disk_images ?: 'public';
        $mediaId = DB::table('curator')->where('disk', $disk)->where('path', $entity->resource_slug.'/'.$image->filename)->value('id');

        return $mediaId === null ? [null, 'pending'] : [(int) $mediaId, null];
    }
}
