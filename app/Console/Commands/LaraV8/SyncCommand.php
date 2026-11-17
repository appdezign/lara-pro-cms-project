<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\SiteOverrides;
use App\Console\Commands\LaraV8\Support\V8Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Throwable;

/**
 * The final sync: brings an imported v10 site up to date with v8 by running every import
 * step in order, each with --fresh (v10 rows that v8 no longer has are removed), then the URL
 * rewrite and lara:v8:verify. The site overrides (v8-overrides.php) are applied by the imports.
 * A step that reports an error stops the sync: the later --fresh steps would remove v10 rows
 * (e.g. the media links of a content type whose records were not imported). A dry run runs on.
 *
 * Before it: load a fresh dump of the production v8 database into the lara_v8 connection and
 * rsync the production media to LARA_V8_MEDIA_PATH (see the runbook).
 */
class SyncCommand extends Command
{
    protected $signature = 'lara:v8:sync
        {--dry-run : Run every step as a dry run: report, change nothing}
        {--force : Run in production without asking}';

    protected $description = 'Final sync: run every v8 import step in order with --fresh, rewrite the URLs and verify';

    /**
     * The steps in order: command => options (besides --dry-run).
     * Not included on purpose: entity --update (regenerates code; run it by hand after a
     * reported difference) and users --prune (would remove users created in v10).
     */
    private const STEPS = [
        'lara:v8:import-entities' => [],
        'lara:v8:import-users' => [],
        'lara:v8:import-content' => ['--fresh' => true],
        'lara:v8:import-objects' => ['--fresh' => true],
        'lara:v8:import-menus' => ['--fresh' => true],
        'lara:v8:import-settings' => ['--fresh' => true],
        'lara:v8:import-media' => ['--fresh' => true],
        'lara:v8:rewrite-urls' => [],
    ];

    public function handle(V8Source $source): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && App::isProduction() && ! $this->option('force') && ! $this->confirm('This is production. Sync from v8 and remove v10 rows that v8 does not have?')) {
            return self::FAILURE;
        }

        if (! is_dir((string) config('v8-migration.media_path'))) {
            $this->error('The v8 media path (LARA_V8_MEDIA_PATH) is not set or does not exist.');

            return self::FAILURE;
        }

        try {
            $latest = $this->latestChange($source);
        } catch (Throwable $e) {
            $this->error('The v8 database cannot be read: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('v8 database: '.$source->connection()->getDatabaseName().($latest ? ", latest change {$latest}" : '').' — make sure it is a fresh dump of production.');
        $this->info('Site overrides: '.count(SiteOverrides::all()).' ('.SiteOverrides::file().').');

        $failed = [];
        $skipped = [];

        foreach (self::STEPS as $command => $options) {
            if ($failed !== [] && ! $dryRun) {
                $skipped[] = $command;

                continue;
            }

            $this->newLine();
            $this->line("<comment>▶ {$command}</comment>".($dryRun ? ' (dry run)' : ''));

            if ($this->call($command, $options + ($dryRun ? ['--dry-run' => true] : [])) !== self::SUCCESS) {
                $failed[] = $command;
            }
        }

        if ($skipped !== []) {
            $this->newLine();
            $this->error("Stopped after the error in {$failed[0]}; not run: ".implode(', ', $skipped).'. Fix the error and run the sync again.');
        }

        $this->newLine();
        $this->line('<comment>▶ lara:v8:verify</comment>');
        // after a dry run v10 is unchanged, so verify shows what the sync would still change
        if ($this->call('lara:v8:verify') !== self::SUCCESS) {
            $failed[] = 'lara:v8:verify';
        }

        $this->newLine();

        if ($failed !== []) {
            $this->error('Errors in: '.implode(', ', $failed).'.');

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run finished: nothing changed.' : 'Sync finished.');

        return self::SUCCESS;
    }

    /**
     * The most recent updated_at in the v8 content tables: shows how fresh the dump is.
     */
    private function latestChange(V8Source $source): ?string
    {
        $latest = null;

        foreach ($source->contentEntities() as $entity) {
            $table = $source->contentTable($entity->entity_key, $entity->group_key);

            if ($table && $source->hasTable($table) && in_array('updated_at', $source->columns($table), true)) {
                $value = $source->connection()->table($table)->max('updated_at');
                $latest = $value && $value > $latest ? $value : $latest;
            }
        }

        return $latest;
    }
}
