<?php

namespace App\Console\Commands\LaraV8\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * The demo site code a fresh v10 clone ships in laracms/app: the generated classes of the
 * demo content types and their migrations, factories and seeders.
 *
 * A migrated site builds its own content types from the v8 configuration, so this code
 * is removed first (lara:v8:prepare-target). Everything else in laracms/app stays:
 * Legacy, providers, routes, config, custom blocks, and the other front controllers.
 */
final class DemoCode
{
    /**
     * Folders (relative to laracms/app) whose contents are demo code.
     */
    public const FOLDERS = [
        'Models',
        'Entities',
        'Policies',
        'Filament/Resources',
        'Http/Controllers/Front/Entity',
        'Http/Controllers/Front/Form',
        'Database/Factories',
        'Database/Migrations',
        'Database/Seeders',
    ];

    /**
     * The files that would be removed, relative to $appPath.
     *
     * @return list<string>
     */
    public static function files(string $appPath): array
    {
        $files = [];

        foreach (self::FOLDERS as $folder) {
            $path = rtrim($appPath, '/').'/'.$folder;

            if (! (new Filesystem)->isDirectory($path)) {
                continue;
            }

            foreach ((new Filesystem)->allFiles($path, hidden: true) as $file) {
                $files[] = $folder.'/'.$file->getRelativePathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Empty the demo folders; the folders themselves stay, because the builder writes into them.
     * Files in $keep (relative to $appPath, e.g. DemoStandardTypes::files()) stay too.
     *
     * @param  list<string>  $keep
     * @return int the number of removed files
     */
    public static function remove(string $appPath, array $keep = []): int
    {
        $files = new Filesystem;
        $root = rtrim($appPath, '/');

        if ($keep === []) {
            $count = count(self::files($appPath));

            foreach (self::FOLDERS as $folder) {
                if ($files->isDirectory("{$root}/{$folder}")) {
                    $files->cleanDirectory("{$root}/{$folder}");
                }
            }

            return $count;
        }

        $remove = array_values(array_diff(self::files($appPath), $keep));

        foreach ($remove as $file) {
            $files->delete("{$root}/{$file}");
        }

        // subfolders left empty (e.g. Filament/Resources/Teams/Pages) go too; the demo folders stay
        foreach (self::FOLDERS as $folder) {
            if ($files->isDirectory("{$root}/{$folder}")) {
                foreach ($files->directories("{$root}/{$folder}") as $directory) {
                    self::removeEmpty($files, $directory);
                }
            }
        }

        return count($remove);
    }

    private static function removeEmpty(Filesystem $files, string $directory): void
    {
        foreach ($files->directories($directory) as $child) {
            self::removeEmpty($files, $child);
        }

        if ($files->files($directory, true) === [] && $files->directories($directory) === []) {
            $files->deleteDirectory($directory);
        }
    }
}
