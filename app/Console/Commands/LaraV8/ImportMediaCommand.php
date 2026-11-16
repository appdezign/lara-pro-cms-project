<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\MediaImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Imports the v8 images, files, videos and video files (MediaImporter) and copies the files
 * from the v8 media path (LARA_V8_MEDIA_PATH). Rerunnable: only missing or changed files are copied.
 */
class ImportMediaCommand extends Command
{
    protected $signature = 'lara:v8:import-media
        {--dry-run : Report what would change, copy and write nothing}
        {--fresh : Also remove v10 media links and lists that v8 does not have (Curator items and files stay)}';

    protected $description = 'Import the v8 images, files, videos and video files, and copy the files';

    /**
     * Levels that are shown without --verbose.
     */
    private const SHOWN = ['create', 'update', 'differs', 'warning', 'error'];

    public function handle(MediaImporter $importer): int
    {
        if (! is_dir((string) config('v8-migration.media_path'))) {
            $this->error('The v8 media path (LARA_V8_MEDIA_PATH) is not set or does not exist.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        try {
            $report = $importer->run($dryRun, (bool) $this->option('fresh'));
        } catch (Throwable $e) {
            $this->error('Import stopped: '.$e->getMessage());

            return self::FAILURE;
        }

        $shown = $this->output->isVerbose() ? $report : array_values(array_filter($report, fn (array $line): bool => in_array($line['level'], self::SHOWN, true)));

        $this->table(['Object', 'Result', 'Message'], array_map(fn (array $line): array => [$line['entity'], $line['level'], $line['message']], $shown));

        $counts = array_count_values(array_column($report, 'level'));
        ksort($counts);
        $this->line(collect($counts)->map(fn (int $n, string $level): string => "{$level} {$n}")->implode(', ').($dryRun ? ' (dry run)' : ''));

        if (! $dryRun && (($counts['create'] ?? 0) + ($counts['update'] ?? 0)) > 0) {
            Artisan::call('optimize:clear');
            $this->info('Caches cleared.');
        }

        return ($counts['error'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
