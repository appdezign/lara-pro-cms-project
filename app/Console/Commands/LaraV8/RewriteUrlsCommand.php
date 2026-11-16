<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\UrlRewriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Rewrites the v8 media URLs in the imported HTML and reports internal links that do not
 * reach a page in v10 (UrlRewriter). Run after lara:v8:import-media and lara:v8:import-menus.
 */
class RewriteUrlsCommand extends Command
{
    protected $signature = 'lara:v8:rewrite-urls
        {--dry-run : Report what would change, change and copy nothing}';

    protected $description = 'Rewrite the v8 media URLs in the content HTML and check the internal links';

    /**
     * Levels that are shown without --verbose.
     */
    private const SHOWN = ['create', 'update', 'differs', 'warning', 'error'];

    public function handle(UrlRewriter $rewriter): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $report = $rewriter->run($dryRun);
        } catch (Throwable $e) {
            $this->error('Rewrite stopped: '.$e->getMessage());

            return self::FAILURE;
        }

        $shown = $this->output->isVerbose() ? $report : array_values(array_filter($report, fn (array $line): bool => in_array($line['level'], self::SHOWN, true)));

        $this->table(['Object', 'Result', 'Message'], array_map(fn (array $line): array => [$line['entity'], $line['level'], $line['message']], $shown));

        $counts = array_count_values(array_column($report, 'level'));
        ksort($counts);
        $this->line(collect($counts)->map(fn (int $n, string $level): string => "{$level} {$n}")->implode(', ').($dryRun ? ' (dry run)' : ''));

        if (! $dryRun && ($counts['update'] ?? 0) > 0) {
            Artisan::call('optimize:clear');
            $this->info('Caches cleared.');
        }

        return ($counts['error'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
