<?php

namespace Tests\Feature;

use Lara\Common\Models\Tag;
use Lara\Front\Http\Concerns\HasFrontEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A list sorted by tag shows a heading for each tag with objects in itself or
 * below, and none for the root tag or for tags without objects.
 */
class SortByTagRenderTest extends TestCase
{
    use HasFrontEntity;

    public function test_the_list_shows_only_tags_with_objects_and_not_the_root(): void
    {
        $html = $this->render('list', $this->tree());

        $this->assertStringContainsString('Tag with objects', $html);
        $this->assertStringNotContainsString('Empty tag', $html);
        $this->assertStringNotContainsString('root', $html);
    }

    public function test_the_grid_shows_only_tags_with_objects_and_not_the_root(): void
    {
        $html = $this->render('grid', $this->tree());

        $this->assertStringContainsString('Tag with objects', $html);
        $this->assertStringNotContainsString('Empty tag', $html);
        $this->assertStringNotContainsString('root', $html);
    }

    /**
     * A taxonomy without a root tag has its real tags at depth 0; they are shown.
     */
    #[DataProvider('listTypes')]
    public function test_top_level_tags_without_a_root_are_shown(string $listType): void
    {
        $html = $this->render($listType, $this->tag('Top level tag', 0, 1, 2, 1, slug: 'top-level-tag'))
            .$this->render($listType, $this->tag('Empty top level tag', 0, 3, 4, 0, slug: 'empty-top-level-tag'));

        $this->assertStringContainsString('Top level tag', $html);
        $this->assertStringNotContainsString('Empty top level tag', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function listTypes(): array
    {
        return ['list' => ['list'], 'grid' => ['grid']];
    }

    private function render(string $listType, Tag $node): string
    {
        $data = (object) ['menutag' => null, 'params' => new class
        {
            public function getVType(): string
            {
                return 'list';
            }

            public function getGridCols(): int
            {
                return 3;
            }
        }];

        return view('content._partials.sort_by_tag_'.$listType.'_render', [
            'node' => $node,
            'data' => $data,
            'entity' => $this->getResourceBySlug('blogs'),
        ])->render();
    }

    /**
     * root (no slug, as core creates it) > "Tag with objects" (one object below it, in a child tag) and "Empty tag".
     * Objects are counted, not rendered: the tags themselves have no objects.
     */
    private function tree(): Tag
    {
        $child = $this->tag('Child tag', 2, 3, 4, 1, slug: 'child-tag');
        $withObjects = $this->tag('Tag with objects', 1, 2, 5, 1, [$child], 'tag-with-objects');
        $empty = $this->tag('Empty tag', 1, 6, 7, 0, slug: 'empty-tag');

        return $this->tag('root', 0, 1, 8, 1, [$withObjects, $empty]);
    }

    /**
     * @param  list<Tag>  $children
     */
    private function tag(string $title, int $depth, int $lft, int $rgt, int $childObjectCount, array $children = [], ?string $slug = null): Tag
    {
        $tag = new Tag(['title' => $title, 'slug' => $slug, 'publish' => 1]);
        $tag->depth = $depth;
        $tag->setLft($lft)->setRgt($rgt);
        $tag->setRelation('children', collect($children));
        $tag->objects = collect();
        $tag->objectcount = 0;
        $tag->childobjectcount = $childObjectCount;

        return $tag;
    }
}
