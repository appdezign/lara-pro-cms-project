<?php

/**
 * Roll the tested dependency set of laracms10pro out to a client project.
 *
 * Usage:
 *   php bin/sync-client-deps.php /path/to/client [--dry-run] [--no-test]
 *
 * Steps:
 *   1. composer.json: laracms10pro's file, with the client's own name, description and version.
 *   2. composer.lock: copied as is (the frozen, tested set).
 *   3. composer update --lock: refreshes the content-hash only, no package changes.
 *   4. composer install: installs exactly the locked versions.
 *   5. Client-only packages (in the client's require/require-dev, not in laracms10pro) are added
 *      again with `composer require`, which leaves every other locked package alone.
 *   6. composer validate, then composer test (unless --no-test).
 *
 * --dry-run only reports what would change.
 */
$sourceDir = dirname(__DIR__);
$keepKeys = ['name', 'description', 'version'];

$arguments = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $arguments, true);
$runTests = ! in_array('--no-test', $arguments, true);
$paths = array_values(array_filter($arguments, fn (string $argument): bool => ! str_starts_with($argument, '--')));

if (count($paths) !== 1) {
    fail('Usage: php bin/sync-client-deps.php /path/to/client [--dry-run] [--no-test]');
}

$targetDir = realpath($paths[0]) ?: fail('Client directory not found: '.$paths[0]);

if ($targetDir === realpath($sourceDir)) {
    fail('The client directory is laracms10pro itself.');
}

foreach (["{$sourceDir}/composer.json", "{$sourceDir}/composer.lock", "{$targetDir}/composer.json"] as $file) {
    if (! is_file($file)) {
        fail('Missing file: '.$file);
    }
}

$sourceText = file_get_contents("{$sourceDir}/composer.json");
$source = decode($sourceText, 'laracms10pro composer.json');
$target = decode(file_get_contents("{$targetDir}/composer.json"), 'client composer.json');

// refuse to overwrite uncommitted changes, so a sync can always be undone with git
if (is_dir("{$targetDir}/.git")) {
    $dirty = trim((string) shell_exec('git -C '.escapeshellarg($targetDir).' status --porcelain composer.json composer.lock'));

    if ($dirty !== '' && ! $dryRun) {
        fail("composer.json or composer.lock has uncommitted changes in the client:\n{$dirty}\nCommit or stash them first.");
    }
} else {
    warn('The client is not a git repository; the old composer.json and composer.lock cannot be restored with git.');
}

$newText = $sourceText;

foreach ($keepKeys as $key) {
    $newText = replaceTopLevelValue($newText, $key, $target[$key] ?? null);
}

$newJson = decode($newText, 'merged composer.json');

$clientOnly = [];

foreach (['require', 'require-dev'] as $section) {
    foreach ($target[$section] ?? [] as $package => $constraint) {
        if (! isset($source['require'][$package]) && ! isset($source['require-dev'][$package])) {
            $clientOnly[] = ['package' => $package, 'constraint' => $constraint, 'dev' => $section === 'require-dev'];
        }
    }
}

info('Source: '.$sourceDir.' ('.($source['version'] ?? 'no version').')');
info('Client: '.$targetDir.' ('.($target['name'] ?? '?').' '.($target['version'] ?? '').')');

$changedKeys = array_values(array_filter(
    array_unique([...array_keys($target), ...array_keys($newJson)]),
    fn (string $key): bool => ($target[$key] ?? null) !== ($newJson[$key] ?? null)
));
info('composer.json sections that change: '.($changedKeys === [] ? 'none' : implode(', ', $changedKeys)));

foreach ($clientOnly as $extra) {
    info('Client-only package, added again afterwards: '.$extra['package'].' '.$extra['constraint'].($extra['dev'] ? ' (dev)' : ''));
}

if ($dryRun) {
    info('Dry run: nothing written.');
    exit(0);
}

file_put_contents("{$targetDir}/composer.json", $newText);
copy("{$sourceDir}/composer.lock", "{$targetDir}/composer.lock") || fail('Could not copy composer.lock.');
info('composer.json and composer.lock written.');

composer($targetDir, 'update --lock --no-interaction');
composer($targetDir, 'install --no-interaction');

foreach ($clientOnly as $extra) {
    composer($targetDir, 'require '.($extra['dev'] ? '--dev ' : '').escapeshellarg($extra['package'].':'.$extra['constraint']).' --no-interaction');
}

// validate also warns about things like the version field; only a stale lock is a failure here
$validation = (string) shell_exec('composer validate --no-check-publish --no-interaction --working-dir='.escapeshellarg($targetDir).' 2>&1');

if (str_contains($validation, 'lock file is not up to date')) {
    fail("composer validate still reports a stale lock file:\n".$validation);
}

info('composer validate: lock file is up to date.');

if ($runTests) {
    composer($targetDir, 'test');
}

info('Done. Review the diff in the client, then commit composer.json and composer.lock.');

/**
 * Replace the value of a top-level string key in the composer.json text, keeping its formatting.
 * A key the client does not have is removed.
 */
function replaceTopLevelValue(string $text, string $key, ?string $value): string
{
    $pattern = '/^([ \t]*)"'.preg_quote($key, '/').'"\s*:\s*"(?:[^"\\\\]|\\\\.)*"(,?)[ \t]*\R/m';

    if (! preg_match($pattern, $text)) {
        if ($value !== null) {
            warn("laracms10pro has no \"{$key}\"; the client's value is dropped.");
        }

        return $text;
    }

    return preg_replace_callback($pattern, function (array $match) use ($key, $value): string {
        if ($value === null) {
            return '';
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $match[1].'"'.$key.'": '.$encoded.$match[2].PHP_EOL;
    }, $text, 1);
}

/**
 * @return array<string, mixed>
 */
function decode(string $json, string $label): array
{
    try {
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail("Invalid JSON in {$label}: ".$exception->getMessage());
    }
}

function composer(string $directory, string $command): void
{
    info('> composer '.$command);

    passthru('composer '.$command.' --working-dir='.escapeshellarg($directory), $exitCode);

    if ($exitCode !== 0) {
        fail("composer {$command} failed (exit code {$exitCode}). Restore the client with: git checkout composer.json composer.lock && composer install");
    }
}

function info(string $message): void
{
    fwrite(STDOUT, "\033[32m".$message."\033[0m".PHP_EOL);
}

function warn(string $message): void
{
    fwrite(STDERR, "\033[33m".$message."\033[0m".PHP_EOL);
}

function fail(string $message): never
{
    fwrite(STDERR, "\033[31m".$message."\033[0m".PHP_EOL);
    exit(1);
}
