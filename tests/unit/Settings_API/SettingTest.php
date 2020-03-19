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


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Setting::set_id()
	 *
	 * @param string $input input ID
	 * @param string $expected expected return ID
	 *
	 * @dataProvider provider_set_id
	 */
	public function test_set_id( $input, $expected ) {

		$setting = new Setting();
		$setting->set_id( $input );
		$this->assertEquals( $expected, $setting->get_id() );
	}


	/** Provider methods **********************************************************************************************/


	/**
	 * Provider for test_set_id()
	 *
	 * @return array
	 */
	public function provider_set_id() {

		return [
			[ 'my-setting', 'my-setting' ],
			[ '', '' ],
		];
	}


}
