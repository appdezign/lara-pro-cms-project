<?php

namespace App\Console\Commands\LaraV8\Support\Concerns;

use App\Console\Commands\LaraV8\Support\SiteOverrides;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes imported rows to a v10 table: only the rows that differ, with the column default for a
 * v8 NULL in a NOT NULL column, and reports the v10 rows that v8 does not have (removed with $fresh).
 * Rows with the same key as an earlier row are reported and skipped (the first one wins).
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
     * @param  string|null  $from  the v8 table, named in the report
     */
    private function sync(string $label, string $table, array $keyColumns, Collection $rows, bool $dryRun, bool $fresh, array $derived = [], bool $reportExtra = true, ?string $from = null): void
    {
        // the key values as strings (as before), JSON-encoded so a '#' in a value cannot collide
        $keyOf = fn (array|object $row): string => json_encode(array_map(fn (string $column): string => (string) data_get($row, $column), $keyColumns), JSON_UNESCAPED_UNICODE);
        $existing = DB::table($table)->get()->keyBy($keyOf);

        // v8 NULL in a v10 column that is NOT NULL with a default (e.g. tags.depth): the default
        $defaults = $this->columnDefaults($table);

        $rows = $rows->map(function (array $row) use ($defaults, $table): array {
            foreach ($row as $column => $value) {
                if ($value === null && array_key_exists($column, $defaults)) {
                    $row[$column] = $defaults[$column];
                }
            }

            // the site's deliberate differences from v8 (v8-overrides.php)
            return SiteOverrides::apply($table, $row);
        });

        // two v8 rows for one v10 row (e.g. one image file used twice in a content type): the first wins
        $unique = $rows->unique($keyOf)->values();

        if ($unique->count() < $rows->count()) {
            $this->add('warning', $label, "{$table}: ".($rows->count() - $unique->count()).' row(s) have the same key ('.implode(', ', $keyColumns).') as an earlier row; the first one is used.');
            $rows = $unique;
        }

        $counts = ['create' => 0, 'update' => 0, 'same' => 0];
        $wanted = [];
        $inserts = [];

        foreach ($rows as $row) {
            $key = $keyOf($row);
            $wanted[$key] = true;
            $current = $existing[$key] ?? null;

            if ($current === null) {
                $counts['create']++;

                // inserted in batches, per set of columns (an override can add a column to one row)
                if (! $dryRun) {
                    $columns = implode(',', array_keys($row));
                    $inserts[$columns][] = $row;

                    if (count($inserts[$columns]) >= 100) {
                        DB::table($table)->insert($inserts[$columns]);
                        $inserts[$columns] = [];
                    }
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

        foreach (array_filter($inserts) as $batch) {
            DB::table($table)->insert($batch);
        }

        $verb = $dryRun ? 'would be ' : '';
        $level = $counts['update'] > 0 ? 'update' : ($counts['create'] > 0 ? 'create' : 'same');
        $this->add($level, $label, ($from !== null ? "{$from} → " : '')."{$table}: {$rows->count()} row(s); {$counts['create']} {$verb}created, {$counts['update']} {$verb}updated, {$counts['same']} unchanged.");

        $extra = $existing->keys()->reject(fn (string $key): bool => isset($wanted[$key]))->values();
        // with an id key, the report names the rows
        $ids = $keyColumns === ['id'] && $extra->count() <= 50 ? ' (ids '.$extra->map(fn (string $key): string => (string) $existing[$key]->id)->implode(', ').')' : '';

        if ($extra->isEmpty() || (! $fresh && ! $reportExtra)) {
            return;
        }

        if ($fresh && ! $dryRun) {
            foreach ($extra as $key) {
                // by the values of the v10 row itself (a NULL key column matches with whereNull)
                $query = DB::table($table);

                foreach ($keyColumns as $column) {
                    $value = $existing[$key]->{$column};
                    $value === null ? $query->whereNull($column) : $query->where($column, $value);
                }

                $query->delete();
            }

            $this->add('update', $label, "{$table}: removed {$extra->count()} row(s) that v8 does not have{$ids}.");
        } else {
            $this->add('differs', $label, "{$table}: {$extra->count()} row(s) that v8 does not have{$ids}; kept (remove with --fresh).");
        }
    }

    /**
     * The defaults of the NOT NULL columns of a v10 table, as values. information_schema holds
     * the default as SQL: MariaDB quotes string literals ('public'). An expression default
     * (CURRENT_TIMESTAMP, current_timestamp()) is no value and is left out: filling in the
     * current time would make the row differ on every run (no imported table has one).
     *
     * @return array<string, mixed>
     */
    private function columnDefaults(string $table): array
    {
        $defaults = [];

        foreach (DB::select(
            'select column_name as name, column_default as value from information_schema.columns where table_schema = database() and table_name = ? and is_nullable = ? and column_default is not null',
            [$table, 'NO'],
        ) as $column) {
            $value = self::columnDefaultValue((string) $column->value);

            if ($value !== null) {
                $defaults[$column->name] = $value;
            }
        }

        return $defaults;
    }

    /**
     * The value of a column default as information_schema gives it; null for no value.
     */
    private static function columnDefaultValue(string $sql): ?string
    {
        if (preg_match("/^'(.*)'$/s", $sql, $m)) {
            return str_replace("''", "'", $m[1]);
        }

        if (preg_match('/^current_timestamp(\(\d*\))?$/i', $sql) || strtoupper($sql) === 'NULL') {
            return null;
        }

        return $sql;
    }

    private function normalize(mixed $value): string
    {
        if ($value === null) {
            return "\0null";
        }

        $string = (string) $value;

        // JSON columns come back with spaces after the separators and with the object keys in
        // MySQL's own order (shorter keys first), so objects are compared with sorted keys
        if (Str::startsWith($string, ['[', '{']) && ($decoded = json_decode($string, true)) !== null) {
            return json_encode(self::sortJsonKeys($decoded), JSON_UNESCAPED_UNICODE);
        }

        return $string;
    }

    private static function sortJsonKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $value = array_map(fn (mixed $item): mixed => self::sortJsonKeys($item), $value);

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
