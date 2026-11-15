<?php

namespace App\Console\Commands\LaraV8;

use App\Console\Commands\LaraV8\Support\V8Inventory;
use Illuminate\Console\Command;
use Throwable;

/**
 * Describes the v8 source site before it is migrated. Read-only.
 *
 * Use --markdown to paste the result into the plan or the runbook.
 */
class InventoryCommand extends Command
{
    protected $signature = 'lara:v8:inventory
        {--markdown : Print Markdown tables instead of console tables}';

    protected $description = 'Describe the v8 source site: content types, fields, relations, views, media and what has no v10 counterpart';

    public function handle(V8Inventory $inventory): int
    {
        try {
            $sections = $inventory->sections();
        } catch (Throwable $e) {
            $this->error('Cannot read the v8 source ('.config('v8-migration.connection').'): '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($sections as $section) {
            $this->option('markdown') ? $this->markdown($section) : $this->console($section);
        }

        $count = count($inventory->findings());
        $this->newLine();
        $count === 0
            ? $this->info('No findings.')
            : $this->warn("{$count} finding(s): see the last section.");

        return self::SUCCESS;
    }

    /**
     * @param  array{title: string, headers: list<string>, rows: list<list<string|int>>}  $section
     */
    private function console(array $section): void
    {
        $this->newLine();
        $this->info($section['title']);
        $this->table($section['headers'], $section['rows']);
    }

    /**
     * @param  array{title: string, headers: list<string>, rows: list<list<string|int>>}  $section
     */
    private function markdown(array $section): void
    {
        $cell = fn ($value): string => str_replace(['|', "\n"], ['\|', ' '], (string) $value);

        $this->line('');
        $this->line('### '.$section['title']);
        $this->line('');
        $this->line('| '.implode(' | ', array_map($cell, $section['headers'])).' |');
        $this->line('|'.str_repeat(' --- |', count($section['headers'])));

        foreach ($section['rows'] as $row) {
            $this->line('| '.implode(' | ', array_map($cell, $row)).' |');
        }
    }
}
