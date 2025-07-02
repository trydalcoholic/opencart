<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\Renderer;

use Throwable;

/**
 * Error Renderer Interface
 *
 * Defines contract for error page renderers
 *
 * @package OpenCart\System\Library\Error\Renderer
 */
interface RendererInterface {
	/**
	 * Render error data
	 *
	 * @param Throwable $e
	 *
	 * @return void
	 */
	public function render(Throwable $e): void;
}
