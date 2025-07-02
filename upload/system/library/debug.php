<?php
declare(strict_types=1);

namespace Opencart\System\Library;

use ErrorException;
use Exception;
use JsonException;
use Opencart\System\Library\Debug\View;
use RuntimeException;
use Opencart\System\Engine\Config;
use Throwable;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Debug toolkit for OpenCart developers
 */
final class Debug {
	/**
	 * @var Debug\View|null
	 */
	private static ?Debug\View $view = null;

	/**
	 * @var Config|null
	 */
	private static ?Config $config = null;

	/**
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Prevent instantiation
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 */
	public function __wakeup(): void {
		throw new RuntimeException("Cannot unserialize singleton");
	}

	/**
	 * Initialize debug system
	 *
	 * @param  Config  $config
	 *
	 * @return void
	 * @throws ErrorException
	 * @throws JsonException
	 * @throws Throwable
	 */
	public static function init(Config $config): void {
		if (self::$initialized) {
			return;
		}

		self::$config = $config;

		// Try to create our own Twig instance with full control
		try {
			self::$view = new Debug\View($config, self::createTwigEngine());
		} catch (Throwable $e) {
			// Fallback if Twig is not available or fails
			self::$view = new Debug\View($config, null);
		}
		self::$initialized = true;

		if ($config->get('error_display')) {
			self::registerHandlers();
		}
	}

	/**
	 * Create our own Twig engine with blackjack and... you know
	 *
	 * @return Environment
	 * @throws Exception if Twig is not available
	 */
	private static function createTwigEngine(): Environment {
		// Check if Twig is available
		if (!class_exists(Environment::class)) {
			throw new RuntimeException('Twig is not available');
		}

		// Create filesystem loader with our debug namespace
		$loader = new FilesystemLoader();
		$loader->addPath(DIR_SYSTEM . 'library/debug/view/template/', 'debug');

		// Configure Twig for debug tools (no cache, always reload)
		$twig = new Environment($loader, [
			'cache' => false,            // No cache - we want immediate changes
			'debug' => true,             // Enable debug mode for development
			'auto_reload' => true,       // Always reload templates
			'autoescape' => false,       // We handle escaping manually in debug output
			'strict_variables' => false, // Don't throw errors for undefined variables
		]);

		// Add debug extension for {{ dump() }} function
		$twig->addExtension(new DebugExtension());

		return $twig;
	}

	/**
	 * Register error and exception handlers
	 *
	 * @return void
	 * @throws ErrorException
	 * @throws JsonException
	 * @throws Throwable
	 */
	private static function registerHandlers(): void {
		// Convert PHP errors to exceptions
		set_error_handler(static function(int $code, string $message, string $file, int $line): ?bool {
			// Skip suppressed errors (@)
			if (!(error_reporting() & $code)) {
				return false;
			}

			// Add file and line info to message like in default OpenCart
			throw new ErrorException($message . ' in ' . $file . ' on line ' . $line, 0, $code, $file, $line);
		});

		// Handle all exceptions
		set_exception_handler(static function(Throwable $e): void {
			$handler = new Debug\Exception(self::getView());
			$handler->handle($e);
		});
	}

	/**
	 * Dump variables and die
	 *
	 * @param  mixed  ...$vars
	 *
	 * @return void
	 * @throws ErrorException
	 * @throws JsonException
	 * @throws Throwable
	 */
	public static function dd(...$vars): void {
		if (!self::isEnabled()) {
			return;
		}

		$dump = new Debug\Dump(self::getView());
		$dump->handle($vars, true);
	}

	/**
	 * Dump variables without dying
	 *
	 * @param  mixed  ...$vars
	 *
	 * @return void
	 * @throws ErrorException
	 * @throws JsonException
	 * @throws Throwable
	 */
	public static function dump(...$vars): void {
		if (!self::isEnabled()) {
			return;
		}

		$dump = new Debug\Dump(self::getView());
		$dump->handle($vars);
	}

	/**
	 * Get view instance
	 *
	 * @return View
	 * @throws ErrorException
	 * @throws JsonException
	 * @throws Throwable
	 */
	public static function getView(): Debug\View {
		if (self::$view === null) {
			self::init();
		}

		return self::$view;
	}

	/**
	 * Check if debug is enabled
	 *
	 * @return bool
	 */
	private static function isEnabled(): bool {
		return self::$config?->get('error_display') ?? false;
	}

	/**
	 * Get configuration value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getConfig(string $key, mixed $default = null): mixed {
		return self::$config?->get($key) ?: $default;
	}
}
