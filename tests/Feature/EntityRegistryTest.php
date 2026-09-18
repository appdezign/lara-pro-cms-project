<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lara\Common\Entities\EntityConfig;
use Lara\Common\Entities\EntityRegistry;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityCustomField;
use RuntimeException;
use Tests\TestCase;

/**
 * Entity configuration used to be cached under two separate rememberForever
 * keys with no Cache::forget anywhere in the codebase, so a config change only
 * took effect once the whole application cache was wiped. These tests pin the
 * single-key + observer-driven invalidation behaviour that replaced it.
 */
class EntityRegistryTest extends TestCase
{
    private function registry(): EntityRegistry
    {
        return app(EntityRegistry::class);
    }

    public function test_it_is_a_singleton(): void
    {
        $this->assertSame($this->registry(), $this->registry());
    }

    public function test_it_returns_a_config_for_a_known_slug(): void
    {
        $entity = Entity::first();

        if (!$entity) {
            $this->markTestSkipped('No entities in the current database.');
        }

        $config = $this->registry()->get($entity->resource_slug);

        $this->assertInstanceOf(EntityConfig::class, $config);
        $this->assertSame($entity->resource_slug, $config->resourceSlug);
        $this->assertSame((int) $entity->id, $config->id);
    }

    public function test_config_values_match_the_underlying_row(): void
    {
        $entity = Entity::first();

        if (!$entity) {
            $this->markTestSkipped('No entities in the current database.');
        }

        $config = $this->registry()->get($entity->resource_slug);

        $this->assertSame((bool) $entity->col_has_lead, $config->content->hasLead);
        $this->assertSame((int) $entity->col_extra_body_fields, $config->content->extraBodyFields);
        $this->assertSame((bool) $entity->media_has_featured, $config->media->hasFeatured);
        $this->assertSame((int) $entity->media_max_gallery, $config->media->maxGallery);
        $this->assertSame((bool) $entity->objrel_has_terms, $config->relations->hasTerms);
        $this->assertSame((bool) $entity->filter_is_open, $config->filters->isOpen);
    }

    public function test_an_unknown_slug_returns_null_or_throws(): void
    {
        $this->assertNull($this->registry()->find('no-such-entity'));
        $this->assertNull($this->registry()->model('no-such-entity'));

        $this->expectException(RuntimeException::class);
        $this->registry()->get('no-such-entity');
    }

    public function test_reads_are_served_from_one_cache_key(): void
    {
        Cache::forget('lara:entities:v1');
        app()->forgetInstance(EntityRegistry::class);

        // warm the cache
        $this->registry()->all();

        app()->forgetInstance(EntityRegistry::class);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->registry()->all();

        $this->assertSame(0, $queries, 'A warm registry must not query the database.');
    }

    public function test_saving_an_entity_invalidates_the_cache(): void
    {
        $entity = Entity::first();

        if (!$entity) {
            $this->markTestSkipped('No entities in the current database.');
        }

        $versionBefore = $this->registry()->version();
        $original = $entity->col_has_lead;

        $entity->col_has_lead = !$original;
        $entity->save();

        try {
            $this->assertNotSame(
                $versionBefore,
                app(EntityRegistry::class)->version(),
                'Saving an entity must bump the config version.'
            );

            $this->assertSame(
                (bool) !$original,
                app(EntityRegistry::class)->get($entity->resource_slug)->content->hasLead,
                'The new value must be visible without clearing the whole cache.'
            );
        } finally {
            $entity->col_has_lead = $original;
            $entity->save();
        }
    }

    /**
     * During setup the table is missing or unseeded. Caching that empty result
     * forever would survive the setup run, and the seeders insert with the query
     * builder so no model event fires to flush it.
     */
    public function test_an_empty_result_is_not_cached(): void
    {
        Cache::forget('lara:entities:v1');

        $registry = new class extends EntityRegistry
        {
            protected function load(): array
            {
                return [];
            }
        };

        $this->assertNull($registry->find('blogs'));
        $this->assertFalse(
            Cache::has('lara:entities:v1'),
            'An empty entity set must not be cached.'
        );
    }

    public function test_saving_a_custom_field_invalidates_the_cache(): void
    {
        $field = EntityCustomField::first();

        if (!$field) {
            $this->markTestSkipped('No entity custom fields in the current database.');
        }

        $versionBefore = $this->registry()->version();

        $field->save();

        $this->assertNotSame(
            $versionBefore,
            app(EntityRegistry::class)->version(),
            'Saving a custom field must bump the config version.'
        );
    }

    public function test_the_version_is_stable_between_changes(): void
    {
        $first = $this->registry()->version();

        app()->forgetInstance(EntityRegistry::class);

        $this->assertSame(
            $first,
            app(EntityRegistry::class)->version(),
            'The version must only change when configuration changes.'
        );
    }

    /**
     * Values derived from entity config (custom field schemas, table columns,
     * relation filters) live under their own rememberForever keys. They append
     * the registry version so that one bump invalidates the whole family,
     * without cache tags and without enumerating every key.
     */
    public function test_derived_cache_keys_change_with_the_version(): void
    {
        $entity = Entity::first();

        if (!$entity) {
            $this->markTestSkipped('No entities in the current database.');
        }

        $derivedKey = fn(): string => 'lara_entity_custom_fields_' . $entity->resource_slug . '_content_'
            . app(EntityRegistry::class)->version();

        $keyBefore = $derivedKey();
        Cache::forever($keyBefore, 'stale value');

        $original = $entity->col_has_lead;
        $entity->col_has_lead = !$original;
        $entity->save();

        try {
            $keyAfter = $derivedKey();

            $this->assertNotSame($keyBefore, $keyAfter, 'The derived key must change with the version.');
            $this->assertFalse(Cache::has($keyAfter), 'The new derived key must be a cache miss.');
            $this->assertSame('stale value', Cache::get($keyBefore), 'The old entry is simply orphaned.');
        } finally {
            Cache::forget($keyBefore);
            $entity->col_has_lead = $original;
            $entity->save();
        }
    }

    /**
     * The previous design cached the same row under two keys that were written
     * and read independently, so they could disagree.
     */
    public function test_both_config_apis_see_the_same_values(): void
    {
        $entity = Entity::where('resource_slug', 'blogs')->first();

        if (!$entity) {
            $this->markTestSkipped('No "blogs" entity in the current database.');
        }

        $fromRegistry = $this->registry()->get('blogs');
        $fromResource = \Lara\App\Filament\Resources\Blogs\BlogResource::getEntity();

        $this->assertSame($fromRegistry->id, (int) $fromResource->id);
        $this->assertSame($fromRegistry->content->hasLead, (bool) $fromResource->col_has_lead);

        $original = $entity->col_has_lead;
        $entity->col_has_lead = !$original;
        $entity->save();

        try {
            $this->assertSame(
                app(EntityRegistry::class)->get('blogs')->content->hasLead,
                (bool) \Lara\App\Filament\Resources\Blogs\BlogResource::getEntity()->col_has_lead,
                'The frontend and admin views of entity config must not diverge.'
            );
        } finally {
            $entity->col_has_lead = $original;
            $entity->save();
        }
    }
}
