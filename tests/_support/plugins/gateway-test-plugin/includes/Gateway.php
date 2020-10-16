<?php

namespace SkyVerge\WooCommerce\GatewayTestPlugin;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

defined( 'ABSPATH' ) or exit;

class Gateway extends Framework\SV_WC_Payment_Gateway {

	public function __construct() {

		parent::__construct(
			'test_gateway',
			sv_wc_gateway_test_plugin(),
			[
				'method_title'       => __( 'Test Gateway', 'sv-wc-gateway-test-plugin' ),
				'supports'           => [
					self::FEATURE_PAYMENT_FORM,
					self::FEATURE_APPLE_PAY,
				],
			]
		);
	}


	protected function get_method_form_fields() {

		return [];
	}


	public function get_api() {

		return new API();
	}


}
