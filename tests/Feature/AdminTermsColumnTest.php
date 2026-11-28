<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Lara\App\Filament\Resources\Blogs\Pages\ListBlogs;
use Lara\App\Models\Blog;
use Lara\Common\Models\Tag;
use Lara\Common\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The terms column of a content list (HasBaseTable) shows the first term only, with ".."
 * when there are more, and all of them in the tooltip. Joining every term made the column so
 * wide on a site with many or long tags (gain's news) that the actions fell off the table.
 */
class AdminTermsColumnTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_record_with_several_terms_shows_the_first_and_a_marker(): void
    {
        $this->actingAsAdmin();
        $tags = $this->tags(2);
        $blog = Blog::factory()->create(['language' => 'nl']);
        $blog->terms()->attach($tags->pluck('id'));
        $first = $blog->terms()->first()->title;

        Livewire::test(ListBlogs::class)
            ->assertTableColumnStateSet('terms', $first.' ..', $blog)
            ->assertTableColumnStateNotSet('terms', $blog->terms->pluck('title')->implode(', '), $blog);
    }

    public function test_a_record_with_one_term_shows_it_as_it_is(): void
    {
        $this->actingAsAdmin();
        $tag = $this->tags(1)->first();
        $blog = Blog::factory()->create(['language' => 'nl']);
        $blog->terms()->attach($tag->id);

        Livewire::test(ListBlogs::class)
            ->assertTableColumnStateSet('terms', $tag->title, $blog);
    }

    /**
     * @return Collection<int, Tag>
     */
    private function tags(int $count)
    {
        $tags = Tag::where('resource_slug', 'blogs')->where('language', 'nl')->take($count)->get();

        if ($tags->count() < $count) {
            $this->markTestSkipped("Fewer than {$count} blog tags in the test database.");
        }

        return $tags;
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
