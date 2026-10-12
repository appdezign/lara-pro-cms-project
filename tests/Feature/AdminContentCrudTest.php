<?php

namespace Tests\Feature;

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\App\Filament\Resources\Blogs\Pages\CreateBlog;
use Lara\App\Filament\Resources\Blogs\Pages\EditBlog;
use Lara\App\Filament\Resources\Blogs\Pages\ListBlogs;
use Lara\App\Models\Blog;
use Lara\Common\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Saving content through the admin. AdminSmokeTest only renders the pages;
 * these tests go through the Livewire forms of one representative resource
 * (Blog), since every content resource shares the same base pages and form.
 */
class AdminContentCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creating_a_blog_stores_it_in_the_content_language_for_the_current_user(): void
    {
        $admin = $this->actingAsAdmin();

        Livewire::test(CreateBlog::class)
            ->fillForm(['title' => 'Plan test blog'])
            ->call('create')
            ->assertHasNoFormErrors();

        $blog = Blog::where('title', 'Plan test blog')->sole();
        $this->assertSame('nl', $blog->language);
        $this->assertSame('plan-test-blog', $blog->slug);
        $this->assertSame($admin->id, (int) $blog->user_id);
    }

    public function test_a_blog_created_as_published_is_stored_as_published(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateBlog::class)
            ->fillForm(['title' => 'Plan test blog', 'publish' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, (int) Blog::where('title', 'Plan test blog')->sole()->publish);
    }

    public function test_a_blog_without_a_title_is_rejected(): void
    {
        $this->actingAsAdmin();
        $countBefore = Blog::withTrashed()->count();

        Livewire::test(CreateBlog::class)
            ->fillForm(['title' => ''])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);

        $this->assertSame($countBefore, Blog::withTrashed()->count());
    }

    public function test_editing_a_blog_saves_the_new_title(): void
    {
        $this->actingAsAdmin();
        $blog = Blog::factory()->create(['language' => 'nl']);

        Livewire::test(EditBlog::class, ['record' => $blog->id])
            ->fillForm(['title' => 'Changed title'])
            ->callAction('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Changed title', $blog->fresh()->title);
    }

    public function test_deleting_a_blog_from_the_list_moves_it_to_the_trash(): void
    {
        $this->actingAsAdmin();
        $blog = Blog::factory()->create(['language' => 'nl']);

        Livewire::test(ListBlogs::class)
            ->callAction(TestAction::make('delete')->table($blog));

        $this->assertSoftDeleted($blog);
    }

    private function actingAsAdmin(): User
    {
        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);
        Filament::setCurrentPanel('admin');

        return $user;
    }
}
