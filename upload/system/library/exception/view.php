<?php
declare(strict_types=1);

namespace Opencart\System\Library\Exception;

use Exception;
use RuntimeException;
use JsonException;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Exception View
 */
final class View {
	/**
	 * Render debug page
	 *
	 * @throws Exception
	 */
	public function render(array $data, $response_type = 'html'): void {
		match($response_type) {
			'json'  => $this->renderJson($data),
			default => $this->renderHtml($data)
		};

		exit();
	}

	/**
	 * Get exception CSS content
	 */
	private function getCss(): string {
		$file = DIR_SYSTEM . 'library/exception/view/css/exception.css';

		return is_file($file) ? file_get_contents($file) : '';
	}

	/**
	 * Get exception JS content
	 */
	private function getJs(): string {
		$file = DIR_SYSTEM . 'library/exception/view/js/exception.js';

		return is_file($file) ? file_get_contents($file) : '';
	}

	/**
	 * Get editor configuration
	 *
	 * @return array
	 */
//	public function getEditorConfig(): array {
//		return [
//			'protocol'   => $this->config->get('dev_editor_protocol') ?: 'auto',
//			'custom_url' => $this->config->get('dev_editor_custom_url') ?: '',
//			'enabled'    => $this->config->get('dev_open_in_editor') ?: true,
//		];
//	}

	/**
	 *
	 * @return Environment
	 * @throws Exception if Twig is not available
	 */
	private static function createTwigEngine(): Environment {
		// Check if Twig is available
		if (!class_exists(Environment::class)) {
			throw new RuntimeException('Twig is not available');
		}

		// Create filesystem loader with our exception namespace
		$loader = new FilesystemLoader();
		$loader->addPath(DIR_SYSTEM . 'library/exception/view/template/', 'exception');

		// Configure Twig for exception tools (no cache, always reload)
		$twig = new Environment($loader, [
			'cache'            => false, // No cache - we want immediate changes
			'debug'            => true,  // Enable debug mode for development
			'auto_reload'      => true,  // Always reload templates
			'autoescape'       => false, // We handle escaping manually in debug output
			'strict_variables' => false, // Don't throw errors for undefined variables
		]);

		// Add debug extension for {{ dump() }} function
		$twig->addExtension(new DebugExtension());

		return $twig;
	}

	/**
	 * Render JSON error response
	 *
	 * @param  array  $data
	 *
	 * @return void
	 * @throws JsonException
	 */
	private function renderJson(array $data): void {
		header('Content-Type: application/json');

		$trace = array_map(static function(array $frame): array {
			return [
				'file' => $frame['file'],
				'line' => $frame['line'],
				'function' => $frame['function']
			];
		}, $data['frames']);

		$suggestions = array_map(static function(array $suggestion): string {
			return $suggestion['text'];
		}, $data['suggestions']);

		echo json_encode([
			'error'       => true,
			'message'     => $data['message'],
			'type'        => $data['heading_title'],
			'file'        => $data['file'],
			'line'        => $data['line'],
			'trace'       => $trace,
			'suggestions' => $suggestions,
			'tech_info'   => $data['tech_info'],
		], JSON_THROW_ON_ERROR);
	}

	/**
	 * Render HTML error page for browser requests
	 *
	 * Attempts to use the configured template file. If template is not found,
	 * falls back to basic HTML output with stack trace information.
	 *
	 * @param  array  $data
	 *
	 * @return void This method terminates execution
	 */
	private function renderHtml(array $data): void {
//		$data['editor_config'] = $this->getEditorConfig();
		$data['css'] = $this->getCss();
		$data['js'] = $this->getJs();

		// Try to create our own Twig instance with full control
		try {
			$twig = $this->createTwigEngine();
			echo $twig->render('@exception/exception.twig', $data);
		} catch (Exception $e) {
			// Fallback if Twig is not available or fails
			$this->renderFallback($data);
		}
	}

	/**
	 * Render fallback when Twig is not available
	 */
	private function renderFallback(array $data): void {
		echo '
        <style>
            :root {
              --color-bg: oklch(98% 0.005 260);
              --color-text: oklch(20% 0.01 260);
              --color-header-text: oklch(70% 0.18 29);
              --color-frame-bg: oklch(96% 0.005 260);
              --color-frame-border: oklch(90% 0.01 260);
              --color-error-line-text: oklch(55% 0.18 29);
            }

            /* Reset */
            html {
              text-size-adjust: none;
              padding: 1.25rem;
            }

            html,
            body {
              font-size: 16px;
              color: var(--color-text);
              background-color: var(--color-bg);
              font-family: "JetBrains Mono", "Consolas", "Monaco", "monospace";
            }

            body {
              min-block-size: 100vh;
              display: flex;
              flex-direction: column;
              margin: 0;
              padding: 0;
            }

            h1 {
              color: var(--color-header-text);
            }

            details {
              background-color: var(--color-frame-bg);
              border-radius: 0.375rem;
              border: 0.063rem solid var(--color-frame-border);
            }

            details[open] {
              border-radius: 0.375rem 0.375rem 0 0;
            }

            summary {
              padding: 1rem;
              border-block-end: 1px solid var(--color-frame-border);
              cursor: pointer;
              background-color: var(--color-frame-bg);
              border-radius: 0.375rem;
              display: flex;
              flex-direction: column;
              gap: 0.25rem;
              list-style: none;
              font-weight: bold;
            }

            pre {
              font-size: 0.875rem;
              line-height: 1.25rem;
              margin: 0;
              padding: 0;
              overflow-x: auto;
            }

            strong {
              color: var(--color-error-line-text);
              font-weight: bold;
            }
        </style>
        ';

		echo '<h1>' . ($data['heading_title'] ?? 'Exception') . '</h1>';
		echo '<p>' . ($data['message'] ?? 'Unknown error') . '</p>';

		if (!empty($data['frames'])) {
			echo '<hr />';
			echo '<div style="display: flex; flex-direction: column; gap: 1.25rem;">';

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
