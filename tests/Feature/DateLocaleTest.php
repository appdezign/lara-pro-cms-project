<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Mcamara\LaravelLocalization\LaravelLocalization;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The dateLocale middleware on the front routes sets the language of formatted
 * dates (Carbon) to the locale of the URL. These tests only read, so the
 * application can be rebuilt per locale (see FrontendRenderTest).
 */
class DateLocaleTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=');

        parent::tearDown();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function locales(): array
    {
        return [
            'dutch' => ['nl', 'en'],
            'english' => ['en', 'nl'],
        ];
    }

    #[DataProvider('locales')]
    public function test_dates_follow_the_locale_of_the_url(string $locale, string $otherLocale): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'='.$locale);
        $this->refreshApplication();
        Carbon::setLocale($otherLocale);

        $this->get('/'.$locale)->assertOk();

        $this->assertSame($locale, Carbon::getLocale());
    }
}
