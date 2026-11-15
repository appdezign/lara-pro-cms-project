<?php

namespace App\Console\Commands\LaraV8\Support;

use Filament\Notifications\NotificationMailbox;

/**
 * Collects the Filament notifications the admin builder sends (failures, warnings,
 * "relation not found in model"), so a console command can report them instead
 * of losing them in a session nobody reads.
 */
class RecordingNotificationMailbox extends NotificationMailbox
{
    /** @var list<array<string, mixed>> */
    private array $recorded = [];

    /** @param array<string, mixed> $notification */
    public function push(array $notification): void
    {
        $this->recorded[] = $notification;
    }

    /**
     * The recorded notifications as plain text with their status (success, warning,
     * danger, info), oldest first; empties the recording.
     *
     * @return list<array{status: string, message: string}>
     */
    public function takeNotifications(): array
    {
        $notifications = array_map(function (array $notification): array {
            $body = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string) ($notification['body'] ?? ''))));

            return [
                'status' => (string) ($notification['status'] ?? 'info'),
                'message' => trim(($notification['title'] ?? '').($body !== '' ? ': '.html_entity_decode($body) : '')),
            ];
        }, $this->recorded);

        $this->recorded = [];

        return $notifications;
    }

    /**
     * The recorded messages as plain text, oldest first; empties the recording.
     *
     * @return list<string>
     */
    public function takeMessages(): array
    {
        return array_column($this->takeNotifications(), 'message');
    }
}
