<?php

namespace App\Console\Commands\LaraV8\Support;

use App\Console\Commands\LaraV8\Support\Concerns\SyncsRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lara\Front\LaraTheme\Theme;
use Throwable;

/**
 * Imports the objects attached to v8 content: taxonomies, tags, taggables, pageables,
 * SEO (v8 seo + opengraph → the filarank table) and layout (v8 key/value rows → one v10 row
 * per record). v8 class names (Eve\Models\Blog) become the v10 model classes.
 *
 * Rerunnable: only rows that differ are written; with $fresh, v10 rows that v8 does not
 * have are removed. Run lara:v8:import-content first.
 */
class ObjectImporter
{
    use SyncsRows;

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    /** @var array<string, string> v8 model class => v10 model class */
    private array $classes = [];

    /** @var array<string, string> v10 model class => v10 table */
    private array $tables = [];

    public function __construct(private readonly V8Source $source) {}

    /**
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(bool $dryRun = false, bool $fresh = false): array
    {
        $this->report = [];
        $this->classes = $this->source->entities()
            ->filter(fn (object $entity): bool => $this->source->isContentGroup($entity->group_key))
            ->mapWithKeys(fn (object $entity): array => [$entity->entity_model_class => V8Map::modelClass($entity->entity_key)])
            ->all();

        $this->taxonomies($dryRun, $fresh);
        $this->tags($dryRun, $fresh);
        $this->morphRows('taggables', 'lara_object_taggables', ['tag_id'], $dryRun, $fresh);
        $this->morphRows('pageables', 'lara_object_pageables', ['page_id'], $dryRun, $fresh);
        $this->seo($dryRun, $fresh);
        $this->layout($dryRun, $fresh);
        $this->notConverted();

        return $this->report;
    }

    private function taxonomies(bool $dryRun, bool $fresh): void
    {
        $rows = $this->source->connection()->table('lara_object_taxonomies')->orderBy('id')->get()
            ->map(fn (object $row): array => [...(array) $row, 'locked_at' => null, 'locked_by' => null]);

        $this->sync('taxonomies', 'lara_object_taxonomies', ['id'], $rows, $dryRun, $fresh);
    }

    /**
     * v8 keeps every tag tree (one per resource, language and taxonomy) under a root tag; v10 has
     * none: its tags start at the top level. The v8 roots are not imported and their children move
     * up one level (see treeWithoutRoots()).
     */
    private function tags(bool $dryRun, bool $fresh): void
    {
        $db = $this->source->connection();
        $v8Tags = $db->table('lara_object_tags')->orderBy('id')->get();
        $published = $v8Tags->filter(fn (object $tag): bool => ! V8Source::isEmpty($tag->publish_from) || ! V8Source::isEmpty($tag->publish_to))->count();

        if ($published > 0) {
            $this->add('warning', 'tags', "{$published} v8 tag(s) have a publish_from/publish_to date; v10 tags have none, so the dates are not imported.");
        }

        $referenced = $db->table('lara_object_taggables')->pluck('tag_id')
            ->merge($db->table('lara_menu_menu_items')->whereNotNull('tag_id')->pluck('tag_id'))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $tree = self::treeWithoutRoots($v8Tags, $referenced);

        if ($tree['dropped'] !== []) {
            $this->add('info', 'tags', count($tree['dropped']).' v8 root tag(s) not imported; v10 has no root tag, so their children are top-level tags.');
        }

        foreach ($tree['kept'] as $id) {
            $this->add('warning', 'tags', "v8 root tag #{$id} has objects or menu items; imported as a top-level tag.");
        }

        $rows = $v8Tags
            ->filter(fn (object $tag): bool => isset($tree['nodes'][(int) $tag->id]))
            ->map(function (object $tag) use ($tree): array {
                $row = collect((array) $tag)->except(['entity_key', 'publish_from', 'publish_to'])->all();

                return [
                    ...$row,
                    ...$tree['nodes'][(int) $tag->id],
                    'resource_slug' => V8Map::resourceSlug($tag->entity_key),
                    'locked_at' => null,
                    'locked_by' => null,
                ];
            })
            ->values();

        $this->sync('tags', 'lara_object_tags', ['id'], $rows, $dryRun, $fresh);
    }

