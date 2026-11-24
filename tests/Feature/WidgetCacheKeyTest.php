<?php

namespace Tests\Feature;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Facades\File;
use Lara\Front\Widgets\CtaCacheWidget;
use Lara\Front\Widgets\EntityCacheWidget;
use LaravelLocalization;
use Tests\TestCase;

/**
 * Widgets render language-specific HTML, so a cached widget must never serve one language's
 * output on the other language's site. Every widget's cache key therefore holds the locale.
 */
class WidgetCacheKeyTest extends TestCase
{
    protected function tearDown(): void
    {
        LaravelLocalization::setLocale(config('app.locale'));

        parent::tearDown();
    }

    public function test_every_widget_cache_key_differs_per_language(): void
    {
        $widgetClasses = [];

        foreach (File::files(base_path('laracms/core/src/front/Widgets')) as $file) {
            $widgetClass = 'Lara\\Front\\Widgets\\'.$file->getFilenameWithoutExtension();

            if (is_subclass_of($widgetClass, AbstractWidget::class)) {
                $widgetClasses[] = $widgetClass;
            }
        }

        $this->assertNotEmpty($widgetClasses, 'No widget classes found.');

        foreach ($widgetClasses as $widgetClass) {
            LaravelLocalization::setLocale('nl');
            $dutchKey = (new $widgetClass)->cacheKey();

            LaravelLocalization::setLocale('en');
            $englishKey = (new $widgetClass)->cacheKey();

            $this->assertNotSame($dutchKey, $englishKey, $widgetClass.' uses the same cache key for every language.');
        }
    }

    public function test_cta_cache_key_differs_per_template(): void
    {
        $contactKey = (new CtaCacheWidget(['hook' => 'contact', 'template' => 'contact']))->cacheKey();
        $newsletterKey = (new CtaCacheWidget(['hook' => 'contact', 'template' => 'newsletter']))->cacheKey();

        $this->assertNotSame($contactKey, $newsletterKey);
    }

    public function test_entity_cache_key_differs_per_setting_but_not_per_grid(): void
    {
        $settings = ['resource_slug' => 'blogs', 'parent' => 'home', 'count' => 3, 'needs_image' => true];
        $key = (new EntityCacheWidget($settings))->cacheKey();

        $this->assertNotSame($key, (new EntityCacheWidget([...$settings, 'count' => 4]))->cacheKey());
        $this->assertNotSame($key, (new EntityCacheWidget([...$settings, 'needs_image' => false]))->cacheKey());
        $this->assertSame($key, (new EntityCacheWidget([...$settings, 'grid' => (object) ['hasSidebar' => true]]))->cacheKey());
    }
}
