<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\Renderer;

use Opencart\System\Engine\Config;
use Opencart\System\Library\Error\Factory\TwigEngineFactory;
use Opencart\System\Library\Error\Formatter\LogFormatter;
use Opencart\System\Library\Error\Helper\SyntaxHighlighter;
use Opencart\System\Library\Error\View\ViewModel;
use Opencart\System\Library\Log;
use RuntimeException;
use Throwable;
use Twig\Error\Error as TwigError;

/**
 * HtmlRenderer
 *
 * @package OpenCart\System\Library\Error\Renderer
 */
final class HtmlRenderer implements RendererInterface {
	private ViewModel $view_model;
	private array $editor_config;

	public function __construct(
		private TwigEngineFactory $twig_factory,
		private Config $config,
		private Log $log
	) {
		$this->view_model = new ViewModel();
		$this->editor_config = $this->getEditorConfig();
	}

	/**
	 * Render HTML error page for browser requests
	 *
	 * Attempts to use the configured template file. If template is not found,
	 * falls back to basic HTML output with stack trace information.
	 *
	 * @param Throwable $e
	 *
	 * @return void This method terminates execution
	 */
	public function render(Throwable $e): void {
		$data = $this->view_model->build($e);
		$data = $this->addHtmlData($data);

		$data['css'] = $this->getCss();
		$data['js'] = $this->getJs();

		// Try to create our own Twig instance with full control
		try {
			$twig = $this->twig_factory->create();

			echo $twig->render('@error/error.twig', $data);
		} catch (RuntimeException|TwigError $e) {
			$this->logTwigError($e);
			// Twig unavailable or template issues - use fallback
			$this->renderFallback($data);
		}
	}

	/**
	 * Enrich data with HTML-specific elements
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function addHtmlData(array $data): array {
		foreach ($data['frames'] as &$frame) {
			if (isset($frame['code'])) {
				$frame['code_html'] = $this->buildCodeHtml(
					$frame['code'],
					$frame['start_line'],
					$frame['line'],
					$frame['full_path']
				);
			}
		}

		return $data;
	}

	/**
	 * Build HTML for syntax highlighted code with line numbers
	 *
	 * @param string $raw_code   Raw source code
	 * @param int    $start      Starting line number
	 * @param int    $error_line Line number where error occurred
	 * @param string $file_path  Full file path for editor links
	 *
	 * @return string HTML with syntax highlighting
	 */
	private function buildCodeHtml(string $raw_code, int $start, int $error_line, string $file_path): string {
		$code_lines = explode("\n", $raw_code);
		$html = '';

		foreach ($code_lines as $index => $raw_line) {
			$line_number = $start + $index;
			$is_error = $line_number === $error_line;
			$classes = 'code-line' . ($is_error ? ' code-line--error' : '');
			$highlighted = SyntaxHighlighter::highlight($raw_line);

			$editor_url = $this->generateEditorUrl($file_path, $line_number);

			$html .= sprintf(
				'<div class="%s">
						<a href="%s" class="editor-link" title="Open in editor">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
								<path d="M21 6.75736L19 8.75736V4H10V9H5V20H19V17.2426L21 15.2426V21.0082C21 21.556 20.5551 22 20.0066 22H3.9934C3.44476 22 3 21.5501 3 20.9932V8L9.00319 2H19.9978C20.5513 2 21 2.45531 21 2.9918V6.75736ZM21.7782 8.80761L23.1924 10.2218L15.4142 18L13.9979 17.9979L14 16.5858L21.7782 8.80761Z"></path>
							</svg>
						</a>
						<span class="code-line__number">%d</span>
						<span class="code-line__content">%s</span>
					</div>',
				$classes,
				htmlspecialchars($editor_url, ENT_QUOTES, 'UTF-8'),
				$line_number,
				$highlighted
			);
		}

		return $html;
	}

