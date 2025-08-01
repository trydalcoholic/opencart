<?php
namespace Opencart\Admin\Controller\Task\Catalog;
/**
 * Class Category
 *
 * @package Opencart\Admin\Controller\Task\Catalog
 */
class Category extends \Opencart\System\Engine\Controller {
	/**
	 * Generate
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('task/catalog/category');

		$json = [];

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		//if (!$this->user->hasPermission('modify', 'task/catalog/article')) {
		$json['error'] = $this->language->get('error_permission');
		//}

		$directory = DIR_CATALOG . 'view/data/cms/';

		//if (!is_dir($directory) && !mkdir($directory, 0777)) {
		//	$json['error'] = $this->language->get('error_directory');
		//}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function clear(): void {
		$this->load->language('task/catalog/category');

		$json = [];

		if (!$this->user->hasPermission('modify', 'task/catalog/language')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
