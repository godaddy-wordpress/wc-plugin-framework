<?php

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Setting;

class SettingTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	protected function _before() {

	}


	protected function _after() {

	}


	/** Tests *********************************************************************************************************/


	/**
	 * @see Setting::validate_value()
	 *
	 * @param mixed $value value to pass to method
	 * @param string $type setting type
	 * @param bool $type whether the value should be considered valid or not
	 *
	 * @dataProvider provider_validate_value
	 * */
	public function test_validate_value( $value, $type, $expected ) {

		$setting = new Setting();
		$setting->set_type( $type );

		$this->assertSame( $expected, $setting->validate_value( $value ) );
	}


	/**
	 * Provider for test_validate_value()
	 *
	 * @return array
	 */
	public function provider_validate_value() {

		require_once( 'woocommerce/Settings_API/Setting.php' );

		return [
			[ 'example', Setting::TYPE_STRING, true ],
			[ 3.1415926, Setting::TYPE_STRING, false ],

			[ 'https://skyverge.com/', Setting::TYPE_URL, true ],
			[ 'file:///tmp/', Setting::TYPE_URL, false ],
			[ 'example', Setting::TYPE_URL, false ],

			[ 'test@example.com', Setting::TYPE_EMAIL, true ],
			[ 'not-an-email.com', Setting::TYPE_EMAIL, false ],
			[ '', Setting::TYPE_EMAIL, false ],

			[ 1729, Setting::TYPE_INTEGER, true ],
			[ 'hi', Setting::TYPE_INTEGER, false ],

			[ 3.14, Setting::TYPE_FLOAT, true ],
			[ 3000, Setting::TYPE_FLOAT, false ],
			[ 'hi', Setting::TYPE_FLOAT, false ],

			[ true, Setting::TYPE_BOOLEAN, true ],
			[ false, Setting::TYPE_BOOLEAN, true ],
			[ 'yes', Setting::TYPE_BOOLEAN, false ],
			[ 'no', Setting::TYPE_BOOLEAN, false ],
			[ 1, Setting::TYPE_BOOLEAN, false ],
			[ 0, Setting::TYPE_BOOLEAN, false ],
		];
	}


}
