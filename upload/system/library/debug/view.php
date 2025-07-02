<?php
declare(strict_types=1);

namespace Opencart\System\Library\Debug;

use Exception;
use Opencart\System\Engine\Config;
use RuntimeException;
use Throwable;
use Twig\Environment;

/**
 * Debug View - handles rendering for all debug tools
 */
final class View {
	/**
	 * Constructor
	 *
	 * @param  Config           $config
	 * @param  Environment|null $twig
	 */
	public function __construct(
		private Config $config,
		private ?Environment $twig = null
	) {}

	/**
	 * Render debug page
	 *
	 * @throws Exception
	 */
	public function render(string $template_path, array $data): string {
		$data['tech_info'] = $this->getTechInfo();
		$data['editor_config'] = $this->getEditorConfig();
		$data['css'] = $this->getDebugCss();
		$data['js'] = $this->getDebugJs();

		if ($this->twig) {
			try {
				return $this->twig->render("@debug/$template_path.twig", $data);
			} catch (Throwable $e) {
				// Twig failed, let the caller handle fallback
				throw new RuntimeException("Twig template rendering failed: " . $e->getMessage(), 0, $e);
			}
		}

		// No Twig available, let the caller handle fallback
		throw new RuntimeException("Twig is not available for rendering debug templates");
	}

	/**
	 * Get debug CSS content
	 */
	private function getDebugCss(): string {
		$file = DIR_SYSTEM . 'library/debug/view/css/debug.css';

		return is_file($file) ? file_get_contents($file) : '';
	}

	/**
	 * Get debug JS content
	 */
	private function getDebugJs(): string {
		$file = DIR_SYSTEM . 'library/debug/view/js/debug.js';

		return is_file($file) ? file_get_contents($file) : '';
	}

	/**
	 * Get technical information about current request
	 *
	 * @return array
	 */
	public function getTechInfo(): array {
		$memoryUsage = memory_get_peak_usage(true);
		$memoryMb = round($memoryUsage / 1024 / 1024, 2);
		$memoryLimit = ini_get('memory_limit');

		return [
			'PHP Version'      => PHP_VERSION,
			'OpenCart Version' => defined('VERSION') ? VERSION : 'Unknown',
			'Error Time'       => date('Y-m-d H:i:s'),
			'Peak Memory'      => "$memoryMb MB / $memoryLimit",
			'Request Method'   => htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'Unknown'),
			'Request URI'      => htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown'),
			'Server Software'  => htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'),
		];
	}

	/**
	 * Get editor configuration
	 *
	 * @return array
	 */
	public function getEditorConfig(): array {
		return [
			'protocol' => $this->config->get('dev_editor_protocol') ?: 'auto',
			'custom_url' => $this->config->get('dev_editor_custom_url') ?: '',
			'enabled' => $this->config->get('dev_open_in_editor') ?: true,
		];
	}

	/**
	 * Get configuration value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getConfig(string $key, mixed $default = null): mixed {
		return $this->config->get($key) ?: $default;
	}

	/**
	 * Fallback rendering when template not found
	 *
	 * @param string $type
	 * @param array $data
	 * @return void
	 */
	private function renderFallback(string $type, array $data): void {
		echo "<h1>Debug - {$type}</h1>";
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}
}
