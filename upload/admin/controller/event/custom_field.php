<?php
namespace Opencart\Admin\Controller\Event;
/**
 * Class Custom Field
 *
 * @package Opencart\Admin\Controller\Event
 */
class CustomField extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * Adds task to generate new custom_field list
	 *
	 * model/customer/custom_field/addCustomField/after
	 * model/customer/custom_field/editCustomField/after
	 * model/customer/custom_field/deleteCustomField/after
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args
	 * @param mixed             $output
	 *
	 * @return void
	 */
	public function index(string &$route, array &$args, &$output): void {
		$tasks = [];

		$tasks[] = [
			'code'   => 'custom_field',
			'action' => 'catalog/data/custom_field',
			'args'   => []
		];

		$tasks[] = [
			'code'   => 'custom_field',
			'action' => 'admin/data/custom_field',
			'args'   => []
		];

		$this->load->model('setting/task');

		foreach ($tasks as $task) {
			$this->model_setting_task->addTask($task);
		}
	}
}
