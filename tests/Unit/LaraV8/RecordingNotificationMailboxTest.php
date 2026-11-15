<?php

namespace Tests\Unit\LaraV8;

use App\Console\Commands\LaraV8\Support\RecordingNotificationMailbox;
use PHPUnit\Framework\TestCase;

class RecordingNotificationMailboxTest extends TestCase
{
    public function test_notifications_become_plain_text_with_their_status(): void
    {
        $mailbox = new RecordingNotificationMailbox;
        $mailbox->push(['title' => 'New column created: url', 'status' => 'success']);
        $mailbox->push(['title' => 'Relation not found in model !', 'body' => 'Method: \'events\'<br><br>Add it &amp; save.', 'status' => 'danger']);

        $this->assertSame([
            ['status' => 'success', 'message' => 'New column created: url'],
            ['status' => 'danger', 'message' => "Relation not found in model !: Method: 'events'  Add it & save."],
        ], $mailbox->takeNotifications());
    }

    public function test_taking_empties_the_recording(): void
    {
        $mailbox = new RecordingNotificationMailbox;
        $mailbox->push(['title' => 'One']);

        $this->assertSame(['One'], $mailbox->takeMessages());
        $this->assertSame([], $mailbox->takeMessages());
    }
}
