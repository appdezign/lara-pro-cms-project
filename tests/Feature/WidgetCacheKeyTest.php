<?php

namespace Tests\Feature;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Facades\File;
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
}
