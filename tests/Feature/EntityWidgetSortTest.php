<?php

namespace Tests\Feature;

use Arrilot\Widgets\Facade as Widget;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Lara\App\Models\Blog;
use Lara\Common\Models\LaraWidget;
use Lara\Common\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Entity widgets list the objects of an entity on a page. When the entity has
 * a "sticky" custom field (blogs do), sticky objects come first, before the
 * entity's own sort order (blogs: newest first).
 *
 * The widget's objects are captured from its view, so the test does not depend
 * on the markup of a theme template.
 */
class EntityWidgetSortTest extends TestCase
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

    #[DataProvider('entityWidgets')]
    public function test_a_sticky_blog_comes_before_newer_blogs(string $widgetName): void
    {
        $sticky = Blog::factory()->create(['title' => 'Old sticky blog', 'language' => 'nl', 'sticky' => 1, 'publish' => 1, 'publish_from' => now()->subWeek()]);
        $newer = Blog::factory()->create(['title' => 'New blog', 'language' => 'nl', 'sticky' => 0, 'publish' => 1, 'publish_from' => now()->subDay()]);
        $widget = $this->blogsWidget();

        $objects = $this->objectsShownBy($widgetName, $widget);

        $this->assertSame(
            [$sticky->id, $newer->id],
            $objects->whereIn('id', [$sticky->id, $newer->id])->pluck('id')->values()->all(),
        );
    }

    private function blogsWidget(): LaraWidget
    {
        return LaraWidget::forceCreate([
            'user_id' => User::value('id'),
            'language' => 'nl',
            'title' => 'zz blogs widget',
            'slug' => 'zz-blogs-widget',
            'type' => 'entity',
            'resource_slug' => 'blogs',
            'resource_term' => 'none',
            'template' => 'team_blogs',
            'publish' => 1,
        ]);
    }

    private function objectsShownBy(string $widgetName, LaraWidget $widget): Collection
    {
        $objects = null;

        View::composer('_widgets.lara.entity.*', function ($view) use (&$objects) {
            $objects = $view->getData()['widgetObjects'];
        });

        Widget::run($widgetName, ['widget_id' => $widget->id, 'grid' => (object) []])->toHtml();

        $this->assertNotNull($objects, $widgetName.' rendered no entity widget view.');

        return collect($objects);
    }
}
