<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\App\Models\Blog;
use Lara\Common\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Who may open the admin pages of a content resource.
 *
 * Panel access comes from the role (has_panel_access), resource access from
 * the Spatie permissions the role screen grants (view_any_blog, create_blog,
 * update_blog). Blog stands in for every resource: they share HasBasePolicy.
 */
class AdminAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{string}>
     */
    public static function blogPages(): array
    {
        return [
            'index' => ['index'],
            'create' => ['create'],
            'edit' => ['edit'],
        ];
    }

    #[DataProvider('blogPages')]
    public function test_a_guest_is_redirected_to_the_login(string $page): void
    {
        $response = $this->get($this->blogPageUrl($page));

        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    #[DataProvider('blogPages')]
    public function test_a_user_without_panel_access_is_forbidden(string $page): void
    {
        $this->actingAs($this->userWithRole('member'));

        $response = $this->get($this->blogPageUrl($page));

        $response->assertForbidden();
    }

    #[DataProvider('blogPages')]
    public function test_a_panel_user_without_blog_permissions_is_forbidden(string $page): void
    {
        $this->actingAs($this->userWithRole($this->panelRole([])->name));

        $response = $this->get($this->blogPageUrl($page));

        $response->assertForbidden();
    }

    public function test_view_permission_alone_does_not_allow_creating_or_editing(): void
    {
        $this->actingAs($this->userWithRole($this->panelRole(['view_any_blog', 'view_blog'])->name));

        $this->get($this->blogPageUrl('index'))->assertOk();
        $this->get($this->blogPageUrl('create'))->assertForbidden();
        $this->get($this->blogPageUrl('edit'))->assertForbidden();
    }

    #[DataProvider('blogPages')]
    public function test_an_administrator_can_open_the_page(string $page): void
    {
        $this->actingAs($this->userWithRole('administrator'));

        $response = $this->get($this->blogPageUrl($page));

        $response->assertOk();
    }

    private function blogPageUrl(string $page): string
    {
        if ($page === 'edit') {
            return route('filament.admin.resources.blogs.edit', Blog::factory()->create(['language' => 'nl']));
        }

        return route('filament.admin.resources.blogs.'.$page);
    }

    private function userWithRole(string $role): User
    {
        $user = User::forceCreate([
            'name' => 'authz-test',
            'email' => 'authz-test@example.com',
            'locale' => 'nl',
            'password' => bcrypt('password'),
            'last_renew_password_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function panelRole(array $permissions): Role
    {
        $role = Role::create(['name' => 'authz-test-role', 'guard_name' => 'web']);
        $role->forceFill(['has_panel_access' => 1])->save();
        $role->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }
}
