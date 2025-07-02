<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\Analyzer;

use Throwable;

/**
 * Solution Analyzer
 *
 * Analyzes exceptions and provides helpful suggestions
 *
 * @package OpenCart\System\Library\Error\Analyzer
 */
final class SolutionAnalyzer {
	/**
	 * Analyze exception and generate suggestions
	 *
	 * @param Throwable $e
	 *
	 * @return array
	 */
	public function analyze(Throwable $e): array {
		$suggestions = [];
		$message = strtolower($e->getMessage());
		$file = $e->getFile();
		$exception_class = get_class($e);

		// Database issues
		if ($exception_class === 'mysqli_sql_exception' || str_contains($message, 'mysqli') || str_contains($message, 'mysql')) {
			$suggestions = array_merge($suggestions, $this->getDatabaseSuggestions($message));
		}

		// File not found issues
		if (str_contains($message, 'no such file or directory') || str_contains($message, 'failed to open stream')) {
			$suggestions = array_merge($suggestions, $this->getFileNotFoundSuggestions());
		}

		// Class not found issues
		if (str_contains($message, 'class') && str_contains($message, 'not found')) {
			$suggestions = array_merge($suggestions, $this->getClassNotFoundSuggestions());
		}

		// Memory issues
		if (str_contains($message, 'memory') || str_contains($message, 'allowed memory size')) {
			$suggestions = array_merge($suggestions, $this->getMemorySuggestions());
		}

		// Model issues
		if (str_contains($file, 'model/') || (str_contains($message, 'model') && str_contains($message, 'not found'))) {
			$suggestions = array_merge($suggestions, $this->getModelSuggestions());
		}

		// Template issues
		if ($exception_class === 'Twig\Error\SyntaxError' || str_contains($message, 'twig')) {
			$suggestions = array_merge($suggestions, $this->getTemplateSuggestions());
		}

		// OpenCart-specific issues
		if (str_contains($file, 'extension/') || str_contains($message, 'extension')) {
			$suggestions = array_merge($suggestions, $this->getExtensionSuggestions());
		}

		// Permission issues
		if (str_contains($message, 'permission denied') || str_contains($message, 'write')) {
			$suggestions = array_merge($suggestions, $this->getPermissionSuggestions());
		}

		return $suggestions;
	}

	private function getDatabaseSuggestions(string $message): array {
		$suggestions = [];

		// Connection refused
		if (str_contains($message, 'connection refused')) {
			$suggestions[] = [
				'icon' => '🔑',
				'text' => 'MySQL running but refusing connection. Check username/password in config.php'
			];

			$suggestions[] = [
				'icon' => '🔢',
				'text' => 'Verify port number in config.php'
			];

			$suggestions[] = [
				'icon' => '🌐',
				'text' => 'Check if MySQL allows connections from your host'
			];
		}

		// Access denied
		if (str_contains($message, 'access denied')) {
			$suggestions[] = [
				'icon' => '🔑',
				'text' => 'Check database username and password in config.php'
			];

			$suggestions[] = [
				'icon' => '🏗️',
				'text' => 'Verify database user has proper privileges (SELECT, INSERT, UPDATE, DELETE)'
			];

			$suggestions[] = [
				'icon' => '🌍',
				'text' => 'Check if database user is allowed to connect from current host'
			];
		}

		// Database doesn't exist
		if (str_contains($message, 'unknown database') || str_contains($message, "doesn't exist")) {
			$suggestions[] = [
				'icon' => '🗃️',
				'text' => 'Check if database name is correct in config.php'
			];

			$suggestions[] = [
				'icon' => '📋',
				'text' => 'Verify database exists: SHOW DATABASES;'
			];

			$suggestions[] = [
				'icon' => '🔨',
				'text' => 'Create database if missing or run OpenCart installation'
			];
		}

		// Table doesn't exist
		if (str_contains($message, "table") && str_contains($message, "doesn't exist")) {
			$suggestions[] = [
				'icon' => '📊',
				'text' => 'Check if OpenCart tables are properly installed'
			];

			$suggestions[] = [
				'icon' => '🔄',
				'text' => 'Run database installation/migration scripts'
			];

			$suggestions[] = [
				'icon' => '🏪',
				'text' => 'Verify table prefix in config.php matches database'
			];
		}

		// Too many connections
		if (str_contains($message, 'too many connections')) {
			$suggestions[] = [
				'icon' => '⚡',
				'text' => 'Increase max_connections in MySQL configuration'
			];

			$suggestions[] = [
				'icon' => '🔄',
				'text' => 'Check for connection leaks - ensure connections are properly closed'
			];
		}

		// Server has gone away
		if (str_contains($message, 'server has gone away')) {
			$suggestions[] = [
				'icon' => '⏰',
				'text' => 'Increase wait_timeout and interactive_timeout in MySQL'
			];

			$suggestions[] = [
				'icon' => '📦',
				'text' => 'Check max_allowed_packet size for large queries'
			];
		}

		// Syntax errors
		if (str_contains($message, 'syntax error') || str_contains($message, 'sql syntax')) {
			$suggestions[] = [
				'icon' => '📝',
				'text' => 'Check SQL query syntax for typos or missing quotes'
			];

			$suggestions[] = [
				'icon' => '🔍',
				'text' => 'Verify table and column names are correct'
			];
		}

		// Deadlock
		if (str_contains($message, 'deadlock')) {
			$suggestions[] = [
				'icon' => '🔒',
				'text' => 'Database deadlock detected - transaction will be retried'
			];

			$suggestions[] = [
				'icon' => '🔄',
				'text' => 'Consider optimizing query order to prevent deadlocks'
			];
		}

		// General database connection issues
		if (empty($suggestions)) {
			$suggestions[] = [
				'icon' => '🗄️',
				'text' => 'Check database connection settings in config.php'
			];

			$suggestions[] = [
				'icon' => '🔧',
				'text' => 'Verify MySQL/MariaDB service is running and accessible'
			];
		}

		return $suggestions;
	}

