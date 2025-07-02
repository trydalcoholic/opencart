<?php
namespace Debug;

class Profiler {
	private static array $events = [];
	private static array $timers = [];

	public static function start($name): void {
		self::$timers[$name] = microtime(true);
	}

	public static function stop($name): void {
		if (isset(self::$timers[$name])) {
			$start_time = self::$timers[$name];
			$end_time = microtime(true);
			$duration = ($end_time - $start_time) * 1000; // in ms

			self::$events[] = [
				'name' => $name,
				'time' => $duration
			];

			unset(self::$timers[$name]);
		}
	}

	public static function getEvents(): array {
		return self::$events;
	}
}
