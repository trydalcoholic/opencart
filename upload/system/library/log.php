<?php
/**
 * @package		OpenCart
 *
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2022, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 *
 * @see		https://www.opencart.com
 */
namespace Opencart\System\Library;
/**
 * Class Log
 */
class Log {
	/**
	 * @var string
	 */
	private string $file;
	/**
	 * @var string
	 */
	private string $filename;
	/**
	 * @var object
	 */
	private object $config;

	/**
	 * Constructor
	 *
	 * Initialize log file with provided filename and configuration object.
	 *
	 * @param string $filename Log file name (e.g., 'error.log', 'custom.log')
	 * @param object $config   Configuration object with get() method for accessing settings
	 *                         - error_filename: Default error log filename
	 *                         - config_error_log: Enable/disable error logging
	 *                         - error_date_format: Date format for log entries
	 *                         - error_log_max_length: Maximum message length
	 *                         - error_log_rotation_days: Days before rotating logs
	 *                         - error_log_rotation_size: Size limit for log rotation
	 *                         - error_log_rotation_date_format: Date format for rotated files
	 *                         - error_log_filename_format: File naming format template
	 *                         - error_log_counter_format: Counter format for size rotation
	 *
	 * @example
	 *
	 * $config = new Config();
	 * $log = new Log('application.log', $config);
	 *
	 * // Custom log file with rotation
	 * $log = new Log('payment_errors.log', $config);
	 */
	public function __construct(string $filename, object $config) {
		$this->filename = $filename;
		$this->file = DIR_LOGS . $filename;
		$this->config = $config;
	}

