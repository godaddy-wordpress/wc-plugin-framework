<?php

namespace Settings_API;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Control;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Exception;

define( 'ABSPATH', true );

class SettingTest extends \Codeception\Test\Unit {


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once( 'woocommerce/Settings_API/Abstract_Settings.php' );
		require_once( 'woocommerce/Settings_API/Setting.php' );
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_id()
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


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_type()
	 *
	 * @param string $input input type
	 * @param string $expected expected return type
	 *
	 * @dataProvider provider_set_type
	 */
	public function test_set_type( $input, $expected ) {

		$setting = new Setting();
		$setting->set_type( $input );

		$this->assertEquals( $expected, $setting->get_type() );
	}


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_name()
	 *
	 * @param string $input input name
	 * @param string $expected expected return name
	 *
	 * @dataProvider provider_set_name
	 */
	public function test_set_name( $input, $expected ) {

		$setting = new Setting();
		$setting->set_name( $input );

		$this->assertEquals( $expected, $setting->get_name() );
	}


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_description()
	 *
	 * @param string $input input description
	 * @param string $expected expected return description
	 *
	 * @dataProvider provider_set_description
	 */
	public function test_set_description( $input, $expected ) {

		$setting = new Setting();
		$setting->set_description( $input );

		$this->assertEquals( $expected, $setting->get_description() );
	}


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_is_multi()
	 *
	 * @param bool $input input value
	 * @param bool $expected expected return value
	 *
	 * @dataProvider provider_set_is_multi
	 */
	public function test_set_is_multi( $input, $expected ) {

		$setting = new Setting();
		$setting->set_is_multi( $input );

		$this->assertEquals( $expected, $setting->is_is_multi() );
	}


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_options()
	 *
	 * @param array $input input options
	 * @param array $expected expected return options
	 *
	 * @dataProvider provider_set_options
	 */
	public function test_set_options( $input, $expected ) {

		$setting = new Setting();
		$setting->set_options( $input );

		$this->assertEquals( $expected, $setting->get_options() );
	}


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_default()
	 *
	 * @param int|float|string|bool|array $input input default value
	 * @param int|float|string|bool|array $expected expected return default value
	 *
	 * @dataProvider provider_set_default
	 */
	public function test_set_default( $input, $expected ) {

		$setting = new Setting();
		$setting->set_default( $input );

		$this->assertEquals( $expected, $setting->get_default() );
	}


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_value()
	 *
	 * @param int|float|string|bool|array $input input value
	 * @param int|float|string|bool|array $expected expected return value
	 *
	 * @dataProvider provider_set_value
	 */
	public function test_set_value( $input, $expected ) {

		$setting = new Setting();
		$setting->set_value( $input );

		$this->assertEquals( $expected, $setting->get_value() );
	}


	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting::set_control()
	 *
	 * @param Control $input input control
	 * @param Control $expected expected return control
	 * @throws SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_set_control
	 */
	public function test_set_control( $input, $expected ) {

		$setting = new Setting();
		$setting->set_control( $input );

		$this->assertEquals( $expected, $setting->get_control() );
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


	/**
	 * Provider for test_set_type()
	 *
	 * @return array
	 */
	public function provider_set_type() {

		return [
			[ 'string', 'string' ],
			[ 'url', 'url' ],
			[ 'email', 'email' ],
			[ 'integer', 'integer' ],
			[ 'float', 'float' ],
			[ 'boolean', 'boolean' ],
			[ '', '' ],
		];
	}


	/**
	 * Provider for test_set_name()
	 *
	 * @return array
	 */
	public function provider_set_name() {

		return [
			[ 'My Setting', 'My Setting' ],
			[ '', '' ],
		];
	}


	/**
	 * Provider for test_set_description()
	 *
	 * @return array
	 */
	public function provider_set_description() {

		return [
			[ 'Use this setting to configure it', 'Use this setting to configure it' ],
			[ '', '' ],
		];
	}


	/**
	 * Provider for test_set_is_multi()
	 *
	 * @return array
	 */
	public function provider_set_is_multi() {

		return [
			[ true, true ],
			[ false, false ],
		];
	}


	/**
	 * Provider for test_set_options()
	 *
	 * @return array
	 */
	public function provider_set_options() {

		return [
			[ [ 'example 1', 'example 2' ], [ 'example 1', 'example 2' ] ],
			[ [ -1, 1, 2 ], [ -1, 1, 2 ] ],
			[ [ 1.5, 2.5, -3 ], [ 1.5, 2.5, -3 ] ],
			[ [ true, false ], [ true, false ] ],
		];
	}


	/**
	 * Provider for test_set_default()
	 *
	 * @return array
	 */
	public function provider_set_default() {

		return [
			[ 'example', 'example' ],
			[ 'example.com', 'example.com' ],
			[ 'test@example.com', 'test@example.com' ],
			[ 1, 1 ],
			[ 0.5, 0.5 ],
			[ false, false ],
			[ '', '' ],
		];
	}


	/**
	 * Provider for test_set_value()
	 *
	 * @return array
	 */
	public function provider_set_value() {

		return [
			[ 'example', 'example' ],
			[ 'example.com', 'example.com' ],
			[ 'test@example.com', 'test@example.com' ],
			[ 1, 1 ],
			[ 0.5, 0.5 ],
			[ false, false ],
			[ '', '' ],
		];
	}


	/**
	 * Provider for test_set_control()
	 *
	 * @return array
	 */
	public function provider_set_control() {

		require_once( 'woocommerce/Settings_API/Control.php' );

		$control = new Control();

		return [
			[ $control, $control ],
		];
	}


}
