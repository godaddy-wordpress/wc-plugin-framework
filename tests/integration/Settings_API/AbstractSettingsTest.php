<?php

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Abstract_Settings;

/**
 * Tests for the Abstract_Settings class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Abstract_Settings
 */
class AbstractSettingsTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** @var Abstract_Settings */
	protected $settings;


	protected function _before() {

		require_once 'woocommerce/Settings_API/Abstract_Settings.php';
	}


	protected function _after() {

	}


	/** Tests *********************************************************************************************************/


	/** @see Abstract_Settings::__construct() */
	public function test_constructor() {

		$this->assertEquals( 'test-plugin', $this->get_settings()->id );
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Gets the settings instance.
	 *
	 * @return Abstract_Settings
	 */
	protected function get_settings() {

		if ( null === $this->settings ) {

			$this->settings = new class( 'test-plugin' ) extends Abstract_Settings {


				protected function register_settings() {

				}


				/**
				 * TODO: remove when load_settings() is implemented in Framework\Settings_API\Abstract_Settings {WV 2020-03-20}
				 */
				protected function load_settings() {

				}


			};
		}

		return $this->settings;
	}


}