	/**
	 * Write
	 *
	 * Write a message to the log file with optional context data.
	 * Handles message formatting, rotation, and various data types.
	 *
	 * @param mixed      $message Log message (string, array, object, etc.)
	 * @param array|null $context Optional context data to include with the message
	 *
	 * @return void
	 *
	 * @example
	 *
	 * // Simple string message
	 * $log->write('User login successful');
	 *
	 * // Message with context
	 * $log->write('Payment failed', [
	 *     'user_id' => 123,
	 *     'amount' => 99.99,
	 *     'error_code' => 'CARD_DECLINED'
	 * ]);
	 *
	 * // Log array data
	 * $log->write([
	 *     'action' => 'order_created',
	 *     'order_id' => 456,
	 *     'total' => 199.99
	 * ]);
	 *
	 * // Log exception
	 * try {
	 *     // some code
	 * } catch (Exception $e) {
	 *     $log->write($e->getMessage(), [
	 *         'file' => $e->getFile(),
	 *         'line' => $e->getLine(),
	 *         'trace' => $e->getTraceAsString()
	 *     ]);
	 * }
	 */
	public function write(mixed $message, ?array $context = null): void {
		$error_filename = $this->config->get('error_filename');
		$config_error_log = $this->config->get('config_error_log');

		// Do not write to error log file if error logging is disabled, but allow writing to custom log files
		if ($this->filename == $error_filename && !$config_error_log) {
			return;
		}

		// Check if rotation is needed and get the current log file
		$current_file = $this->getCurrentLogFile();

		$formatted = $this->formatMessage($message);

		// Add context information if provided
		if ($context !== null && !empty($context)) {
			$formatted .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		// Sanitize message for file system safety
		$formatted = str_replace(["\0", "\r"], ['', ''], $formatted);

		// Ensure valid UTF-8 encoding to prevent file corruption and display issues
		if (!mb_check_encoding($formatted, 'UTF-8')) {
			$formatted = mb_convert_encoding($formatted, 'UTF-8', 'auto');
		}

		// Limit message length to prevent huge log files if configured
		$max_length = $this->config->get('error_log_max_length');

		if ($max_length !== null && mb_strlen($formatted) > (int)$max_length) {
			$formatted = mb_substr($formatted, 0, (int)$max_length) . '... [truncated]';
		}

		$output = date($this->config->get('error_date_format')) . ' - ' . $formatted . PHP_EOL;

		$handle = fopen($current_file, 'a');

		if ($handle) {
			flock($handle, LOCK_EX);
			fwrite($handle, $output);
			flock($handle, LOCK_UN);
			fclose($handle);
		}
	}

	/**
	 * Get Current Log File
	 *
	 * Determines which log file to use for writing based on rotation settings.
	 *
	 * @return string The full path to the log file that should be used for writing
	 */
	private function getCurrentLogFile(): string {
		$rotation_days = $this->config->get('error_log_rotation_days');
		$rotation_size = $this->config->get('error_log_rotation_size');

		// If rotation is disabled, use original file
		if ($rotation_days === null && $rotation_size === null) {
			return $this->file;
		}

		$path_info = pathinfo($this->file);
		$filename = $path_info['filename'];
		$extension = $path_info['extension'] ?? '';

		// Check for date-based rotation
		if ($rotation_days !== null) {
			$date_format = $this->config->get('error_log_rotation_date_format') ?: 'Y-m-d';
			$date_suffix = date($date_format);
			$dated_file = $this->formatLogFilename($filename, $extension, $date_suffix);

			// Check if we need size-based rotation within the same date period
			if ($rotation_size !== null && file_exists($dated_file)) {
				$size_limit = $this->parseSizeString($rotation_size);

				if (filesize($dated_file) >= $size_limit) {
					return $this->getNextSizeRotatedFile();
				}
			}

			return $dated_file;
		}

		// Only size-based rotation
		if ($rotation_size !== null && file_exists($this->file)) {
			$size_limit = $this->parseSizeString($rotation_size);

			if (filesize($this->file) >= $size_limit) {
				return $this->getNextSizeRotatedFile();
			}
		}

		return $this->file;
	}

	/**
	 * Format Log Filename
	 *
	 * Formats filename using configurable template with placeholders.
	 *
	 * @param string   $filename    Original filename without extension
	 * @param string   $extension   File extension with dot
	 * @param string   $date_suffix Date string for rotation
	 * @param int|null $counter     Counter for size rotation
	 *
	 * @return string Full path to formatted log file
	 */
	private function formatLogFilename(string $filename, string $extension, string $date_suffix, ?int $counter = null): string {
		$format = $this->config->get('error_log_filename_format') ?: '{filename}_{date}.{extension}';

		// Prepare replacements
		$replacements = [
			'{filename}'  => $filename,
			'{date}'      => $date_suffix,
			'{extension}' => $extension,
			'{counter}'   => ''
		];

		// Add counter if provided
		if ($counter !== null) {
			$counter_format = $this->config->get('error_log_counter_format') ?: '_{counter}';
			$replacements['{counter}'] = str_replace('{counter}', (string)$counter, $counter_format);
		}

		// Replace placeholders
		$formatted_filename = str_replace(array_keys($replacements), array_values($replacements), $format);

		// Clean up double separators and problematic characters
		$formatted_filename = preg_replace('/[_-]{2,}/', '_', $formatted_filename);
		$formatted_filename = trim($formatted_filename, '_-');

		return DIR_LOGS . $formatted_filename;
	}

	/**
	 * Parse Size String
	 *
	 * Converts size strings like "5MB" or "1GB" into bytes.
	 *
	 * @param string $size_string Size string with unit (e.g., "5MB", "1GB")
	 *
	 * @return int Size in bytes
	 */
	private function parseSizeString(string $size_string): int {
		$size_string = strtoupper(trim($size_string));

		$suffix = [
			'YB',
			'ZB',
			'EB',
			'PB',
			'TB',
			'GB',
			'MB',
			'KB',
			'B'
		];

		$multipliers = [
			'B'  => 1,
			'KB' => 1024,
			'MB' => 1024 ** 2,
			'GB' => 1024 ** 3,
			'TB' => 1024 ** 4,
			'PB' => 1024 ** 5,
			'EB' => 1024 ** 6,
			'ZB' => 1024 ** 7,
			'YB' => 1024 ** 8
		];

		foreach ($suffix as $unit) {
			if (str_ends_with($size_string, $unit)) {
				$number = (float)str_replace($unit, '', $size_string);

				return (int)($number * $multipliers[$unit]);
			}
		}

		// If no unit specified, treat as bytes
		return (int)$size_string;
	}

	/**
	 * Get Next Size Rotated File
	 *
	 * Finds the next available rotated file name when size limit is exceeded.
	 *
	 * @return string Path to the next rotated log file to use
	 */
	private function getNextSizeRotatedFile(): string {
		$path_info = pathinfo($this->file);
		$filename = $path_info['filename'];
		$extension = $path_info['extension'] ?? '';

		$counter = 2;

		while (true) {
			// Get rotation date if date rotation is enabled
			$date_suffix = '';

			if ($this->config->get('error_log_rotation_days') !== null) {
				$date_format = $this->config->get('error_log_rotation_date_format') ?: 'Y-m-d';
				$date_suffix = date($date_format);
			}

			// Use formatLogFilename to generate the next file with counter
			$new_file = $this->formatLogFilename($filename, $extension, $date_suffix, $counter);

			if (!file_exists($new_file)) {
				return $new_file;
			}

			$size_limit = $this->parseSizeString($this->config->get('error_log_rotation_size'));

			if (filesize($new_file) < $size_limit) {
				return $new_file;
			}

			$counter++;
		}
	}

	/**
	 * Format Message
	 *
	 * Converts any PHP data type to string format suitable for logging.
	 *
	 * @param mixed $message The message to format for logging
	 *
	 * @return string Formatted message as string
	 */
	private function formatMessage(mixed $message): string {
		return match (true) {
			is_string($message)  => $message,
			is_numeric($message) => (string)$message,
			is_bool($message)    => $message ? 'true' : 'false',
			null === $message    => 'null',
			is_array($message)   => json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: print_r($message, true),
			is_object($message)  => $this->formatObject($message),
			default              => print_r($message, true)
		};
	}

	/**
	 * Format Object
	 *
	 * Converts objects to their string representation for logging.
	 *
	 * @param object $message The object to format for logging
	 *
	 * @return string String representation of the object
	 */
	private function formatObject(object $message): string {
		if (method_exists($message, '__toString')) {
			return (string)$message;
		}

		$json = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $json ?: print_r($message, true);
	}
}
