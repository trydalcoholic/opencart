<?php
namespace Opencart\System\Library\Debug;

/**
 * Exception Handler for OpenCart
 *
 * Provides a beautiful error page with stack traces, technical information,
 * and helpful suggestions for resolving common issues.
 *
 * @package OpenCart\System\Library\Debug
 */
final class ExceptionHandler {
	/**
	 * Constructor
	 *
	 * @param string $template_path Path to the exception template file
	 */
	public function __construct(
		private string $template_path = DIR_SYSTEM . 'library/debug/template/exception.tpl'
	) {}

	/**
	 * Handle exception and display error page
	 *
	 * Automatically detects request type (HTML/JSON) and renders appropriate response.
	 * In CLI mode, re-throws the exception for proper command line handling.
	 *
	 * @param \Throwable $e The exception to handle
	 * @return void This method terminates execution
	 * @throws \Throwable Re-throws exception in CLI mode
	 */
	public function __invoke(\Throwable $e): void {
		if (PHP_SAPI === 'cli') {
			throw $e;
		}

		http_response_code(500);

		$response_type = $this->detectResponseType();

		match($response_type) {
			'json' => $this->renderJson($e),
			default => $this->renderHtml($e)
		};
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
	 * Prepare data for error template rendering
	 *
	 * Extracts exception information and formats it for display including
	 * title, stack trace frames, technical environment info, and contextual suggestions.
	 * All output data is HTML-escaped for security.
	 *
	 * @param \Throwable $e The exception to process
	 * @return array<string, mixed> Formatted data array for template rendering
	 */
	private function prepareData(\Throwable $e): array {
		$heading_title = htmlspecialchars(get_class($e));
		$message = htmlspecialchars($e->getMessage());
		$title = $heading_title . ': ' . $message;
		$frames = $this->getFrames($e);
		$tech_info = $this->getTechInfo();
		$suggestions = $this->getSuggestedSolutions($e);

		return [
			'e'             => $e,
			'title'         => $title,
			'heading_title' => $heading_title,
			'message'       => $message,
			'frames'        => $frames,
			'tech_info'     => $tech_info,
			'suggestions'   => $suggestions,
		];
	}

	/**
	 * Extract stack trace frames with code snippets
	 *
	 * Reads source files and extracts code context around each stack frame.
	 * Limits to maximum 15 frames to prevent memory issues with deep stacks.
	 *
	 * @param \Throwable $e The exception
	 * @return array Array of frame information including file, line, function, and code
	 */
	private function getFrames(\Throwable $e): array {
		$frames = [];
		$traces = $e->getTrace();
		$max_frames = 15; // Limit frames to prevent memory issues

		// Don't add exception location - use only the trace which has function info
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

			// Extract code snippet around the error line
			$code_snippet = implode(
				"\n",
				array_map('rtrim', array_slice($lines, $start - 1, $end - $start + 1))
			) . "\n";

			$frames[] = [
				'file'       => htmlspecialchars(str_replace(dirname(DIR_SYSTEM) . '/', '', $file)),
				'line'       => $line,
				'function'   => htmlspecialchars(($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? 'n/a')),
				'code'       => $code_snippet,
				'start_line' => $start
			];
		}

		return $frames;
	}

