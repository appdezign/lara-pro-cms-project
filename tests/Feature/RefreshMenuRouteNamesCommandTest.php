<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\Common\Models\MenuItem;
use Tests\TestCase;

/**
 * lara:menu:refresh-routenames brings the route name a menu item records in
 * line with MenuRouteName (menus saved before the naming changed recorded the
 * menu item ID in the name).
 */
class RefreshMenuRouteNamesCommandTest extends TestCase
{
    use DatabaseTransactions;

    private const OLD_NAME = 'entitytag.blogs.33.news.index';

    private const NEW_NAME = 'entitytag.blogs.news.index';

    public function test_a_dry_run_reports_an_outdated_route_name_and_changes_nothing(): void
    {
        $menuItem = $this->newsMenuItemWithOldName();

        $this->artisan('lara:menu:refresh-routenames', ['--dry-run' => true])
            ->expectsOutputToContain('1 menu items would change')
            ->assertSuccessful();

        $this->assertSame(self::OLD_NAME, $menuItem->fresh()->routename);
    }

    public function test_a_run_corrects_an_outdated_route_name(): void
    {
        $menuItem = $this->newsMenuItemWithOldName();

        $this->artisan('lara:menu:refresh-routenames')
            ->expectsOutputToContain('1 menu items updated')
            ->assertSuccessful();

        $this->assertSame(self::NEW_NAME, $menuItem->fresh()->routename);
    }

    public function test_menu_items_with_the_right_route_name_are_left_alone(): void
    {
        $this->artisan('lara:menu:refresh-routenames')
            ->expectsOutput('All menu items already record the right route name.')
            ->assertSuccessful();
    }

    /**
     * The Dutch news list of the seed menu ("news"), with the old name that contained its ID.
     */
    private function newsMenuItemWithOldName(): MenuItem
    {
        $menuItem = MenuItem::where('routename', self::NEW_NAME)->first();

        if (! $menuItem) {
            $this->markTestSkipped('No "news" blogs menu item in the current database.');
        }

        $menuItem->routename = self::OLD_NAME;
        $menuItem->saveQuietly();

        return $menuItem;
    }
}
