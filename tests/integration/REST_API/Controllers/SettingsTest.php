<?php

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\REST_API\Controllers\Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Abstract_Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Setting;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Control;

/**
 * Tests for the Settings class.
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

		$settings = $this->get_settings_instance();

		$settings->register_setting( 'test_one', Setting::TYPE_STRING, [
			'name'        => 'Test Setting One',
			'description' => 'A simple setting',
			'options'     => [ 'a', 'b', 'c' ],
			'default'     => 'c',
		] );

		$settings->register_control( 'test_one', Control::TYPE_SELECT, [
			'name'        => 'Select field',
			'description' => 'A regular select input field for setting one',
			'options'     => [
				'a' => 'A',
				'b' => 'B',
				'c' => 'C'
			],
		] );

		$settings->register_setting( 'test_two', Setting::TYPE_STRING, [
			'name'        => 'Test Setting Two',
			'description' => 'Another simple setting',
			'options'     => [ 'a', 'b', 'c' ],
			'default'     => 'b',
		] );

		$settings->register_control( 'test_two', Control::TYPE_SELECT, [
			'name'        => 'Select field',
			'description' => 'A regular select input field for setting two',
			'options'     => [
				'a' => 'A',
				'b' => 'B',
				'c' => 'C'
			],
		] );

		$settings->register_setting( 'test_three', Setting::TYPE_STRING, [
			'name'        => 'Test Setting Three',
			'description' => 'A third simple setting',
			'options'     => [ 'a', 'b', 'c' ],
			'default'     => 'a',
		] );
	}


	protected function _after() {

	}


	/** Tests *********************************************************************************************************/


	/** @see Settings::get_items() */
	public function test_get_items() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$setting_objects = [
			'test_one'   => $settings->get_setting( 'test_one' ),
			'test_two'   => $settings->get_setting( 'test_two' ),
			'test_three' => $settings->get_setting( 'test_three' ),
		];

		$setting_objects['test_one']->set_value( 'a' );

		$request  = new WP_REST_Request( 'GET', "/wc/v3/{$settings->get_id()}/settings" );
		$response = $controller->get_items( $request );

		$this->assertTrue( $response instanceof WP_REST_Response );
		$this->assertSame( 200, $response->get_status() );

		$items = $response->get_data();

		foreach ( $items as $item ) {

			$setting = $setting_objects[ $item['id'] ];

			$this->assert_item_matches_setting( $item, $setting );

			if ( $control = $setting->get_control() ) {
				$this->assert_item_matches_control( $item['control'], $control );
			}
		}

		$this->assertEquals( count( $setting_objects ), count( $items ) );
	}


	/** @see Settings::get_item() */
	public function test_get_item() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$setting = $settings->get_setting( 'test_one' );
		$setting->set_value( 'a' );

		$request = new WP_REST_Request( 'GET', "/wc/v3/{$settings->get_id()}/settings/{$setting->get_id()}" );
		$request->set_url_params( [ 'id' => $setting->get_id() ] );

		$response = $controller->get_item( $request );

		$this->assertTrue( $response instanceof WP_REST_Response );

		$this->assertSame( 200, $response->get_status() );

		$item = $response->get_data();

		$this->assert_item_matches_setting( $item, $setting );
		$this->assert_item_matches_control( $item['control'], $setting->get_control() );
	}


	/** @see Settings::get_item() */
	public function test_get_item_not_found() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$setting_id = 'test';

		$request = new WP_REST_Request( 'GET', "/wc/v3/{$settings->get_id()}/settings/{$setting_id}" );
		$request->set_url_params( [ 'id' => $setting_id ] );

		$response = $controller->get_item( $request );

		$this->assertTrue( $response instanceof WP_Error );
		$this->assertSame( 'wc_rest_setting_not_found', $response->get_error_code() );
		$this->assertSame( [ 'status' => 404 ], $response->get_error_data() );
	}


	/** @see Settings::prepare_setting_item() */
	public function test_prepare_setting_item() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$setting = $settings->get_setting( 'test_one' );
		$setting->set_value( 'a' );

		$item = $controller->prepare_setting_item( $setting, null );

		$this->assert_item_matches_setting( $item, $setting );
		$this->assert_item_matches_control( $item['control'], $setting->get_control() );
	}


	/** @see Settings::prepare_setting_item() */
	public function test_prepare_setting_item_value_not_set() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$settings->register_setting( 'test', Setting::TYPE_STRING );

		$item = $controller->prepare_setting_item( $settings->get_setting( 'test' ), null );

		// $item['value'] is null if no value has been set for the setting
		// we want users of the API to differentiate between a setting that's been set vs. a setting that has a default value but not saved yet
		$this->assertEquals( null, $item['value'] );
	}


	/** @see Settings::prepare_setting_item() */
	public function test_prepare_setting_item_control_not_set() {

		$settings   = $this->get_settings_instance();
		$controller = new Settings( $settings );

		$settings->register_setting( 'test', Setting::TYPE_STRING );

		$item = $controller->prepare_setting_item( $settings->get_setting( 'test' ), null );

		$this->assertSame( null, $item['control'] );
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


	/**
	 * Asserts that entries in an array match the properties of a setting.
	 *
	 * @param array $item a data array
	 * @param Setting $setting a setting object
	 */
	private function assert_item_matches_setting( array $item, $setting ) {

		$this->assertEquals( $setting->get_id(), $item['id'] );
		$this->assertEquals( $setting->get_type(), $item['type'] );
		$this->assertEquals( $setting->get_name(), $item['name'] );
		$this->assertEquals( $setting->get_description(), $item['description'] );
		$this->assertEquals( $setting->is_is_multi(), $item['is_multi'] );
		$this->assertEquals( $setting->get_options(), $item['options'] );
		$this->assertEquals( $setting->get_default(), $item['default'] );
		$this->assertEquals( $setting->get_value(), $item['value'] );
	}


	/**
	 * Asserts that entries in an array match the properties of a control.
	 *
	 * @param array $item a data array
	 * @param Control $control a control object
	 */
	private function assert_item_matches_control( array $item, $control ) {

		$this->assertEquals( $control->get_type(), $item['type'] );
		$this->assertEquals( $control->get_name(), $item['name'] );
		$this->assertEquals( $control->get_description(), $item['description'] );
		$this->assertEquals( $control->get_options(), $item['options'] );
	}


}
