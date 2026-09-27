<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'database:backup
        {--database= : The database to back up (defaults to the configured database)}';

    protected $description = 'Backup the MySQL database';

    public function handle(): int
    {
        // Get database connection settings from config
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = $this->option('database') ?: config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        if (! preg_match('/^\w+$/', $dbName)) {
            $this->error('Invalid database name: '.$dbName);

            return Command::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        // One baseline per database, so backing up one never overwrites the other
        $backupFile = "{$backupDir}/{$dbName}_baseline.sql";

        $this->info("Starting backup of database '{$dbName}'...");

        // Build the mysqldump process command
        $process = new Process([
            'mysqldump',
            '--host='.$dbHost,
            '--port='.$dbPort,
            '--user='.$dbUser,
            '--password='.$dbPass,
            '--ssl=0',
            '--single-transaction',
            $dbName,
        ]);

        // Set timeout to 1 hour (3600 seconds)
        $process->setTimeout(3600);

        try {
            // Run the mysqldump command
            $process->mustRun();

            // Save output to file
            file_put_contents($backupFile, $process->getOutput());

            $this->info('Database backup was successful.');
            $this->info('Backup saved to: '.$backupFile);
        } catch (ProcessFailedException $exception) {
            $this->error('Database backup failed: '.$exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
