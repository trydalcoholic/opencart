<?php
namespace Opencart\Admin\Controller\Tool;
/**
 * Class Log
 *
 * @package Opencart\Admin\Controller\Tool
 */
class Log extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('tool/log');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('tool/log', 'user_token=' . $this->session->data['user_token'])
		];

		$data['delete'] = $this->url->link('tool/log.delete', 'user_token=' . $this->session->data['user_token']);
		$data['download'] = $this->url->link('tool/log.download', 'user_token=' . $this->session->data['user_token']);

		$data['list'] = $this->load->controller('tool/log.getList');

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('tool/log', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('tool/log');

		$this->response->setOutput($this->load->controller('tool/log.getList'));
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		$data['logs'] = [];

		$files = oc_directory_read(DIR_LOGS, false, '/\.log$/');

		$total_size = 0;

		foreach ($files as $file) {
			$error = '';

			$filename = basename($file);

			$size = filesize($file);
			$total_size += $size;

			// Format file size
			$suffix = [
				'B',
				'KB',
				'MB',
				'GB',
				'TB',
				'PB',
				'EB',
				'ZB',
				'YB'
			];

			$i = 0;
			$temp_size = $size;

			while (($temp_size / 1024) > 1) {
				$temp_size /= 1024;
				$i++;
			}

			$formatted_size = round($temp_size, 2) . ' ' . $suffix[$i];

			if ($size >= 3145728) {
				$error = sprintf($this->language->get('error_size'), $filename, $formatted_size);
			}

			$handle = fopen($file, 'r+');

			$data['logs'][] = [
				'name'     => $filename,
				'size'     => $formatted_size,
				'output'   => fread($handle, 3145728),
				'download' => $this->url->link('tool/log.download', 'user_token=' . $this->session->data['user_token'] . '&filename=' . $filename),
				'clear'    => $this->url->link('tool/log.clear', 'user_token=' . $this->session->data['user_token'] . '&filename=' . $filename),
				'delete'   => $this->url->link('tool/log.delete', 'user_token=' . $this->session->data['user_token'] . '&filename=' . $filename),
				'error'    => $error
			];

			fclose($handle);
		}

		$suffix = [
			'B',
			'KB',
			'MB',
			'GB',
			'TB',
			'PB',
			'EB',
			'ZB',
			'YB'
		];

		$i = 0;
		$temp_total_size = $total_size;

		while (($temp_total_size / 1024) > 1) {
			$temp_total_size /= 1024;
			$i++;
		}

		$data['total_size'] = round($temp_total_size, 2) . ' ' . $suffix[$i];

		$date_format = $this->config->get('error_log_rotation_date_format');
		$filename_format = $this->config->get('error_log_filename_format'); // '{filename}_{date}.{extension}'

		// Preparing a dynamic pattern: replace placeholders with capture groups
		$pattern = preg_quote($filename_format, '/');
		$pattern = str_replace(
			['\{filename\}', '\{date\}', '\{extension\}', '\{counter\}'],
			['(?<filename>[^_]+)', '(?<date>.*?)', '\.(?<extension>[a-z]+)', '(?<counter>\d+)?'],
			$pattern
		);

		$pattern = '/^' . $pattern . '$/i'; // Full pattern for the file name

		$sort_priority = [];
		$sort_time = [];
		$sort_name = [];

		foreach ($data['logs'] as $key => $log) {
			$filepath = DIR_LOGS . $log['name'];

			$error_filename = $this->config->get('error_filename');
			$base_name = pathinfo($error_filename, PATHINFO_FILENAME);
			$extension = pathinfo($error_filename, PATHINFO_EXTENSION);

			// Check whether it is an error log (primary or rotated)
			$match_pattern = str_replace(['(?<filename>[^_]+)', '\.(?<extension>[a-z]+)'], [preg_quote($base_name, '/'), preg_quote($extension, '/')], $pattern);

			if (preg_match($match_pattern, $log['name'], $matches)) {
				$sort_priority[$key] = 0;

				$date_str = $matches['date'] ?? '';

				if ($date_str) {
					$date_time = \DateTime::createFromFormat($date_format, $date_str);

					if ($date_time === false) {
						// Fallback: try to parse as 'Y-m-d' explicitly if the config did not work
						$date_time = \DateTime::createFromFormat('!Y-m-d', $date_str);
					}

					if ($date_time instanceof \DateTimeInterface) {
						$sort_time[$key] = $date_time->getTimestamp();
					} elseif (is_file($filepath)) {
						$sort_time[$key] = filemtime($filepath);
					} else {
						$sort_time[$key] = 0;
					}
				} else {
					// For the main log without a date — use max to keep it at the top in DESC
					$sort_time[$key] = PHP_INT_MAX;
				}
			} else {
				$sort_priority[$key] = 1;
				$sort_time[$key] = is_file($filepath) ? filemtime($filepath) : 0;
			}

			$sort_name[$key] = $log['name'];
		}

		array_multisort(
			$sort_priority,
			SORT_ASC,
			$sort_time,
			SORT_DESC,
			$sort_name,
			SORT_ASC,
			$data['logs']
		);

		$data['user_token'] = $this->session->data['user_token'];
		$data['delete'] = $this->url->link('tool/log.delete', 'user_token=' . $this->session->data['user_token']);

		return $this->load->view('tool/log_list', $data);
	}

	/**
	 * Download
	 *
	 * @return void
	 */
	public function download(): void {
		$this->load->language('tool/log');

		if (isset($this->request->get['filename'])) {
			$filename = (string)basename(html_entity_decode($this->request->get['filename'], ENT_QUOTES, 'UTF-8'));
		} else {
			$filename = '';
		}

		$file = DIR_LOGS . $filename;

		if (!is_file($file)) {
			$this->session->data['error'] = sprintf($this->language->get('error_file'), $filename);

			$this->response->redirect($this->url->link('tool/log', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (!filesize($file)) {
			$this->session->data['error'] = sprintf($this->language->get('error_empty'), $filename);

			$this->response->redirect($this->url->link('tool/log', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->response->addheader('Pragma: public');
		$this->response->addheader('Expires: 0');
		$this->response->addheader('Content-Description: File Transfer');
		$this->response->addheader('Content-Type: application/octet-stream');
		$this->response->addheader('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d_H-i-s', time()) . '_error.log"');
		$this->response->addheader('Content-Transfer-Encoding: binary');

		$this->response->setOutput(file_get_contents($file, true, null));
	}

	/**
	 * Clear
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->load->language('tool/log');

		if (isset($this->request->get['filename'])) {
			$filename = (string)basename(html_entity_decode($this->request->get['filename'], ENT_QUOTES, 'UTF-8'));
		} else {
			$filename = '';
		}

		$json = [];

		if (!$this->user->hasPermission('modify', 'tool/log')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$file = DIR_LOGS . $filename;

		if (!is_file($file)) {
			$json['error'] = sprintf($this->language->get('error_file'), $filename);
		}

		if (!$json) {
			$handle = fopen($file, 'w+');

			fclose($handle);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->load->language('tool/log');

		$json = [];

		if (!$this->user->hasPermission('modify', 'tool/log')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (isset($this->request->post['selected'])) {
			$selected = (array)$this->request->post['selected'];

			if (empty($selected)) {
				$json['error'] = $this->language->get('error_no_selection');
			} else {
				$deleted_count = 0;
				$failed_files = [];

				foreach ($selected as $filename) {
					$filename = basename($filename);
					$file = DIR_LOGS . $filename;

					if (is_file($file) && unlink($file)) {
						$deleted_count++;
					} else {
						$failed_files[] = $filename;
					}
				}

				if ($deleted_count > 0 && empty($failed_files)) {
					$json['success'] = sprintf($this->language->get('text_delete_multiple'), $deleted_count);
				} elseif ($deleted_count > 0 && !empty($failed_files)) {
					$json['success'] = sprintf($this->language->get('text_delete_partial'), $deleted_count, count($failed_files));
				} else {
					$json['error'] = $this->language->get('error_delete');
				}
			}
		} elseif (isset($this->request->get['filename'])) {
			$filename = basename($this->request->get['filename']);

			if (empty($filename)) {
				$json['error'] = sprintf($this->language->get('error_file'), $filename);
			} else {
				$file = DIR_LOGS . $filename;

				if (is_file($file) && unlink($file)) {
					$json['success'] = $this->language->get('text_delete');
				} else {
					$json['error'] = $this->language->get('error_delete');
				}
			}
		} else {
			$json['error'] = $this->language->get('error_no_selection');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
