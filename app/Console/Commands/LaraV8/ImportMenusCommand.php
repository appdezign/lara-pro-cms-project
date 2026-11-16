<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\MenuImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Imports the v8 menus, menu items and redirects (MenuImporter), then rebuilds the
 * route names (lara:menu:refresh-routenames) and the route cache (lara:route:cache).
 */
class ImportMenusCommand extends Command
{
    protected $signature = 'lara:v8:import-menus
        {--dry-run : Report what would change, change nothing}
        {--fresh : Also remove v10 menus, menu items and redirects that v8 does not have}
        {--no-routes : Do not rebuild the route names and the route cache}';

    protected $description = 'Import the v8 menus, menu items and redirects, then rebuild the route names and the route cache';

    /**
     * Levels that are shown without --verbose.
     */
    private const SHOWN = ['create', 'update', 'differs', 'warning', 'error'];

    public function handle(MenuImporter $importer): int
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

        if (($counts['error'] ?? 0) > 0) {
            return self::FAILURE;
        }

        if ($dryRun || $this->option('no-routes')) {
            return self::SUCCESS;
        }

        // the imported route names are the v8 ones; v10 names menu routes after their URL
        Artisan::call('optimize:clear');

        foreach (['lara:menu:refresh-routenames', 'lara:route:cache'] as $command) {
            $this->line("php artisan {$command}");

            if (Artisan::call($command) !== self::SUCCESS) {
                $this->error(trim(Artisan::output()));

                return self::FAILURE;
            }

            $this->line(collect(explode("\n", trim(Artisan::output())))->take(-3)->implode("\n"));
        }

        return self::SUCCESS;
    }
}
