<?php

defined( 'ABSPATH' ) or exit;

/**
 * Gets the main plugin instance.
 *
 * @return \SkyVerge\WooCommerce\Test_Plugin\Plugin
 * @since 1.0.0
 *
 */
function sv_wc_test_plugin() {

	return \SkyVerge\WooCommerce\Test_Plugin\Plugin::instance();
}
