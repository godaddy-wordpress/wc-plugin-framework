<?php
namespace SkyVerge\WooCommerce\TestPlugin;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

defined( 'ABSPATH' ) or exit;

class Gateway extends Framework\SV_WC_Payment_Gateway {


	public function __construct() {

		parent::__construct( Plugin::GATEWAY_ID, sv_wc_test_plugin(), [
			'supports' => [
				self::FEATURE_PRODUCTS,
				self::FEATURE_CUSTOMER_ID,
				self::FEATURE_TOKENIZATION,
			],
		] );
	}


	/**
	 * @return array
	 */
	protected function get_method_form_fields() {

		return [];
	}


	/**
	 * @return API
	 */
	public function get_api() {

		return new API();
	}


	/**
	 * @return string
	 */
	public function get_environment() {

		return self::ENVIRONMENT_PRODUCTION;
	}


}
