<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Lara\Common\Models\Tag;
use Lara\Front\Http\Concerns\HasFrontEntity;
use Lara\Front\Http\Lara\FrontActiveRoute;
use Lara\Front\Http\Lara\FrontParams;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A list sorted by tag shows a heading for each published tag with objects in
 * itself or below. v10 has no root tag (the v8 import drops it), so the
 * top-level tags are real tags.
 */
class SortByTagRenderTest extends TestCase
{
    use HasFrontEntity;

    /**
     * @return array<string, array{string}>
     */
    public static function listTypes(): array
    {
        return ['list' => ['list'], 'grid' => ['grid']];
    }

    #[DataProvider('listTypes')]
    public function test_top_level_and_nested_tags_with_objects_are_shown(string $listType): void
    {
        $child = $this->tag('Nested tag', 1, 2, 3, 1);
        $html = $this->render($listType, $this->tag('Top level tag', 0, 1, 4, 1, [$child]));

        $this->assertStringContainsString('Top level tag', $html);
        $this->assertStringContainsString('Nested tag', $html);
    }

    #[DataProvider('listTypes')]
    public function test_a_tag_without_objects_in_itself_or_below_is_skipped(string $listType): void
    {
        $html = $this->render($listType, $this->tag('Empty tag', 0, 1, 2, 0));

        $this->assertStringNotContainsString('Empty tag', $html);
    }

    /**
     * A top-level tag is not reached through the publish check on children.
     */
    #[DataProvider('listTypes')]
    public function test_an_unpublished_tag_is_skipped_also_at_the_top_level(string $listType): void
    {
        $html = $this->render($listType, $this->tag('Unpublished tag', 0, 1, 2, 1, publish: 0));

        $this->assertStringNotContainsString('Unpublished tag', $html);
    }

    #[DataProvider('listTypes')]
    public function test_a_tag_heading_is_closed_without_attributes(string $listType): void
    {
        $html = $this->render($listType, $this->tag('Top level tag', 0, 1, 2, 1));

        $this->assertMatchesRegularExpression('#Top level tag\s*</h2>#', $html);
    }

    /**
     * The filter menu beside a list (content._sidebars.left_tags) skips unpublished
     * tags and the tags below them, as the sorted lists do.
     */
    public function test_the_tag_filter_menu_skips_unpublished_tags(): void
    {
        $hiddenChild = $this->tag('Below unpublished tag', 1, 2, 3, 1);
        $unpublished = $this->tag('Unpublished tag', 0, 1, 4, 1, [$hiddenChild], publish: 0);
        $publishedChild = $this->tag('Published child', 1, 6, 7, 1);
        $published = $this->tag('Published tag', 0, 5, 8, 1, [$publishedChild]);

        $html = collect([$unpublished, $published])->map(fn (Tag $node) => view('content._partials.tag_menu_render', [
            'node' => $node,
            'data' => (object) ['params' => new FrontParams],
            'activeroute' => new FrontActiveRoute,
        ])->render())->implode('');

        $this->assertStringContainsString('Published tag', $html);
        $this->assertStringContainsString('Published child', $html);
        $this->assertStringNotContainsString('Unpublished tag', $html);
        $this->assertStringNotContainsString('Below unpublished tag', $html);
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
     * A tag as getFrontObjectsSortedByTaxonomy() prepares it. Objects are counted, not
     * rendered: the tags themselves have no objects.
     *
     * @param  list<Tag>  $children
     */
    private function tag(string $title, int $depth, int $lft, int $rgt, int $childObjectCount, array $children = [], int $publish = 1): Tag
    {
        $tag = new Tag(['title' => $title, 'slug' => Str::slug($title), 'publish' => $publish]);
        $tag->depth = $depth;
        $tag->route = $tag->slug;
        $tag->setLft($lft)->setRgt($rgt);
        $tag->setRelation('children', collect($children));
        $tag->objects = collect();
        $tag->objectcount = 0;
        $tag->childobjectcount = $childObjectCount;

        return $tag;
    }
}
