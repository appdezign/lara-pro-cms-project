<?php

namespace Tests\Feature;

use Lara\Common\Models\Entity;
use Lara\Common\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The entity builder's edit page opens for every kind of entity.
 *
 * The Views and Relations tabs embed relation managers that allow only some
 * cgroups (EntityViewsRelationManager: entity and page; EntRelRelationManager:
 * entity). Filament aborts with 403 when a refused relation manager is
 * mounted, so those tabs must not render for the other cgroups. The "base"
 * entity has no content table, so its Backup columns list must stay empty.
 */
class AdminEntityEditTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function cgroups(): array
    {
        return [
            'entity' => ['entity'],
            'page' => ['page'],
            'base' => ['base'],
            'block' => ['block'],
            'form' => ['form'],
            'taxonomy' => ['taxonomy'],
        ];
    }

    #[DataProvider('cgroups')]
    public function test_a_superadmin_can_open_the_edit_page(string $cgroup): void
    {
        $entity = Entity::where('cgroup', $cgroup)->orderBy('id')->first();

        if (! $entity) {
            $this->markTestSkipped("No entity with cgroup \"{$cgroup}\" in the test database.");
        }

        $this->actingAs($this->superadmin());

        $response = $this->get(route('filament.admin.resources.entities.edit', $entity));

        $response->assertOk();
    }

    private function superadmin(): User
    {
        return User::role('superadmin')->firstOrFail();
    }
}
