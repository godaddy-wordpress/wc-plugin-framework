<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Exception;

class SettingTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	protected function _before() {

	}


	protected function _after() {

	}


	/** Tests *********************************************************************************************************/


	/**
	 * @see Setting::set_default()
	 *
	 * @param mixed $value default value to try and set
	 * @param string $expected expected default value
	 * @param bool $is_multi whether the setting should be multi
	 *
	 * @dataProvider provider_set_default
	 */
	public function test_set_default( $value, $expected, $is_multi = false ) {

		$setting = new Setting();
		$setting->set_type( Setting::TYPE_STRING );
		$setting->set_is_multi( $is_multi );
		$setting->set_default( $value );

		$this->assertSame( $expected, $setting->get_default() );
	}


	/** @see test_set_default() */
	public function provider_set_default() {

		return [
			[ 'valid', 'valid' ],
			[ 1, null ],
			[ [ 'string-0', 'string-1' ], [ 'string-0', 'string-1' ], true ],
			[ [ 'string-0', 1 ], [ 'string-0' ], true ],
			[ [ 1, 2 ], null, true ],
			[ [], null, true ],
			[ 'valid', [ 'valid' ], true ],
			[ 1, null, true ],
			[ null, null ],
			[ null, null, true ],
		];
	}


	/**
	 * @see Setting::update_value()
	 *
	 * @param mixed $value value to pass to method
	 * @param string $type setting type
	 * @param array $options setting options
	 * @param mixed $expected setting value after execution
	 * @param bool $exception whether an exception is expected
	 *
	 * @dataProvider provider_update_value
	 */
	public function test_update_value( $value, $type, $options, $expected, $exception ) {

		if ( $exception ) {
			$this->expectException( SV_WC_Plugin_Exception::class );
		}

		$setting = new Setting();
		$setting->set_type( $type );
		$setting->set_options( $options );

		$setting->update_value( $value );

		$this->assertSame( $expected, $setting->get_value() );
	}


	/**
	 * Provider for test_update_value()
	 *
	 * @return array
	 */
	public function provider_update_value() {

		require_once( 'woocommerce/Settings_API/Setting.php' );

		return [
			[ 'valid', Setting::TYPE_STRING, [], 'valid', false ],
			[ 123, Setting::TYPE_STRING, [], null, true ],
			[ 'green', Setting::TYPE_STRING, [ 'green', 'red' ], 'green', false ],
			[ 'not an option', Setting::TYPE_STRING, [ 'green', 'red' ], null, true ],

			[ 'https://skyverge.com/', Setting::TYPE_URL, [], 'https://skyverge.com/', false ],
			[ 'file:///tmp/', Setting::TYPE_URL, [], null, true ],
			[ 'https://skyverge.com/', Setting::TYPE_URL, [ 'https://skyverge.com/', 'http://skyverge.com/' ], 'https://skyverge.com/', false ],
			[ 'https://google.com/', Setting::TYPE_URL, [ 'https://skyverge.com/', 'http://skyverge.com/' ], null, true ],

			[ 'test@example.com', Setting::TYPE_EMAIL, [], 'test@example.com', false ],
			[ 'not-an-email.com', Setting::TYPE_EMAIL, [], null, true ],
			[ 'test@example.com', Setting::TYPE_EMAIL, [ 'test@example.com' ], 'test@example.com', false ],
			[ 'another@example.com', Setting::TYPE_EMAIL, [ 'test@example.com' ], null, true ],

			[ 12345, Setting::TYPE_INTEGER, [], 12345, false ],
			[ 1.345, Setting::TYPE_INTEGER, [], null, true ],
			[ '234', Setting::TYPE_INTEGER, [], null, true ],
			[ 1, Setting::TYPE_INTEGER, [ 1, 2 ], 1, false ],
			[ 3, Setting::TYPE_INTEGER, [ 1, 2 ], null, true ],

			[ 12345, Setting::TYPE_FLOAT, [], 12345, false ],
			[ 1.345, Setting::TYPE_FLOAT, [], 1.345, false ],
			[ '234', Setting::TYPE_FLOAT, [], null, true ],
			[ 1.5, Setting::TYPE_FLOAT, [ 1.5, 2.5 ], 1.5, false ],
			[ 3.5, Setting::TYPE_FLOAT, [ 1.5, 2.5 ], null, true ],

			[ true, Setting::TYPE_BOOLEAN, [], true, false ],
			[ 'yes', Setting::TYPE_BOOLEAN, [], null, true ],
			[ 1, Setting::TYPE_BOOLEAN, [], null, true ],
			// it beats me why someone would have a boolean setting with only one option, but in theory it is possible
			[ true, Setting::TYPE_BOOLEAN, [ true ], true, false ],
			[ false, Setting::TYPE_BOOLEAN, [ true ], null, true ],
		];
	}


	/**
	 * @see Setting::validate_value()
	 *
	 * @param mixed $value value to pass to method
	 * @param string $type setting type
	 * @param bool $expected whether the value should be considered valid or not
	 *
	 * @dataProvider provider_validate_value
	 */
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

			[ 12345, Setting::TYPE_INTEGER, true ],
			[ 1.345, Setting::TYPE_INTEGER, false ],
			[ '234', Setting::TYPE_INTEGER, false ],
			[ '2.4', Setting::TYPE_INTEGER, false ],
			[ 'hey', Setting::TYPE_INTEGER, false ],

			[ 12345, Setting::TYPE_FLOAT, true ],
			[ 1.345, Setting::TYPE_FLOAT, true ],
			[ '234', Setting::TYPE_FLOAT, false ],
			[ '2.4', Setting::TYPE_FLOAT, false ],
			[ 'hey', Setting::TYPE_FLOAT, false ],

			[ true, Setting::TYPE_BOOLEAN, true ],
			[ false, Setting::TYPE_BOOLEAN, true ],
			[ 'yes', Setting::TYPE_BOOLEAN, false ],
			[ 'no', Setting::TYPE_BOOLEAN, false ],
			[ 1, Setting::TYPE_BOOLEAN, false ],
			[ 0, Setting::TYPE_BOOLEAN, false ],
		];
	}


	/**
	 * @see Setting::set_options()
	 *
	 * @param string $setting_type setting type
	 * @param array $input input options
	 * @param array $expected expected return options
	 *
	 * @dataProvider provider_set_options
	 */
	public function test_set_options( $setting_type, $input, $expected ) {

		$setting = new Setting();
		$setting->set_type( $setting_type );
		$setting->set_options( $input );

		$this->assertEquals( $expected, $setting->get_options() );
	}


	/**
	 * Provider for test_set_options()
	 *
	 * @return array
	 */
	public function provider_set_options() {

		require_once( 'woocommerce/Settings_API/Setting.php' );

		return [
			[ Setting::TYPE_STRING, [ 'example 1', 'example 2', 0 ], [ 'example 1', 'example 2' ] ],
			[ Setting::TYPE_URL, [ 'http://www.example1.test', 'https://www.example2.test', 'invalid-url' ], [ 'http://www.example1.test', 'https://www.example2.test' ] ],
			[ Setting::TYPE_EMAIL, [ 'example@example1.test', 'example@example2.test', 'invalid-email' ], [ 'example@example1.test', 'example@example2.test' ] ],
			[ Setting::TYPE_INTEGER, [ - 1, 1, 2, 2.4 ], [ - 1, 1, 2 ] ],
			[ Setting::TYPE_FLOAT, [ 1.5, 2.5, - 3.0, 'invalid-float' ], [ 1.5, 2.5, - 3.0 ] ],
			[ Setting::TYPE_BOOLEAN, [ true, false, 'invalid-boolean' ], [ true, false ] ],
		];
	}


}
