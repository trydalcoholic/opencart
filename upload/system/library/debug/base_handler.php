<?php
declare(strict_types=1);

namespace Opencart\System\Library\Debug;

use RuntimeException;

/**
 * Base handler for debug components
 */
abstract class BaseHandler {
	public function __construct(
		protected View $view
	) {}

	/**
	 * Detect expected response type
	 */
	protected function detectResponseType(): string {
		// AJAX requests
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			return 'json';
		}

		// Content-Type: application/json
		if (!empty($_SERVER['CONTENT_TYPE']) &&
			str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
			return 'json';
		}

		// Accept: application/json
		if (!empty($_SERVER['HTTP_ACCEPT']) &&
			str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
			return 'json';
		}

		return 'html';
	}

	/**
	 * Handle response based on type
	 */
	protected function handleResponse(string $template_path, array $data, bool $exit = true): void {
		$responseType = $this->detectResponseType();

		match($responseType) {
			'json' => $this->renderJson($data, $exit),
			default => $this->renderHtml($template_path, $data, $exit)
		};
	}

	/**
	 * Render HTML response
	 *
	 * @throws \Exception
	 */
	protected function renderHtml(string $template_path, array $data, bool $exit = true): void {
		try {
			echo $this->view->render($template_path, $data);
		} catch (RuntimeException $e) {
			$this->renderFallback($data);
		}

		if ($exit) {
			exit();
		}
	}

	/**
	 * Render JSON response - override in child classes
	 */
	abstract protected function renderJson(array $data, bool $exit = true): void;

	/**
	 * Render fallback when Twig is not available - each handler implements this
	 */
	abstract protected function renderFallback(array $data): void;
}
