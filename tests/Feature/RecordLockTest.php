<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\App\Models\Blog;
use Lara\Common\Models\User;
use Tests\TestCase;

/**
 * A record that a user opens in the admin is locked for the other users until
 * it is unlocked (HasLaraLocks, used by every content model through BaseModel).
 */
class RecordLockTest extends TestCase
{
    use DatabaseTransactions;

    public function test_locking_records_who_locked_the_record_and_when(): void
    {
        $this->freezeSecond();
        $editor = $this->user('admin');
        $this->actingAs($editor);
        $blog = Blog::factory()->create(['language' => 'nl']);

        $blog->lockRecord();

        $blog->refresh();
        $this->assertSame($editor->id, (int) $blog->locked_by);
        $this->assertSame(now()->toDateTimeString(), (string) $blog->locked_at);
    }

    public function test_a_locked_record_is_locked_for_other_users_only(): void
    {
        $this->actingAs($this->user('admin'));
        $blog = Blog::factory()->create(['language' => 'nl']);
        $blog->lockRecord();

        $this->assertFalse($blog->isLocked());

        $this->actingAs($this->user('superadmin'));
        $this->assertTrue($blog->isLocked());
    }

    public function test_an_unlocked_record_is_open_for_everyone(): void
    {
        $this->actingAs($this->user('admin'));
        $blog = Blog::factory()->create(['language' => 'nl']);
        $blog->lockRecord();

        $blog->unlockRecord();

        $this->actingAs($this->user('superadmin'));
        $this->assertFalse($blog->fresh()->isLocked());
        $this->assertNull($blog->fresh()->locked_by);
    }

    private function user(string $name): User
    {
        $user = User::where('name', $name)->first();

        if (! $user) {
            $this->markTestSkipped('No "'.$name.'" user in the current database.');
        }

        return $user;
    }
}
