<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\View;

use Error;
use ErrorException;
use Exception;
use Opencart\System\Library\Error\Analyzer\SolutionAnalyzer;
use Throwable;

/**
 * Error View Model
 *
 * Prepares exception data for view rendering with proper error location detection
 * and code snippet extraction for debugging purposes.
 *
 * @package Opencart\System\Library\Error\View
 */
final class ViewModel {
	private const MAX_FRAMES = 15;
	private const CONTEXT_LINES = 7;
	private const SYSTEM_PATHS = ['/system/engine/', '/system/library/'];

	/**
	 * Build complete error data array for view rendering
	 *
	 * @param Throwable $e The exception to process
	 *
	 * @return array<string, mixed> Complete error data for view
	 */
	public function build(Throwable $e): array {
		return [
			'e'             => $e,
			'title'         => $this->buildTitle($e),
			'heading_title' => $this->buildHeadingTitle($e),
			'message'       => htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
			'file'          => $e->getFile(),
			'line'          => $e->getLine(),
			'exceptions'    => $this->buildExceptionChain($e),
			'frames'        => $this->buildFrames($e),
			'tech_info'     => $this->buildTechInfo(),
			'suggestions'   => $this->buildSuggestions($e),
		];
	}

	/**
	 * Build full page title combining heading and message
	 *
	 * @param Throwable $e The exception
	 *
	 * @return string Complete page title
	 */
	private function buildTitle(Throwable $e): string {
		return $this->buildHeadingTitle($e) . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Build main heading showing exception class, file and line
	 *
	 * @param Throwable $e The exception
	 *
	 * @return string Formatted heading
	 */
	private function buildHeadingTitle(Throwable $e): string {
		return htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8') . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
	}

	/**
	 * Build complete exception chain for nested exceptions
	 *
	 * @param Throwable $e The root exception
	 *
	 * @return array<int, array<string, mixed>> Array of exception data
	 */
	private function buildExceptionChain(Throwable $e): array {
		$exceptions = [];
		$current = $e;

		while ($current !== null) {
			$exceptions[] = [
				'class'   => get_class($current),
				'message' => htmlspecialchars($current->getMessage(), ENT_QUOTES, 'UTF-8'),
				'file'    => $current->getFile(),
				'line'    => $current->getLine()
			];

			$current = $current->getPrevious();
		}

		return $exceptions;
	}

	/**
	 * Extract stack trace frames with code snippets
	 *
	 * @param Throwable $e The exception
	 *
	 * @return array<int, array<string, mixed>> Array of frame data with code snippets
	 */
	private function buildFrames(Throwable $e): array {
		$frames = [];
		$error_location = $this->determineErrorLocation($e);

		// Add main error frame first
		$frames[] = $this->buildCodeFrame($error_location['file'], $error_location['line'], 'ERROR SOURCE');

		// Add stack trace frames
		$traces = $e->getTrace();

		foreach (array_slice($traces, 0, self::MAX_FRAMES) as $trace) {
			if (!isset($trace['file'], $trace['line']) || !is_file($trace['file'])) {
				continue;
			}

			// Skip duplicate with main error frame
			if ($trace['file'] === $error_location['file'] && $trace['line'] === $error_location['line']) {
				continue;
			}

			$function = ($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? 'n/a');

			$frames[] = $this->buildCodeFrame($trace['file'], $trace['line'], $function);
		}

		return $frames;
	}

	/**
	 * Build a single code frame with syntax highlighting
	 *
	 * @param string $file     Full file path
	 * @param int    $line     Line number
	 * @param string $function Function/method name for this frame
	 *
	 * @return array<string, mixed> Frame data with highlighted code
	 */
	private function buildCodeFrame(string $file, int $line, string $function): array {
		$lines = @file($file);

		if ($lines === false) {
			return [
				'file'       => htmlspecialchars($this->relativePath($file), ENT_QUOTES, 'UTF-8'),
				'line'       => $line,
				'function'   => htmlspecialchars($function, ENT_QUOTES, 'UTF-8'),
				'code'       => 'File not readable',
				'start_line' => $line,
				'full_path'  => $file,
			];
		}

		$start = max(1, $line - self::CONTEXT_LINES);
		$end = min(count($lines), $line + self::CONTEXT_LINES);

		$raw_code = implode(
			"\n",
			array_map('rtrim', array_slice($lines, $start - 1, $end - $start + 1))
		);

		return [
			'file'       => htmlspecialchars($this->relativePath($file), ENT_QUOTES, 'UTF-8'),
			'line'       => $line,
			'function'   => htmlspecialchars($function, ENT_QUOTES, 'UTF-8'),
			'code'       => $raw_code,
			'start_line' => $start,
			'full_path'  => $file,
		];
	}

	/**
	 * Determine the actual location where error should be displayed
	 *
	 * Different logic for different exception types:
	 * - Error/ErrorException: Show actual error location
	 * - User exceptions: Show where exception was thrown
	 * - System exceptions: Show where user code called system code
	 *
	 * @param Throwable $e The exception
	 *
	 * @return array{file: string, line: int} Error location
	 */
	private function determineErrorLocation(Throwable $e): array {
		// For PHP errors (Error/ErrorException) - always use exception location
		if ($e instanceof Error || $e instanceof ErrorException) {
			return ['file' => $e->getFile(), 'line' => $e->getLine()];
		}

		// For regular exceptions - check if thrown in user or system code
		if ($e instanceof Exception) {
			$traces = $e->getTrace();

			// If thrown in user code - show where it was thrown
			if ($this->isUserThrownException($e)) {
				return [
					'file' => $e->getFile(),
					'line' => $e->getLine(),
				];
			}

			// If thrown in system code - show where user code called it
			if (isset($traces[0]['file'], $traces[0]['line'])) {
				return [
					'file' => $traces[0]['file'],
					'line' => $traces[0]['line'],
				];
			}
		}

		// Fallback to exception location
		return [
			'file' => $e->getFile(),
			'line' => $e->getLine(),
		];
	}

	/**
	 * Check if exception was thrown directly by user code
	 *
	 * @param Exception $e The exception to check
	 *
	 * @return bool True if thrown in user code, false if in system code
	 */
	private function isUserThrownException(Exception $e): bool {
		$exception_file = $e->getFile();

		foreach (self::SYSTEM_PATHS as $path) {
			if (str_contains($exception_file, $path)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Convert absolute file path to relative path from project root
	 *
	 * @param string $file Absolute file path
	 *
	 * @return string Relative file path
	 */
	private function relativePath(string $file): string {
		return str_replace(dirname(DIR_SYSTEM) . '/', '', $file);
	}

	/**
	 * Get technical information about current request and environment
	 *
	 * @return array<string, string> Technical information
	 */
	private function buildTechInfo(): array {
		$memory_usage = memory_get_peak_usage(true);
		$memory_mb = round($memory_usage / 1024 / 1024, 2);
		$memory_limit = ini_get('memory_limit');

		return [
			'PHP Version'      => PHP_VERSION,
			'OpenCart Version' => defined('VERSION') ? VERSION : 'Unknown',
			'Error Time'       => date('Y-m-d H:i:s'),
			'Peak Memory'      => "{$memory_mb} MB / {$memory_limit}",
			'Request Method'   => htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
			'Request URI'      => htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
			'Server Software'  => htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
		];
	}

	/**
	 * Build suggestions for resolving the error
	 *
	 * @param Throwable $e The exception to analyze
	 *
	 * @return array<int, string> Array of suggestions
	 */
	private function buildSuggestions(Throwable $e): array {
		return (new SolutionAnalyzer())->analyze($e);
	}
}
