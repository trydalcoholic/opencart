<?php

declare(strict_types = 1);

namespace Opencart\System\Library\Migration;

use PDO;

/**
 * Database schema builder for migration system
 *
 * Provides DDL operations for creating and modifying database tables
 * Supports MySQL and PostgreSQL with proper type mapping
 */
final class Schema {
	private PDO $pdo;
	private string $prefix;
	private string $driver;

	/**
	 * Initialize schema builder with database connection
	 *
	 * @param Connection $connection Database connection instance
	 */
	public function __construct(Connection $connection) {
		$this->pdo = $connection->getPdo();
		$this->prefix = $connection->getPrefix();
		$this->driver = $connection->getDriverName();
	}

	/**
	 * Create new database table
	 *
	 * @param string $name Table name (without prefix)
	 * @param array $fields Array of field definitions
	 * @param array $options Table options (engine, charset, indexes, etc.)
	 * @return void
	 */
	public function createTable(string $name, array $fields, array $options = []): void {
		$table_name = $this->prefix . $name;
		$sql = "CREATE TABLE `$table_name` (\n";

		$fields_sql = [];
		$primary_keys = [];

		foreach ($fields as $field) {
			$fields_sql[] = $this->buildFieldSql($field);

			if (!empty($field['primary'])) {
				$primary_keys[] = $this->quoteIdentifier($field['name']);
			}
		}

		$sql .= implode(",\n", $fields_sql);

		if (!empty($primary_keys)) {
			$sql .= ",\n  PRIMARY KEY (" . implode(', ', $primary_keys) . ")";
		}

		if (!empty($options['indexes'])) {
			foreach ($options['indexes'] as $index) {
				$index_keys = array_map(fn($key) => $this->quoteIdentifier($key), $index['keys']);
				$sql .= ",\n  KEY " . $this->quoteIdentifier($index['name']) . " (" . implode(', ', $index_keys) . ")";
			}
		}

		$sql .= "\n)";

		$sql .= $this->buildTableOptions($options);

		$this->pdo->exec($sql);
	}

	/**
	 * Drop existing database table
	 *
	 * @param string $name Table name (without prefix)
	 * @return void
	 */
	public function dropTable(string $name): void {
		$table_name = $this->prefix . $name;
		$quoted_name = $this->quoteIdentifier($table_name);

		$this->pdo->exec("DROP TABLE IF EXISTS $quoted_name");
	}

	/**
	 * Add new column to existing table
	 *
	 * @param string $table Table name (without prefix)
	 * @param string $name Column name
	 * @param array $definition Column definition
	 * @return void
	 */
	public function addColumn(string $table, string $name, array $definition): void {
		$table_name = $this->prefix . $table;
		$field_sql = $this->buildFieldSql(['name' => $name] + $definition);

		$quoted_table = $this->quoteIdentifier($table_name);
		$this->pdo->exec("ALTER TABLE $quoted_table ADD COLUMN $field_sql");
	}

	/**
	 * Drop column from existing table
	 *
	 * @param string $table Table name (without prefix)
	 * @param string $name Column name
	 * @return void
	 */
	public function dropColumn(string $table, string $name): void {
		$table_name = $this->prefix . $table;
		$quoted_table = $this->quoteIdentifier($table_name);
		$quoted_name = $this->quoteIdentifier($name);

		$this->pdo->exec("ALTER TABLE $quoted_table DROP COLUMN $quoted_name");
	}

	/**
	 * Build SQL definition for single field
	 *
	 * @param array $field Field definition array
	 * @return string SQL field definition
	 */
	private function buildFieldSql(array $field): string {
		$name = $this->quoteIdentifier($field['name']);
		$type = $this->mapColumnType($field);

		$sql = "  $name $type";

		if (!empty($field['not_null'])) {
			$sql .= ' NOT NULL';
		}

		if (isset($field['default'])) {
			$sql .= " DEFAULT " . $this->quoteValue($field['default']);
		}

		if (!empty($field['auto_increment']) && $this->driver === 'mysql') {
			$sql .= ' AUTO_INCREMENT';
		}

		return $sql;
	}

	/**
	 * Map column type to database-specific type
	 *
	 * @param array $field Complete field definition
	 * @return string Database-specific column type
	 */
	private function mapColumnType(array $field): string {
		$type = $field['type'];

		return match($this->driver) {
			'mysql' => $this->mapMysqlType($type),
			'pgsql' => $this->mapPgsqlType($field),
			default => $type
		};
	}

	/**
	 * Map column type for MySQL database
	 *
	 * @param string $type Generic column type
	 * @return string MySQL-specific column type
	 */
	private function mapMysqlType(string $type): string {
		return match(true) {
			$type === 'text' => 'TEXT',
			$type === 'mediumtext' => 'MEDIUMTEXT',
			$type === 'datetime' => 'DATETIME',
			$type === 'date' => 'DATE',
			$type === 'tinyint(1)' => 'TINYINT(1)',
			default => $type
		};
	}

	/**
	 * Map column type for PostgreSQL database
	 *
	 * @param array $field Complete field definition
	 * @return string PostgreSQL-specific column type
	 */
	private function mapPgsqlType(array $field): string {
		$type = $field['type'];

		return match(true) {
			str_contains($type, 'int(') && !empty($field['auto_increment']) => 'INTEGER GENERATED ALWAYS AS IDENTITY',
			str_contains($type, 'int(')                                     => 'INTEGER',
			str_contains($type, 'varchar(')                                 => str_replace('varchar', 'VARCHAR', $type),
			str_contains($type, 'decimal(')                                 => str_replace('decimal', 'DECIMAL', $type),
			$type === 'text', $type === 'mediumtext'                        => 'TEXT',
			$type === 'datetime'                                            => 'TIMESTAMP',
			$type === 'date'                                                => 'DATE',
			$type === 'tinyint(1)'                                          => 'BOOLEAN',
			default => $type
		};
	}

	/**
	 * Build table options string for CREATE TABLE
	 *
	 * @param array $options Table options
	 * @return string Database-specific table options
	 */
	private function buildTableOptions(array $options): string {
		return match($this->driver) {
			'mysql' => $this->buildMysqlTableOptions($options),
			default => '' // PostgreSQL doesn't need additional table options
		};
	}

	/**
	 * Build MySQL-specific table options
	 *
	 * @param array $options Table options
	 * @return string MySQL table options string
	 */
	private function buildMysqlTableOptions(array $options): string {
		$engine = $options['engine'] ?? 'InnoDB';
		$charset = $options['charset'] ?? 'utf8mb4';
		$collate = $options['collate'] ?? 'utf8mb4_unicode_ci';

		return " ENGINE=$engine CHARSET=$charset COLLATE=$collate";
	}

	/**
	 * Quote database identifier for current driver
	 *
	 * @param string $identifier Database identifier (table, column name)
	 * @return string Quoted identifier
	 */
	private function quoteIdentifier(string $identifier): string {
		return match($this->driver) {
			'pgsql' => "\"$identifier\"",
			default => "`$identifier`" // mysql
		};
	}

	/**
	 * Quote string value for SQL queries
	 *
	 * @param string $value String value to quote
	 * @return string Quoted string value
	 */
	private function quoteValue(string $value): string {
		return "'" . str_replace("'", "''", $value) . "'";
	}
}
