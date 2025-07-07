<?php

declare(strict_types = 1);

use Opencart\System\Library\Migration\Connection;
use Opencart\System\Library\Migration\Schema;

/**
 * {{ DESCRIPTION }}
 */
return new class {
	/**
	 * Apply migration
	 *
	 * @param Schema $schema
	 * @param Connection $connection
	 * @return void
	 */
	public function up(Schema $schema, Connection $connection): void {
{{ UP_CODE }}
	}

	/**
	 * Rollback migration
	 *
	 * @param Schema $schema
	 * @param Connection $connection
	 * @return void
	 */
	public function down(Schema $schema, Connection $connection): void {
{{ DOWN_CODE }}
	}
};
