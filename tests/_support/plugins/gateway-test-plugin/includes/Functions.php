<?php

defined( 'ABSPATH' ) or exit;

/**
 * Gets the main plugin instance.
 *
 * @since 1.0.0
 *
 * @return \SkyVerge\WooCommerce\GatewayTestPlugin\Plugin
 */
function sv_wc_gateway_test_plugin() {

	return \SkyVerge\WooCommerce\GatewayTestPlugin\Plugin::instance();
}
