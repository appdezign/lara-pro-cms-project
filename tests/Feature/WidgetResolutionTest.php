<?php

namespace Tests\Feature;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Views call widgets by name (`@widget('laraTextWidget', …)`), and arrilot/laravel-widgets turns
 * that name into a class inside `laravel-widgets.default_namespace`. A widget that moves, or a
 * namespace that changes, only shows up when a page renders that widget. These tests resolve
 * every name the views use, the way the widget factory does.
 */
class WidgetResolutionTest extends TestCase
{
    /**
     * @return list<string> widget names used in the themes and the core views
     */
    private function widgetNamesUsedInViews(): array
    {
        $names = [];

        // each theme is a symlink, which File::allFiles() does not descend into from its parent
        $directories = [...File::directories(base_path('laracms/themes')), base_path('laracms/core/resources/views')];

        foreach ($directories as $directory) {
            foreach (File::allFiles($directory) as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                preg_match_all("/@(?:async)?[wW]idget\\('([^']+)'/", $file->getContents(), $matches);
                array_push($names, ...$matches[1]);
            }
        }

        return array_values(array_unique($names));
    }

    public function test_every_widget_the_views_call_resolves_to_a_widget_class(): void
    {
        $names = $this->widgetNamesUsedInViews();
        $namespace = config('laravel-widgets.default_namespace');

        $this->assertNotEmpty($names, 'No widget calls found in the views.');

        foreach ($names as $name) {
            // the same conversion AbstractWidgetFactory::parseFullWidgetNameFromString() applies
            $widgetClass = $namespace.'\\'.Str::studly(str_replace('.', '\\_', $name));

            $this->assertTrue(class_exists($widgetClass), '@widget(\''.$name.'\') resolves to '.$widgetClass.', which does not exist.');
            $this->assertTrue(is_subclass_of($widgetClass, AbstractWidget::class), $widgetClass.' is not a widget.');
        }
    }

    public function test_every_widget_class_is_in_the_configured_namespace(): void
    {
        $namespace = config('laravel-widgets.default_namespace');

        foreach (File::files(base_path('laracms/core/src/front/Widgets')) as $file) {
            $widgetClass = $namespace.'\\'.$file->getFilenameWithoutExtension();

            $this->assertTrue(class_exists($widgetClass), $file->getFilename().' is not '.$widgetClass.'.');
        }
    }
}
