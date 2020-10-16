<?php
namespace SkyVerge\WooCommerce\GatewayTestPlugin;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

defined( 'ABSPATH' ) or exit;

class Plugin extends Framework\SV_WC_Payment_Gateway_Plugin {


	/** @var Plugin single instance of this plugin */
	protected static $instance;

	/** string version number */
	const VERSION = '1.0.0';

	/** string the plugin ID */
	const PLUGIN_ID = 'gateway_test_plugin';


	/**
	 * Constructs the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			[
				'text_domain' => 'sv-wc-gateway-test-plugin',
				'gateways'    => [
					'test_gateway' => '\SkyVerge\WooCommerce\GatewayTestPlugin\Gateway',
				]
			]
		);

		add_filter( 'wc_payment_gateway_gateway_test_plugin_activate_apple_pay', '__return_true' );

		// make this plugin's gateway the one set up for Apple Pay
		add_filter( 'pre_option_sv_wc_apple_pay_payment_gateway', function () {

			return $this->get_gateway()->get_id();
		} );
	}


	public function get_documentation_url() {

		return 'https://example.com';
	}


	public function get_settings_url( $plugin_id = null ) {

		return admin_url( 'admin.php?page=wc-settings' );
	}


	public function get_plugin_name() {

		return 'Framework Gateway Test Plugin';
	}


	protected function get_file() {

		return __DIR__;
	}


	/** Helper methods ******************************************************/


	/**
	 * Gets the main plugin instance.
	 *
	 * Ensures only one instance is/can be loaded.
	 *
	 * @see sv_wc_gateway_test_plugin()
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
