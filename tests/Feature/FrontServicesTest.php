<?php

namespace Tests\Feature;

use Lara\Front\Services\FrontEntityResolver;
use Lara\Front\Services\FrontListBuilder;
use Lara\Front\Services\FrontMenuRepository;
use Lara\Front\Services\FrontObjectRepository;
use Lara\Front\Services\FrontPageContext;
use Lara\Front\Services\FrontRouteResolver;
use Lara\Front\Services\FrontSecurityGuard;
use Lara\Front\Services\FrontTermRepository;
use Lara\Front\Services\FrontViewResolver;
use ReflectionClass;
use Tests\TestCase;

/**
 * The services extracted from the front controller trait stack.
 *
 * The point of the extraction is that this logic no longer needs a controller:
 * each service resolves from the container on its own, holds no request state,
 * and declares its collaborators as constructor dependencies. These tests keep
 * it that way.
 */
class FrontServicesTest extends TestCase
{
    /**
     * @return list<class-string>
     */
    public static function serviceProvider(): array
    {
        return [
            [FrontEntityResolver::class],
            [FrontRouteResolver::class],
            [FrontTermRepository::class],
            [FrontObjectRepository::class],
            [FrontMenuRepository::class],
            [FrontListBuilder::class],
            [FrontViewResolver::class],
            [FrontPageContext::class],
            [FrontSecurityGuard::class],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('serviceProvider')]
    public function test_each_service_resolves_from_the_container(string $service): void
    {
        $this->assertInstanceOf($service, app($service));
    }

    /**
     * Any state must be an injected collaborator, never request or controller
     * data. That is what made these untestable as traits.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('serviceProvider')]
    public function test_each_service_holds_only_injected_collaborators(string $service): void
    {
        $reflection = new ReflectionClass($service);
        $properties = $reflection->getProperties();

        if ($properties === []) {
            // fully stateless, which is the ideal
            $this->addToAssertionCount(1);

            return;
        }

        foreach ($properties as $property) {
            $type = $property->getType();

            $this->assertNotNull(
                $type,
                $service . '::$' . $property->getName() . ' has no type; services must not carry loose state.'
            );

            $this->assertStringStartsWith(
                'Lara\Front\Services\\',
                (string) $type,
                $service . '::$' . $property->getName() . ' is not an injected service.'
            );

            $this->assertTrue(
                $property->isReadOnly(),
                $service . '::$' . $property->getName() . ' must be readonly.'
            );
        }
    }

    /**
     * The trait shims must stay thin: they exist only to keep existing call
     * sites working, so nothing but delegation belongs in them.
     */
    public function test_the_trait_shims_stay_thin(): void
    {
        $shims = [
            'HasFrontEntity', 'HasFrontRoutes', 'HasFrontTerms', 'HasFrontObject',
            'HasFrontMenu', 'HasFrontList', 'HasFrontView', 'HasFrontend', 'HasFrontSecurity',
        ];

        $oversized = [];

        foreach ($shims as $shim) {
            $path = base_path('laracms/core/src/front/Http/Concerns/' . $shim . '.php');

            $this->assertFileExists($path);

            $lines = count(file($path) ?: []);

            if ($lines > 100) {
                $oversized[] = $shim . ' (' . $lines . ' lines)';
            }
        }

        $this->assertSame([], $oversized, 'Shims should delegate, not accumulate logic.');
    }
}
