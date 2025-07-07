<?php

declare(strict_types = 1);

namespace Opencart\System\Library\Migration;

use PDO;
use InvalidArgumentException;

/**
 * Database connection manager for migration system
 *
 * Creates PDO connection from OpenCart configuration
 * Supports MySQL and PostgreSQL databases
 */
final class Connection {
	private PDO $pdo;
	private string $prefix;

	/**
	 * Initialize database connection from OpenCart config
	 *
	 * @param array $config OpenCart database configuration array
	 * @throws InvalidArgumentException When unsupported database driver specified
	 */
	public function __construct(array $config) {
		$this->prefix = $config['DB_PREFIX'] ?? 'oc_';

		$dsn = $this->buildDsn($config);
		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		];

		$this->pdo = new PDO(
			dsn: $dsn,
			username: $config['DB_USERNAME'] ?? '',
			password: $config['DB_PASSWORD'] ?? '',
			options: $options,
		);
	}

	/**
	 * Get PDO instance for direct database operations
	 *
	 * @return PDO Database connection instance
	 */
	public function getPdo(): PDO {
		return $this->pdo;
	}

	/**
	 * Get database table prefix
	 *
	 * @return string Table prefix (e.g., 'oc_')
	 */
	public function getPrefix(): string {
		return $this->prefix;
	}

	/**
	 * Get database driver name
	 *
	 * @return string Driver name ('mysql' or 'pgsql')
	 */
	public function getDriverName(): string {
		return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Build DSN string from configuration
	 *
	 * @param array $config Database configuration
	 * @return string PDO DSN string
	 * @throws InvalidArgumentException When unsupported driver specified
	 */
	private function buildDsn(array $config): string {
		$driver = $config['DB_DRIVER'] ?? 'mysqli';
		$hostname = $config['DB_HOSTNAME'] ?? 'localhost';
		$database = $config['DB_DATABASE'] ?? '';
		$port = $config['DB_PORT'] ?? '';

		return match($driver) {
			'mysqli' => "mysql:host=$hostname" . ($port ? ";port=$port" : '') . ";dbname=$database;charset=utf8mb4",
			'pgsql'  => "pgsql:host=$hostname" . ($port ? ";port=$port" : '') . ";dbname=$database",
			default  => throw new InvalidArgumentException("Unsupported database driver: $driver")
		};
	}
}
