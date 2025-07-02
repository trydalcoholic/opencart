<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\Factory;

use RuntimeException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Twig Engine Factory
 *
 * Creates and configures Twig Environment instances for error page rendering.
 * Handles template loading, debug configuration, and extension setup.
 *
 * @package OpenCart\System\Library\Error\Factory
 */
final class TwigEngineFactory {
	/**
	 * Constructor
	 *
	 * @param string $templates_path Path to error templates directory
	 */
	public function __construct(
		private string $templates_path,
	) {}

	/**
	 * @throws LoaderError      if template path is invalid
	 * @throws RuntimeException if Twig is not available
	 *
	 * @return Environment
	 */
	public function create(): Environment {
		// Check if Twig is available
		if (!class_exists(Environment::class)) {
			throw new RuntimeException('Twig is not available');
		}

		// Create filesystem loader with our error namespace
		$loader = new FilesystemLoader();
		$loader->addPath($this->templates_path, 'error');

		// Configure Twig for error tools (no cache, always reload)
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
}
