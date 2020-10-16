<?php
namespace SkyVerge\WooCommerce\TestPlugin;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

defined( 'ABSPATH' ) or exit;

class Plugin extends Framework\SV_WC_Payment_Gateway_Plugin {


	/** @var Plugin single instance of this plugin */
	protected static $instance;

	/** string version number */
	const VERSION = '1.0.0';

	/** string the plugin ID */
	const PLUGIN_ID = 'test_plugin';

	const GATEWAY_ID = 'test_gateway';

	const GATEWAY_CLASS = Gateway::class;


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
				'text_domain' => 'sv-wc-test-plugin',
				'gateways' => [
					self::GATEWAY_ID => self::GATEWAY_CLASS,
				],
				'supports' => [
					self::FEATURE_CUSTOMER_ID,
				],
			]
		);
	}


	public function get_documentation_url() {

		return 'https://example.com';
	}


	public function get_settings_url( $plugin_id = null ) {

		return admin_url( 'admin.php?page=wc-settings' );
	}


	public function get_plugin_name() {

		return 'Plugin Framework Test';
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
	 * @see sv_wc_test_plugin()
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
