<?php

namespace App\Console\Commands\LaraV8\Support;

use App\Console\Commands\LaraV8\Support\Concerns\SyncsRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityView;

/**
 * Imports the v8 menus, menu items and redirects.
 *
 * v8 builds every menu (per language) as one tree under a root node: in the main menu the
 * home page, in the others a 'root' item. v10 has no single root: the home page (is_home)
 * and the other top-level items are siblings at depth 0, ordered by position (1001, 1002, …).
 * So the trees are flattened: a 'root' item is dropped, the home root becomes the first
 * top-level item, and their children move up one level. lft/rgt/depth/position are rebuilt.
 *
 * Menu items keep their v8 id; entity_id and entity_view_id (v8 ids) become the v10 ids.
 * Route names are rebuilt afterwards by lara:menu:refresh-routenames.
 */
class MenuImporter
{
    use SyncsRows;

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    public function __construct(private readonly V8Source $source) {}

    /**
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(bool $dryRun = false, bool $fresh = false): array
    {
        $this->report = [];

        $this->menus($dryRun, $fresh);
        $this->items($dryRun, $fresh);
        $this->redirects($dryRun, $fresh);

        return $this->report;
    }

    private function menus(bool $dryRun, bool $fresh): void
    {
        $rows = $this->source->connection()->table('lara_menu_menus')->orderBy('id')->get()
            ->map(fn (object $menu): array => [
                'id' => $menu->id,
                'title' => $menu->title,
                'slug' => $menu->slug,
                'created_at' => $menu->created_at,
                'updated_at' => $menu->updated_at,
                'locked_at' => null,
                'locked_by' => null,
            ]);

        if (! $rows->contains(fn (array $menu): bool => $menu['slug'] === 'main')) {
            $this->add('warning', 'menus', "v8 has no menu with the slug 'main'; the v10 front uses it as the main menu.");
        }

        $this->sync('menus', 'lara_menu_menus', ['id'], $rows, $dryRun, $fresh);
    }

    private function items(bool $dryRun, bool $fresh): void
    {
        $entities = $this->entityMap();
        $views = $this->viewMap($entities);
        $pageTemplates = $this->source->connection()->table('lara_content_pages')->pluck('template', 'id');
        $pageIds = DB::table('lara_content_pages')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $rows = collect();

        $items = $this->source->connection()->table('lara_menu_menu_items')->orderBy('menu_id')->orderBy('language')->orderBy('lft')->get();

        foreach ($items->groupBy(fn (object $item): string => $item->menu_id.'#'.$item->language) as $group => $tree) {
            [$menuId, $language] = explode('#', $group);

            // a page item whose page does not exist: skipped when nothing hangs below it (the v10
            // Pages list cannot handle it), otherwise kept so its children keep their place
            $tree = $tree->reject(function (object $item) use ($tree, $pageIds): bool {
                if ($item->type !== 'page' || ! $item->object_id || in_array((int) $item->object_id, $pageIds, true)) {
                    return false;
                }

                $hasChildren = $tree->contains(fn (object $other): bool => (int) $other->parent_id === (int) $item->id);
                $this->add('warning', 'menu items', "Menu item {$item->id} '{$item->title}' points to page {$item->object_id}, which does not exist; ".($hasChildren ? 'kept for its children.' : 'skipped.'));

                return ! $hasChildren;
            })->values();

            $flat = $this->flatten($tree);
            $position = 1000;

            foreach ($flat as $node) {
                $item = $node['item'];
                $position++;

                $entityId = $item->entity_id ? ($entities[$item->entity_id] ?? null) : null;
                $viewId = $item->entity_view_id ? $this->viewId($views, (int) $item->entity_view_id, $pageTemplates[$item->object_id] ?? null) : null;

                if ($item->entity_id && $entityId === null) {
                    $this->add('warning', 'menu items', "Menu item {$item->id} '{$item->title}': v8 entity {$item->entity_id} has no v10 entity.");
                }

                if ($item->entity_view_id && $viewId === null) {
                    $this->add('warning', 'menu items', "Menu item {$item->id} '{$item->title}': v8 view {$item->entity_view_id} has no v10 view.");
                }

                $rows->push([
                    'id' => $item->id,
                    'language' => $item->language,
                    'language_parent' => null,
                    'menu_id' => $item->menu_id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'slug_lock' => (int) $item->slug_lock,
                    'type' => $item->type,
                    'is_home' => (int) $node['is_home'],
                    'route' => $node['is_home'] ? null : $item->route,
                    'routename' => $item->routename,
                    'route_has_auth' => (int) $item->route_has_auth,
                    'entity_id' => $entityId,
                    'entity_view_id' => $viewId,
                    'object_id' => $item->object_id,
                    'tag_id' => $item->tag_id,
                    'url' => $item->url,
                    'locked_by_admin' => (int) $item->locked_by_admin,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'publish' => (int) $item->publish,
                    'parent_id' => $node['parent_id'],
                    'lft' => $node['lft'],
                    'rgt' => $node['rgt'],
                    'depth' => $node['depth'],
                    'position' => $position,
                    'locked_at' => null,
                    'locked_by' => null,
                ]);
            }

            $dropped = $tree->count() - count($flat);

            if ($dropped > 0) {
                $this->add('info', 'menu items', "Menu {$menuId} ({$language}): {$dropped} v8 root item(s) dropped; v10 menus have no root.");
            }
        }

        $this->sync('menu items', 'lara_menu_menu_items', ['id'], $rows, $dryRun, $fresh, derived: ['routename']);
    }

    /**
     * The v10 tree of one v8 menu tree (one menu, one language), in order.
     *
     * @param  Collection<int, object>  $tree  v8 items ordered by lft
     * @return list<array{item: object, parent_id: ?int, depth: int, lft: int, rgt: int, is_home: bool}>
     */
    public static function flatten(Collection $tree): array
    {
        $children = $tree->groupBy(fn (object $item): string => (string) ($item->parent_id ?? ''));
        $ids = $tree->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $nodes = [];

        // top level: items without a parent, or whose parent is not in this tree
        $roots = $tree->filter(fn (object $item): bool => $item->parent_id === null || ! in_array((string) $item->parent_id, $ids, true))->values();
        $topLevel = [];

        foreach ($roots as $root) {
            if ($root->type === 'root') {
                // a v8 root node: its children are the top level
                array_push($topLevel, ...($children[(string) $root->id] ?? collect())->all());
            } elseif ($root->type === 'page' && $roots->count() === 1) {
                // the v8 home page as the root of the tree: first top-level item, its children follow it
                $topLevel[] = ['home' => $root];
                array_push($topLevel, ...($children[(string) $root->id] ?? collect())->all());
            } else {
                $topLevel[] = $root;
            }
        }

        $counter = 0;

        $walk = function (object $item, ?int $parentId, int $depth, bool $isHome, bool $withChildren) use (&$walk, &$nodes, &$counter, $children): void {
            $index = count($nodes);
            $nodes[] = ['item' => $item, 'parent_id' => $parentId, 'depth' => $depth, 'lft' => ++$counter, 'rgt' => 0, 'is_home' => $isHome];

            if ($withChildren) {
                foreach ($children[(string) $item->id] ?? [] as $child) {
                    $walk($child, (int) $item->id, $depth + 1, false, true);
                }
            }

            $nodes[$index]['rgt'] = ++$counter;
        };

        foreach ($topLevel as $entry) {
            if (is_array($entry)) {
                $walk($entry['home'], null, 0, true, false);
            } else {
                $walk($entry, null, 0, false, true);
            }
        }

        return $nodes;
    }

