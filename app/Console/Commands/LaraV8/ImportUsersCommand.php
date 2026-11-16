<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\UserImporter;
use Illuminate\Console\Command;
use Throwable;

/**
 * Imports the v8 users, roles, role permissions and role assignments (UserImporter).
 * Rerunnable: it only changes what differs from v8.
 */
class ImportUsersCommand extends Command
{
    protected $signature = 'lara:v8:import-users
        {--dry-run : Report what would change, change nothing}
        {--prune : Remove v10 users that v8 does not have (their content is removed with them)}';

    protected $description = 'Import the v8 users, roles, role permissions and role assignments';

    /**
     * Levels that are shown without --verbose.
     */
    private const SHOWN = ['create', 'update', 'differs', 'warning', 'error'];

    public function handle(UserImporter $importer): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $report = $importer->run($dryRun, (bool) $this->option('prune'));
        } catch (Throwable $e) {
            $this->error('Import stopped: '.$e->getMessage());

            return self::FAILURE;
        }

        $shown = $this->output->isVerbose() ? $report : array_values(array_filter($report, fn (array $line): bool => in_array($line['level'], self::SHOWN, true)));

        $this->table(['Subject', 'Result', 'Message'], array_map(fn (array $line): array => [$line['entity'], $line['level'], $line['message']], $shown));

        $counts = array_count_values(array_column($report, 'level'));
        ksort($counts);
        $this->line(collect($counts)->map(fn (int $n, string $level): string => "{$level} {$n}")->implode(', ').($dryRun ? ' (dry run)' : ''));

        return ($counts['error'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
