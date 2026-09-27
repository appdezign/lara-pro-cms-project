<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ImportDatabase extends Command
{
    protected $signature = 'database:import
        {file : The SQL file in storage/app/backups}
        {--database= : The database to import into (defaults to the configured database)}';

    protected $description = 'Import the MySQL database from a SQL file';

    public function handle(): int
    {
        // Load database connection parameters
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = $this->option('database') ?: config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        if (! preg_match('/^\w+$/', $dbName)) {
            $this->error('Invalid database name: '.$dbName);

            return Command::FAILURE;
        }

        // Get filename argument from command
        $file = $this->argument('file');
        $filePath = storage_path('app/backups/'.$file);

        // Validate SQL file exists
        if (! file_exists($filePath)) {
            $this->error('The specified SQL file does not exist: '.$filePath);

            return Command::FAILURE;
        }

        // Warn when the file looks like a backup of another database
        $otherDatabase = $this->getSourceDatabase($file);
        if ($otherDatabase && $otherDatabase != $dbName) {
            $this->warn("'{$file}' looks like a backup of '{$otherDatabase}', not of '{$dbName}'.");
        }

        // Safety confirmation before running destructive import
        if (! $this->confirm("This will overwrite database '{$dbName}' with '{$file}'. Do you wish to continue?")) {
            $this->info('Import cancelled.');

            return Command::SUCCESS;
        }

        $this->info("Starting import into database '{$dbName}'...");

        // Build mysql process command
        $process = new Process([
            'mysql',
            '--host='.$dbHost,
            '--port='.$dbPort,
            '--user='.$dbUser,
            '--password='.$dbPass,
            '--ssl=0',
            $dbName,
        ]);

        $process->setTimeout(3600);

        // Pipe SQL content directly to the process input
        $process->setInput(file_get_contents($filePath));

        try {
            // Execute mysql import command
            $process->mustRun();
            $this->info('Database import was successful.');
        } catch (ProcessFailedException $exception) {
            $this->error('Database import failed: '.$exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Backups made by database:backup are named '{database}_baseline.sql'.
     */
    private function getSourceDatabase(string $file): ?string
    {
        if (preg_match('/^(\w+)_baseline\.sql$/', $file, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
