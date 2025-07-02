<?php
namespace Debug;

class DB {
	private              $db;
	private static array $logs = [];

	public function __construct($db) {
		$this->db = $db;
	}

	public function query($sql) {
		$start_time = microtime(true);
		$result = $this->db->query($sql);
		$end_time = microtime(true);

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		self::$logs[] = [
			'sql'       => $sql,
			'time'      => ($end_time - $start_time) * 1000,
			'rows'      => is_object($result) ? $result->num_rows : 'N/A',
			'backtrace' => $backtrace
		];

		return $result;
	}

	public static function getLogs(): array {
		return self::$logs;
	}

	public function __call($name, $arguments) {
		return call_user_func_array([$this->db, $name], $arguments);
	}

	public function __get($name) {
		return $this->db->$name;
	}

	public function __set($name, $value) {
		$this->db->$name = $value;
	}

	public function __isset($name) {
		return isset($this->db->$name);
	}

	public function __unset($name) {
		unset($this->db->$name);
	}
}
