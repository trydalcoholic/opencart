<?php

declare(strict_types=1);

namespace Opencart\System\Library\Error\Helper;

/**
 * ResponseTypeDetector
 *
 * @package OpenCart\System\Library\Error\Helper
 */
final class ResponseTypeDetector {
	/**
	 * Detect the expected response type based on request headers
	 *
	 * Analyzes HTTP headers to determine if the client expects JSON or HTML response.
	 * Checks for AJAX requests, JSON content type, and accept headers.
	 *
	 * @return string Response type ('json' or 'html')
	 */
	public static function detect(): string {
		// X-Requested-With: XMLHttpRequest
		$x_requested_with = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

		if ($x_requested_with === 'xmlhttprequest') {
			return 'json';
		}

		// Content-Type: application/json
		$content_type = strtolower($_SERVER['CONTENT_TYPE'] ?? '');

		if (str_contains($content_type, 'application/json')) {
			return 'json';
		}

		// Accept: application/json, text/html
		$http_accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');

		if (empty($http_accept)) {
			return 'html';
		}

		// Accept: application/json (without text/html)
		if (str_contains($http_accept, 'application/json') && !str_contains($http_accept, 'text/html')) {
			return 'json';
		}

		// Accept: application/json, text/html (JSON has higher priority)
		$json_pos = strpos($http_accept, 'application/json');
		$html_pos = strpos($http_accept, 'text/html');

		if ($json_pos !== false && $html_pos !== false && $json_pos < $html_pos) {
			return 'json';
		}

		return 'html';
	}
}
