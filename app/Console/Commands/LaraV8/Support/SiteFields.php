<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Facades\DB;
use Lara\Admin\Enums\CustomFieldType;
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
 *
 * `type`/`options` replace the mapped v10 field type (V8Map::FIELD_TYPES) and options; `skip`
 * leaves the field out (not built, values not imported); `image` marks a value that is the id
 * of one of the record's own v8 images, imported as the v10 media id (curator) of that image.
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
     * Problems with the settings: keys that are no v8 custom field of a content type the site
     * imports. Checked by the inventory.
     *
     * @param  array<string, list<string>>  $v8Fields  entity key => its v8 custom field names
     * @return list<string>
     */
    public static function problems(array $v8Fields): array
    {
        $problems = [];

        foreach (self::all() as $key => $setting) {
            [$entity, $field] = array_pad(explode('.', (string) $key, 2), 2, null);

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
