<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Lara\Common\Models\Entity;
use Throwable;

/**
 * Rewrites the v8 media URLs in the imported HTML (lead, body and other text columns) and
 * checks the internal links.
 *
 * - Media: /assets/media/{entity key}/{file} → /storage/{the path the media import copied it to};
 *   other v8 media folders (editor uploads such as images/shares) are copied to the same path on
 *   the public disk → /storage/{path}. Absolute URLs only for the hosts in v8-migration.site_hosts.
 * - Internal links (/{locale}/…) are matched against the v10 routes and reported when they only
 *   reach the 404 route, or point to a detail page whose slug does not exist; other root paths
 *   are checked as files in public/. Links are reported, not changed.
 *
 * Rerunnable: once rewritten, an HTML column has no v8 media URL left.
 */
class UrlRewriter
{
    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    /** @var array<string, string> v8 entity key => v10 resource slug */
    private array $slugs = [];

    /** @var array<string, list<string>> unresolved URL => where */
    private array $unresolved = [];

    /** @var array<string, list<string>> broken internal link => where */
    private array $brokenLinks = [];

    /** @var array<string, true> the public-disk paths copied (or, in a dry run, to be copied) */
    private array $copied = [];

    /** @var array<string, array<string, string>>|null v8 entity key => file name => sub-folder ('file/', 'video/') */
    private ?array $subfolders = null;

    public function __construct(private readonly V8Source $source) {}

    /**
     * v8 entity key => v10 resource slug.
     *
     * @return array<string, string>
     */
    private function slugs(): array
    {
        return $this->slugs ?: $this->slugs = $this->source->contentEntities()
            ->mapWithKeys(fn (object $entity): array => [$entity->entity_key => V8Map::resourceSlug($entity->entity_key)])->all();
    }

    /**
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(bool $dryRun = false): array
    {
        $this->report = [];
        $this->unresolved = [];
        $this->brokenLinks = [];
        $this->copied = [];
        $this->slugs();

        $rewritten = 0;
        $rows = 0;

        foreach (Entity::whereIn('cgroup', ['page', 'entity', 'block', 'form'])->orderBy('resource_slug')->get() as $entity) {
            try {
                $table = $entity->model_class::getTableName();
            } catch (Throwable) {
                continue;
            }

            $columns = collect(DB::select(
                'select column_name as name from information_schema.columns where table_schema = database() and table_name = ? and data_type in (?, ?, ?)',
                [$table, 'text', 'mediumtext', 'longtext'],
            ))->pluck('name')->all();

            foreach (DB::table($table)->get(['id', ...$columns]) as $row) {
                $changes = [];

                foreach ($columns as $column) {
                    $html = (string) ($row->{$column} ?? '');

                    if ($html === '' || ! str_contains($html, '=')) {
                        continue;
                    }

                    $where = "{$table}.{$column}#{$row->id}";
                    $new = $this->rewriteMedia($html, $where, $dryRun);
                    $this->checkLinks($new, $where);

                    if ($new !== $html) {
                        $changes[$column] = $new;
                        $rewritten += substr_count($html, '/assets/media/') - substr_count($new, '/assets/media/');
                    }
                }

                if ($changes !== []) {
                    $rows++;

                    if (! $dryRun) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            }
        }

        $verb = $dryRun ? 'would be ' : '';
        $this->add($rewritten > 0 ? 'update' : 'same', 'media urls', "{$rewritten} v8 media URL(s) in {$rows} row(s) {$verb}rewritten; ".count($this->copied)." editor file(s) {$verb}copied.");

        foreach ($this->unresolved as $url => $where) {
            $this->add('warning', 'media urls', "Media URL {$url} has no file in the v8 media path; left as it is (".implode(', ', array_slice($where, 0, 3)).(count($where) > 3 ? ', …' : '').').');
        }

        foreach ($this->brokenLinks as $link => $where) {
            $this->add('warning', 'links', "Internal link {$link} does not reach a page in v10 (".implode(', ', array_slice($where, 0, 3)).(count($where) > 3 ? ', …' : '').').');
        }

        return $this->report;
    }

    /**
     * Rewrite the v8 media URLs in src/href attributes. Also used by the content import, so the
     * imported HTML already has the v10 URLs and a rerun does not bring the v8 ones back.
     */
    public function rewriteMedia(string $html, string $where, bool $dryRun): string
    {
        $hosts = array_map('strtolower', (array) config('v8-migration.site_hosts', []));

        return preg_replace_callback('/(\b(?:src|href)\s*=\s*["\'])((?:https?:\/\/([^\/"\']+))?\/assets\/media\/([^"\'?#]+))/i', function (array $m) use ($hosts, $where, $dryRun): string {
            [$all, $prefix, $url, $host, $path] = $m + [null, null, null, '', ''];

            if ($host !== '' && ! in_array(strtolower(preg_replace('/^www\./i', '', $host)), array_map(fn (string $h): string => preg_replace('/^www\./i', '', $h), $hosts), true)) {
                return $all;
            }

            $target = $this->mediaTarget(rawurldecode($path), $dryRun);

            if ($target === null) {
                $this->unresolved[$url][] = $where;

                return $all;
            }

            return $prefix.'/storage/'.implode('/', array_map('rawurlencode', explode('/', $target)));
        }, $html) ?? $html;
    }

