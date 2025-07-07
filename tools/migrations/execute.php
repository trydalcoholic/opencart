<?php

declare(strict_types = 1);

use Opencart\System\Library\Migration\Connection;
use Opencart\System\Library\Migration\Runner;

require_once __DIR__ . '/../../upload/system/library/migration/connection.php';
require_once __DIR__ . '/../../upload/system/library/migration/schema.php';
require_once __DIR__ . '/../../upload/system/library/migration/runner.php';

const DIR_SYSTEM = __DIR__.'/../../upload/system/';

$config = [
	'DB_DRIVER'   => 'mysqli',
	'DB_HOSTNAME' => 'mysql',
	'DB_USERNAME' => 'root',
	'DB_PASSWORD' => 'opencart',
	'DB_DATABASE' => 'opencart',
	'DB_PREFIX'   => 'oc_',
	'DB_PORT'     => '3306',
];

$command = $argv[1] ?? 'migrate';
$migration_name = $argv[2] ?? '';

try {
	$connection = new Connection($config);
	$migration_path = __DIR__ . '/generated/';
	$runner = new Runner($connection, $migration_path);

	match($command) {
		'migrate' => runMigrations($runner, $migration_path),
		'rollback' => rollbackMigration($runner, $migration_name),
		'status' => showStatus($runner, $migration_path),
		default => showHelp()
	};

} catch (Exception $e) {
	echo "Error: " . $e->getMessage() . "\n";
	echo "Trace: " . $e->getTraceAsString() . "\n";
}

function runMigrations(Runner $runner, string $migration_path): void {
	echo "Running migrations...\n";
	echo "Migration path: $migration_path\n";

	$files = glob($migration_path . '*.php');
	echo "Found " . count($files) . " migration files\n";

	$runner->migrate();
	echo "Done!\n";
}

function rollbackMigration(Runner $runner, string $migration_name): void {
	if (empty($migration_name)) {
		echo "Please specify migration name to rollback\n";
		echo "Usage: php execute.php rollback migration_name\n";
		return;
	}

	echo "Rolling back migration: $migration_name\n";
	$runner->rollback($migration_name);
	echo "Rollback completed!\n";
}

function showStatus(Runner $runner, string $migration_path): void {
	echo "Migration status:\n";
	echo "=================\n";
	echo "This feature coming soon...\n";
}

function showHelp(): void {
	echo "OpenCart Migration Tool\n";
	echo "Usage:\n";
	echo "  php execute.php migrate                    - Run all pending migrations\n";
	echo "  php execute.php rollback <migration_name>  - Rollback specific migration\n";
	echo "  php execute.php status                     - Show migration status\n";
}
