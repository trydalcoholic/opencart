<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\Formatter;

use JsonException;
use Throwable;

/**
 * Log Formatter
 *
 * Formats exception messages for logging
 *
 * @package OpenCart\System\Library\Error\Formatter
 */
final class LogFormatter {
	/**
	 * Simple human-readable format (default)
	 *
	 * @param Throwable $e
	 *
	 * @return string
	 */
	public static function simple(Throwable $e): string {
		return sprintf(
			'[%s] %s: %s in %s:%d',
			date('Y-m-d H:i:s'),
			get_class($e),
			$e->getMessage(),
			basename($e->getFile()),
			$e->getLine()
		);
	}

	/**
	 * JSON format for modern log analysis
	 *
	 * @param Throwable $e
	 *
	 * @throws JsonException
	 *
	 * @return string
	 */
	public static function json(Throwable $e): string {
		$data = [
			'timestamp' => date('c'),
			'level'     => 'error',
			'exception' => get_class($e),
			'message'   => $e->getMessage(),
			'file'      => $e->getFile(),
			'line'      => $e->getLine(),
			'context'   => [
				'url'    => $_SERVER['REQUEST_URI'] ?? 'CLI',
				'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
				'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
			]
		];

		return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
	}

	/**
	 * PSR-3 compatible format
	 *
	 * @param Throwable $e
	 *
	 * @return string
	 */
	public static function psr3(Throwable $e): string {
		return sprintf(
			'[%s] opencart.ERROR: %s {"exception":"%s","file":"%s","line":%d}',
			date('Y-m-d H:i:s'),
			$e->getMessage(),
			get_class($e),
			$e->getFile(),
			$e->getLine()
		);
	}

	/**
	 * Apache/Nginx style format
	 *
	 * @param Throwable $e
	 *
	 * @return string
	 */
	public static function apache(Throwable $e): string {
		return sprintf(
			'%s [error] %s: %s, referer: %s',
			date('[d/M/Y:H:i:s O]'),
			get_class($e),
			$e->getMessage(),
			$_SERVER['HTTP_REFERER'] ?? '-'
		);
	}
}
