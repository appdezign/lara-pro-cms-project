<?php

namespace App\Console\Commands\LaraV8\Support;

/**
 * Per-site changes to imported rows: the v10 site deliberately differs from v8 there
 * (e.g. a widget on another hook, a language unpublished). The importers apply them while
 * they build the rows, so v8 + overrides is the wanted state: a rerun keeps them and
 * lara:v8:verify sees them as in sync.
 *
 * The file (config v8-migration.overrides, default v8-overrides.php in the project root) is
 * site data and returns a list of:
 *
 *     ['table' => 'lara_blocks_widgets', 'where' => ['id' => 591], 'set' => ['hook' => 'home2'], 'reason' => '...']
 *
 * `where` matches the imported row (all columns equal, compared as strings).
 */
class SiteOverrides
{
    /** @var list<array{table: string, where: array<string, mixed>, set: array<string, mixed>, reason?: string}>|null */
    private static ?array $overrides = null;

    private static ?string $loadedFrom = null;

    /**
     * @return list<array{table: string, where: array<string, mixed>, set: array<string, mixed>, reason?: string}>
     */
    public static function all(): array
    {
        $file = self::file();

        if (self::$overrides === null || self::$loadedFrom !== $file) {
            self::$loadedFrom = $file;
            self::$overrides = is_file($file) ? array_values((array) require $file) : [];
        }

        return self::$overrides;
    }

    public static function file(): string
    {
        return (string) (config('v8-migration.overrides') ?: base_path('v8-overrides.php'));
    }

    /**
     * Forget the loaded file (tests, or after the file changed).
     */
    public static function flush(): void
    {
        self::$overrides = null;
        self::$loadedFrom = null;
    }

    /**
     * The row with the overrides for its table applied.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function apply(string $table, array $row): array
    {
        foreach (self::all() as $override) {
            if (($override['table'] ?? null) === $table && self::matches($override['where'] ?? [], $row)) {
                $row = array_merge($row, $override['set'] ?? []);
            }
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $where
     * @param  array<string, mixed>|object  $row
     */
    public static function matches(array $where, array|object $row): bool
    {
        if ($where === []) {
            return false;
        }

        foreach ($where as $column => $value) {
            $actual = data_get($row, $column);

            if ($actual === null || (string) $actual !== (string) $value) {
                return false;
            }
        }

        return true;
    }
}
