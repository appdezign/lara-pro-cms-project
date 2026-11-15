<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\EntityImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Builds the site's own content types from the v8 configuration (EntityImporter).
 * Run lara:v8:prepare-target first; rerun at any time, it only creates what is missing.
 */
class ImportEntitiesCommand extends Command
{
    protected $signature = 'lara:v8:import-entities
        {--only= : Comma separated v8 entity keys, e.g. blog,team}
        {--dry-run : Report what would change, change nothing}
        {--update : Apply differences to entities, fields and views that already exist}';

    protected $description = 'Build the site content types (code, table, fields, relations, views) from the v8 configuration';

    /**
     * Levels that are shown without --verbose.
     */
    private const SHOWN = ['create', 'update', 'differs', 'warning', 'error', 'todo', 'builder'];

    public function handle(EntityImporter $importer): int
    {
        $only = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('only')))));
        $dryRun = (bool) $this->option('dry-run');

        try {
            $report = $importer->run($only, $dryRun, (bool) $this->option('update'));
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
            $this->info('Caches cleared. Build the routes with: php artisan lara:route:cache');
        }

        return ($counts['error'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
