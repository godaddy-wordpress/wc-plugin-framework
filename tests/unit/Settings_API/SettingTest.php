<?php

namespace Settings_API;

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Setting;

define( 'ABSPATH', true );

class SettingTest extends \Codeception\Test\Unit {


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once( 'woocommerce/Settings_API/Setting.php' );
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


}
