<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\App\Models\Blog;
use Lara\App\Models\Doc;
use Lara\App\Models\Event;
use Lara\App\Models\Gallery;
use Lara\App\Models\Location;
use Lara\App\Models\Portfolio;
use Lara\App\Models\Product;
use Lara\App\Models\Service;
use Lara\App\Models\Team;
use Lara\App\Models\Testimonial;
use Lara\App\Models\Video;
use Lara\Common\Models\Entity;
use Lara\Common\Models\Page;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the content factories, which all build their attributes from the
 * entity configuration through the HasLaraFactory trait: standard columns,
 * custom fields and belongsTo relations.
 *
 * The test database holds essential seed data that must survive the suite, so
 * everything runs inside a transaction. Never swap this for RefreshDatabase,
 * which would wipe that seed data.
 */
class ContentFactoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function contentModels(): array
    {
        return [
            'pages' => [Page::class],
            'blogs' => [Blog::class],
            'teams' => [Team::class],
            'locations' => [Location::class],
            'events' => [Event::class],
            'services' => [Service::class],
            'testimonials' => [Testimonial::class],
            'portfolios' => [Portfolio::class],
            'galleries' => [Gallery::class],
            'docs' => [Doc::class],
            'videos' => [Video::class],
            'products' => [Product::class],
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    #[DataProvider('contentModels')]
    public function test_the_factory_creates_a_published_record_with_all_configured_columns(string $modelClass): void
    {
        $record = $modelClass::factory()->create()->fresh();

        $this->assertNotNull($record);
        $this->assertSame(config('app.locale'), $record->language);
        $this->assertNotEmpty($record->title);
        $this->assertNotEmpty($record->slug);
        $this->assertEquals(1, $record->publish);

        $entity = Entity::where('model_class', $modelClass)->firstOrFail();

        if ($entity->col_has_lead) {
            $this->assertNotEmpty($record->lead, 'lead is empty');
        }

        if ($entity->col_has_body) {
            $this->assertNotEmpty($record->body, 'body is empty');
        }

        $alwaysFilled = ['string', 'text', 'textarea', 'richeditor', 'richeditormin', 'email', 'date', 'time', 'datetime', 'colorpicker'];

        foreach ($entity->customfields->whereIn('field_type', $alwaysFilled) as $customfield) {
            if ($entity->resource_slug == 'pages' && $customfield->field_name == 'menuroute') {
                continue;
            }

            $this->assertNotEmpty($record->getAttribute($customfield->field_name), $customfield->field_name.' is empty');
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    #[DataProvider('contentModels')]
    public function test_choice_fields_only_get_values_from_their_options(string $modelClass): void
    {
        $record = $modelClass::factory()->create()->fresh();

        $this->assertNotNull($record);

        $entity = Entity::where('model_class', $modelClass)->firstOrFail();

        $singleChoice = ['select', 'togglebuttons', 'radio'];
        $multipleChoice = ['multiselect', 'multitogglebuttons', 'checkboxlist'];

        foreach ($entity->customfields->whereIn('field_type', [...$singleChoice, ...$multipleChoice]) as $customfield) {
            $options = $customfield->field_options ?? [];

            if (empty($options) || str_starts_with($options[0], 'get_')) {
                continue;
            }

            $value = $record->getAttribute($customfield->field_name);
            $values = in_array($customfield->field_type, $multipleChoice)
                ? (is_array($value) ? $value : json_decode($value, true))
                : [$value];

            $this->assertNotEmpty($values, $customfield->field_name.' has no value');

            foreach ($values as $selected) {
                $this->assertContains($selected, $options, $customfield->field_name.' got a value outside its options');
            }
        }
    }

    public function test_the_product_factory_fills_every_field_type(): void
    {
        $product = Product::factory()->create()->fresh();

        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $product->mycolor);
        $this->assertContains((int) $product->mytoggle, [0, 1]);
        $this->assertContains((int) $product->mycheckbox, [0, 1]);
        $this->assertIsArray($product->mytagsinput);
        $this->assertCount(3, $product->mytagsinput);
        $this->assertGreaterThan(0, (float) $product->mydecimal);
        $this->assertContains($product->myradio, ['seven', 'eight', 'nine']);
    }

    public function test_the_page_factory_never_creates_a_homepage_or_a_menu_route(): void
    {
        $pages = Page::factory()->count(10)->create();

        foreach ($pages as $page) {
            $page->refresh();
            $this->assertEquals(0, $page->ishome);
            $this->assertNull($page->menuroute);
        }
    }

    public function test_the_geolocation_field_fills_the_geo_columns(): void
    {
        $location = Location::factory()->create()->fresh();

        $this->assertSame('manual', $location->geo_location);
        $this->assertNotEmpty($location->geo_address);
        $this->assertNotEmpty($location->geo_pcode);
        $this->assertNotEmpty($location->geo_city);
        $this->assertNotEmpty($location->geo_country);
        $this->assertNotNull($location->geo_latitude);
        $this->assertNotNull($location->geo_longitude);
    }

    public function test_events_never_end_before_they_start(): void
    {
        $events = Event::factory()->count(20)->create();

        foreach ($events as $event) {
            $attributes = $event->fresh()->getAttributes();

            $this->assertGreaterThanOrEqual($attributes['startdate'], $attributes['enddate']);
            $this->assertGreaterThanOrEqual($attributes['starttime'], $attributes['endtime']);
        }
    }

    public function test_a_belongs_to_relation_reuses_an_existing_record(): void
    {
        Location::factory()->create();
        $locationCount = Location::count();

        $teams = Team::factory()->count(3)->create();

        $this->assertSame($locationCount, Location::count());

        foreach ($teams as $team) {
            $this->assertNotNull($team->location);
        }
    }

    public function test_a_belongs_to_relation_creates_a_record_when_none_exists_in_the_language(): void
    {
        config(['app.locale' => 'zz']);

        $team = Team::factory()->create();

        $this->assertNotNull($team->location);
        $this->assertSame('zz', $team->location->language);
    }

    public function test_an_explicit_parent_creates_no_extra_records(): void
    {
        $location = Location::factory()->create();
        $locationCount = Location::count();

        $overridden = Team::factory()->create(['location_id' => $location->id]);
        $withFor = Team::factory()->for($location)->create();

        $this->assertSame($locationCount, Location::count());
        $this->assertSame($location->id, $overridden->location_id);
        $this->assertSame($location->id, $withFor->location_id);
    }

    public function test_a_recycled_parent_wins_over_other_existing_records(): void
    {
        Location::factory()->count(3)->create();
        $recycled = Location::factory()->create();

        $teams = Team::factory()->count(5)->recycle($recycled)->create();

        foreach ($teams as $team) {
            $this->assertSame($recycled->id, $team->location_id);
        }
    }
}
