<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\View;

use JsonException;
use Opencart\System\Engine\Config;
use Opencart\System\Library\Error\Factory\TwigEngineFactory;
use Opencart\System\Library\Error\Renderer\HtmlRenderer;
use Opencart\System\Library\Error\Renderer\JsonRenderer;
use Opencart\System\Library\Log;
use Throwable;

/**
 * Error View
 *
 * Renders error pages in HTML or JSON format for debugging and production use.
 * Handles both Twig-based templating and fallback rendering.
 *
 * @package Opencart\System\Library\Error\View
 */
final class View {
	private HtmlRenderer $html_renderer;
	private JsonRenderer $json_renderer;

	/**
	 * Constructor
	 *
	 * Initializes Twig engine factory with error templates path.
	 *
	 * @param Config $config
	 * @param Log    $log
	 */
	public function __construct(Config $config, Log $log) {
		$twig_factory = new TwigEngineFactory(
			templates_path: DIR_SYSTEM . 'library/error/frontend/template/'
		);

		$this->html_renderer = new HtmlRenderer($twig_factory, $config, $log);
		$this->json_renderer = new JsonRenderer();
	}

	/**
	 * Render error page
	 *
	 * @param Throwable $e
	 * @param string    $response_type Response format (html|json)
	 *
	 * @throws JsonException
	 *
	 * @return void
	 */
	public function render(Throwable $e, string $response_type = 'html'): void {
		match ($response_type) {
			'json'  => $this->json_renderer->render($e),
			default => $this->html_renderer->render($e)
		};
	}
}
