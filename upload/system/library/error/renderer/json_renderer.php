<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\Renderer;

use JsonException;
use Opencart\System\Library\Error\View\ViewModel;
use Throwable;

/**
 * JsonRenderer
 *
 * @package OpenCart\System\Library\Error\Renderer
 */
final class JsonRenderer implements RendererInterface {
	private ViewModel $view_model;

	public function __construct() {
		$this->view_model = new ViewModel();
	}

	/**
	 * Render JSON error response
	 *
	 * @param Throwable $e
	 *
	 * @throws JsonException
	 *
	 * @return void
	 */
	public function render(Throwable $e): void {
		$data = $this->view_model->build($e);

		$trace = array_map(static fn (array $frame): array => [
			'file'     => $frame['file'],
			'line'     => $frame['line'],
			'function' => $frame['function']
		], $data['frames']);

		$suggestions = array_map(static fn (array $suggestion): string => $suggestion['text'], $data['suggestions']);

		header('Content-Type: application/json');

		echo json_encode([
			'error'       => true,
			'message'     => $data['message'],
			'type'        => $data['heading_title'],
			'file'        => $data['file'],
			'line'        => $data['line'],
			'exceptions'  => $data['exceptions'],
			'trace'       => $trace,
			'suggestions' => $suggestions,
			'tech_info'   => $data['tech_info'],
		], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
	}
}
