<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Lara\Common\Models\Entity;
use Lara\Common\Models\User;
use Tests\TestCase;

/**
 * Renders every registered admin resource page as an authenticated admin.
 *
 * This is the broadest available check that entity configuration still resolves:
 * the index/create/edit pages go through HasLaraEntity, LaraBaseTable and
 * LaraBaseForm, which read config via EntityRegistry and cache values derived
 * from it. A 403 is an expected permission outcome, not a failure.
 */
class AdminSmokeTest extends TestCase
{
    public function test_admin_resource_pages_render(): void
    {
        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);

        $checked = 0;
        $failures = [];

        foreach (Entity::orderBy('resource_slug')->get() as $entity) {
            foreach (['index', 'create'] as $page) {
                $name = 'filament.admin.resources.'.$entity->resource_slug.'.'.$page;

                if (! Route::has($name)) {
                    continue;
                }

                $checked++;

                try {
                    $status = $this->get(route($name))->status();

                    if (! in_array($status, [200, 403], true)) {
                        $failures[] = $entity->resource_slug.'.'.$page.' => HTTP '.$status;
                    }
                } catch (\Throwable $e) {
                    $failures[] = $entity->resource_slug.'.'.$page.' => '
                        .class_basename($e).': '.substr($e->getMessage(), 0, 160);
                }
            }
        }

        fwrite(STDERR, "\n  rendered {$checked} admin pages\n");

        $this->assertSame([], $failures);
    }
}
