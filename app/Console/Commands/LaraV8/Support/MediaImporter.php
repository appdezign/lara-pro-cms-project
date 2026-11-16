<?php

namespace App\Console\Commands\LaraV8\Support;

use App\Console\Commands\LaraV8\Support\Concerns\SyncsRows;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Lara\Common\Models\Entity;
use Throwable;

/**
 * Imports the v8 media: images (→ Curator items + lara_object_images links), files, videos
 * and video files (→ one JSON list per record), and copies the files.
 *
 * - Files keep their v8 name (user decision 2026-11-16); source: the v8 media path
 *   ({entity key}/{file}, or its _archive, V8Source::mediaFile()); target: the entity's v10
 *   disk, images in {resource slug}/, files in {resource slug}/file/, video files in
 *   {resource slug}/video/ (the folders the v10 admin uploads to).
 * - Image types: featured, hero, icon from the v8 flags; gallery for an image without a flag,
 *   and for a flagged image that v8 did not hide from the gallery. Order from the v8 position.
 * - Rerunnable: a file is copied when it is missing or its size differs; only rows that differ
 *   are written. Curator items are never removed; $fresh removes the links v8 does not have.
 */
class MediaImporter
{
    use SyncsRows;

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    /** @var array<string, object> v8 model class => v8 entity */
    private array $entities = [];

    /** @var array<string, ?Entity> v8 entity key => v10 entity */
    private array $targets = [];

    private int $copied = 0;

    private int $missing = 0;

    public function __construct(private readonly V8Source $source) {}

    /**
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(bool $dryRun = false, bool $fresh = false): array
    {
        $this->report = [];
        $this->copied = 0;
        $this->missing = 0;
        $this->entities = $this->source->contentEntities()->keyBy('entity_model_class')->all();

        $this->images($dryRun, $fresh);
        $this->files($dryRun, $fresh);
        $this->videos($dryRun, $fresh);
        $this->videofiles($dryRun, $fresh);

        $this->add($this->copied > 0 ? 'create' : 'same', 'files', ($dryRun ? 'Would copy ' : 'Copied ')."{$this->copied} file(s) to the v10 disks.");

        if ($this->missing > 0) {
            $this->add('warning', 'files', "{$this->missing} v8 media row(s) point to a file that is missing in the v8 media path (also not in _archive); skipped.");
        }

        return $this->report;
    }

    private function images(bool $dryRun, bool $fresh): void
    {
        $v8Images = $this->source->connection()->table('lara_object_images')->orderBy('position')->orderBy('id')->get();
        $curator = collect();
        $links = [];
        $unsupported = 0;

        foreach ($v8Images as $image) {
            [$entity, $target, $recordId] = $this->owner($image) ?? [null, null, null];

            if ($target === null) {
                continue;
            }

            if ((int) $image->herosize > 0 || (int) $image->prevent_cropping === 1) {
                $unsupported++;
            }

            $disk = $target->media_disk_images ?: 'public';
            $path = $target->resource_slug.'/'.$image->filename;

            if (! $this->copy($entity->entity_key, $image->filename, $disk, $path, $dryRun)) {
                continue;
            }

            $curator->push($this->curatorRow($image, $disk, $target->resource_slug, $path));

            foreach ($this->imageTypes($image) as $type) {
                $links[$target->model_class.'#'.$recordId.'#'.$type][] = ['path' => $disk.'#'.$path, 'v8' => $image];
            }
        }

        if ($unsupported > 0) {
            $this->add('warning', 'images', "{$unsupported} v8 image(s) use herosize or prevent_cropping, which v10 does not have.");
        }

        $this->sync('curator', 'curator', ['disk', 'path'], $curator, $dryRun, false, reportExtra: false);

        if ($dryRun) {
            $this->add('info', 'images', array_sum(array_map('count', $links)).' image link(s) would be written (lara_object_images).');

            return;
        }

        $mediaIds = DB::table('curator')->get(['id', 'disk', 'path'])->mapWithKeys(fn (object $row): array => [$row->disk.'#'.$row->path => (int) $row->id]);
        $rows = collect();

        foreach ($links as $key => $images) {
            [$modelClass, $recordId, $type] = explode('#', $key);

            foreach (array_values($images) as $index => $image) {
                $rows->push([
                    'mediable_type' => $modelClass,
                    'mediable_id' => (int) $recordId,
                    'media_id' => $mediaIds[$image['path']],
                    'order' => $index + 1,
                    'type' => $type,
                    'created_at' => $image['v8']->created_at,
                    'updated_at' => $image['v8']->updated_at,
                ]);
            }
        }

        $this->sync('image links', 'lara_object_images', ['mediable_type', 'mediable_id', 'media_id', 'type'], $rows, false, $fresh);
    }

    /**
     * @return list<string>
     */
    public static function imageTypes(object $image): array
    {
        $types = array_keys(array_filter([
            'featured' => (int) $image->featured === 1,
            'hero' => (int) $image->ishero === 1,
            'icon' => (int) $image->isicon === 1,
        ]));

        if ($types === [] || (int) $image->hide_in_gallery === 0) {
            $types[] = 'gallery';
        }

        return $types;
    }

