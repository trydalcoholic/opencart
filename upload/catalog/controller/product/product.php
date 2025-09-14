<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Product
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Product extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return ?\Opencart\System\Engine\Action
	 */
	public function index() {
		// Language
		$this->load->language('product/product');

		$language_code = (string)$this->config->get('config_language');

		$query_keys = [
			'category_id',
			'description',
			'filter',
			'limit',
			'manufacturer_id',
			'order',
			'page',
			'path',
			'product_id',
			'search',
			'sort',
			'sub_category',
			'tag'
		];

		// Select only the query parameters from $this->request->get that will be used, and add the language code to the collection
		$query_params = collection($this->request->get)->only($query_keys)->put('language', $language_code);

		// This helps quickly generate URLs with only the language code parameter
		$query_language = $query_params->only(['language'])->toQueryString();

		// Product
		$product_id = (int)$query_params->get('product_id', 0);

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			return new \Opencart\System\Engine\Action('error/not_found');
		}

		$this->document->setTitle($product_info['meta_title']);
		$this->document->setDescription($product_info['meta_description']);
		$this->document->setKeywords($product_info['meta_keyword']);
		$this->document->addLink($this->url->link('product/product', $query_params->only(['language', 'product_id'])->toQueryString()), 'canonical');

		$this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', $query_language)
		];

		// Category
		$this->load->model('catalog/category');

		if ($query_params->has('path')) {
			$path = '';

			$parts = explode('_', (string)$query_params->get('path'));

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = $path_id;
				} else {
					$path .= '_' . $path_id;
				}

				$category_info = $this->model_catalog_category->getCategory((int)$path_id);

				if ($category_info) {
					$params = $query_params->only(['language'])->merge(['path' => $path]);

					$data['breadcrumbs'][] = [
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', $params->toQueryString())
					];
				}
			}

			// Set the last category breadcrumb
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$params = $query_params->only(['language', 'limit', 'order', 'page', 'path', 'sort']);

				$data['breadcrumbs'][] = [
					'text' => $category_info['name'],
					'href' => $this->url->link('product/category', $params->toQueryString())
				];
			}
		}

		// Manufacturer
		$this->load->model('catalog/manufacturer');

		if ($query_params->has('manufacturer_id')) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_brand'),
				'href' => $this->url->link('product/manufacturer', $query_language)
			];

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer((int)$query_params->get('manufacturer_id'));

			if ($manufacturer_info) {
				$params = $query_params->only(['language', 'limit', 'manufacturer_id', 'order', 'page', 'sort']);

				$data['breadcrumbs'][] = [
					'text' => $manufacturer_info['name'],
					'href' => $this->url->link('product/manufacturer.info', $params->toQueryString())
				];
			}
		}

		if ($query_params->has('search') || $query_params->has('tag')) {
			$params = $query_params->except(['path', 'manufacturer_id', 'product_id', 'filter']);

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_search'),
				'href' => $this->url->link('product/search', $params->toQueryString())
			];
		}

		$data['breadcrumbs'][] = [
			'text' => $product_info['name'],
			'href' => $this->url->link('product/product', $query_params->toQueryString())
		];

		$data['heading_title'] = $product_info['name'];

		$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
		$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', $query_language), $this->url->link('account/register', $query_language));
		$data['text_reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);

		$data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);

		$data['error_upload_size'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size'));

		$data['config_file_max_size'] = ((int)$this->config->get('config_file_max_size') * 1024 * 1024);

		$this->session->data['upload_token'] = oc_token(32);

		$params = $query_params->only(['language'])->merge(['upload_token' => $this->session->data['upload_token']]);

		$data['upload'] = $this->url->link('tool/upload', $params->toQueryString());

		$data['product_id'] = $product_id;

		$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

		if ($manufacturer_info) {
			$data['manufacturer'] = $manufacturer_info['name'];
		} else {
			$data['manufacturer'] = '';
		}

		$params = $query_params->only(['language'])->merge(['manufacturer_id' => $product_info['manufacturer_id']]);

		$data['manufacturers'] = $this->url->link('product/manufacturer.info', $params->toQueryString());
		$data['model'] = $product_info['model'];

		$data['product_codes'] = [];

		$results = $this->model_catalog_product->getCodes($product_id);

		foreach ($results as $result) {
			if ($result['status']) {
				$data['product_codes'][] = $result;
			}
		}

		$data['reward'] = $product_info['reward'];
		$data['points'] = $product_info['points'];
		$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');

		// Stock Status
		if ($product_info['quantity'] <= 0) {
			$stock_status_id = $product_info['stock_status_id'];

			$data['stock'] = false;
		} elseif (!$this->config->get('config_stock_display')) {
			$stock_status_id = (int)$this->config->get('config_stock_status_id');

			$data['stock'] = true;
		} else {
			$stock_status_id = 0;

			$data['stock'] = true;
		}

		$this->load->model('localisation/stock_status');

		$stock_status_info = $this->model_localisation_stock_status->getStockStatus($stock_status_id);

		if ($stock_status_info) {
			$data['stock_status'] = $stock_status_info['name'];
		} else {
			$data['stock_status'] = $product_info['quantity'];
		}

		$data['rating'] = (int)$product_info['rating'];
		$data['review_status'] = (int)$this->config->get('config_review_status');
		$data['review'] = $this->load->controller('product/review');

		$data['wishlist_add'] = $this->url->link('account/wishlist.add', $language_code);
		$data['compare_add'] = $this->url->link('product/compare.add', $language_code);

		// Image
		$this->load->model('tool/image');

		if ($product_info['image'] && is_file(DIR_IMAGE . html_entity_decode($product_info['image'], ENT_QUOTES, 'UTF-8'))) {
			$data['popup'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height'));
			$data['thumb'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('config_image_thumb_width'), $this->config->get('config_image_thumb_height'));
		} else {
			$data['popup'] = '';
			$data['thumb'] = '';
		}

		$data['images'] = [];

		$results = $this->model_catalog_product->getImages($product_id);

		foreach ($results as $result) {
			if ($result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
				$data['images'][] = [
					'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height')),
					'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('config_image_thumb_width'), $this->config->get('config_image_thumb_height'))
				];
			}
		}

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$data['price'] = $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
		} else {
			$data['price'] = false;
		}

		if ((float)$product_info['special']) {
			$data['special'] = $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));
		} else {
			$data['special'] = false;
		}

		if ($this->config->get('config_tax')) {
			$data['tax'] = (float)$product_info['special'] ? $product_info['special'] : $product_info['price'];
		} else {
			$data['tax'] = false;
		}

		$discounts = $this->model_catalog_product->getDiscounts($product_id);

		$data['discounts'] = [];

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			foreach ($discounts as $discount) {
				$data['discounts'][] = ['price' => $this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax'))] + $discount;
			}
		}

		$data['options'] = [];

		// Check if product is variant
		if ($product_info['master_id']) {
			$master_id = (int)$product_info['master_id'];
		} else {
			$master_id = (int)$product_id;
		}

		$product_options = $this->model_catalog_product->getOptions($master_id);

		foreach ($product_options as $option) {
			if ($product_id && !isset($product_info['override']['variant'][$option['product_option_id']])) {
				$product_option_value_data = [];

				foreach ($option['product_option_value'] as $option_value) {
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
						} else {
							$price = false;
						}

						if ($option_value['image'] && is_file(DIR_IMAGE . html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8'))) {
							$image = $option_value['image'];
						} else {
							$image = '';
						}

						$product_option_value_data[] = [
							'image' => $this->model_tool_image->resize($image, 50, 50),
							'price' => $price
						] + $option_value;
					}
				}

				$data['options'][] = ['product_option_value' => $product_option_value_data] + $option;
			}
		}

		// Subscription Plans
		$data['subscription_plans'] = [];

		$results = $this->model_catalog_product->getSubscriptions($product_id);

		foreach ($results as $result) {
			$description = '';

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				if ($result['duration']) {
					$price = ($product_info['special'] ?: $product_info['price']) / $result['duration'];
				} else {
					$price = ($product_info['special'] ?: $product_info['price']);
				}

				$price = $this->tax->calculate($price, $product_info['tax_class_id'], $this->config->get('config_tax'));
				$cycle = $result['cycle'];
				$frequency = $this->language->get('text_' . $result['frequency']);
				$duration = $result['duration'];

				if ($duration) {
					$description = sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
				} else {
					$description = sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
				}
			}

			$data['subscription_plans'][] = ['description' => $description] + $result;
		}

		if ($product_info['minimum']) {
			$data['minimum'] = $product_info['minimum'];
		} else {
			$data['minimum'] = 1;
		}

		$data['share'] = $this->url->link('product/product', $query_params->only(['language', 'product_id'])->toQueryString());

		// Attribute Groups
		$data['attribute_groups'] = $this->model_catalog_product->getAttributes($product_id);

		// Related
		$data['related'] = $this->load->controller('product/related');

		// Tag
		$data['tags'] = [];

		if ($product_info['tag']) {
			$tags = explode(',', $product_info['tag']);

			foreach ($tags as $tag) {
				$tag = trim($tag);

				$params = $query_params->only(['language'])->merge(['tag' => $tag]);

				$data['tags'][] = [
					'tag'  => $tag,
					'href' => $this->url->link('product/search', $params->toQueryString())
				];
			}
		}

		if ($this->config->get('config_product_report_status')) {
			$this->model_catalog_product->addReport($product_id, oc_get_ip());
		}

		$data['language'] = $this->config->get('config_language');
		$data['currency'] = $this->session->data['currency'];

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/product', $data));

		return null;
	}
}
