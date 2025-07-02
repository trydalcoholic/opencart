<?php
declare(strict_types=1);

namespace Opencart\System\Library\Debug;

use JsonException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;

/**
 * Handles the rendering of debug information for variables.
 */
final class Dump extends BaseHandler {
	/**
	 * @var string[] A list of internal files to skip when searching for the caller.
	 */
	private const INTERNAL_FILES = ['Debug.php', 'Dump.php', 'general.php'];

	/**
	 * @var array An accessible color palette using the oklch() color space.
	 */
	private const COLORS = [
		"boolean"  => "oklch(76% 0.16 325)",
		"number"   => "oklch(75% 0.15 80)",
		"string"   => "oklch(70% 0.17 145)",
		"class"    => "oklch(70% 0.18 250)",
		"property" => "oklch(76% 0.16 325)",
		"modifier" => "oklch(70% 0.05 0)",
		"type"     => "oklch(70% 0.18 250)",
		"resource" => "oklch(75% 0.15 80)",
		"null"     => "oklch(76% 0.16 325)",
		"error"    => "oklch(70% 0.22 25)",
		"meta"     => "oklch(70% 0.05 0)",
		"border"   => "oklch(30% 0.01 0)",
	];

	/**
	 * @var array Tracks object hashes to prevent circular references.
	 */
	private array $processed_hashes = [];

	/**
	 * Handle variable dumping.
	 *
	 * @param  array  $vars  Variables to dump.
	 * @param  bool  $die  Whether to exit after dumping.
	 *
	 * @return void
	 */
	public function handle(array $vars, bool $die = false): void {
		$this->processed_hashes = [];

		try {
			if (PHP_SAPI === 'cli') {
				$this->renderCli($vars);
			} else {
				$data = $this->prepareData($vars);
				$this->handleResponse('dump', $data, $die);
			}
		} finally {
			$this->processed_hashes = [];
		}

		if ($die) {
			exit();
		}
	}

	/**
	 * Get caller information from the backtrace.
	 *
	 * @return array{file: string, line: int, function: string}
	 */
	private function getCaller(): array {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		foreach ($trace as $frame) {
			if (isset($frame['file']) && !in_array(basename($frame['file']), self::INTERNAL_FILES, true)) {
				return [
					'file'     => $frame['file'],
					'line'     => $frame['line'] ?? 0,
					'function' => $frame['function'] ?? 'unknown',
				];
			}
		}

		return ['file' => 'unknown', 'line' => 0, 'function' => 'unknown'];
	}

	/**
	 * Get memory_limit from php.ini in bytes.
	 *
	 * @return int Returns -1 for unlimited.
	 */
	private function memoryLimitBytes(): int {
		$val = trim(ini_get('memory_limit'));

		if ($val === '' || $val === '-1') {
			return -1;
		}

		$num = (int)$val;
		$last = strtolower($val[strlen($val) - 1]);

		return match ($last) {
			'g' => $num * 1024 * 1024 * 1024,
			'm' => $num * 1024 * 1024,
			'k' => $num * 1024,
			default => $num,
		};
	}

	/**
	 * Prepare data for template rendering.
	 *
	 * @param array $vars
	 * @return array
	 */
	private function prepareData(array $vars): array {
		$formatted_vars = [];
		foreach ($vars as $index => $variable) {
			$formatted_vars[] = [
				'index' => $index,
				'raw'   => $variable,
				'simple_value' => $this->formatSimple($variable)
			];
		}

		return [
			'caller'    => $this->getCaller(),
			'variables' => $formatted_vars,
			'tech_info' => $this->view->getTechInfo(),
			'count'     => count($vars)
		];
	}

	/**
	 * Render dump for Command Line Interface.
	 *
	 * @param array $vars
	 * @return void
	 */
	private function renderCli(array $vars): void {
		$caller = $this->getCaller();

		echo "\n=== DEBUG DUMP ===\n";
		echo "Called from: {$caller['file']}:{$caller['line']}\n";
		echo "Function: {$caller['function']}\n\n";

		foreach ($vars as $index => $variable) {
			echo "Variable #" . ($index + 1) . ":\n";
			var_dump($variable);
			echo "\n";
		}

		echo "==================\n\n";
	}