	/**
	 * Generate editor URL for opening file at specific line
	 *
	 * @param string $file
	 * @param int    $line
	 *
	 * @return string
	 */
	private function generateEditorUrl(string $file, int $line): string {
		$config = $this->getEditorConfig();

		if (!$config['enabled']) {
			return '#';
		}

		$host_path = $this->mapPath($file, $config['path_mapping']);
		$editor_key = $config['default_editor'];

		if (!isset($config['editors'][$editor_key])) {
			return '#';
		}

		$editor = $config['editors'][$editor_key];

		return str_replace(
			['{file}', '{line}'],
			[urlencode($host_path), $line],
			$editor['url']
		);
	}

	/**
	 * Map container path to host path using configuration
	 *
	 * If mapping is not configured (empty strings), returns path as-is.
	 * Otherwise, performs Docker container to host path mapping.
	 *
	 * @param string $path    File path to map
	 * @param array  $mapping Mapping configuration with 'from' and 'to' keys
	 *
	 * @return string Mapped path or original path if no mapping configured
	 */
	private function mapPath(string $path, array $mapping): string {
		// If mapping is not configured (empty strings) - return path as-is
		if (empty($mapping['from']) || empty($mapping['to'])) {
			return $path;
		}

		// Otherwise perform Docker container to host path mapping
		if (str_starts_with($path, $mapping['from'])) {
			return str_replace($mapping['from'], $mapping['to'], $path);
		}

		return $path;
	}

	/**
	 * Get editor configuration
	 */
	private function getEditorConfig(): array {
		$path_to = $this->config->get('dev_editor_path_to');

		$enabled = $this->config->get('dev_editor_enabled') && !empty($path_to);

		return [
			'enabled'        => $enabled,
			'default_editor' => $this->config->get('dev_editor_default') ?? 'phpstorm',
			'editors'        => [
				'vscode' => [
					'name' => 'Visual Studio Code',
					'url'  => 'vscode://file/{file}:{line}',
				],
				'cursor' => [
					'name' => 'Cursor',
					'url'  => 'cursor://file/{file}:{line}',
				],
				'phpstorm' => [
					'name' => 'PHPStorm',
					'url'  => 'phpstorm://open?file={file}&line={line}',
				],
				'webstorm' => [
					'name' => 'WebStorm',
					'url'  => 'webstorm://open?file={file}&line={line}',
				],
				'sublimetext' => [
					'name' => 'Sublime Text',
					'url'  => 'subl://open?url=file://{file}&line={line}',
				],
				'zed' => [
					'name' => 'Zed',
					'url'  => 'zed://file/{file}:{line}',
				],
				'windsurf' => [
					'name' => 'Windsurf',
					'url'  => 'windsurf://file/{file}:{line}',
				],
			],
			'path_mapping' => [
				'from' => $this->config->get('dev_editor_path_from') ?? '/var/www/',
				'to'   => $path_to ?? ''
			]
		];
	}

	/**
	 * Log Twig rendering errors
	 *
	 * Logs Twig template rendering failures if error logging is enabled in configuration.
	 * Uses simple format for consistency with main error handler.
	 *
	 * @param RuntimeException|TwigError $e The exception that occurred during Twig rendering
	 *
	 * @return void
	 */
	private function logTwigError(RuntimeException|TwigError $e): void {
		if ($this->config->get('config_error_log')) {
			$message = LogFormatter::simple($e); // or json/psr3/apache

			$this->log->write($message);
		}
	}

	/**
	 * Get error CSS content
	 */
	private function getCss(): string {
		$file = DIR_SYSTEM . 'library/error/frontend/css/error.css';

		return is_file($file) ? file_get_contents($file) : '';
	}

	/**
	 * Get error JS content
	 */
	private function getJs(): string {
		$file = DIR_SYSTEM . 'library/error/frontend/js/error.js';

		return is_file($file) ? file_get_contents($file) : '';
	}

	/**
	 * Render fallback when Twig is not available
	 *
	 * @param array $data
	 */
	private function renderFallback(array $data): void {
		$title = $data['title'] ?? ($data['heading_title'] . ': ' . $data['message']);

		echo '<!DOCTYPE html>';
		echo '<html><head>';
		echo '<title>' . htmlspecialchars($title) . '</title>';
		echo '<style>
            :root {
                --bg: oklch(97% 0.01 260);
                --text: oklch(20% 0.01 260);
                --error: oklch(55% 0.15 29);
                --warning: oklch(65% 0.12 65);
                --error-bg: oklch(55% 0.15 29 / 0.1);
                --border: oklch(85% 0.01 260);
                --code-bg: oklch(100% 0 0);
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: oklch(15% 0.01 260);
                    --text: oklch(90% 0.01 260);
                    --error: oklch(70% 0.15 29);
                    --warning: oklch(75% 0.12 65);
                    --error-bg: oklch(70% 0.15 29 / 0.15);
                    --border: oklch(30% 0.01 260);
                    --code-bg: oklch(20% 0.01 260);
                }
            }

