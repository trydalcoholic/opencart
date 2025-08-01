<?php
namespace Opencart\Admin\Controller\Mail;
/**
 * Class Customer
 *
 * @package Opencart\Admin\Controller\Mail
 */
class Customer extends \Opencart\System\Engine\Controller {
	/**
	 * Approve
	 *
	 * admin/model/customer/customer_approval.approveCustomer/after
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args
	 * @param mixed             $output
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function approve(string &$route, array &$args, &$output): void {
		// Customer
		if (isset($args[0])) {
			$customer_id = (int)$args[0];
		} else {
			$customer_id = 0;
		}

		$this->load->model('customer/customer');

		$customer_info = $this->model_customer_customer->getCustomer($customer_id);

		if (!$customer_info) {
			return;
		}

		// Setting
		$this->load->model('setting/store');

		$store_info = $this->model_setting_store->getStore($customer_info['store_id']);

		if ($store_info) {
			$this->load->model('setting/setting');

			$store_logo = html_entity_decode($this->model_setting_setting->getValue('config_logo', $store_info['store_id']), ENT_QUOTES, 'UTF-8');
			$store_name = html_entity_decode($store_info['name'], ENT_QUOTES, 'UTF-8');
			$store_url = $store_info['url'];
		} else {
			$store_logo = html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8');
			$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
			$store_url = HTTP_CATALOG;
		}

		// Language
		$this->load->model('localisation/language');

		$language_info = $this->model_localisation_language->getLanguage($customer_info['language_id']);

		if ($language_info) {
			$language_code = $language_info['code'];
		} else {
			$language_code = $this->config->get('config_language');
		}

		// Load the language for any mails using a different country code and prefixing it, so it does not pollute the main data pool.
		$this->load->language('default', 'mail', $language_code);
		$this->load->language('mail/customer_approve', 'mail', $language_code);

		// Add language vars to the template folder
		$results = $this->language->all('mail');

		foreach ($results as $key => $value) {
			$data[$key] = $value;
		}

		// Image
		$this->load->model('tool/image');

		if (is_file(DIR_IMAGE . $store_logo)) {
			$data['logo'] = $store_url . 'image/' . $store_logo;
		} else {
			$data['logo'] = '';
		}

		$subject = sprintf($this->language->get('mail_text_subject'), $store_name);

		$data['text_welcome'] = sprintf($this->language->get('mail_text_welcome'), $store_name);

		$data['login'] = $store_url . 'index.php?route=account/login';

		$data['store'] = $store_name;
		$data['store_url'] = $store_url;

		$task_data = [
			'code'   => 'mail_customer',
			'action' => 'admin/mail',
			'args'   => [
				'to'      => $customer_info['email'],
				'from'    => $this->config->get('config_email'),
				'sender'  => $store_name,
				'subject' => $subject,
				'content' => $this->load->view('mail/customer_approve', $data)
			]
		];

		$this->load->model('setting/task');

		$this->model_setting_task->addTask($task_data);
	}

	/**
	 * Deny
	 *
	 * admin/model/customer/customer_approval.denyCustomer/after
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args
	 * @param mixed             $output
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function deny(string &$route, array &$args, &$output): void {
		if (isset($args[0])) {
			$customer_id = (int)$args[0];
		} else {
			$customer_id = 0;
		}

		// Customer
		$this->load->model('customer/customer');

		$customer_info = $this->model_customer_customer->getCustomer($customer_id);

		if (!$customer_info) {
			return;
		}

		// Language
		$this->load->model('localisation/language');

		$language_info = $this->model_localisation_language->getLanguage($customer_info['language_id']);

		if (!$language_info) {
			return;
		}

		// Setting
		$this->load->model('setting/store');

		$store_info = $this->model_setting_store->getStore($customer_info['store_id']);

		if ($store_info) {
			$this->load->model('setting/setting');

			$store_logo = html_entity_decode($this->model_setting_setting->getValue('config_logo', $customer_info['store_id']), ENT_QUOTES, 'UTF-8');
			$store_name = html_entity_decode($store_info['name'], ENT_QUOTES, 'UTF-8');
			$store_url = $store_info['url'];
		} else {
			$store_logo = html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8');
			$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
			$store_url = HTTP_CATALOG;
		}

		// Load the language for any mails using a different country code and prefixing it, so it does not pollute the main data pool.
		$this->load->language('default', 'mail', $language_info['code']);
		$this->load->language('mail/customer_deny', 'mail', $language_info['code']);

		// Add language vars to the template folder
		$results = $this->language->all('mail');

		foreach ($results as $key => $value) {
			$data[$key] = $value;
		}

		// Image
		$this->load->model('tool/image');

		if (is_file(DIR_IMAGE . $store_logo)) {
			$data['logo'] = $store_url . 'image/' . $store_logo;
		} else {
			$data['logo'] = '';
		}

		$subject = sprintf($this->language->get('mail_text_subject'), $store_name);

		$data['text_welcome'] = sprintf($this->language->get('mail_text_welcome'), $store_name);

		$data['contact'] = $store_url . 'index.php?route=information/contact';

		$data['store'] = $store_name;
		$data['store_url'] = $store_url;

		$task_data = [
			'code'   => 'mail_customer',
			'action' => 'admin/mail',
			'args'   => [
				'to'      => $customer_info['email'],
				'from'    => $this->config->get('config_email'),
				'sender'  => $store_name,
				'subject' => $subject,
				'content' => $this->load->view('mail/customer_deny', $data)
			]
		];

		$this->load->model('setting/task');

		$this->model_setting_task->addTask($task_data);
	}
}
