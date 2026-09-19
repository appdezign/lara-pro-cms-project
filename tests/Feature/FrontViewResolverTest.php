<?php

namespace Tests\Feature;

use Lara\Front\Http\Lara\FrontActiveRoute;
use Lara\Front\Services\FrontViewResolver;
use Lara\Front\LaraTheme\Theme;
use Tests\TestCase;

/**
 * FrontViewResolver, tested directly.
 *
 * This logic used to live in HasFrontView as private trait methods, reachable
 * only by rendering a whole page through a front controller. As a stateless
 * service it can be resolved and called on its own, which is the point of
 * extracting it.
 */
class FrontViewResolverTest extends TestCase
{
    private function resolver(): FrontViewResolver
    {
        return app(FrontViewResolver::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // the resolver reads the active theme when looking for view files
        Theme::set(config('lara-front.theme', 'demo'), config('lara-front.parent_theme', 'base'));
    }

    public function test_it_resolves_from_the_container(): void
    {
        $this->assertInstanceOf(FrontViewResolver::class, $this->resolver());
    }

    public function test_it_needs_no_controller_state(): void
    {
        $reflection = new \ReflectionClass(FrontViewResolver::class);

        $this->assertSame(
            [],
            $reflection->getProperties(),
            'FrontViewResolver must stay stateless; give methods their inputs as parameters.'
        );
    }

    public function test_it_returns_a_default_theme_layout(): void
    {
        $layout = $this->resolver()->getDefaultThemeLayout();

        $this->assertIsObject($layout, 'A default layout object is expected.');
    }

    public function test_the_grid_of_a_layout_is_an_object(): void
    {
        $layout = $this->resolver()->getDefaultThemeLayout();

        $this->assertIsObject($this->resolver()->getGrid($layout));
    }

    /**
     * The resolver decides which theme blade file backs an entity view, and has
     * to say no for a view that does not exist.
     */
    public function test_it_reports_a_missing_theme_view_file(): void
    {
        $entity = new \Lara\Common\Entities\PagesEntity();

        $this->assertFalse(
            $this->resolver()->checkThemeViewFile($entity, 'zz/no/such/view'),
            'A view path that does not exist must not be reported as present.'
        );
    }

    public function test_it_resolves_a_view_file_for_a_real_entity_and_route(): void
    {
        $entity = new \Lara\Common\Entities\PagesEntity();

        $activeroute = new FrontActiveRoute(new \stdClass());
        $activeroute->setPrefix('entity');
        $activeroute->setMethod('show');

        $viewfile = $this->resolver()->getFrontViewFile($entity, $activeroute);

        $this->assertIsString($viewfile);
        $this->assertNotSame('', $viewfile);
    }
}