	/**
	 * Get technical information about the current request and environment
	 *
	 * Collects system information, memory usage, and request details.
	 * All server data is HTML-escaped for security.
	 *
	 * @return array Associative array of technical information
	 */
	private function getTechInfo(): array {
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
	 * Generate suggested solutions based on exception type and message
	 *
	 * Analyzes the exception message and file path to provide contextual
	 * hints for resolving common OpenCart and PHP issues.
	 *
	 * @param \Throwable $e The exception to analyze
	 * @return array Array of suggestions with icons and text
	 */
	private function getSuggestedSolutions(\Throwable $e): array {
		$suggestions = [];
		$message = strtolower($e->getMessage());
		$file = $e->getFile();

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

		// Variable initialization issues
		if (str_contains($message, 'undefined')) {
			$suggestions[] = [
				'icon' => '🔍',
				'text' => 'Check if variables are properly initialized before use'
			];
		}

		// File permission issues
		if (str_contains($message, 'permission denied') && !str_contains($message, 'no such file')) {
			$suggestions[] = [
				'icon' => '🔐',
				'text' => 'Check file/directory permissions (should be 755/644)'
			];
			$suggestions[] = [
				'icon' => '📁',
				'text' => 'Verify web server has write access to storage directories'
			];
		}

		// Database connection issues
		if (str_contains($message, 'database') || str_contains($message, 'mysql') || str_contains($message, 'pdo')) {
			$suggestions[] = [
				'icon' => '🗄️',
				'text' => 'Check database connection settings in config.php'
			];
			$suggestions[] = [
				'icon' => '🔌',
				'text' => 'Verify MySQL service is running'
			];
		}

		// Memory limit issues
		if (str_contains($message, 'memory') || str_contains($message, 'allowed memory size')) {
			$suggestions[] = [
				'icon' => '💾',
				'text' => 'Increase memory_limit in php.ini'
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

			// Only suggest controller-specific advice if it's actually a controller class issue
			if (str_contains($message, 'controller')) {
				$suggestions[] = [
					'icon' => '🎮',
					'text' => 'Check controller file location and naming convention'
				];
			}
		}

		// Function not defined issues
		if (str_contains($message, 'function') && str_contains($message, 'not defined')) {
			$suggestions[] = [
				'icon' => '⚙️',
				'text' => 'Check if function exists or required extension is loaded'
			];
		}

		// Template issues - only when it's actually about templates
		if (str_contains($message, 'template') ||
			(str_contains($file, 'template') && str_contains($message, 'not found'))) {
			$suggestions[] = [
				'icon' => '🎨',
				'text' => 'Check if template file exists in correct theme directory'
			];
		}

		// Model issues - only when it's actually about models
		if (str_contains($message, 'model') ||
			(str_contains($file, 'model') && str_contains($message, 'not found'))) {
			$suggestions[] = [
				'icon' => '📊',
				'text' => 'Check model file location and class structure'
			];
		}

		// Route/action issues
		if (str_contains($message, 'route') || str_contains($message, 'action')) {
			$suggestions[] = [
				'icon' => '🗺️',
				'text' => 'Check route configuration and controller method exists'
			];
		}

		return $suggestions;
	}

	/**
	 * Render JSON error response for AJAX requests
	 *
	 * Outputs a structured JSON error response with exception details.
	 * Sets appropriate HTTP status code and content type headers.
	 *
	 * @param \Throwable $e The exception to render
	 * @return void This method terminates execution
	 * @throws \JsonException If JSON encoding fails
	 */
	private function renderJson(\Throwable $e): void {
		header('Content-Type: application/json');

		echo json_encode([
			'error'   => true,
			'message' => $e->getMessage(),
			'type'    => get_class($e),
			'file'    => $e->getFile(),
			'line'    => $e->getLine()
		], JSON_THROW_ON_ERROR);

		exit();
	}

	/**
	 * Render HTML error page for browser requests
	 *
	 * Attempts to use the configured template file. If template is not found,
	 * falls back to basic HTML output with stack trace information.
	 *
	 * @param \Throwable $e The exception to render
	 * @return void This method terminates execution
	 */
	private function renderHtml(\Throwable $e): void {
		$data = $this->prepareData($e);

		if (!is_file($this->template_path)) {
			// Fallback - basic HTML output
			echo '<h1>' . $data['heading_title'] . '</h1>';
			echo '<p>' . $data['message'] . '</p>';

			if (!empty($data['frames'])) {
				echo '<hr />';
				echo '<div style="display: flex; flex-direction: column; gap: 20px;">';

				foreach ($data['frames'] as $index => $frame) {
					$is_first = $index === 0;

					echo '<details' . ($is_first ? ' open' : '') . '>';
					echo '  <summary style="cursor: pointer;">' . $frame['file'] . ':' . $frame['line'] . ' - ' . $frame['function'] . '()</summary>';

					$lines = explode("\n", $frame['code']);
					$current_line = $frame['start_line'];
					$error_line = $frame['line'];

					echo '  <pre>';

					foreach ($lines as $line_content) {
						$is_error_line = ($current_line === $error_line);
						$prefix = $is_error_line ? '→ ' : '  ';
						$line_number = str_pad($current_line, 3);
						$content = rtrim($line_content);

						if ($is_error_line) {
							echo '<strong>' . $prefix . $line_number . ' ' . $content . '</strong>' . "\n";
						} else {
							echo $prefix . $line_number . ' ' . $content . "\n";
						}

						$current_line++;
					}

					echo '  </pre>';
					echo '</details>';
				}

				echo '</div>';
			}

			exit();
		}

		// Use template file
		extract($data, EXTR_SKIP);
		require $this->template_path;
		exit();
	}
}