	private function getFileNotFoundSuggestions(): array {
		return [
			['icon' => '📄', 'text' => 'Check if the file path is correct and file exists'],
			['icon' => '🔍', 'text' => 'Verify file permissions and access rights'],
		];
	}

	private function getClassNotFoundSuggestions(): array {
		return [
			['icon' => '📦', 'text' => 'Check if class file exists and is properly included'],
			['icon' => '🏷️', 'text' => 'Verify namespace and class name spelling'],
		];
	}

	private function getMemorySuggestions(): array {
		return [
			['icon' => '💾', 'text' => 'Increase memory_limit in php.ini'],
			['icon' => '🔍', 'text' => 'Check for memory leaks in your code'],
			['icon' => '📊', 'text' => 'Profile memory usage to identify the bottleneck'],
		];
	}

	private function getModelSuggestions(): array {
		return [
			['icon' => '🏗️', 'text' => 'Check if model is loaded in controller: $this->load->model(\'path/to/model\')'],
			['icon' => '🗃️', 'text' => 'Verify related database tables exist and are populated'],
			['icon' => '📁', 'text' => 'Ensure model file exists in catalog/model/ or admin/model/'],
		];
	}

	private function getTemplateSuggestions(): array {
		return [
			['icon' => '📝', 'text' => 'Check Twig syntax in template file: variables, tags, filters'],
			['icon' => '🔍', 'text' => 'Verify template path and namespace in addPath()'],
			['icon' => '🗑️', 'text' => 'Clear template cache in storage/cache/template/'],
		];
	}

	private function getExtensionSuggestions(): array {
		return [
			['icon' => '🛠️', 'text' => 'Check if the extension is installed and enabled in Admin > Extensions'],
			['icon' => '🔄', 'text' => 'Refresh modifications in Admin > Extensions > Modifications'],
			['icon' => '📂', 'text' => 'Verify extension files are uploaded to catalog/extension/ or admin/extension/'],
		];
	}

	private function getPermissionSuggestions(): array {
		return [
			['icon' => '🔒', 'text' => 'Check file permissions on storage/ (cache, logs, modifications) - should be 755/644'],
			['icon' => '👤', 'text' => 'Verify web server user has write access to the directory'],
			['icon' => '🔧', 'text' => 'Check SELinux or AppArmor restrictions if on Linux'],
		];
	}
}
