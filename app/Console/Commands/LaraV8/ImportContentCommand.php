<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\ContentImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Imports the records of every v8 content type (ContentImporter), keeping the v8 ids.
 * Rerunnable: it only writes rows that differ from v8.
 */
class ImportContentCommand extends Command
{
    protected $signature = 'lara:v8:import-content
        {--entity= : Comma separated v8 entity keys, e.g. page,blog}
        {--dry-run : Report what would change, change nothing}
        {--fresh : Also remove v10 rows that v8 does not have (e.g. the pages of the essential seed)}';

    protected $description = 'Import the records of every v8 content type (pages, entities, blocks, forms)';

    /**
     * Levels that are shown without --verbose.
     */
    private const SHOWN = ['create', 'update', 'differs', 'warning', 'error'];

    public function handle(ContentImporter $importer): int
    {
        $only = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('entity')))));
        $dryRun = (bool) $this->option('dry-run');

        try {
            $report = $importer->run($only, $dryRun, (bool) $this->option('fresh'));
        } catch (Throwable $e) {
            $this->error('Import stopped: '.$e->getMessage());

            return self::FAILURE;
        }

        $shown = $this->output->isVerbose() ? $report : array_values(array_filter($report, fn (array $line): bool => in_array($line['level'], self::SHOWN, true)));

        $this->table(['Entity', 'Result', 'Message'], array_map(fn (array $line): array => [$line['entity'], $line['level'], $line['message']], $shown));

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
