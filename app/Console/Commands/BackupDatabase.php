<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
	protected $signature = 'database:backup';

	protected $description = 'Backup the MySQL database';

	public function handle()
	{
		// Get database connection settings from config
		$dbHost = config('database.connections.mysql.host');
		$dbPort = config('database.connections.mysql.port');
		$dbName = config('database.connections.mysql.database');
		$dbUser = config('database.connections.mysql.username');
		$dbPass = config('database.connections.mysql.password');

		$backupDir = storage_path('app/backups');
		if (!is_dir($backupDir)) {
			mkdir($backupDir, 0777, true);
		}

		// Create backup directory and filename
		$backupFile = "{$backupDir}/laracms10_baseline.sql";

		if (!is_dir(storage_path('backups'))) {
			mkdir(storage_path('backups'), 0755, true);
		}

		$this->info('Starting database backup...');

		// Build the mysqldump process command
		$process = new Process([
			'mysqldump',
			'--host=' . $dbHost,
			'--port=' . $dbPort,
			'--user=' . $dbUser,
			'--password=' . $dbPass,
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
			$this->info('Backup saved to: ' . $backupFile);
		} catch (ProcessFailedException $exception) {
			$this->error('Database backup failed: ' . $exception->getMessage());
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}
}