	/**
	 * Render dump as a JSON response.
	 *
	 * @param array $data
	 * @param bool  $exit
	 *
	 * @return void
	 * @throws JsonException
	 */
	protected function renderJson(array $data, bool $exit = true): void {
		header('Content-Type: application/json');

		$variables = array_map(
			static fn(array $variable_data) => $variable_data['simple_value'],
			$data['variables']
		);

		echo json_encode([
			'debug_dump' => true,
			'caller'     => $data['caller'],
			'variables'  => $variables,
			'count'      => $data['count']
		], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

		if ($exit) {
			exit();
		}
	}

	/**
	 * Render a fallback HTML view when the primary template engine is not available.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function renderFallback(array $data): void {
		$caller = $data['caller'];

		echo '<h1>Debug Dump</h1>';
		echo '<p>Called from: <strong>' . htmlspecialchars($caller['file']) . ':' . $caller['line'] . '</strong> in ' . htmlspecialchars($caller['function']) . '()</p>';

		if (!empty($data['variables'])) {
			echo '<hr />';

			foreach ($data['variables'] as $variable_data) {
				echo $this->renderVariableHtml($variable_data['raw'], 0, 10, true);
			}
		}
	}

	/**
	 * Main HTML rendering function for a single variable.
	 *
	 * @param mixed $var
	 * @param int $depth
	 * @param int $max_depth
	 * @param bool $is_root
	 * @return string
	 */
	public function renderVariableHtml(mixed $var, int $depth = 0, int $max_depth = 10, bool $is_root = false): string {
		if ($depth > $max_depth) {
			return sprintf('<span style="color: %s;">... max depth reached ...</span>', self::COLORS['meta']);
		}

		$memory_limit = $this->memoryLimitBytes();
		if ($memory_limit > 0 && ($memory_limit - memory_get_usage(true)) < 1024 * 1024) {
			return sprintf('<span style="color: %s;">... insufficient memory ...</span>', self::COLORS['error']);
		}

		$type = gettype($var);
		$color = self::COLORS[$type] ?? '#111';

		return match ($type) {
			'boolean', 'NULL' => sprintf('<span style="font-weight: 600; color: %s;">%s</span>', $color, $var === true ? 'true' : ($var === false ? 'false' : 'null')),
			'integer', 'double' => sprintf('<span style="color: %s;">%s</span>', $color, is_int($var) ? number_format($var) : $var),
			'string'   => $this->renderStringHtml($var, $depth),
			'array'    => $this->renderArrayHtml($var, $depth, $max_depth, $is_root),
			'object'   => $this->renderObjectHtml($var, $depth, $max_depth, $is_root),
			'resource' => sprintf('<span style="color: %s;">resource(%s) #%d</span>', self::COLORS['resource'], get_resource_type($var), (int)$var),
			default    => 'unknown type',
		};
	}

	/**
	 * Renders a string for HTML view.
	 */
	private function renderStringHtml(string $value, int $depth): string {
		$length = strlen($value);
		$max_length = max(80, 500 - ($depth * 80));

		$html = sprintf('<span style="color: %s;">"', self::COLORS['string']);
		if ($length > $max_length) {
			$html .= htmlspecialchars(substr($value, 0, $max_length)) . '...';
		} else {
			$html .= htmlspecialchars($value);
		}
		$html .= '"</span>';

		if ($length > $max_length) {
			$html .= sprintf(' <span style="color: %s;">(length: %s)</span>', self::COLORS['meta'], number_format($length));
		}

		return $html;
	}

	/**
	 * Renders an array for HTML view without collapsible sections.
	 */
	private function renderArrayHtml(array $array, int $depth, int $max_depth, bool $is_root): string {
		$count = count($array);
		if ($count === 0) {
			return '[]';
		}

		$summary_text = $is_root ? sprintf('array <span style="color: %s;">(count: %d)</span>', self::COLORS['meta'], $count) : sprintf('array <span style="color: %s;">[%d]</span>', self::COLORS['meta'], $count);
		$html = '<div>' . $summary_text;
		$html .= '<div style="margin-inline-start: 1.25rem; border-inline-start: 1px dotted ' . self::COLORS['border'] . '; padding-inline-start: 0.75rem;">';

		$max_items = ($depth <= 1) ? 100 : 50;
		$shown = 0;
		foreach ($array as $key => $value) {
			if ($shown >= $max_items) {
				$html .= sprintf('<div style="color: %s;">... %d more items ...</div>', self::COLORS['meta'], $count - $shown);
				break;
			}
			$key_formatted = is_string($key)
				? sprintf('<span style="font-weight: 600; color: %s;">\'%s\'</span>', self::COLORS['property'], htmlspecialchars($key))
				: sprintf('<span style="color: %s;">%s</span>', self::COLORS['number'], $key);
			$html .= '<div>' . $key_formatted . ' => ' . $this->renderVariableHtml($value, $depth + 1, $max_depth) . '</div>';
			$shown++;
		}

		$html .= '</div></div>';
		return $html;
	}

	/**
	 * Renders an object for HTML view without collapsible sections.
	 */
	private function renderObjectHtml(object $object, int $depth, int $max_depth, bool $is_root): string {
		$className = get_class($object);
		$hash = spl_object_hash($object);

		if (isset($this->processed_hashes[$hash])) {
			return sprintf('<span style="color: %s;">%s</span> <span style="color: %s;">{*RECURSION*}</span>', self::COLORS['class'], $className, self::COLORS['error']);
		}
		$this->processed_hashes[$hash] = true;

		if ($object instanceof \DateTimeInterface) {
			return sprintf('<span style="font-weight: 600; color: %s;">%s</span> {<span style="color: %s;">%s</span>}', self::COLORS['class'], $className, self::COLORS['string'], $object->format('c'));
		}
		if ($object instanceof \Throwable) {
			return sprintf(
				'<span style="font-weight: 600; color: %s;">%s</span> {message: <span style="color: %s;">"%s"</span>, file: <span style="color: %s;">"%s:%d"</span>}',
				self::COLORS['class'], $className, self::COLORS['string'], htmlspecialchars($object->getMessage()), self::COLORS['string'], htmlspecialchars(basename($object->getFile())), $object->getLine()
			);
		}

		try {
			$reflection = new ReflectionClass($object);
			$properties = $reflection->getProperties();
			$prop_count = count($properties);

			$header = sprintf('<span style="font-weight: 600; color: %s;">%s</span>', self::COLORS['class'], htmlspecialchars($className));
			if ($prop_count > 0) {
				$header .= sprintf(' <span style="color: %s;">{%d properties}</span>', self::COLORS['meta'], $prop_count);
			} else {
				$header .= ' {}';
			}

			$html = '<div>' . $header;

			if ($prop_count > 0) {
				$html .= '<div style="margin-inline-start: 1.25rem; border-inline-start: 1px dotted ' . self::COLORS['border'] . '; padding-inline-start: 0.75rem;">';
				$max_properties = ($depth <= 1) ? 100 : 50;
				$shown = 0;
				foreach ($properties as $property) {
					if ($shown >= $max_properties) {
						$html .= sprintf('<div style="color: %s;">... %d more properties ...</div>', self::COLORS['meta'], $prop_count - $shown);
						break;
					}
					$property->setAccessible(true);
					$modifiers = implode(' ', \Reflection::getModifierNames($property->getModifiers()));
					$type_str = ($type = $property->getType()) ? sprintf(' <span style="color: %s;">%s</span>', self::COLORS['type'], $this->formatType($type)) : '';
					try {
						$value_html = $this->renderVariableHtml($property->getValue($object), $depth + 1, $max_depth);
					} catch (\Throwable $e) {
						$value_html = sprintf('<span style="color: %s;">&lt;Error: %s&gt;</span>', self::COLORS['error'], htmlspecialchars($e->getMessage()));
					}
					$html .= sprintf(
						'<div><span style="color: %s;">%s</span>%s <span style="font-weight: 600; color: %s;">$%s</span> = %s</div>',
						self::COLORS['modifier'], $modifiers, $type_str, self::COLORS['property'], htmlspecialchars($property->getName()), $value_html
					);
					$shown++;
				}
				$html .= '</div>';
			}

			$html .= '</div>';
			return $html;
		} catch (\Throwable $e) {
			return sprintf('<span style="color: %s;">%s</span> <span style="color: %s;">{error: %s}</span>', self::COLORS['class'], $className, self::COLORS['error'], htmlspecialchars($e->getMessage()));
		}
	}

	/**
	 * Formats a ReflectionType to a string.
	 */
	private function formatType(?ReflectionType $type): string {
		if ($type === null) {
			return 'mixed';
		}
		if ($type instanceof \ReflectionUnionType) {
			return implode('|', array_map([$this, 'formatType'], $type->getTypes()));
		}
		if ($type instanceof ReflectionNamedType) {
			return ($type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '') . $type->getName();
		}
		return 'unknown';
	}

	/**
	 * Creates a simple, non-recursive representation of a variable for JSON output.
	 */
	private function formatSimple(mixed $var, int $depth = 0): mixed {
		if ($depth > 3) {
			return '...max depth...';
		}
		return match(gettype($var)) {
			'object' => 'Object(' . get_class($var) . ')',
			'array' => array_map(fn($value) => $this->formatSimple($value, $depth + 1), $var),
			'resource' => 'Resource(' . get_resource_type($var) . ')',
			default => $var,
		};
	}
}
