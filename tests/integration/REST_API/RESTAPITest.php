<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Abstract_Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper;

/**
 * Tests for the REST_API class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\REST_API
 */
class RESTAPITest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var Abstract_Settings */
	protected $settings;


	protected function _before() {

		require_once 'woocommerce/class-sv-wc-plugin.php';
		require_once 'woocommerce/rest-api/Controllers/Settings.php';
		require_once 'woocommerce/Settings_API/Abstract_Settings.php';
	}


	protected function _after() {

	}


	/** Tests *********************************************************************************************************/


	/** @see Abstract_Settings::get_id() */
	public function test_register_routes() {

		$settings = $this->get_settings_instance();
		$plugin   = $this->make( SkyVerge\WooCommerce\TestPlugin\Plugin::class, [ 'get_settings_handler' =>  $settings ] );

		$handler = new Framework\REST_API( $plugin );
		$handler->register_routes();

		$this->assertArrayHasKey( "/wc/v3/{$settings->get_id()}/settings", rest_get_server()->get_routes() );
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Gets the settings instance.
	 *
	 * @return Abstract_Settings
	 */
	protected function get_settings_instance() {

		if ( null === $this->settings ) {

			$this->settings = new class( 'test-plugin' ) extends Abstract_Settings {


				protected function register_settings() {

				}


			};
		}

		return $this->settings;
	}
}
