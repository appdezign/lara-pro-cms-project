<?php

namespace Tests\Feature\Legacy;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\App\Legacy\Models\CustomBlog;
use Lara\App\Models\Blog;
use Lara\Common\Models\User;
use Tests\TestCase;

/**
 * The custom blog controller is the example of a legacy (non-Livewire) admin
 * controller inside the Filament panel. It works on its own model and table (CustomBlog),
 * separate from the Blog entity. Its routes are registered by the panel
 * (LegacyNavigation, listed in lara-admin.panel_extensions), so they get the panel's auth middleware.
 */
class CustomBlogControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_the_login(): void
    {
        $blog = CustomBlog::factory()->create();

        $this->get(route('filament.admin.custom-blog.index'))->assertRedirect();
        $this->get(route('filament.admin.custom-blog.edit', $blog))->assertRedirect();
        $this->patch(route('filament.admin.custom-blog.update', $blog), ['title' => 'Changed', 'publish' => 1])->assertRedirect();

        $this->assertNotSame('Changed', $blog->fresh()->title);
    }

    public function test_pages_render_for_an_admin(): void
    {
        $this->actingAsAdmin();

        $blog = CustomBlog::factory()->create();

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

        $this->assertFalse(CustomBlog::where('title', 'Guest blog')->exists());
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

        $blog = CustomBlog::where('title', 'A new custom blog')->firstOrFail();

        $this->assertSame('nl', $blog->language);
        $this->assertSame(auth()->id(), $blog->user_id);
        $this->assertTrue($blog->publish);
    }

    public function test_store_redirects_to_the_edit_page(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('filament.admin.custom-blog.store'), ['title' => 'Redirect check', 'publish' => 0]);

        $blog = CustomBlog::where('title', 'Redirect check')->firstOrFail();

        $response->assertRedirect(route('filament.admin.custom-blog.edit', $blog));
    }

    public function test_store_shows_validation_errors_on_the_form(): void
    {
        $this->actingAsAdmin();

        $create = route('filament.admin.custom-blog.create');
        $count = CustomBlog::count();

        $this->from($create)
            ->post(route('filament.admin.custom-blog.store'), ['title' => '', 'publish' => 1])
            ->assertRedirect($create)
            ->assertSessionHasErrors('title');

        $this->assertSame($count, CustomBlog::count());
        $this->get($create)->assertOk()->assertSee('fi-fo-field-wrp-error-message', false);
    }

    public function test_update_saves_the_validated_fields(): void
    {
        $this->actingAsAdmin();

        $blog = CustomBlog::factory()->create(['publish' => 0]);

        $this->patch(route('filament.admin.custom-blog.update', $blog), [
            'title' => 'Updated title',
            'publish' => 1,
            'language' => 'en',
        ])->assertRedirect(route('filament.admin.custom-blog.edit', $blog));

        $blog->refresh();

        $this->assertSame('Updated title', $blog->title);
        $this->assertTrue($blog->publish);
        $this->assertSame('nl', $blog->language);
    }

    public function test_store_saves_the_body(): void
    {
        $this->actingAsAdmin();

        $this->post(route('filament.admin.custom-blog.store'), [
            'title' => 'A blog with a body',
            'publish' => 1,
            'body' => 'First paragraph',
        ]);

        $this->assertSame('First paragraph', CustomBlog::where('title', 'A blog with a body')->firstOrFail()->body);
    }

    public function test_update_replaces_the_body(): void
    {
        $this->actingAsAdmin();

        $blog = CustomBlog::factory()->create(['body' => 'Old body']);

        $this->patch(route('filament.admin.custom-blog.update', $blog), [
            'title' => $blog->title,
            'publish' => 1,
            'body' => 'Changed body',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Changed body', $blog->fresh()->body);
    }

    public function test_an_empty_body_is_stored_as_null(): void
    {
        $this->actingAsAdmin();

        $blog = CustomBlog::factory()->create(['body' => 'Old body']);

        $this->patch(route('filament.admin.custom-blog.update', $blog), [
            'title' => $blog->title,
            'publish' => 1,
            'body' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($blog->fresh()->body);
    }

    public function test_a_too_long_body_is_rejected(): void
    {
        $this->actingAsAdmin();

        $blog = CustomBlog::factory()->create(['body' => 'Old body']);
        $edit = route('filament.admin.custom-blog.edit', $blog);

        $this->from($edit)
            ->patch(route('filament.admin.custom-blog.update', $blog), [
                'title' => $blog->title,
                'publish' => 1,
                'body' => str_repeat('a', 65536),
            ])
            ->assertRedirect($edit)
            ->assertSessionHasErrors('body');

        $this->assertSame('Old body', $blog->fresh()->body);

        $this->get($edit)->assertOk()
            ->assertSee('fi-fo-field-wrp-error-message', false)
            ->assertSee(str_repeat('a', 65536), false)
            ->assertDontSee('Old body');
    }

    public function test_the_body_textarea_is_rendered(): void
    {
        $this->actingAsAdmin();

        $blog = CustomBlog::factory()->create(['body' => '<p>Hello</p>']);

        $this->get(route('filament.admin.custom-blog.create'))->assertOk()
            ->assertSee('<textarea', false)
            ->assertSee('name="body"', false);

        $this->get(route('filament.admin.custom-blog.edit', $blog))->assertOk()
            ->assertSee('name="body"', false)
            ->assertSee('&lt;p&gt;Hello&lt;/p&gt;</textarea>', false);
    }

    public function test_update_shows_validation_errors_on_the_form(): void
    {
        $this->actingAsAdmin();

        $blog = CustomBlog::factory()->create();
        $edit = route('filament.admin.custom-blog.edit', $blog);

        $this->from($edit)
            ->patch(route('filament.admin.custom-blog.update', $blog), ['title' => '', 'publish' => 1])
            ->assertRedirect($edit)
            ->assertSessionHasErrors('title');

        $this->get($edit)->assertOk()->assertSee('fi-fo-field-wrp-error-message', false);
    }

    public function test_store_does_not_create_a_blog_entity_record(): void
    {
        $this->actingAsAdmin();

        $this->post(route('filament.admin.custom-blog.store'), ['title' => 'Only a custom blog', 'publish' => 1]);

        $this->assertDatabaseHas('lara_custom_blogs', ['title' => 'Only a custom blog']);
        $this->assertDatabaseMissing('lara_content_blogs', ['title' => 'Only a custom blog']);
    }

    public function test_the_index_does_not_list_blog_entity_records(): void
    {
        $this->actingAsAdmin();

        Blog::factory()->create(['language' => 'nl', 'title' => 'An entity blog']);

        $this->get(route('filament.admin.custom-blog.index'))
            ->assertOk()
            ->assertDontSee('An entity blog');
    }

    public function test_store_generates_the_slug_from_the_title(): void
    {
        $this->actingAsAdmin();

        $this->post(route('filament.admin.custom-blog.store'), ['title' => 'My First Post', 'publish' => 1]);

        $this->assertSame('my-first-post', CustomBlog::where('title', 'My First Post')->firstOrFail()->slug);
    }

    public function test_the_slug_is_unique_across_languages(): void
    {
        $this->actingAsAdmin();

        CustomBlog::factory()->create(['language' => 'en', 'title' => 'My First Post']);

        $this->post(route('filament.admin.custom-blog.store'), ['title' => 'My First Post', 'publish' => 1]);

        $this->assertDatabaseHas('lara_custom_blogs', ['language' => 'en', 'slug' => 'my-first-post']);
        $this->assertDatabaseHas('lara_custom_blogs', ['language' => 'nl', 'slug' => 'my-first-post-2']);
    }

    public function test_update_keeps_the_slug_when_the_title_changes(): void
    {
        $this->actingAsAdmin();

        $blog = CustomBlog::factory()->create(['title' => 'My First Post']);

        $this->patch(route('filament.admin.custom-blog.update', $blog), ['title' => 'Renamed post', 'publish' => 1])
            ->assertSessionHasNoErrors();

        $blog->refresh();

        $this->assertSame('Renamed post', $blog->title);
        $this->assertSame('my-first-post', $blog->slug);
    }

    public function test_update_keeps_the_author(): void
    {
        $author = User::where('name', 'superadmin')->firstOrFail();
        $blog = CustomBlog::factory()->create(['user_id' => $author->id]);
        $this->actingAsAdmin();

        $this->patch(route('filament.admin.custom-blog.update', $blog), ['title' => $blog->title, 'publish' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame($author->id, $blog->fresh()->user_id);
    }

    public function test_a_user_without_custom_blog_permissions_gets_a_403_on_the_index(): void
    {
        $this->actingAsWebmaster();

        $this->get(route('filament.admin.custom-blog.index'))->assertForbidden();
    }

    public function test_a_user_without_update_permission_cannot_save_a_blog(): void
    {
        $blog = CustomBlog::factory()->create(['title' => 'Unchanged title']);
        $this->actingAsWebmaster();

        $this->patch(route('filament.admin.custom-blog.update', $blog), ['title' => 'Changed', 'publish' => 1])
            ->assertForbidden();

        $this->assertSame('Unchanged title', $blog->fresh()->title);
    }

    public function test_the_index_lists_only_the_chosen_content_language(): void
    {
        $this->actingAsAdmin();
        CustomBlog::factory()->create(['language' => 'nl', 'title' => 'A Dutch blog']);
        CustomBlog::factory()->create(['language' => 'en', 'title' => 'An English blog']);

        $this->get(route('filament.admin.custom-blog.index', ['clanguage' => 'en']))
            ->assertSee('An English blog')
            ->assertDontSee('A Dutch blog');
    }

    public function test_the_chosen_content_language_applies_to_the_next_request(): void
    {
        $this->actingAsAdmin();
        CustomBlog::factory()->create(['language' => 'nl', 'title' => 'A Dutch blog']);
        CustomBlog::factory()->create(['language' => 'en', 'title' => 'An English blog']);
        $this->get(route('filament.admin.custom-blog.index', ['clanguage' => 'en']));

        $this->get(route('filament.admin.custom-blog.index'))
            ->assertSee('An English blog')
            ->assertDontSee('A Dutch blog');
    }

    public function test_the_index_lists_the_default_content_language_without_a_choice(): void
    {
        $this->actingAsAdmin();
        CustomBlog::factory()->create(['language' => 'nl', 'title' => 'A Dutch blog']);
        CustomBlog::factory()->create(['language' => 'en', 'title' => 'An English blog']);

        $this->get(route('filament.admin.custom-blog.index'))
            ->assertSee('A Dutch blog')
            ->assertDontSee('An English blog');
    }

    public function test_store_saves_in_the_chosen_content_language(): void
    {
        $this->actingAsAdmin();

        $this->withSession(['_lara_global' => ['clanguage' => 'en']])
            ->post(route('filament.admin.custom-blog.store'), [
                'title' => 'Stored in English',
                'publish' => 1,
                'language' => 'nl',
            ]);

        $this->assertSame('en', CustomBlog::where('title', 'Stored in English')->firstOrFail()->language);
    }

    public function test_the_index_offers_a_switch_for_each_content_language(): void
    {
        $this->actingAsAdmin();

        $this->get(route('filament.admin.custom-blog.index'))
            ->assertSee('href="'.route('filament.admin.custom-blog.index', ['clanguage' => 'nl']).'"', false)
            ->assertSee('href="'.route('filament.admin.custom-blog.index', ['clanguage' => 'en']).'"', false);
    }

    public function test_store_removes_unsafe_html_from_the_body(): void
    {
        $this->actingAsAdmin();

        $this->post(route('filament.admin.custom-blog.store'), [
            'title' => 'Unsafe body',
            'publish' => 1,
            'body' => '<p onclick="steal()">Hi<script>alert(1)</script></p>',
        ]);

        $this->assertSame('<p>Hi</p>', CustomBlog::where('title', 'Unsafe body')->firstOrFail()->body);
    }

    public function test_store_removes_a_javascript_link_url(): void
    {
        $this->actingAsAdmin();

        $this->post(route('filament.admin.custom-blog.store'), [
            'title' => 'Unsafe link',
            'publish' => 1,
            'body' => '<p><a href="javascript:alert(1)">click</a></p>',
        ]);

        $body = CustomBlog::where('title', 'Unsafe link')->firstOrFail()->body;

        $this->assertStringContainsString('click', $body);
        $this->assertStringNotContainsString('javascript:', $body);
    }

    public function test_store_keeps_the_editor_formatting(): void
    {
        $this->actingAsAdmin();
        $body = '<h2>Title</h2><p><strong>Bold</strong> and <em>italic</em></p><ul><li><p>Item</p></li></ul><p><a href="https://example.com">link</a></p>';

        $this->post(route('filament.admin.custom-blog.store'), ['title' => 'Formatted body', 'publish' => 1, 'body' => $body]);

        $this->assertSame($body, CustomBlog::where('title', 'Formatted body')->firstOrFail()->body);
    }

    public function test_a_body_that_is_empty_after_sanitizing_is_stored_as_null(): void
    {
        $this->actingAsAdmin();

        $this->post(route('filament.admin.custom-blog.store'), [
            'title' => 'Only a script',
            'publish' => 1,
            'body' => '<script>alert(1)</script>',
        ])->assertSessionHasNoErrors();

        $this->assertNull(CustomBlog::where('title', 'Only a script')->firstOrFail()->body);
    }

    public function test_the_create_and_edit_forms_offer_the_rich_text_editor(): void
    {
        $this->actingAsAdmin();
        $blog = CustomBlog::factory()->create();

        foreach ([route('filament.admin.custom-blog.create'), route('filament.admin.custom-blog.edit', $blog)] as $url) {
            $this->get($url)
                ->assertSee('x-data="laraTiptapEditor()"', false)
                ->assertSee('class="fi-fo-rich-editor-toolbar"', false)
                ->assertSee('x-on:click="run(\'toggleBold\')"', false)
                ->assertSee('name="body"', false);
        }
    }

    private function actingAsAdmin(): void
    {
        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);
    }

    /**
     * A user with the webmaster role: it can enter the panel but has none of the *_customblog
     * permissions, so a 403 comes from CustomBlogPolicy and not from the panel.
     * The test database has no webmaster user, so one is created inside the transaction.
     */
    private function actingAsWebmaster(): void
    {
        $user = User::forceCreate([
            'name' => 'webmaster-test',
            'email' => 'webmaster-test@example.com',
            'locale' => 'nl',
            'password' => bcrypt('password'),
            'last_renew_password_at' => now(),
        ]);
        $user->assignRole('webmaster');

        $this->assertTrue($user->hasPanelAccess());
        $this->assertFalse($user->can('view_any_customblog'));

        $this->actingAs($user);
    }
}
