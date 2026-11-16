<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\ObjectImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Imports taxonomies, tags, taggables, pageables, SEO and layout (ObjectImporter).
 * Rerunnable: it only writes rows that differ from v8.
 */
class ImportObjectsCommand extends Command
{
    protected $signature = 'lara:v8:import-objects
        {--dry-run : Report what would change, change nothing}
        {--fresh : Also remove v10 rows that v8 does not have}';

    protected $description = 'Import the v8 taxonomies, tags, taggables, pageables, SEO and layout';

    /**
     * Levels that are shown without --verbose.
     */
    private const SHOWN = ['create', 'update', 'differs', 'warning', 'error'];

    public function handle(ObjectImporter $importer): int
    {
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