    /**
     * The v10 tag trees: v8 root tags dropped, their children moved up one level, and
     * parent_id/depth/lft/rgt rebuilt per resource, language and taxonomy in v8 order.
     *
     * A v8 root is a top-level tag titled "root" (in another language "[EN] root"). A root that
     * taggables or menu items point to is kept as a top-level tag (reported).
     *
     * @param  Collection<int, object>  $tags  v8 tags
     * @param  list<int>  $referenced  ids of the tags that taggables or menu items point to
     * @return array{nodes: array<int, array{parent_id: ?int, depth: int, lft: int, rgt: int}>, dropped: list<int>, kept: list<int>}
     */
    public static function treeWithoutRoots(Collection $tags, array $referenced): array
    {
        $referenced = array_flip($referenced);
        $nodes = [];
        $dropped = [];
        $kept = [];

        $scopes = $tags->groupBy(fn (object $tag): string => $tag->entity_key.'|'.$tag->language.'|'.$tag->taxonomy_id);

        foreach ($scopes as $scope) {
            $scope = $scope->sortBy(fn (object $tag): string => sprintf('%010d-%010d', (int) $tag->lft, (int) $tag->id))->values();
            $ids = array_flip($scope->pluck('id')->map(fn ($id): int => (int) $id)->all());
            $children = $scope->groupBy(fn (object $tag): string => $tag->parent_id === null ? '' : (string) (int) $tag->parent_id);
            $topLevel = [];

            // top level: tags without a parent, or whose parent is not in this tree
            foreach ($scope as $tag) {
                if ($tag->parent_id !== null && isset($ids[(int) $tag->parent_id])) {
                    continue;
                }

                if (! self::isV8RootTag($tag)) {
                    $topLevel[] = $tag;
                } elseif (isset($referenced[(int) $tag->id])) {
                    $kept[] = (int) $tag->id;
                    $topLevel[] = $tag;
                } else {
                    $dropped[] = (int) $tag->id;
                    array_push($topLevel, ...($children[(string) (int) $tag->id] ?? collect())->all());
                }
            }

            $counter = 0;

            $walk = function (object $tag, ?int $parentId, int $depth) use (&$walk, &$nodes, &$counter, $children): void {
                $id = (int) $tag->id;
                $nodes[$id] = ['parent_id' => $parentId, 'depth' => $depth, 'lft' => ++$counter, 'rgt' => 0];

                foreach ($children[(string) $id] ?? [] as $child) {
                    $walk($child, $id, $depth + 1);
                }

                $nodes[$id]['rgt'] = ++$counter;
            };

            foreach ($topLevel as $tag) {
                $walk($tag, null, 0);
            }
        }

        return ['nodes' => $nodes, 'dropped' => $dropped, 'kept' => $kept];
    }

    /**
     * Whether a top-level v8 tag is the root of its tree: v8 names it "root", in another
     * language with a prefix ("[EN] root"), and gives it a slug such as root-2 or en-root-1.
     */
    public static function isV8RootTag(object $tag): bool
    {
        return preg_match('/^(\[[a-z]{2}\]\s*)?root$/i', trim((string) $tag->title)) === 1;
    }

