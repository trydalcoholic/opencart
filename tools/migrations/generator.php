<?php

declare(strict_types = 1);

/**
 * Migration Generator
 */

require_once __DIR__ . '/../../upload/system/helper/db_schema.php';

final class MigrationGenerator {
	private string $output_path;
	private string $template_path;
	private array $table_groups;

	public function __construct() {
		$this->output_path = __DIR__ . '/generated/';
		$this->template_path = __DIR__ . '/template.php';

		if (!is_dir($this->output_path)) {
			mkdir($this->output_path, 0755, true);
		}

		$this->clearGeneratedMigrations();

		// Group tables by functionality
		$this->table_groups = [
			'core' => [
				'session',
				'setting',
				'language',
				'currency',
				'layout',
				'layout_module',
				'layout_route',
				'extension',
				'extension_install',
				'extension_path',
				'modification',
				'event',
				'startup',
				'statistics',
				'cron',
			],
			'user' => [
				'user',
				'user_group',
				'user_authorize',
				'user_login',
				'user_token',
			],
			'catalog' => [
				'category',
				'category_description',
				'category_filter',
				'category_path',
				'category_to_layout',
				'category_to_store',
				'product',
				'product_description',
				'product_attribute',
				'product_code',
				'product_discount',
				'product_filter',
				'product_image',
				'product_option',
				'product_option_value',
				'product_related',
				'product_reward',
				'product_to_category',
				'product_to_download',
				'product_to_layout',
				'product_to_store',
				'product_viewed',
				'manufacturer',
				'manufacturer_description',
				'manufacturer_to_layout',
				'manufacturer_to_store',
			],
			'customer' => [
				'customer',
				'customer_group',
				'customer_group_description',
				'customer_activity',
				'customer_affiliate',
				'customer_approval',
				'customer_authorize',
				'customer_history',
				'customer_ip',
				'customer_login',
				'customer_online',
				'customer_reward',
				'customer_search',
				'customer_transaction',
				'customer_wishlist',
				'customer_token',
				'address',
			],
			'sales' => [
				'order',
				'order_history',
				'order_option',
				'order_product',
				'order_status',
				'order_total',
				'cart',
				'coupon',
				'coupon_category',
				'coupon_history',
				'coupon_product',
				'subscription',
				'subscription_history',
				'subscription_product',
			]
		];
	}

	public function generate(): void {
		$tables = oc_db_schema();

		foreach ($this->table_groups as $group_name => $table_names) {
			$this->generateGroupMigration($group_name, $table_names, $tables);
		}

		echo "Migration files generated successfully!\n";
	}

	private function clearGeneratedMigrations(): void {
		$files = glob($this->output_path . '*.php');

		foreach ($files as $file) {
			unlink($file);
		}

		echo "Cleared existing migrations\n";
	}

	private function generateGroupMigration(string $group_name, array $table_names, array $all_tables): void {
		$migration_name = "5-0-0-0-{$group_name}-001_create_{$group_name}_tables";
		$file_path = $this->output_path . $migration_name . '.php';

		$group_tables = [];

		foreach ($all_tables as $table) {
			if (in_array($table['name'], $table_names, true)) {
				$group_tables[] = $table;
			}
		}

		if (empty($group_tables)) {
			return;
		}

		$content = $this->buildMigrationContent($group_name, $group_tables);

		file_put_contents($file_path, $content);

		echo "Generated: $migration_name.php\n";
	}

	private function buildMigrationContent(string $group_name, array $tables): string {
		$template_content = file_get_contents($this->template_path);

		$up_code = '';
		$down_code = '';

		foreach ($tables as $table) {
			$up_code .= $this->buildCreateTableCode($table);
			$down_code .= "\t\t\$schema->dropTable('{$table['name']}');\n";
		}

		$up_code = rtrim($up_code);
		$down_code = rtrim($down_code);

		return str_replace(
			[
				'{{ DESCRIPTION }}',
				'{{ UP_CODE }}',
				'{{ DOWN_CODE }}'
			],
			[
				"Create $group_name tables for OpenCart 5.0.0.0",
				$up_code,
				$down_code
			],
			$template_content
		);
	}

	private function buildCreateTableCode(array $table): string {
		$code = "\t\t// Create {$table['name']} table\n";
		$code .= "\t\t\$schema->createTable('{$table['name']}', [\n";

		// Fields
		foreach ($table['field'] as $field) {
			$code .= "\t\t\t[\n";
			$code .= "\t\t\t\t'name' => '{$field['name']}',\n";

			$type = str_replace("'", "\\'", $field['type']);
			$code .= "\t\t\t\t'type' => '{$type}'";

			if (!empty($field['auto_increment'])) {
				$code .= ",\n\t\t\t\t'auto_increment' => true";
			}

			if (!empty($field['not_null'])) {
				$code .= ",\n\t\t\t\t'not_null' => true";
			}

			if (isset($field['default'])) {
				$code .= ",\n\t\t\t\t'default' => '{$field['default']}'";
			}

			if (isset($table['primary']) && in_array($field['name'], $table['primary'], true)) {
				$code .= ",\n\t\t\t\t'primary' => true";
			}

			$code .= "\n\t\t\t],\n";
		}

		$code .= "\t\t], [\n";

		// Options
		$code .= "\t\t\t'engine'  => '{$table['engine']}',\n";
		$code .= "\t\t\t'charset' => '{$table['charset']}',\n";
		$code .= "\t\t\t'collate' => '{$table['collate']}'";

		// Indexes
		if (!empty($table['index'])) {
			$code .= ",\n\t\t\t'indexes' => [\n";
			foreach ($table['index'] as $index) {
				$keys = "'" . implode("', '", $index['key']) . "'";
				$code .= "\t\t\t\t[\n";
				$code .= "\t\t\t\t\t'name' => '{$index['name']}',\n";
				$code .= "\t\t\t\t\t'keys' => [{$keys}]\n";
				$code .= "\t\t\t\t],\n";
			}
			$code .= "\t\t\t]";
		}

		$code .= "\n\t\t]);\n\n";

		return $code;
	}
}

// Generate migrations
$generator = new MigrationGenerator();
$generator->generate();
