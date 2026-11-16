<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\Verifier;
use Illuminate\Console\Command;
use Throwable;

/**
 * Checks a finished migration and changes nothing (Verifier): counts per content type and
 * language, whether every import step is in sync with v8, media files and links.
 */
class VerifyCommand extends Command
{
    protected $signature = 'lara:v8:verify
        {--only=* : Run only these checks: counts, sync, media, links, tags}';

    protected $description = 'Verify the v8 migration: counts, sync with v8, media files and links';

    private const CHECKS = ['counts', 'sync', 'media', 'links', 'tags'];

    public function handle(Verifier $verifier): int
    {
        $only = array_values(array_filter((array) $this->option('only')));
        $unknown = array_diff($only, self::CHECKS);

        if ($unknown !== []) {
            $this->error('Unknown check(s): '.implode(', ', $unknown).'. Choose from: '.implode(', ', self::CHECKS).'.');

            return self::FAILURE;
        }

        if ((in_array('sync', $only, true) || $only === []) && ! is_dir((string) config('v8-migration.media_path'))) {
            $this->error('The v8 media path (LARA_V8_MEDIA_PATH) is not set or does not exist; the sync check needs it.');

            return self::FAILURE;
        }

        try {
            $report = $verifier->run($only);
        } catch (Throwable $e) {
            $this->error('Verify stopped: '.$e->getMessage());

            return self::FAILURE;
        }

        $shown = $this->output->isVerbose() ? $report : array_values(array_filter($report, fn (array $line): bool => $line['level'] !== 'ok'));

        if ($shown !== []) {
            $this->table(['Object', 'Result', 'Message'], array_map(fn (array $line): array => [$line['entity'], $line['level'], $line['message']], $shown));
        }

        $counts = array_count_values(array_column($report, 'level'));
        ksort($counts);
        $this->line(collect($counts)->map(fn (int $n, string $level): string => "{$level} {$n}")->implode(', '));

        return ($counts['error'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
