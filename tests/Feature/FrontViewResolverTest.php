<?php

namespace Tests\Feature;

use Lara\Common\Entities\PagesEntity;
use Lara\Front\Http\Lara\FrontActiveRoute;
use Lara\Front\LaraTheme\ContentViewFinder;
use Lara\Front\LaraTheme\Theme;
use Lara\Front\Services\FrontViewResolver;
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
     * A content view the theme does not have falls back to the base template of
     * its content group (services has no index_sort view of its own).
     */
    public function test_a_missing_content_view_falls_back_to_its_content_group_template(): void
    {
        $finder = view()->getFinder();

        $this->assertInstanceOf(ContentViewFinder::class, $finder);
        $this->assertStringEndsWith(
            str_replace('/', DIRECTORY_SEPARATOR, 'content/__templates/entity/index/index_sort.blade.php'),
            $finder->find('content.services.index.index_sort')
        );
    }

    public function test_a_view_with_its_own_file_does_not_fall_back(): void
    {
        $this->assertStringEndsWith(
            str_replace('/', DIRECTORY_SEPARATOR, 'content/services/index/index_filter.blade.php'),
            view()->getFinder()->find('content.services.index.index_filter')
        );
    }

    public function test_a_view_without_a_template_is_still_missing(): void
    {
        $this->assertFalse(view()->exists('content.services.zz_no_such_view'));
        $this->assertFalse(view()->exists('content.zz_no_such_entity.index'));
    }

    public function test_it_resolves_a_view_file_for_a_real_entity_and_route(): void
    {
        $entity = new PagesEntity;

        $activeroute = new FrontActiveRoute;
        $activeroute->setPrefix('entity');
        $activeroute->setMethod('show');

        $viewfile = $this->resolver()->getFrontViewFile($entity, $activeroute);

        $this->assertIsString($viewfile);
        $this->assertNotSame('', $viewfile);
    }
}
