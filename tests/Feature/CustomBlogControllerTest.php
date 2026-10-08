<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\App\Models\Blog;
use Lara\Common\Models\User;
use Tests\TestCase;

/**
 * The custom blog controller is the example of a legacy (non-Livewire) admin
 * controller inside the Filament panel. Its routes are registered by the panel
 * (HasCustomNavigation::getCustomRoutes()), so they get the panel's auth middleware.
 */
class CustomBlogControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_guests_are_redirected_to_the_login(): void
    {
        $blog = Blog::factory()->create(['language' => 'nl']);

        $this->get(route('filament.admin.custom-blog.index'))->assertRedirect();
        $this->get(route('filament.admin.custom-blog.edit', $blog))->assertRedirect();
        $this->patch(route('filament.admin.custom-blog.update', $blog), ['title' => 'Changed', 'publish' => 1])->assertRedirect();

        $this->assertNotSame('Changed', $blog->fresh()->title);
    }

    public function test_pages_render_for_an_admin(): void
    {
        $this->actingAsAdmin();

        $blog = Blog::factory()->create(['language' => 'nl']);

        $this->get(route('filament.admin.custom-blog.index'))->assertOk()->assertSee($blog->title);
        $this->get(route('filament.admin.custom-blog.show', $blog))->assertOk()->assertSee($blog->title);
        $this->get(route('filament.admin.custom-blog.edit', $blog))->assertOk()
            ->assertSee('name="title"', false)
            ->assertSee('form="lara-default-edit-form"', false);
    }

    public function test_guests_cannot_create_a_blog(): void
    {
        $this->get(route('filament.admin.custom-blog.create'))->assertRedirect();
        $this->post(route('filament.admin.custom-blog.store'), ['title' => 'Guest blog', 'publish' => 1])->assertRedirect();

        $this->assertFalse(Blog::where('title', 'Guest blog')->exists());
    }

    public function test_create_page_renders_for_an_admin(): void
    {
        $this->actingAsAdmin();

        $this->get(route('filament.admin.custom-blog.create'))
            ->assertOk()
            ->assertSee('action="'.route('filament.admin.custom-blog.store').'"', false)
            ->assertSee('form="lara-default-create-form"', false);
    }

    public function test_store_creates_a_blog_with_the_standard_columns(): void
    {
        $this->actingAsAdmin();

        $this->post(route('filament.admin.custom-blog.store'), [
            'title' => 'A new custom blog',
            'publish' => 1,
            'language' => 'en',
        ]);

        $blog = Blog::where('title', 'A new custom blog')->firstOrFail();

        $this->assertSame('nl', $blog->language);
        $this->assertSame(auth()->id(), $blog->user_id);
        $this->assertEquals(1, $blog->publish);
        $this->assertNotNull($blog->publish_from);
        $this->assertNotEmpty($blog->slug);
        $this->assertSessionHasLaraCacheClear();
    }

    public function test_store_redirects_to_the_edit_page(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('filament.admin.custom-blog.store'), ['title' => 'Redirect check', 'publish' => 0]);

        $blog = Blog::where('title', 'Redirect check')->firstOrFail();

        $response->assertRedirect(route('filament.admin.custom-blog.edit', $blog));
    }

    public function test_store_shows_validation_errors_on_the_form(): void
    {
        $this->actingAsAdmin();

        $create = route('filament.admin.custom-blog.create');
        $count = Blog::count();

        $this->from($create)
            ->post(route('filament.admin.custom-blog.store'), ['title' => '', 'publish' => 1])
            ->assertRedirect($create)
            ->assertSessionHasErrors('title');

        $this->assertSame($count, Blog::count());
        $this->get($create)->assertOk()->assertSee('fi-fo-field-wrp-error-message', false);
    }

    public function test_update_saves_the_validated_fields(): void
    {
        $this->actingAsAdmin();

        $blog = Blog::factory()->create(['language' => 'nl', 'publish' => 0]);

        $this->patch(route('filament.admin.custom-blog.update', $blog), [
            'title' => 'Updated title',
            'publish' => 1,
            'language' => 'en',
        ])->assertRedirect(route('filament.admin.custom-blog.edit', $blog));

        $blog->refresh();

        $this->assertSame('Updated title', $blog->title);
        $this->assertEquals(1, $blog->publish);
        $this->assertSame('nl', $blog->language);
    }

    public function test_update_shows_validation_errors_on_the_form(): void
    {
        $this->actingAsAdmin();

        $blog = Blog::factory()->create(['language' => 'nl']);
        $edit = route('filament.admin.custom-blog.edit', $blog);

        $this->from($edit)
            ->patch(route('filament.admin.custom-blog.update', $blog), ['title' => '', 'publish' => 1])
            ->assertRedirect($edit)
            ->assertSessionHasErrors('title');

        $this->get($edit)->assertOk()->assertSee('fi-fo-field-wrp-error-message', false);
    }

    private function assertSessionHasLaraCacheClear(): void
    {
        $this->assertSame(['response_cache', 'route_cache'], session('laracacheclear'));
    }

    private function actingAsAdmin(): void
    {
        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);
    }
}
