<?php

namespace App\Console\Commands\LaraV8\Support\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes imported rows to a v10 table: only the rows that differ, with the column default for a
 * v8 NULL in a NOT NULL column, and reports the v10 rows that v8 does not have (removed with $fresh).
 *
 * The using class reports through add(level, entity, message).
 */
trait SyncsRows
{
    abstract private function add(string $level, string $entity, string $message): void;

    /**
     * Write the rows that differ, keyed by $keyColumns; report the v10 rows that v8 does not have.
     * Columns in $derived are written for new rows only: v10 maintains them itself afterwards
     * (e.g. menu item route names, rebuilt by lara:menu:refresh-routenames).
     *
     * @param  list<string>  $keyColumns
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  list<string>  $derived
     * @param  bool  $reportExtra  false where v10 keeps rows of its own (settings, translations): they are not reported
     */
    private function sync(string $label, string $table, array $keyColumns, Collection $rows, bool $dryRun, bool $fresh, array $derived = [], bool $reportExtra = true): void
    {
        $keyOf = fn (array|object $row): string => implode('#', array_map(fn (string $column): string => (string) data_get($row, $column), $keyColumns));
        $existing = DB::table($table)->get()->keyBy($keyOf);

        // v8 NULL in a v10 column that is NOT NULL with a default (e.g. tags.depth): the default
        $defaults = collect(DB::select(
            'select column_name as name, column_default as value from information_schema.columns where table_schema = database() and table_name = ? and is_nullable = ? and column_default is not null',
            [$table, 'NO'],
        ))->pluck('value', 'name')->all();

        $rows = $rows->map(function (array $row) use ($defaults): array {
            foreach ($row as $column => $value) {
                if ($value === null && array_key_exists($column, $defaults)) {
                    $row[$column] = $defaults[$column];
                }
            }

            return $row;
        });
        $counts = ['create' => 0, 'update' => 0, 'same' => 0];
        $wanted = [];

        foreach ($rows as $row) {
            $key = $keyOf($row);
            $wanted[$key] = true;
            $current = $existing[$key] ?? null;

            if ($current === null) {
                $counts['create']++;

                if (! $dryRun) {
                    DB::table($table)->insert($row);
                }

                continue;
            }

            // updated_at alone is no difference: v10 touches it when a record is saved
            // (e.g. by the Pages list); it is written along with a real change
            $changed = array_filter(
                array_diff_key($row, array_flip([...$derived, 'updated_at'])),
                fn ($value, string $column): bool => $this->normalize($current->{$column} ?? null) !== $this->normalize($value),
                ARRAY_FILTER_USE_BOTH,
            );

            if ($changed === []) {
                $counts['same']++;

                continue;
            }

            if (array_key_exists('updated_at', $row)) {
                $changed['updated_at'] = $row['updated_at'];
            }

            $counts['update']++;

            if (! $dryRun) {
                $query = DB::table($table);

                foreach ($keyColumns as $column) {
                    $query->where($column, $row[$column]);
                }

                $query->update($changed);
            }
        }

        $verb = $dryRun ? 'would be ' : '';
        $level = $counts['update'] > 0 ? 'update' : ($counts['create'] > 0 ? 'create' : 'same');
        $this->add($level, $label, "{$table}: {$rows->count()} row(s); {$counts['create']} {$verb}created, {$counts['update']} {$verb}updated, {$counts['same']} unchanged.");

        $extra = $existing->keys()->reject(fn (string $key): bool => isset($wanted[$key]))->values();

        if ($extra->isEmpty() || (! $fresh && ! $reportExtra)) {
            return;
        }

        if ($fresh && ! $dryRun) {
            foreach ($extra as $key) {
                $query = DB::table($table);

                foreach (array_combine($keyColumns, explode('#', $key)) as $column => $value) {
                    $query->where($column, $value);
                }

                $query->delete();
            }

            $this->add('update', $label, "{$table}: removed {$extra->count()} row(s) that v8 does not have.");
        } else {
            $this->add('differs', $label, "{$table}: {$extra->count()} row(s) that v8 does not have; kept (remove with --fresh).");
        }
    }

    private function normalize(mixed $value): string
    {
        if ($value === null) {
            return "\0null";
        }

        $string = (string) $value;

        // JSON columns come back with spaces after the separators
        if (Str::startsWith($string, ['[', '{']) && ($decoded = json_decode($string, true)) !== null) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        return $string;
    }
}
