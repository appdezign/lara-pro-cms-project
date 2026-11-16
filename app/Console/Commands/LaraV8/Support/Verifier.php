<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Lara\Common\Models\Entity;
use Lara\Common\Models\Tag;
use Throwable;

/**
 * Checks a finished migration; changes nothing.
 *
 * 1. Counts: rows and published rows per content type and language, v8 against v10.
 * 2. In sync: every import step in dry-run mode; anything it would create, update or
 *    remove means v10 differs from v8 (e.g. edited in v10 after the import, or v8 changed).
 * 3. Media: every Curator file, image link and file / video-file entry exists on its disk.
 * 4. Links: the v8 media URLs left in the HTML and the internal links that do not reach a
 *    page (UrlRewriter, dry run).
 * 5. Tags: no v8 root tag in v10, and every tag tree is a valid nested set.
 *
 * Levels: ok, warning, error. Errors are missing content or files; differences that an
 * import would resolve are warnings.
 */
class Verifier
{
    /** Import report levels that mean "v10 is not what an import of v8 gives". */
    private const OUT_OF_SYNC = ['create', 'update', 'differs', 'error'];

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    public function __construct(private readonly V8Source $source) {}

    /**
     * @param  list<string>  $checks  counts, sync, media, links, tags; empty for all
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(array $checks = []): array
    {
        $this->report = [];
        $all = $checks === [];

        if ($all || in_array('counts', $checks, true)) {
            $this->counts();
        }
        if ($all || in_array('sync', $checks, true)) {
            $this->sync();
        }
        if ($all || in_array('media', $checks, true)) {
            $this->media();
        }
        if ($all || in_array('links', $checks, true)) {
            $this->links();
        }
        if ($all || in_array('tags', $checks, true)) {
            $this->tags();
        }

        return $this->report;
    }

    /**
     * Rows and published rows per content type and language.
     */
    private function counts(): void
    {
        foreach ($this->source->contentEntities() as $v8) {
            $key = $v8->entity_key;
            $v8Table = $this->source->contentTable($key, $v8->group_key);
            $entity = Entity::where('resource_slug', V8Map::resourceSlug($key))->first();

            if ($v8Table === null || ! $this->source->hasTable($v8Table) || $entity === null) {
                $this->add('error', $key, 'No v8 table or no v10 entity '.V8Map::resourceSlug($key).'.');

                continue;
            }

            $v10Table = $entity->model_class::getTableName();
            // published = what the front shows: without a status every non-trashed row
            // (v10 ignores the publish column then; v8 had none)
            $hasStatus = (bool) $entity->col_has_status;
            $v8Counts = $this->languageCounts($this->source->connection(), $v8Table, $this->source->columns($v8Table), $hasStatus);
            $v10Counts = $this->languageCounts(DB::connection(), $v10Table, DB::getSchemaBuilder()->getColumnListing($v10Table), $hasStatus);

            foreach (array_unique([...array_keys($v8Counts), ...array_keys($v10Counts)]) as $language) {
                $old = $v8Counts[$language] ?? ['rows' => 0, 'published' => 0];
                $new = $v10Counts[$language] ?? ['rows' => 0, 'published' => 0];
                $label = "{$v10Table} [{$language}]: {$new['rows']} row(s), {$new['published']} published";

                if ($old === $new) {
                    $this->add('ok', $key, "{$label}, as v8.");
                } else {
                    $this->add($new['rows'] < $old['rows'] ? 'error' : 'warning', $key, "{$label}; v8 has {$old['rows']} row(s), {$old['published']} published.");
                }
            }
        }
    }

