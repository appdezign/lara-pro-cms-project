<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ImportDatabase extends Command
{
	protected $signature = 'database:import {file}';

	protected $description = 'Import the MySQL database from a SQL file';

	public function handle()
	{
		// Load database connection parameters
		$dbHost = config('database.connections.mysql.host');
		$dbPort = config('database.connections.mysql.port');
		$dbName = config('database.connections.mysql.database');
		$dbUser = config('database.connections.mysql.username');
		$dbPass = config('database.connections.mysql.password');

		// Get filename argument from command
		$file = $this->argument('file');
		$filePath = storage_path('app/backups/' . $file);

		// Validate SQL file exists
		if (!file_exists($filePath)) {
			$this->error('The specified SQL file does not exist: ' . $filePath);
			return Command::FAILURE;
		}

		// Safety confirmation before running destructive import
		if (!$this->confirm('This will import the database. Do you wish to continue?')) {
			$this->info('Import cancelled.');
			return Command::SUCCESS;
		}

		$this->info('Starting database import...');

		// Build mysql process command
		$process = new Process([
			'mysql',
			'--host=' . $dbHost,
			'--port=' . $dbPort,
			'--user=' . $dbUser,
			'--password=' . $dbPass,
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
			$this->error('Database import failed: ' . $exception->getMessage());
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}
}