    /**
     * Polymorphic link tables that keep their v8 id: only the class name changes.
     *
     * @param  list<string>  $otherColumns
     */
    private function morphRows(string $label, string $table, array $otherColumns, bool $dryRun, bool $fresh): void
    {
        $skipped = 0;
        $rows = collect();

        foreach ($this->source->connection()->table($table)->orderBy('id')->get() as $row) {
            $class = $this->v10Class($row->entity_type);

            if ($class === null || ! $this->recordExists($class, (int) $row->entity_id)) {
                $skipped++;

                continue;
            }

            $rows->push([
                'id' => $row->id,
                ...collect((array) $row)->only($otherColumns)->all(),
                'entity_type' => $class,
                'entity_id' => $row->entity_id,
            ]);
        }

        if ($skipped > 0) {
            $this->add('warning', $label, "{$skipped} v8 {$label} row(s) point to a record or content type that does not exist in v10; skipped.");
        }

        $this->sync($label, $table, ['id'], $rows, $dryRun, $fresh);
    }

    /**
     * v8 keeps SEO and opengraph in two tables; v10 (filarank) in one row per record,
     * without an og title or description.
     */
    private function seo(bool $dryRun, bool $fresh): void
    {
        $db = $this->source->connection();
        $seo = $db->table('lara_object_seo')->get()->keyBy(fn (object $row): string => $row->entity_type.'#'.$row->entity_id);
        $og = $db->table('lara_object_opengraph')->get()->keyBy(fn (object $row): string => $row->entity_type.'#'.$row->entity_id);
        $rows = collect();
        $lostOg = 0;
        $truncated = 0;
        $skipped = 0;

        foreach ($seo->keys()->merge($og->keys())->unique() as $key) {
            $s = $seo[$key] ?? null;
            $o = $og[$key] ?? null;
            [$v8Class, $id] = explode('#', $key);
            $class = $this->v10Class($v8Class);

            $title = V8Map::seoTitle($this->text($s->seo_title ?? null), (string) config('filarank.site.name'));
            $description = $this->text($s->seo_description ?? null);
            $focus = $this->text($s->seo_focus ?? null);
            $keywords = $this->keywords($s->seo_keywords ?? null);
            $ogImage = $this->text($o->og_image ?? null);

            if ($title === null && $description === null && $focus === null && $keywords === null && $ogImage === null) {
                if ($this->text($o->og_title ?? null) !== null || $this->text($o->og_description ?? null) !== null) {
                    $lostOg++;
                }

                continue;
            }

            if ($class === null || ! $this->recordExists($class, (int) $id)) {
                $skipped++;

                continue;
            }

            if ($this->text($o->og_title ?? null) !== null || $this->text($o->og_description ?? null) !== null) {
                $lostOg++;
            }

            if ($description !== null && mb_strlen($description) > 500) {
                $description = mb_substr($description, 0, 500);
                $truncated++;
            }

            $rows->push([
                'model_type' => $class,
                'model_id' => (int) $id,
                'title' => $title,
                'description' => $description,
                'focus_keyword' => $focus,
                'additional_keywords' => $keywords === null ? null : json_encode($keywords, JSON_UNESCAPED_UNICODE),
                'locale' => $this->recordLanguage($class, (int) $id),
                'og_image' => $ogImage,
                'created_at' => $s->created_at ?? $o->created_at ?? null,
                'updated_at' => $s->updated_at ?? $o->updated_at ?? null,
            ]);
        }

        if ($lostOg > 0) {
            $this->add('warning', 'seo', "{$lostOg} record(s) have a v8 opengraph title or description; v10 has none (it uses the SEO title and description), so they are not imported.");
        }

        if ($truncated > 0) {
            $this->add('warning', 'seo', "{$truncated} SEO description(s) were longer than 500 characters and were shortened.");
        }

        if ($skipped > 0) {
            $this->add('warning', 'seo', "{$skipped} v8 SEO row(s) point to a record or content type that does not exist in v10; skipped.");
        }

        $this->sync('seo', 'lara_object_seo', ['model_type', 'model_id'], $rows, $dryRun, $fresh);
    }

