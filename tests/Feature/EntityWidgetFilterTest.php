<?php

namespace Tests\Feature;

use Arrilot\Widgets\Facade as Widget;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Lara\App\Models\Blog;
use Lara\Common\Models\LaraWidget;
use Lara\Common\Models\Tag;
use Lara\Common\Models\Taxonomy;
use Lara\Common\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * An entity widget shows the objects of one entity, filtered by the settings
 * of the widget: a tag (resource_term, a tag slug of the default taxonomy) and
 * "image required" (image_required). No tag, stored as null or as the older
 * value "none", means no tag filter.
 *
 * The widget's objects are captured from its view, as in EntityWidgetSortTest.
 */
class EntityWidgetFilterTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{string}>
     */
    public static function entityWidgets(): array
    {
        return [
            'entity widget' => ['laraEntityWidget'],
            'cached entity widget' => ['laraEntityCacheWidget'],
        ];
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function entityWidgetsWithoutTag(): array
    {
        return [
            'entity widget, no tag' => ['laraEntityWidget', null],
            'entity widget, tag "none"' => ['laraEntityWidget', 'none'],
            'cached entity widget, no tag' => ['laraEntityCacheWidget', null],
            'cached entity widget, tag "none"' => ['laraEntityCacheWidget', 'none'],
        ];
    }

    #[DataProvider('entityWidgets')]
    public function test_a_widget_with_a_tag_shows_only_objects_with_that_tag(string $widgetName): void
    {
        $tag = $this->blogTag();
        $tagged = $this->blog('Tagged blog');
        $tagged->terms()->attach($tag->id);
        $untagged = $this->blog('Untagged blog');

        $ids = $this->idsShownBy($widgetName, $this->blogsWidget(['resource_term' => $tag->slug]));

        $this->assertContains($tagged->id, $ids);
        $this->assertNotContains($untagged->id, $ids);
    }

    #[DataProvider('entityWidgetsWithoutTag')]
    public function test_a_widget_without_a_tag_also_shows_untagged_objects(string $widgetName, ?string $resourceTerm): void
    {
        $untagged = $this->blog('Untagged blog');

        $ids = $this->idsShownBy($widgetName, $this->blogsWidget(['resource_term' => $resourceTerm]));

        $this->assertContains($untagged->id, $ids);
    }

    #[DataProvider('entityWidgets')]
    public function test_a_widget_that_requires_an_image_shows_only_objects_with_an_image(string $widgetName): void
    {
        $withImage = $this->blog('Blog with image');
        $this->attachImage($withImage);
        $withoutImage = $this->blog('Blog without image');

        $ids = $this->idsShownBy($widgetName, $this->blogsWidget(['image_required' => 1]));

        $this->assertContains($withImage->id, $ids);
        $this->assertNotContains($withoutImage->id, $ids);
    }

    private function blog(string $title): Blog
    {
        return Blog::factory()->create(['title' => $title, 'language' => 'nl', 'publish' => 1, 'publish_from' => now()->subDay()]);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function blogsWidget(array $settings): LaraWidget
    {
        return LaraWidget::forceCreate([
            'user_id' => User::value('id'),
            'language' => 'nl',
            'title' => 'zz blogs widget',
            'slug' => 'zz-blogs-widget',
            'type' => 'module',
            'resource_slug' => 'blogs',
            'template' => 'team_blogs',
            'publish' => 1,
            ...$settings,
        ]);
    }

    /**
     * A tag of the default taxonomy, the only taxonomy a widget filters on.
     */
    private function blogTag(): Tag
    {
        $tag = Tag::resourceIs('blogs')->langIs('nl')
            ->where('taxonomy_id', Taxonomy::where('is_default', 1)->value('id'))
            ->orderBy('id')
            ->first();

        if (! $tag) {
            $this->markTestSkipped('No Dutch blogs tag in the default taxonomy.');
        }

        return $tag;
    }

    private function attachImage(Blog $blog): void
    {
        $mediaId = DB::table('curator')->value('id');

        if (! $mediaId) {
            $this->markTestSkipped('No media in the current database.');
        }

        $blog->images()->forceCreate(['media_id' => $mediaId, 'order' => 0, 'type' => 'featured']);
    }

    /**
     * @return list<int>
     */
    private function idsShownBy(string $widgetName, LaraWidget $widget): array
    {
        $objects = null;

        View::composer('_widgets.lara.entity.*', function ($view) use (&$objects) {
            $objects = $view->getData()['widgetObjects'];
        });

        Widget::run($widgetName, ['widget_id' => $widget->id, 'grid' => (object) []])->toHtml();

        $this->assertNotNull($objects, $widgetName.' rendered no entity widget view.');

        return Collection::make($objects)->pluck('id')->all();
    }
}
