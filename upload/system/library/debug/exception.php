<?php
declare(strict_types=1);

namespace Opencart\System\Library\Debug;

use JsonException;
use Throwable;

/**
 * Exception Handler for Debug toolkit
 */
final class Exception extends BaseHandler {
	/**
	 * Handle exception and display error page
	 *
	 * @param  Throwable  $e
	 *
	 * @return void
	 * @throws Throwable
	 * @throws JsonException
	 */
	public function handle(Throwable $e): void {
		if (PHP_SAPI === 'cli') {
			throw $e;
		}

		http_response_code(500);
		$this->handleResponse('exception/exception', $this->prepareData($e));
	}

	/**
	 * Prepare data for template rendering
	 *
	 * @param Throwable $e
	 * @return array
	 */
	private function prepareData(Throwable $e): array {
		$headingTitle = htmlspecialchars(get_class($e));
		$message = htmlspecialchars($e->getMessage());
		$title = $headingTitle . ': ' . $message;
		$frames = $this->getFrames($e);
		$techInfo = $this->view->getTechInfo();
		$suggestions = $this->getSuggestedSolutions($e);

		return [
			'e'             => $e,
			'title'         => $title,
			'heading_title' => $headingTitle,
			'message'       => $message,
			'frames'        => $frames,
			'tech_info'     => $techInfo,
			'suggestions'   => $suggestions,
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
		$maxFrames = 15;

		foreach (array_slice($traces, 0, $maxFrames) as $trace) {
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
			$codeSnippet = implode(
				"\n",
				array_map('rtrim', array_slice($lines, $start - 1, $end - $start + 1))
			) . "\n";

			$frames[] = [
				'file'       => htmlspecialchars(str_replace(dirname(DIR_SYSTEM) . '/', '', $file)),
				'line'       => $line,
				'function'   => htmlspecialchars(($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? 'n/a')),
				'code'       => $codeSnippet,
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

		// Database issues
		if (str_contains($message, 'database') || str_contains($message, 'mysql') || str_contains($message, 'pdo')) {
			$suggestions[] = [
				'icon' => '🗄️',
				'text' => 'Check database connection settings in config.php'
			];
		}

		// Memory issues
		if (str_contains($message, 'memory') || str_contains($message, 'allowed memory size')) {
			$suggestions[] = [
				'icon' => '💾',
				'text' => 'Increase memory_limit in php.ini'
			];
		}

		return $suggestions;
	}

	/**
	 * Render JSON error response
	 *
	 * @param  array  $data
	 * @param  bool  $exit
	 *
	 * @return void
	 * @throws JsonException
	 */
	protected function renderJson(array $data, bool $exit = true): void {
		header('Content-Type: application/json');

		['e' => $e] = $data;

		echo json_encode([
			'error'   => true,
			'message' => $e->getMessage(),
			'type'    => get_class($e),
			'file'    => $e->getFile(),
			'line'    => $e->getLine()
		], JSON_THROW_ON_ERROR);

		if ($exit) {
			exit();
		}
	}

	/**
	 * Render fallback when Twig is not available
	 */
	protected function renderFallback(array $data): void {
		echo '<h1>' . ($data['heading_title'] ?? 'Exception') . '</h1>';
		echo '<p>' . ($data['message'] ?? 'Unknown error') . '</p>';

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
					$line_number = str_pad((string)$current_line, 3);
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
	}
}