    /**
     * v8 keeps one row per layout setting (layout_key, layout_value); v10 one row per record.
     */
    private function layout(bool $dryRun, bool $fresh): void
    {
        $options = $this->themeLayoutOptions();
        $rows = collect();

        foreach ($this->source->connection()->table('lara_object_layout')->orderBy('id')->get()->groupBy(fn (object $row): string => $row->entity_type.'#'.$row->entity_id) as $key => $settings) {
            [$v8Class, $id] = explode('#', $key);
            $class = $this->v10Class($v8Class);

            if ($class === null || ! $this->recordExists($class, (int) $id)) {
                $this->add('warning', 'layout', "Layout of {$v8Class} {$id}: the record does not exist in v10; skipped.");

                continue;
            }

            $row = ['entity_type' => $class, 'entity_id' => (int) $id];

            foreach ($settings as $setting) {
                if (! in_array($setting->layout_key, ['header', 'hero', 'pagetitle', 'content', 'share', 'cta', 'footer'], true)) {
                    $this->add('warning', 'layout', "Layout of {$v8Class} {$id}: v8 section '{$setting->layout_key}' has no v10 column; skipped.");

                    continue;
                }

                if ($options !== null && ! in_array($setting->layout_value, $options[$setting->layout_key] ?? [], true)) {
                    $this->add('warning', 'layout', "Layout of {$v8Class} {$id}: '{$setting->layout_value}' is not a {$setting->layout_key} option of the active theme; imported, check the theme.");
                }

                $row[$setting->layout_key] = $setting->layout_value;
            }

            $rows->push($row);
        }

        $this->sync('layout', 'lara_object_layout', ['entity_type', 'entity_id'], $rows, $dryRun, $fresh);
    }

    /**
     * Related objects and remote sync are stored very differently in v10 (JSON lists, other
     * columns); they are reported until a site actually uses them.
     */
    private function notConverted(): void
    {
        foreach (['lara_object_related' => 'related objects', 'lara_object_sync' => 'remote sync settings'] as $table => $what) {
            $count = $this->source->connection()->table($table)->count();

            if ($count > 0) {
                $this->add('warning', 'objects', "{$count} v8 {$what} ({$table}) are not imported: the v10 format differs and is not converted yet.");
            }
        }
    }

    private function v10Class(string $v8Class): ?string
    {
        return $this->classes[$v8Class] ?? null;
    }

    private function table(string $class): ?string
    {
        if (! array_key_exists($class, $this->tables)) {
            try {
                $this->tables[$class] = $class::getTableName();
            } catch (Throwable) {
                $this->tables[$class] = null;
            }
        }

        return $this->tables[$class];
    }

    private function recordExists(string $class, int $id): bool
    {
        $table = $this->table($class);

        return $table !== null && DB::table($table)->where('id', $id)->exists();
    }

    private function recordLanguage(string $class, int $id): ?string
    {
        $table = $this->table($class);

        try {
            return $table ? DB::table($table)->where('id', $id)->value('language') : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The layout options of the active theme (views/_layout/_layout.xml): section => partial files;
     * null when the theme has no layout file.
     *
     * @return array<string, list<string>>|null
     */
    private function themeLayoutOptions(): ?array
    {
        try {
            $path = Theme::path('views').'/_layout/_layout.xml';
        } catch (Throwable) {
            return null;
        }

        if (! is_file($path)) {
            return null;
        }

        $options = [];

        foreach (simplexml_load_file($path) as $partial) {
            $options[(string) $partial->key] = array_map('strval', iterator_to_array($partial->xpath('.//partialFile'), false));
        }

        return $options;
    }

    private function text(?string $value): ?string
    {
        return V8Source::isEmpty($value) || trim((string) $value) === '' ? null : trim((string) $value);
    }

    /**
     * @return list<string>|null
     */
    private function keywords(?string $value): ?array
    {
        $keywords = array_values(array_filter(array_map('trim', explode(',', (string) $this->text($value))), fn (string $keyword): bool => $keyword !== ''));

        return $keywords === [] ? null : $keywords;
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}
