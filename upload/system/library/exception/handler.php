<?php
declare(strict_types=1);

namespace Opencart\System\Library\Exception;

use Opencart\System\Engine\Config;
use Opencart\System\Library\Log;
use Throwable;

/**
 * Exception Handler for OpenCart
 *
 * Provides a beautiful error page with stack traces, technical information,
 * and helpful suggestions for resolving common issues.
 *
 * @package OpenCart\System\Library\Exception
 */
final class Handler {
	private View $view;

	/**
	 * Constructor
	 */
	public function __construct(
		private Config $config,
		private Log $log,
	) {
		$this->view = new View();
	}

	/**
	 * Handle exception and display error page
	 *
	 * @param Throwable  $e
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function handle(Throwable $e): void {
		if (PHP_SAPI === 'cli') {
			throw $e;
		}

		// Log the error if configured
		if ($this->config->get('config_error_log')) {
			$log_message = get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
			$this->log->write($log_message);
		}

		// Check display config
		if (!$this->config->get('config_error_display')) {
			header('Location: ' . $this->config->get('error_page'));
			exit();
		}

		http_response_code(500);

		$this->view->render($this->prepareData($e), $this->detectResponseType());
	}

	/**
	 * Detect the expected response type based on request headers
	 *
	 * Analyzes HTTP headers to determine if the client expects JSON or HTML response.
	 * Checks for AJAX requests, JSON content type, and accept headers.
	 *
	 * @return string Response type ('json' or 'html')
	 */
	private function detectResponseType(): string {
		// AJAX requests
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			return 'json';
		}

		// Content-Type: application/json
		if (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
			return 'json';
		}

		// Accept: application/json
		if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
			return 'json';
		}

		return 'html';
	}

	/**
	 * Prepare data for template rendering
	 *
	 * @param Throwable $e
	 * @return array
	 */
	private function prepareData(Throwable $e): array {
		$file = $e->getFile();
		$line = $e->getLine();
		$heading_title = htmlspecialchars(get_class($e)) . ' in ' . $file . ' on line ' . $line;
		$message = htmlspecialchars($e->getMessage());
		$title = $heading_title . ': ' . $message;

		return [
			'e'             => $e,
			'title'         => $title,
			'heading_title' => $heading_title,
			'message'       => $message,
			'file'          => $e->getFile(),
			'line'          => $e->getLine(),
			'frames'        => $this->getFrames($e),
			'tech_info'     => $this->getTechInfo(),
			'suggestions'   => $this->getSuggestedSolutions($e),
		];
	}

	/**
	 * Get technical information about current request
	 *
	 * @return array
	 */
	public function getTechInfo(): array {
		$memory_usage = memory_get_peak_usage(true);
		$memory_mb = round($memory_usage / 1024 / 1024, 2);
		$memory_limit = ini_get('memory_limit');

		return [
			'PHP Version'      => PHP_VERSION,
			'OpenCart Version' => defined('VERSION') ? VERSION : 'Unknown',
			'Error Time'       => date('Y-m-d H:i:s'),
			'Peak Memory'      => "$memory_mb MB / $memory_limit",
			'Request Method'   => htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'Unknown'),
			'Request URI'      => htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown'),
			'Server Software'  => htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'),
		];
	}

	/**
	 * Extract stack trace frames with code snippets
	 *
	 * @param Throwable $e
	 * @return array
	 */
	private function getFrames(Throwable $e): array {
		$frames = [];
		$traces = $e->getTrace();
		$max_frames = 15;

		foreach (array_slice($traces, 0, $max_frames) as $trace) {
			if (!isset($trace['file']) || !is_file($trace['file'])) {
				continue;
			}

			$file = $trace['file'];
			$line = $trace['line'];

			// Check if file is readable
			$lines = @file($file);

			if ($lines === false) {
				continue;
			}

			$start = max(1, $line - 7);
			$end = min(count($lines), $line + 7);

			// Extract code snippet
			$code_snippet = implode(
				"\n",
				array_map('rtrim', array_slice($lines, $start - 1, $end - $start + 1))
			) . "\n";

			$frames[] = [
				'file'       => htmlspecialchars(str_replace(dirname(DIR_SYSTEM) . '/', '', $file)),
				'line'       => $line,
				'function'   => htmlspecialchars(($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? 'n/a')),
				'code'       => $code_snippet,
				'start_line' => $start,
				'full_path'  => $file,
			];
		}

		return $frames;
	}

	/**
	 * Generate suggested solutions based on exception
	 *
	 * @param Throwable $e
	 * @return array
	 */
	private function getSuggestedSolutions(Throwable $e): array {
		$suggestions = [];

		$message = strtolower($e->getMessage());
		$file = $e->getFile();
		$exception_class = get_class($e);

		// DB issues
		if ($exception_class === 'mysqli_sql_exception' || str_contains($message, 'mysqli') || str_contains($message, 'mysql')) {
			$suggestions = array_merge($suggestions, $this->getDatabaseSuggestions($message));
		}

		// File not found issues
		if (str_contains($message, 'no such file or directory') || str_contains($message, 'failed to open stream')) {
			$suggestions[] = [
				'icon' => '📄',
				'text' => 'Check if the file path is correct and file exists'
			];

			$suggestions[] = [
				'icon' => '🔍',
				'text' => 'Verify file permissions and access rights'
			];
		}

		// Class not found issues
		if (str_contains($message, 'class') && str_contains($message, 'not found')) {
			$suggestions[] = [
				'icon' => '📦',
				'text' => 'Check if class file exists and is properly included'
			];

			$suggestions[] = [
				'icon' => '🏷️',
				'text' => 'Verify namespace and class name spelling'
			];
		}

		// Memory issues
		if (str_contains($message, 'memory') || str_contains($message, 'allowed memory size')) {
			$suggestions[] = [
				'icon' => '💾',
				'text' => 'Increase memory_limit in php.ini'
			];
		}

		// Model issues
		if (str_contains($file, 'model/') || (str_contains($message, 'model') && str_contains($message, 'not found'))) {
			$suggestions[] = [
				'icon' => '🏗️',
				'text' => 'Check if model is loaded in controller: $this->load->model(\'path/to/model\')'
			];

			$suggestions[] = [
				'icon' => '🗃️',
				'text' => 'Verify related database tables exist and are populated'
			];
		}

		// Template issues (Twig-specific)
		if ($exception_class === 'Twig\Error\SyntaxError' || str_contains($message, 'twig')) {
			$suggestions[] = [
				'icon' => '📝',
				'text' => 'Check Twig syntax in template file: variables, tags, filters'
			];

			$suggestions[] = [
				'icon' => '🔍',
				'text' => 'Verify template path and namespace in addPath()'
			];

			$suggestions[] = [
				'icon' => '🗑️',
				'text' => 'Clear template cache in storage/cache/template/'
			];
		}

		// OpenCart-specific issues
		if (str_contains($file, 'extension/') || str_contains($message, 'extension')) {
			$suggestions[] = [
				'icon' => '🛠️',
				'text' => 'Check if the extension is installed and enabled in Admin > Extensions'
			];

			$suggestions[] = [
				'icon' => '🔄',
				'text' => 'Refresh modifications in Admin > Extensions > Modifications'
			];

			$suggestions[] = [
				'icon' => '📂',
				'text' => 'Verify extension files are uploaded to catalog/extension/ or admin/extension/'
			];
		}

		if (str_contains($message, 'permission denied') || str_contains($message, 'write')) {
			$suggestions[] = [
				'icon' => '🔒',
				'text' => 'Check file permissions on storage/ (cache, logs, modifications) - should be 755/644'
			];
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
}