            body {
                font-family: monospace;
                padding-block: 1.25rem;
                padding-inline: 1.25rem;
                background: var(--bg);
                color: var(--text);
                margin: 0;
            }

            h1 {
                color: var(--error);
                margin-block: 0 1rem;
                margin-inline: 0;
            }

            h2 {
                margin-block: 1.5rem 0.75rem;
                margin-inline: 0;
            }

            pre {
                background: var(--code-bg);
                padding-block: 0.625rem;
                padding-inline: 0.625rem;
                border: 1px solid var(--border);
                overflow-x: auto;
                margin: 0;
            }

            summary {
                cursor: pointer;
                font-weight: bold;
                padding-block: 0.5rem;
                padding-inline: 0;
            }

            details {
                margin-block: 0.75rem;
                margin-inline: 0;
            }

            .error-line {
                background: var(--error-bg);
                font-weight: bold;
            }

            .chain {
                background: var(--code-bg);
                padding-block: 0.9375rem;
                padding-inline: 0.9375rem;
                margin-block: 0.625rem;
                margin-inline: 0;
                border-inline-start: 0.25rem solid var(--warning);
            }

            .current {
                border-inline-start-color: var(--error);
            }

            .line {
                display: flex;
            }

            .line-num {
                inline-size: 3rem;
                text-align: end;
                padding-inline-end: 0.5rem;
                color: var(--text);
                opacity: 0.6;
                user-select: none;
            }

            .line-content {
                flex: 1;
            }

            .arrow {
                color: var(--error);
                font-weight: bold;
            }
        </style>';
		echo '</head><body>';

		// Header
		echo '<h1>' . ($data['heading_title'] ?? 'Error') . '</h1>';
		echo '<p>' . ($data['message'] ?? 'Unknown error') . '</p>';

		// Exception Chain
		if (!empty($data['exceptions']) && count($data['exceptions']) > 1) {
			echo '<h2>Exception Chain</h2>';
			foreach ($data['exceptions'] as $index => $exception) {
				$is_current = $index === 0;
				echo '<div class="chain' . ($is_current ? ' current' : '') . '">';
				echo '<strong>' . ($index + 1) . '. ' . $exception['class'] . '</strong><br>';
				echo $exception['message'] . '<br>';
				echo '<small>' . $exception['file'] . ':' . $exception['line'] . '</small>';
				echo '</div>';
			}
		}

		// Stack Trace
		if (!empty($data['frames'])) {
			echo '<h2>Stack Trace</h2>';
			foreach ($data['frames'] as $index => $frame) {
				$is_first = $index === 0;
				echo '<details' . ($is_first ? ' open' : '') . '>';
				echo '<summary><strong>' . $frame['file'] . ':' . $frame['line'] . ' - ' . $frame['function'] . '()</strong></summary>';

				$lines = explode("\n", $frame['code']);
				$start_line = $frame['start_line'];
				$error_line = $frame['line'];

				echo '<pre>';
				foreach ($lines as $line_index => $line_content) {
					$current_line_number = $start_line + $line_index;
					$is_error_line = ($current_line_number === $error_line);

					$prefix = $is_error_line ? '→ ' : '  ';
					$line_num = str_pad((string)$current_line_number, 3);
					$content = rtrim($line_content);

					if ($is_error_line) {
						echo '<span class="error-line">' . $prefix . $line_num . ' ' . htmlspecialchars($content) . '</span>' . "\n";
					} else {
						echo $prefix . $line_num . ' ' . htmlspecialchars($content) . "\n";
					}
				}
				echo '</pre>';
				echo '</details>';
			}
		}

		echo '</body></html>';
	}
}
