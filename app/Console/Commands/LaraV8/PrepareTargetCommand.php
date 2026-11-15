<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\DemoCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

/**
 * Turns a fresh v10 demo install into the essential base a v8 site is migrated onto:
 * removes the demo site code from laracms/app and rebuilds the database with the core
 * migrations and the essential seed (as the setup wizard's "essential" option intends).
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
        $files = DemoCode::files($appPath);
        $database = config('database.connections.'.config('database.default').'.database');

        $this->warn('This removes '.count($files).' demo file(s) from laracms/app ('.implode(', ', DemoCode::FOLDERS).')');
        $this->warn("and rebuilds the database '{$database}' with the core migrations and the essential seed. All its data is lost.");

        if (! $this->option('force') && ! $this->confirm('Continue?')) {
            return self::FAILURE;
        }

        $removed = DemoCode::remove($appPath);
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

        $this->info("Essential base ready in '{$database}'. Next: php artisan lara:v8:import-entities");

        return self::SUCCESS;
    }
}
