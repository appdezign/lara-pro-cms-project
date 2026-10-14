<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Lara\Admin\Concerns\HasFilters;
use Lara\Admin\Resources\Tags\Pages\ListTags;
use Lara\Common\Models\Entity;
use Lara\Common\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HasFilters reads a list page's persisted filters straight from the session,
 * so its key must match the one Filament writes (it changed in Filament 5).
 */
class ActiveTableFilterTest extends TestCase
{
    public function test_a_filter_set_on_the_tag_list_is_read_back_from_the_session(): void
    {
        $user = User::where('name', 'admin')->first();
        $resourceSlug = Entity::where('objrel_has_terms', 1)->value('resource_slug');

        if (! $user || ! $resourceSlug) {
            $this->markTestSkipped('No "admin" user or entity with terms in the current database.');
        }

        $this->actingAs($user);
        Filament::setCurrentPanel('admin');

        Livewire::test(ListTags::class)
            ->filterTable('resource_slug', $resourceSlug);

        $reader = new class
        {
            use HasFilters;

            public static function read(string $filterName): ?string
            {
                return self::getActiveFilterFromPage(ListTags::class, $filterName, 'fallback');
            }
        };

        $this->assertSame($resourceSlug, $reader::read('resource_slug'));
        $this->assertSame('fallback', $reader::read('taxonomy_id'));
    }
}
