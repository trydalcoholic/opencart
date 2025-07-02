<?php
namespace Opencart\Extension\Debug\Catalog\Controller;

class Profiler extends \Opencart\System\Engine\Controller {
	public function before(&$route, &$args): void {
		\Debug\Profiler::start('controller: ' . $route);
	}

	public function after(&$route, &$args, &$output): void {
		\Debug\Profiler::stop('controller: ' . $route);
	}
}
