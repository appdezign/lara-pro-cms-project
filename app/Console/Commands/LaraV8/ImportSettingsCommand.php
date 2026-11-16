<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\SystemImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Imports the v8 settings, languages, blacklist and translations (SystemImporter)
 * and exports the translations to lang/vendor. Rerunnable.
 */
class ImportSettingsCommand extends Command
{
    protected $signature = 'lara:v8:import-settings
        {--dry-run : Report what would change, change nothing}
        {--fresh : Also remove v10 languages and blacklist entries that v8 does not have (settings and translations are never removed)}';

    protected $description = 'Import the v8 settings, languages, blacklist and translations';

    /**
     * Levels that are shown without --verbose.
     */
    private const SHOWN = ['create', 'update', 'differs', 'warning', 'error'];

    public function handle(SystemImporter $importer): int
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