    /**
     * The path on the public disk of a v8 media file (relative to the v8 media path),
     * copying editor uploads that the media import did not copy; null when the file is missing.
     */
    public function mediaTarget(string $path, bool $dryRun): ?string
    {
        $storage = Storage::disk('public');
        [$folder, $file] = array_pad(explode('/', $path, 2), 2, '');

        // a file of a content type: where the media import puts it (also before that import has run:
        // a document or video file of a record goes to file/ or video/, anything else to the folder itself)
        if ($file !== '' && ! str_contains($file, '/') && isset($this->slugs()[$folder])) {
            $source = $this->source->mediaFile($folder, $file);
            $target = $this->slugs()[$folder].'/'.($this->subfolders()[$folder][$file] ?? '').$file;
        } else {
            // an editor upload or another folder: the same path on the public disk
            $source = rtrim((string) config('v8-migration.media_path'), '/').'/'.$path;
            $source = is_file($source) ? $source : null;
            $target = $path;
        }

        if ($source === null) {
            return null;
        }

        if (! isset($this->copied[$target]) && (! $storage->exists($target) || $storage->size($target) !== filesize($source))) {
            $this->copied[$target] = true;

            if (! $dryRun) {
                $stream = fopen($source, 'rb');
                $storage->writeStream($target, $stream, ['visibility' => 'public']);
                is_resource($stream) && fclose($stream);
            }
        }

        return $target;
    }

    /**
     * The v8 files and video files per content type, with the sub-folder the media import copies
     * them to (MediaImporter: {resource slug}/file/, {resource slug}/video/).
     *
     * @return array<string, array<string, string>>
     */
    private function subfolders(): array
    {
        if ($this->subfolders !== null) {
            return $this->subfolders;
        }

        $keys = $this->source->contentEntities()->pluck('entity_key', 'entity_model_class')->all();
        $this->subfolders = [];

        foreach (['lara_object_videofiles' => 'video/', 'lara_object_files' => 'file/'] as $table => $sub) {
            if (! $this->source->hasTable($table)) {
                continue;
            }

            foreach ($this->source->connection()->table($table)->get(['entity_type', 'filename']) as $row) {
                if (isset($keys[$row->entity_type]) && ! V8Source::isEmpty($row->filename)) {
                    $this->subfolders[$keys[$row->entity_type]][$row->filename] = $sub;
                }
            }
        }

        return $this->subfolders;
    }

    /**
     * Report internal links that do not reach a page in v10.
     */
    private function checkLinks(string $html, string $where): void
    {
        if (! preg_match_all('/\bhref\s*=\s*["\']((?:https?:\/\/([^\/"\']+))?(\/[^"\'#]*))["\']/i', $html, $matches, PREG_SET_ORDER)) {
            return;
        }

        $hosts = array_map(fn (string $h): string => preg_replace('/^www\./i', '', strtolower($h)), (array) config('v8-migration.site_hosts', []));
        $locales = array_keys((array) config('laravellocalization.supportedLocales', []));

        foreach ($matches as $m) {
            $host = $m[2] ?? '';
            $path = html_entity_decode(strtok($m[3], '?'));

            if ($host !== '' && ! in_array(preg_replace('/^www\./i', '', strtolower($host)), $hosts, true)) {
                continue;
            }

            if (str_starts_with($path, '/storage/') || str_starts_with($path, '/assets/media/')) {
                continue;
            }

            if (! $this->reaches($path, $locales)) {
                $this->brokenLinks[$m[1]][] = $where;
            }
        }
    }

    /**
     * @param  list<string>  $locales
     */
    public function reaches(string $path, array $locales): bool
    {
        $segments = explode('/', trim($path, '/'));

        // not a localized page: a file or folder in public/
        if (! in_array($segments[0] ?? '', $locales, true)) {
            return $path === '/' || file_exists(public_path(ltrim($path, '/')));
        }

        $routePath = '/'.implode('/', array_slice($segments, 1));

        try {
            $route = app('router')->getRoutes()->match(Request::create($routePath === '/' ? '/' : rtrim($routePath, '/')));
        } catch (Throwable) {
            return false;
        }

        $name = (string) $route->getName();

        if ($name === '' || str_starts_with($name, 'error.')) {
            return false;
        }

        // a detail page: the record must exist
        if (str_ends_with($name, '.show') && str_contains($route->uri(), '{slug}')) {
            $resource = explode('.', $name)[1] ?? null;
            $entity = $resource ? Entity::where('resource_slug', $resource)->first() : null;
            $slug = $route->parameter('slug');

            if ($entity && $slug !== null) {
                try {
                    return DB::table($entity->model_class::getTableName())->where('slug', $slug)->whereNull('deleted_at')->exists();
                } catch (Throwable) {
                    return true;
                }
            }
        }

        return true;
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}