    /**
     * @param  list<string>  $columns
     * @return array<string, array{rows: int, published: int}>
     */
    private function languageCounts($connection, string $table, array $columns, bool $hasStatus): array
    {
        $language = in_array('language', $columns, true) ? 'language' : "'-'";
        $conditions = array_filter([
            $hasStatus && in_array('publish', $columns, true) ? 'publish = 1' : null,
            in_array('deleted_at', $columns, true) ? 'deleted_at is null' : null,
        ]);
        $published = $conditions === [] ? 'count(*)' : 'sum(case when '.implode(' and ', $conditions).' then 1 else 0 end)';

        return $connection->table($table)
            ->select([DB::raw("{$language} as lang"), DB::raw('count(*) as rows_total'), DB::raw("{$published} as published")])
            ->groupBy('lang')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->lang => ['rows' => (int) $row->rows_total, 'published' => (int) $row->published]])
            ->all();
    }

    /**
     * Every import step in dry-run mode: what it would still change.
     */
    private function sync(): void
    {
        $steps = [
            'entities' => fn (): array => (new EntityImporter($this->source))->run([], true),
            'users' => fn (): array => (new UserImporter($this->source))->run(true),
            'content' => fn (): array => (new ContentImporter($this->source))->run([], true),
            'objects' => fn (): array => (new ObjectImporter($this->source))->run(true),
            'menus' => fn (): array => (new MenuImporter($this->source))->run(true),
            'settings' => fn (): array => (new SystemImporter($this->source))->run(true),
            'media' => fn (): array => (new MediaImporter($this->source))->run(true),
        ];

        foreach ($steps as $step => $import) {
            try {
                $lines = array_values(array_filter($import(), fn (array $line): bool => in_array($line['level'], self::OUT_OF_SYNC, true)));
            } catch (Throwable $e) {
                $this->add('error', "sync: {$step}", 'The dry run stopped: '.$e->getMessage());

                continue;
            }

            if ($lines === []) {
                $this->add('ok', "sync: {$step}", 'In sync with v8.');
            }

            foreach ($lines as $line) {
                $this->add($line['level'] === 'error' ? 'error' : 'warning', "sync: {$step}", "{$line['entity']}: {$line['message']}");
            }
        }

        $this->overrides();
    }

    /**
     * Every site override (v8-overrides.php) matches a v10 row that has its values.
     */
    private function overrides(): void
    {
        $overrides = SiteOverrides::all();

        if ($overrides === []) {
            $this->add('ok', 'overrides', 'No site overrides ('.SiteOverrides::file().').');

            return;
        }

        $applied = 0;

        foreach ($overrides as $override) {
            $label = "{$override['table']} ".json_encode($override['where'] ?? []);

            try {
                $row = DB::table($override['table'])->where($override['where'] ?? [])->first();
            } catch (Throwable $e) {
                $this->add('warning', 'overrides', "{$label}: cannot be checked: ".$e->getMessage());

                continue;
            }

            if ($row === null || ($override['where'] ?? []) === []) {
                $this->add('warning', 'overrides', "{$label}: no such row in v10; remove the override or fix its `where`.");

                continue;
            }

            $differs = array_filter($override['set'] ?? [], fn ($value, string $column): bool => (string) ($row->{$column} ?? '') !== (string) $value, ARRAY_FILTER_USE_BOTH);

            if ($differs !== []) {
                $this->add('warning', 'overrides', "{$label}: v10 has ".json_encode(array_intersect_key((array) $row, $differs)).', the override sets '.json_encode($differs).'; run the import of that table.');

                continue;
            }

            // replace: the old text is gone from the column and the new text is there
            $notReplaced = [];

            foreach ($override['replace'] ?? [] as $column => $pairs) {
                foreach ((array) $pairs as $old => $new) {
                    $value = (string) ($row->{$column} ?? '');

                    if (($old !== '' && str_contains($value, (string) $old)) || ((string) $new !== '' && ! str_contains($value, (string) $new))) {
                        $notReplaced[] = "{$column}: '{$old}' → '{$new}'";
                    }
                }
            }

            if ($notReplaced !== []) {
                $this->add('warning', 'overrides', "{$label}: not replaced yet (".implode('; ', $notReplaced).'); run the import of that table.');

                continue;
            }

            $applied++;
        }

        if ($applied > 0) {
            $this->add('ok', 'overrides', "{$applied} of ".count($overrides).' site override(s) in place ('.SiteOverrides::file().').');
        }
    }

    /**
     * Every media reference points to a file that exists.
     */
    private function media(): void
    {
        // Curator items
        $missing = DB::table('curator')->get(['id', 'disk', 'path'])
            ->reject(fn (object $item): bool => $this->exists($item->disk, $item->path));
        $this->result('curator', DB::table('curator')->count().' media item(s)', $missing->map(fn (object $item): string => "#{$item->id} {$item->disk}:{$item->path}"));

        // image links: an existing media item and an existing record
        $links = DB::table('lara_object_images')->get();
        $mediaIds = DB::table('curator')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $broken = $links->reject(fn (object $link): bool => in_array((int) $link->media_id, $mediaIds, true) && $this->recordExists($link->mediable_type, (int) $link->mediable_id));
        $this->result('image links', $links->count().' image link(s)', $broken->map(fn (object $link): string => "#{$link->id} media {$link->media_id} → {$link->mediable_type} {$link->mediable_id}"));

        // JSON lists: files and video files on the disk of their entity
        foreach (['lara_object_files' => ['entity_files', 'doc_filename', 'media_disk_files'], 'lara_object_videofiles' => ['entity_videofiles', 'vidfile_filename', 'media_disk_videos']] as $table => [$column, $field, $diskColumn]) {
            $count = 0;
            $missing = collect();

            foreach (DB::table($table)->get() as $row) {
                $disk = Entity::where('model_class', $row->entity_type)->value($diskColumn) ?: 'public';

                foreach ((array) json_decode((string) $row->{$column}, true) as $item) {
                    $count++;
                    $path = (string) ($item[$field] ?? '');

                    if ($path === '' || ! $this->exists($disk, $path)) {
                        $missing->push(class_basename($row->entity_type)." {$row->entity_id}: {$disk}:{$path}");
                    }
                }
            }

            $this->result(str_replace('lara_object_', '', $table), "{$count} entr(y/ies) in {$table}", $missing);
        }
    }

    /**
     * v8 media URLs left in the HTML, and internal links that do not reach a page.
     */
    private function links(): void
    {
        $lines = (new UrlRewriter($this->source))->run(true);
        $problems = 0;

        foreach ($lines as $line) {
            if ($line['level'] === 'update') {
                $problems++;
                $this->add('warning', 'links', $line['message'].' Run lara:v8:rewrite-urls.');
            } elseif ($line['level'] === 'warning') {
                $problems++;
                $this->add('warning', 'links', $line['message']);
            }
        }

        if ($problems === 0) {
            $this->add('ok', 'links', 'No v8 media URLs left; every internal link reaches a page.');
        }
    }

    /**
     * v10 has no root tag (the import drops the v8 roots), and kalnoy/nestedset needs valid trees.
     */
    private function tags(): void
    {
        $roots = DB::table('lara_object_tags')->whereNull('parent_id')->get()
            ->filter(fn (object $tag): bool => ObjectImporter::isV8RootTag($tag));

        if ($roots->isEmpty()) {
            $this->add('ok', 'tags', 'No v8 root tags.');
        } else {
            $this->add('warning', 'tags', "{$roots->count()} v8 root tag(s) in v10 (#".$roots->pluck('id')->take(5)->implode(', #').'); rerun lara:v8:import-objects --fresh, or check the reported roots with objects.');
        }

        $invalid = DB::table('lara_object_tags')->select(['resource_slug', 'language', 'taxonomy_id'])->distinct()->get()
            ->filter(fn (object $scope): bool => array_sum(Tag::scoped((array) $scope)->countErrors()) > 0)
            ->map(fn (object $scope): string => "{$scope->resource_slug} [{$scope->language}] taxonomy {$scope->taxonomy_id}");

        if ($invalid->isEmpty()) {
            $this->add('ok', 'tags', 'Every tag tree is a valid nested set.');
        } else {
            $this->add('error', 'tags', "Invalid tag tree(s): {$invalid->implode('; ')}.");
        }
    }

    /**
     * @param  Collection<int, string>  $missing
     */
    private function result(string $object, string $what, $missing): void
    {
        if ($missing->isEmpty()) {
            $this->add('ok', $object, "{$what}: all present.");

            return;
        }

        $this->add('error', $object, "{$what}: {$missing->count()} missing (".$missing->take(5)->implode('; ').($missing->count() > 5 ? '; …' : '').').');
    }

    private function exists(?string $disk, string $path): bool
    {
        try {
            return Storage::disk($disk ?: 'public')->exists($path);
        } catch (Throwable) {
            return false;
        }
    }

    private function recordExists(string $modelClass, int $id): bool
    {
        try {
            return DB::table($modelClass::getTableName())->where('id', $id)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}