    /**
     * v8 entity id => v10 entity id.
     *
     * @return array<int, int>
     */
    private function entityMap(): array
    {
        $map = [];

        foreach ($this->source->entities() as $v8) {
            $id = Entity::where('resource_slug', V8Map::resourceSlug($v8->entity_key))->value('id');

            if ($id !== null) {
                $map[(int) $v8->id] = (int) $id;
            }
        }

        return $map;
    }

    /**
     * v8 view id => [v10 entity id, method, filename].
     *
     * @param  array<int, int>  $entities
     * @return array<int, array{0: int, 1: string, 2: string}>
     */
    private function viewMap(array $entities): array
    {
        $map = [];

        foreach ($this->source->connection()->table('lara_ent_entity_views')->get() as $view) {
            if (isset($entities[(int) $view->entity_id])) {
                $map[(int) $view->id] = [$entities[(int) $view->entity_id], $view->method, $view->filename];
            }
        }

        return $map;
    }

    /**
     * The v10 view of a v8 view: same entity, method and filename; for a page with several
     * show views (templates) the one with the page's template.
     *
     * @param  array<int, array{0: int, 1: string, 2: string}>  $views
     */
    private function viewId(array $views, int $v8ViewId, ?string $pageTemplate): ?int
    {
        if (! isset($views[$v8ViewId])) {
            return null;
        }

        [$entityId, $method, $filename] = $views[$v8ViewId];
        $candidates = EntityView::where('entity_id', $entityId)->where('method', $method)->orderBy('id')->get();

        $view = $candidates->firstWhere('template', $pageTemplate)
            ?? $candidates->firstWhere('filename', $filename)
            ?? $candidates->first();

        return $view?->id;
    }

    /**
     * v8 redirects → seo_redirects (filarank). v8 language and title have no v10 column.
     */
    private function redirects(bool $dryRun, bool $fresh): void
    {
        $rows = $this->source->connection()->table('lara_menu_redirects')->orderBy('id')->get()
            ->map(fn (object $redirect): array => [
                'id' => $redirect->id,
                'from_path' => '/'.ltrim((string) $redirect->redirectfrom, '/'),
                'to_path' => (string) $redirect->redirectto,
                'status' => in_array((int) $redirect->redirecttype, [301, 302, 307, 308], true) ? (int) $redirect->redirecttype : 301,
                'active' => (int) $redirect->publish,
                'created_at' => $redirect->created_at,
                'updated_at' => $redirect->updated_at,
            ]);

        $this->sync('redirects', 'seo_redirects', ['id'], $rows, $dryRun, $fresh);
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}
