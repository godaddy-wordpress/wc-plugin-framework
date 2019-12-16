<?php

defined( 'ABSPATH' ) or exit;

/**
 * Gets the main plugin instance.
 *
 * @since 1.0.0
 *
 * @return \SkyVerge\WooCommerce\Test_Plugin\Plugin
 */
function sv_wc_test_plugin() {

	return \SkyVerge\WooCommerce\Test_Plugin\Plugin::instance();
}
