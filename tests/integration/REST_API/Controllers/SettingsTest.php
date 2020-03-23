<?php

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\REST_API\Controllers\Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Abstract_Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Setting;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Control;

/**
 * Tests for the Abstract_Settings class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_6_1\REST_API\Controllers\Settings
 */
class SettingsTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** @var Abstract_Settings */
	protected $settings;


	protected function _before() {

		require_once 'woocommerce/rest-api/Controllers/Settings.php';
		require_once 'woocommerce/Settings_API/Abstract_Settings.php';
		require_once 'woocommerce/Settings_API/Control.php';
		require_once 'woocommerce/Settings_API/Setting.php';
	}


	protected function _after() {

	}


	/** Tests *********************************************************************************************************/


	/** @see Abstract_Settings::get_id() */
	public function test_prepare_item_for_response() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$settings->register_setting( 'test', Setting::TYPE_STRING, [
			'name'        => 'Test Setting',
			'description' => 'A simple setting',
			'options'     => [ 'a', 'b', 'c' ],
			'default'     => 'c',
		] );

		$settings->register_control( 'test', Control::TYPE_SELECT, [
			'name'  => 'Select field',
			'description' => 'A regultar select input field',
			'options'     => [
				'a' => 'A',
				'b' => 'B',
				'c' => 'C'
			],
		] );

		$setting = $settings->get_setting( 'test' );
		$control = $setting->get_control();

		$setting->set_value( 'a' );

		$item = $controller->prepare_item_for_response( $setting, null )->get_data();

		$this->assertEquals( $setting->get_id(),          $item['id'] );
		$this->assertEquals( $setting->get_type(),        $item['type'] );
		$this->assertEquals( $setting->get_name(),        $item['name'] );
		$this->assertEquals( $setting->get_description(), $item['description'] );
		$this->assertEquals( $setting->is_is_multi(),     $item['is_multi'] );
		$this->assertEquals( $setting->get_options(),     $item['options'] );
		$this->assertEquals( $setting->get_default(),     $item['default'] );
		$this->assertEquals( $setting->get_value(),       $item['value'] );

		$this->assertEquals( $control->get_type(),        $item['control']['type'] );
		$this->assertEquals( $control->get_name(),        $item['control']['name'] );
		$this->assertEquals( $control->get_description(), $item['control']['description'] );
		$this->assertEquals( $control->get_options(),     $item['control']['options'] );
	}


	/** @see Abstract_Settings::get_id() */
	public function test_prepare_item_for_response_value_not_set() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$settings->register_setting( 'test', Setting::TYPE_STRING );

		$item = $controller->prepare_item_for_response( $settings->get_setting( 'test' ), null )->get_data();

		$this->assertEquals( null, $item['value'] );
	}


	/** @see Abstract_Settings::get_id() */
	public function test_prepare_item_for_response_control_not_set() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$settings->register_setting( 'test', Setting::TYPE_STRING );

		$item = $controller->prepare_item_for_response( $settings->get_setting( 'test' ), null )->get_data();

		$this->assertArrayNotHasKey( 'control', $item );
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