    /**
     * @return array<string, mixed>
     */
    private function curatorRow(object $image, string $disk, string $directory, string $path): array
    {
        $ext = strtolower(pathinfo($image->filename, PATHINFO_EXTENSION));
        $source = $this->source->mediaFile($this->entities[$image->entity_type]->entity_key, $image->filename);
        $dimensions = $source ? @getimagesize($source) : false;

        return [
            'disk' => $disk,
            'directory' => $directory,
            'visibility' => 'public',
            'name' => pathinfo($image->filename, PATHINFO_FILENAME),
            'path' => $path,
            'width' => $dimensions ? $dimensions[0] : null,
            'height' => $dimensions ? $dimensions[1] : null,
            'size' => $source ? filesize($source) : null,
            'type' => V8Source::isEmpty($image->mimetype) ? ($source ? (mime_content_type($source) ?: 'application/octet-stream') : 'application/octet-stream') : $image->mimetype,
            'ext' => $ext,
            'alt' => V8Source::isEmpty($image->image_alt) ? null : $image->image_alt,
            'title' => V8Source::isEmpty($image->image_title) ? null : $image->image_title,
            'caption' => V8Source::isEmpty($image->caption) ? null : $image->caption,
            'in_use' => 1,
            'created_at' => $image->created_at,
            'updated_at' => $image->updated_at,
        ];
    }

    private function files(bool $dryRun, bool $fresh): void
    {
        $this->jsonLists('files', 'lara_object_files', 'entity_files', 'media_disk_files', $dryRun, $fresh, function (object $file, Entity $target, string $entityKey, string $disk) use ($dryRun): ?array {
            $path = $target->resource_slug.'/file/'.$file->filename;

            if (! $this->copy($entityKey, $file->filename, $disk, $path, $dryRun)) {
                return null;
            }

            return [
                'doc_date' => $this->minutes($file->docdate ?: $file->created_at),
                'doc_filename' => $path,
                'doc_original' => V8Source::isEmpty($file->title) ? $file->filename : $file->title,
            ];
        });
    }

    private function videos(bool $dryRun, bool $fresh): void
    {
        $this->jsonLists('videos', 'lara_object_videos', 'entity_videos', null, $dryRun, $fresh, fn (object $video): ?array => V8Source::isEmpty($video->youtubecode) ? null : [
            'video_title' => $video->title,
            'youtubecode' => $video->youtubecode,
        ]);
    }

    private function videofiles(bool $dryRun, bool $fresh): void
    {
        $cloud = $this->source->connection()->table('lara_object_videofiles')->whereNotNull('cfs_uid')->where('cfs_uid', '!=', '')->count();

        if ($cloud > 0) {
            $this->add('warning', 'video files', "{$cloud} v8 video file(s) are stored in a cloud file service (cfs_uid); only local files are imported.");
        }

        $this->jsonLists('video files', 'lara_object_videofiles', 'entity_videofiles', 'media_disk_videos', $dryRun, $fresh, function (object $video, Entity $target, string $entityKey, string $disk) use ($dryRun): ?array {
            if (V8Source::isEmpty($video->filename)) {
                return null;
            }

            $path = $target->resource_slug.'/video/'.$video->filename;

            if (! $this->copy($entityKey, $video->filename, $disk, $path, $dryRun)) {
                return null;
            }

            return [
                'vidfile_filename' => $path,
                'vidfile_original' => V8Source::isEmpty($video->title) ? $video->filename : $video->title,
                'vidfile_date' => $this->minutes($video->created_at),
            ];
        });
    }

