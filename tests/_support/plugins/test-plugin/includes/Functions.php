<?php

defined( 'ABSPATH' ) or exit;

/**
 * Gets the main plugin instance.
 *
 * @since 1.0.0
 *
 * @return \SkyVerge\WooCommerce\TestPlugin\Plugin
 */
function sv_wc_test_plugin() {

	return \SkyVerge\WooCommerce\TestPlugin\Plugin::instance();
}
