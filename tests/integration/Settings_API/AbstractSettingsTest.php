<?php

use SkyVerge\WooCommerce\PluginFramework\v5_6_1 as Framework;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Abstract_Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Setting;

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
		require_once 'woocommerce/Settings_API/Control.php';
		require_once 'woocommerce/Settings_API/Setting.php';
	}


	protected function _after() {

	}


	/** Tests *********************************************************************************************************/


	/** @see Abstract_Settings::get_id() */
	public function test_get_id() {

		$this->assertEquals( 'test-plugin', $this->get_settings_instance()->get_id() );
	}


	/**
	 * @see Abstract_Settings::unregister_setting()
	 * @see Abstract_Settings::get_setting()
	 */
	public function test_unregister_setting() {

		$this->assertInstanceOf( Setting::class, $this->get_settings_instance()->get_setting( 'test-setting-a' ) );

		$this->get_settings_instance()->unregister_setting( 'test-setting-a' );

		$this->assertNull( $this->get_settings_instance()->get_setting( 'test-setting-a' ) );
	}


	/**
	 * @see Abstract_Settings::get_settings()
	 *
	 * @param array $ids settings IDs to get
	 * @param array $expected_ids expected settings IDs to retrieve
	 *
	 * @dataProvider provider_get_settings
	 */
	public function test_get_settings( $ids, $expected_ids ) {

		$settings = $this->get_settings_instance()->get_settings( $ids );

		$this->assertEquals( array_keys( $settings ), $expected_ids );
	}


	/** @see test_get_settings() */
	public function provider_get_settings() {

		return [
			[ [ 'test-setting-a', 'test-setting-b' ], [ 'test-setting-a', 'test-setting-b' ] ],
			[ [], [ 'test-setting-a', 'test-setting-b', 'test-setting-c' ] ],
			[ [ 'test-setting-x' ], [] ],
		];
	}


	/** @see Abstract_Settings::delete_value() */
	public function test_delete_value() {

		$setting     = $this->get_settings_instance()->get_setting( 'test-setting-a' );
		$option_name = $this->get_settings_instance()->get_option_name_prefix() . '_' . $setting->get_id();

		$this->assertNotEmpty( $setting->get_value() );
		$this->assertNotEmpty( get_option( $option_name ) );

		$this->get_settings_instance()->delete_value( $setting->get_id() );

		$this->assertNull( $setting->get_value() );
		$this->assertFalse( get_option( $option_name ) );
	}


	/** @see Abstract_Settings::delete_value() */
	public function test_delete_value_exception() {

		$this->expectException( Framework\SV_WC_Plugin_Exception::class );

		$this->get_settings_instance()->delete_value( 'not_a_setting' );
	}


	/** @see Abstract_Settings::get_value_for_database() */
	public function test_get_value_for_database() {

		// TODO: implement this test when save() is available {WV 2020-03-20}
		$this->markTestSkipped();
	}


	/** @see Abstract_Settings::get_value_from_database() */
	public function test_get_value_from_database() {

		// TODO: implement this test when load_settings() is available {WV 2020-03-20}
		$this->markTestSkipped();
	}


	/** @see Abstract_Settings::get_setting_types() */
	public function test_get_setting_types() {

		$this->assertIsArray( $this->get_settings_instance()->get_setting_types() );

		add_filter( "wc_{$this->get_settings_instance()->get_id()}_settings_api_setting_types", function() {

			return [ 'my_type' ];
		} );

		$this->assertEquals( [ 'my_type' ], $this->get_settings_instance()->get_setting_types() );
	}


	/** @see Abstract_Settings::get_control_types() */
	public function test_get_control_types() {

		$this->assertIsArray( $this->get_settings_instance()->get_control_types() );

		add_filter( "wc_{$this->get_settings_instance()->get_id()}_settings_api_control_types", function() {

			return [ 'my_type' ];
		} );

		$this->assertEquals( [ 'my_type' ], $this->get_settings_instance()->get_control_types() );
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

					$this->register_setting( 'test-setting-a', Setting::TYPE_STRING, [
						'name'        => 'Test Setting A',
						'description' => 'Description of setting A',
					] );

					$this->register_setting( 'test-setting-b', Setting::TYPE_INTEGER, [
						'name'        => 'Test Setting B',
						'description' => 'Description of setting B',
						'default'     => 3600,
					] );

					$this->register_setting( 'test-setting-c', Setting::TYPE_BOOLEAN, [
						'name'        => 'Test Setting C',
						'description' => 'Description of setting C',
						'default'     => true,
					] );

					// TODO: remove when save() is available
					$this->settings['test-setting-a']->set_value( 'example' );

					update_option( "{$this->get_option_name_prefix()}_{$this->settings['test-setting-a']->get_id()}", 'something' );
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