    /**
     * v8 keeps one row per file/video; v10 one row per record with a JSON list.
     *
     * @param  callable(object, Entity, string, string): ?array<string, mixed>  $item
     */
    private function jsonLists(string $label, string $table, string $column, ?string $diskSetting, bool $dryRun, bool $fresh, callable $item): void
    {
        $lists = [];

        foreach ($this->source->connection()->table($table)->orderBy('id')->get() as $row) {
            [$entity, $target, $recordId] = $this->owner($row) ?? [null, null, null];

            if ($target === null) {
                continue;
            }

            $disk = $diskSetting ? ($target->{$diskSetting} ?: 'public') : 'public';
            $value = $item($row, $target, $entity->entity_key, $disk);

            if ($value !== null) {
                $lists[$target->model_class.'#'.$recordId][] = $value;
            }
        }

        $rows = collect($lists)->map(function (array $items, string $key) use ($column): array {
            [$modelClass, $recordId] = explode('#', $key);

            return ['entity_type' => $modelClass, 'entity_id' => (int) $recordId, $column => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
        })->values();

        $this->sync($label, $table, ['entity_type', 'entity_id'], $rows, $dryRun, $fresh);
    }

    /**
     * The v8 entity, the v10 entity and the record id of a v8 media row, or null when the
     * content type or the record does not exist in v10 (reported once per content type).
     *
     * @return array{0: object, 1: Entity, 2: int}|null
     */
    private function owner(object $row): ?array
    {
        $entity = $this->entities[$row->entity_type] ?? null;

        if ($entity === null) {
            $this->addOnce('warning', 'media', "Media of '{$row->entity_type}', which is not a v8 content type; skipped.");

            return null;
        }

        $target = $this->targets[$entity->entity_key] ??= Entity::where('resource_slug', V8Map::resourceSlug($entity->entity_key))->first();

        if ($target === null) {
            $this->addOnce('warning', 'media', "Media of '{$entity->entity_key}', which has no v10 entity; skipped.");

            return null;
        }

        try {
            $exists = DB::table($target->model_class::getTableName())->where('id', $row->entity_id)->exists();
        } catch (Throwable) {
            $exists = false;
        }

        if (! $exists) {
            $this->addOnce('warning', 'media', "Media of {$entity->entity_key} records that do not exist in v10; skipped.");

            return null;
        }

        return [$entity, $target, (int) $row->entity_id];
    }

    /**
     * Copy a v8 media file to the v10 disk when it is missing there or its size differs.
     */
    private function copy(string $entityKey, string $filename, string $disk, string $path, bool $dryRun): bool
    {
        $source = $this->source->mediaFile($entityKey, $filename);

        if ($source === null) {
            $this->missing++;

            return false;
        }

        $storage = Storage::disk($disk);

        if ($storage->exists($path) && $storage->size($path) === filesize($source)) {
            return true;
        }

        $this->copied++;

        if (! $dryRun) {
            $stream = fopen($source, 'rb');
            $storage->writeStream($path, $stream, ['visibility' => 'public']);
            is_resource($stream) && fclose($stream);
        }

        return true;
    }

    private function minutes(?string $date): ?string
    {
        // the v10 date-time field stores 'Y-m-d H:i'; v8 dates can be a date or a timestamp
        return V8Source::isEmpty($date) ? null : Carbon::parse($date)->format('Y-m-d H:i');
    }

    private function addOnce(string $level, string $entity, string $message): void
    {
        foreach ($this->report as $line) {
            if ($line['message'] === $message) {
                return;
            }
        }

        $this->add($level, $entity, $message);
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}
