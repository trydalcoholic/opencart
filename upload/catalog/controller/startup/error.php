<?php
namespace Opencart\Catalog\Controller\Startup;
use ErrorException;
use Opencart\System\Engine\Controller;
use Opencart\System\Library\Log;
use Throwable;

/**
 * Class Error
 *
 * @package Opencart\Catalog\Controller\Startup
 */
class Error extends Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->registry->set('log', new Log($this->config->get('config_error_filename')));

		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);
	}

	/**
	 * Error
	 *
	 * @param int    $code
	 * @param string $message
	 * @param string $file
	 * @param int    $line
	 *
	 * @return bool
	 * @throws ErrorException
	 */
	public function error(int $code, string $message, string $file, int $line): bool {
		// error suppressed with @
		if (!(error_reporting() & $code)) {
			return false;
		}

		throw new ErrorException($message, 0, $code, $file, $line);
	}

	/**
	 * Exception
	 *
	 * @param Throwable $e
	 *
	 * @return void
	 */
	public function exception(object $e): void {
		$message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();

		if ($this->config->get('config_error_log')) {
			$this->log->write($message);
		}

		if ($this->config->get('config_error_display')) {
			echo $message;
		} else {
			header('Location: ' . $this->config->get('error_page'));
			exit();
		}
	}
}
