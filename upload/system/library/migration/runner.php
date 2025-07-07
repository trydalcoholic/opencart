<?php

declare(strict_types = 1);

namespace Opencart\System\Library\Migration;

use PDO;
use RuntimeException;

/**
 * Migration runner for database schema management
 *
 * Executes migration files in order and tracks applied migrations
 * Supports both up (apply) and down (rollback) operations
 */
final class Runner {
	private Connection $connection;
	private Schema $schema;
	private PDO $pdo;
	private string $prefix;
	private string $migration_path;

	/**
	 * Initialize migration runner
	 *
	 * @param Connection $connection Database connection instance
	 * @param string $migration_path Path to migration files directory
	 */
	public function __construct(Connection $connection, string $migration_path = '') {
		$this->connection = $connection;
		$this->schema = new Schema($connection);
		$this->pdo = $connection->getPdo();
		$this->prefix = $connection->getPrefix();
		$this->migration_path = $migration_path ?: DIR_SYSTEM . 'library/migration/files/';
	}

	/**
	 * Run all pending migrations
	 *
	 * @return void
	 */
	public function migrate(): void {
		$this->createMigrationTable();

		$applied_migrations = $this->getAppliedMigrations();
		$migration_files = $this->getMigrationFiles();

		foreach ($migration_files as $migration_file) {
			$migration_name = pathinfo($migration_file, PATHINFO_FILENAME);

			if (in_array($migration_name, $applied_migrations, true)) {
				continue;
			}

			$this->runMigration($migration_file, $migration_name);
		}
	}

	/**
	 * Rollback specific migration
	 *
	 * @param string $migration_name Name of migration to rollback
	 * @return void
	 * @throws RuntimeException When migration file not found
	 */
	public function rollback(string $migration_name): void {
		$migration_file = $this->findMigrationFile($migration_name);

		if (!$migration_file) {
			throw new RuntimeException("Migration file not found: $migration_name");
		}

		$migration = $this->loadMigration($migration_file);

		if (method_exists($migration, 'down')) {
			$migration->down($this->schema, $this->connection);
		}

		$this->removeMigrationRecord($migration_name);
	}

	/**
	 * Create migration tracking table if it doesn't exist
	 *
	 * @return void
	 * @throws RuntimeException When unsupported database driver used
	 */
	private function createMigrationTable(): void {
		$table_name = $this->prefix . 'migration';

		$sql = match($this->connection->getDriverName()) {
			'mysql' => "CREATE TABLE IF NOT EXISTS `$table_name` (
				`migration_id` int(11) NOT NULL AUTO_INCREMENT,
				`migration_name` varchar(255) NOT NULL,
				`date_excluded` datetime NOT NULL,
				PRIMARY KEY (`migration_id`),
				UNIQUE KEY `migration_name` (`migration_name`)
			) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

			'pgsql' => "CREATE TABLE IF NOT EXISTS \"$table_name\" (
				migration_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
				migration_name VARCHAR(255) NOT NULL UNIQUE,
				date_excluded TIMESTAMP NOT NULL
			)",

			default => throw new RuntimeException("Unsupported database driver")
		};

		$this->pdo->exec($sql);
	}

	/**
	 * Get list of already applied migrations
	 *
	 * @return array Array of migration names that have been executed
	 */
	private function getAppliedMigrations(): array {
		$table_name = $this->prefix . 'migration';

		$stmt = $this->pdo->query("SELECT migration_name FROM `$table_name` ORDER BY migration_id");

		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Get sorted list of migration files from filesystem
	 *
	 * @return array Array of migration file paths, sorted alphabetically
	 */
	private function getMigrationFiles(): array {
		$files = glob($this->migration_path . '*.php');

		sort($files);

		return $files;
	}

	/**
	 * Execute single migration file
	 *
	 * @param string $migration_file Path to migration file
	 * @param string $migration_name Migration identifier for tracking
	 * @return void
	 * @throws RuntimeException When migration doesn't have required up() method
	 */
	private function runMigration(string $migration_file, string $migration_name): void {
		$migration = $this->loadMigration($migration_file);

		if (!method_exists($migration, 'up')) {
			throw new RuntimeException("Migration $migration_name does not have up() method");
		}

		$migration->up($this->schema, $this->connection);

		$this->recordMigration($migration_name);
	}

	/**
	 * Load migration object from file
	 *
	 * @param string $migration_file Path to migration file
	 * @return object Migration instance
	 * @throws RuntimeException When migration file not found
	 */
	private function loadMigration(string $migration_file): object {
		if (!file_exists($migration_file)) {
			throw new RuntimeException("Migration file not found: $migration_file");
		}

		return require $migration_file;
	}

	/**
	 * Record successful migration execution
	 *
	 * @param string $migration_name Migration identifier
	 * @return void
	 */
	private function recordMigration(string $migration_name): void {
		$table_name = $this->prefix . 'migration';

		$stmt = $this->pdo->prepare("INSERT INTO `$table_name` (migration_name, date_excluded) VALUES (?, NOW())");
		$stmt->execute([$migration_name]);
	}

	/**
	 * Remove migration record from tracking table
	 *
	 * @param string $migration_name Migration identifier
	 * @return void
	 */
	private function removeMigrationRecord(string $migration_name): void {
		$table_name = $this->prefix . 'migration';

		$stmt = $this->pdo->prepare("DELETE FROM `$table_name` WHERE migration_name = ?");
		$stmt->execute([$migration_name]);
	}

	/**
	 * Find migration file by name
	 *
	 * @param string $migration_name Migration identifier
	 * @return string|null Path to migration file or null if not found
	 */
	private function findMigrationFile(string $migration_name): ?string {
		$migration_files = $this->getMigrationFiles();

		foreach ($migration_files as $file) {
			if (pathinfo($file, PATHINFO_FILENAME) === $migration_name) {
				return $file;
			}
		}

		return null;
	}
}
