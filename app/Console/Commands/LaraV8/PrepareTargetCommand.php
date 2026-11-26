<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\DemoCode;
use App\Console\Commands\LaraV8\Support\DemoStandardTypes;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

/**
 * Turns a fresh v10 demo install into the essential base a v8 site is migrated onto:
 * removes the demo site code from laracms/app and rebuilds the database with the core
 * migrations and the essential seed (as the setup wizard's "essential" option intends).
 * The standard content types the v8 site lacks (blog, doc, gallery) stay as the demo has
 * them, without records (DemoStandardTypes).
 *
 * Destructive: it empties the site database. Never runs in production.
 */
class PrepareTargetCommand extends Command
{
    protected $signature = 'lara:v8:prepare-target
        {--force : Do not ask for confirmation}';

    protected $description = 'Prepare the v10 site for a v8 migration: remove the demo content types and rebuild an essential database';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This command empties the database and is not run in production.');

            return self::FAILURE;
        }

        $appPath = base_path('laracms/app');
        // the standard types the v8 site lacks, read from the demo install before migrate:fresh
        // empties the database; only a type found there keeps its code (a Filament resource
        // without its entity breaks the admin)
        $captured = DemoStandardTypes::capture(DemoStandardTypes::keys(new V8Source));
        $keys = array_map(fn (array $entity): string => (string) $entity['resource_slug'], $captured['entities']);
        $keep = DemoStandardTypes::files($appPath, DemoStandardTypes::keysOf($captured));
        $files = array_values(array_diff(DemoCode::files($appPath), $keep));
        $database = config('database.connections.'.config('database.default').'.database');

        $this->warn('This removes '.count($files).' demo file(s) from laracms/app ('.implode(', ', DemoCode::FOLDERS).')');
        $this->warn("and rebuilds the database '{$database}' with the core migrations and the essential seed. All its data is lost.");

        foreach ($captured['missing'] as $key) {
            $this->warn("Standard content type '{$key}' (not in v8) is not in this database (not a fresh demo install); it cannot be kept.");
        }

        if ($keys !== []) {
            $this->warn('It keeps the demo standard content type(s) '.implode(', ', $keys).', which the v8 site lacks ('.count($keep).' file(s), without records).');
        }

        if (! $this->option('force') && ! $this->confirm('Continue?')) {
            return self::FAILURE;
        }

        $removed = DemoCode::remove($appPath, $keep);
        $this->info("Removed {$removed} demo file(s).");

        // the optimized class map still lists the removed classes; class_exists() would then
        // include a file that is gone instead of returning false
        $this->line('composer dump-autoload');
        $composer = Process::path(base_path())->timeout(300)->run(['composer', 'dump-autoload', '--no-interaction', '--no-scripts']);

        if ($composer->failed()) {
            $this->error(trim($composer->errorOutput()));

            return self::FAILURE;
        }

        $steps = [
            ['migrate:fresh', ['--force' => true, '--path' => 'laracms/core/src/common/Database/Migrations']],
            ['db:seed', ['--force' => true, '--class' => '\\Lara\\Common\\Database\\Seeders\\DatabaseCommonSeeder']],
            ['optimize:clear', []],
        ];

        foreach ($steps as [$command, $arguments]) {
            $this->line("php artisan {$command}");

            if (Artisan::call($command, $arguments) !== self::SUCCESS) {
                $this->error(trim(Artisan::output()));

                return self::FAILURE;
            }
        }

        foreach (DemoStandardTypes::restore($captured, $appPath.'/Database/Migrations') as $line) {
            $this->info("Kept the demo standard content type {$line} (not in v8).");
        }

        if ($captured['entities'] !== []) {
            $this->line('php artisan optimize:clear');
            Artisan::call('optimize:clear');
        }

        $this->info("Essential base ready in '{$database}'. Next: php artisan lara:v8:import-entities");

        return self::SUCCESS;
    }
}
