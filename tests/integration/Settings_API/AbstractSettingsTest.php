<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Abstract_Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Setting;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Control;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Exception;

/**
 * Tests for the Abstract_Settings class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Abstract_Settings
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
	 * @see Abstract_Settings::register_setting()
	 * @see Abstract_Settings::get_setting()
	 */
	public function test_register_setting() {

		$this->assertTrue( $this->get_settings_instance()->register_setting( 'test-setting-d', Setting::TYPE_EMAIL, [
			'name'        => 'Test Setting D',
			'description' => 'Description of setting D',
		] ) );

		$this->assertInstanceOf( Setting::class, $this->get_settings_instance()->get_setting( 'test-setting-d' ) );

		// existing setting ID
		$this->assertFalse( $this->get_settings_instance()->register_setting( 'test-setting-d', Setting::TYPE_EMAIL, [
			'name'        => 'Test Setting D',
			'description' => 'Description of setting D',
		] ) );

		// invalid setting type
		$this->assertFalse( $this->get_settings_instance()->register_setting( 'test-setting-e', 'invalid-type', [
			'name'        => 'Test Setting E',
			'description' => 'Description of setting E',
		] ) );

		$this->assertNull( $this->get_settings_instance()->get_setting( 'test-setting-e' ) );
	}


	/**
	 * @see Abstract_Settings::load_settings()
	 *
	 * Stored values are defined in get_settings_instance().
	 */
	public function test_load_settings() {

		$this->get_settings_instance()->register_setting( 'test-setting-d', Setting::TYPE_EMAIL, [
			'name'        => 'Test Setting D',
			'description' => 'Description of setting D',
		] );

		$this->assertSame( 'something', $this->get_settings_instance()->get_setting( 'test-setting-a' )->get_value() );
		$this->assertSame( 1729, $this->get_settings_instance()->get_setting( 'test-setting-b' )->get_value() );
		$this->assertSame( true, $this->get_settings_instance()->get_setting( 'test-setting-c' )->get_value() );
		$this->assertSame( null, $this->get_settings_instance()->get_setting( 'test-setting-d' )->get_value() );
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
	 * @see Abstract_Settings::register_control()
	 *
	 * @param string $setting_id the setting ID
	 * @param string $control_type the control type
	 * @param string[] $setting_control_types the control types valid for the setting
	 * @param bool $registered whether the control should be successfully registered or not
	 *
	 * @dataProvider provider_register_control
	 */
	public function test_register_control( $setting_id, $control_type, $setting_control_types, $registered ) {

		$this->get_settings_instance()->register_setting( 'registered_setting', Setting::TYPE_STRING );

		if ( ! empty( $setting_control_types ) ) {

			add_filter( "wc_{$this->get_settings_instance()->get_id()}_settings_api_setting_control_types", function () use ( $setting_control_types ) {

				return $setting_control_types;
			} );
		}

		$this->assertSame( $registered, $this->get_settings_instance()->register_control( $setting_id, $control_type ) );

		if ( $registered ) {

			$this->assertInstanceOf( Control::class, $this->get_settings_instance()->get_setting( $setting_id )->get_control() );

		} elseif ( $setting = $this->get_settings_instance()->get_setting( $setting_id ) ) {

			$this->assertNull( $setting->get_control() );
		}
	}


	/** @see test_register_control() */
	public function provider_register_control() {

		require_once 'woocommerce/Settings_API/Control.php';

		return [
			[ 'unknown_setting',    Control::TYPE_TEXT, [], false ],
			[ 'registered_setting', 'invalid_type',     [], false ],
			[ 'registered_setting', Control::TYPE_TEXT, [], true ],
			[ 'registered_setting', Control::TYPE_TEXT, [ Control::TYPE_TEXT, Control::TYPE_SELECT ], true ],
			[ 'registered_setting', Control::TYPE_EMAIL, [ Control::TYPE_TEXT, Control::TYPE_SELECT ], false ],
		];
	}


	/** @see Abstract_Settings::register_control() */
	public function test_register_control_args() {

		$setting_args = [
			'name'        => 'Setting Name',
			'description' => 'Setting Description',
			'options'     => [ 'black', 'white' ],
		];

		$this->get_settings_instance()->register_setting( 'color', Setting::TYPE_STRING, $setting_args );

		$this->get_settings_instance()->register_control( 'color', Control::TYPE_SELECT, [
			'options' => [
				'black' => 'Black',
				'white' => 'White',
				'red'	=> 'Red',
			],
		] );

		$control = $this->get_settings_instance()->get_setting( 'color' )->get_control();

		// TODO: uncomment assert for $control->get_options when https://github.com/skyverge/wc-plugin-framework/pull/453 is merged {WV 2020-03-20}

		$this->assertEquals( 'color', $control->get_setting_id() );
		$this->assertEquals( Control::TYPE_SELECT, $control->get_type() );
		$this->assertEquals( $setting_args['name'], $control->get_name() );
		$this->assertEquals( $setting_args['description'], $control->get_description() );
		// $this->assertEquals( $setting_args['options'], array_keys( $control->get_options() ) );
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


	/** @see Abstract_Settings::get_value() */
	public function test_get_value() {

		$setting = $this->get_settings_instance()->get_setting( 'test-setting-b' );
		$setting->set_value( 1000 );
		$this->get_settings_instance()->save( $setting->get_id() );

		$this->assertEquals( 1000, $this->get_settings_instance()->get_value( $setting->get_id() ) );
	}


	/**
	 * @see Abstract_Settings::get_value()
	 *
	 * @param mixed $expected_value the returned value
	 * @param bool $with_default whether to return the default value if nothing is stored
	 * @throws Framework\SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_get_value_nothing_stored
	 */
	public function test_get_value_nothing_stored( $expected_value, $with_default ) {

		$setting = $this->get_settings_instance()->get_setting( 'test-setting-b' );
		$this->get_settings_instance()->delete_value( $setting->get_id() );

		$this->assertEquals( $expected_value, $this->get_settings_instance()->get_value( $setting->get_id(), $with_default ) );
	}


	/** @see test_get_value_nothing_stored() */
	public function provider_get_value_nothing_stored() {

		return [
			[ 3600, true ],
			[ null, false ],
		];
	}


	/** @see Abstract_Settings::get_value() */
	public function test_get_value_exception() {

		$this->expectException( Framework\SV_WC_Plugin_Exception::class );

		$this->get_settings_instance()->get_value( 'not_a_setting' );
	}


	/**
	 * @see Abstract_Settings::update_value()
	 *
	 * @param bool $register whether to register a new setting
	 * @param mixed $value value to pass to method
	 * @param string $type setting type
	 * @param array $options setting options
	 * @param mixed $expected setting value after execution
	 * @param bool $exception whether an exception is expected
	 * @throws Framework\SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_update_value
	 */
	public function test_update_value( $register, $value, $type, $options, $expected, $exception ) {

		if ( $exception ) {
			$this->expectException( SV_WC_Plugin_Exception::class );
		}

		$setting_id  = 'test-setting';
		$option_name = $this->get_settings_instance()->get_option_name_prefix() . '_' . $setting_id;

		if ( $register ) {
			$this->get_settings_instance()->register_setting( $setting_id, $type, [ 'options' => $options ] );
		}

		$this->get_settings_instance()->update_value( $setting_id, $value );

		$this->assertSame( $expected, $this->get_settings_instance()->get_setting( $setting_id )->get_value() );

		$setting = $this->get_settings_instance()->get_setting( $setting_id );
		$method  = new ReflectionMethod( Abstract_Settings::class, 'get_value_from_database' );
		$method->setAccessible( true );

		$this->assertEquals( $expected, $method->invokeArgs( $this->get_settings_instance(), [
			get_option( $option_name ),
			$setting
		] ) );
	}


	/**
	 * Provider for test_update_value()
	 *
	 * @return array
	 */
	public function provider_update_value() {

		require_once( 'woocommerce/Settings_API/Setting.php' );

		return [
			[ false, 'valid', Setting::TYPE_STRING, [], null, true ],

			[ true, 'valid', Setting::TYPE_STRING, [], 'valid', false ],
			[ true, 123, Setting::TYPE_STRING, [], null, true ],
			[ true, 'green', Setting::TYPE_STRING, [ 'green', 'red' ], 'green', false ],
			[ true, 'not an option', Setting::TYPE_STRING, [ 'green', 'red' ], null, true ],

			[ true, 'https://skyverge.com/', Setting::TYPE_URL, [], 'https://skyverge.com/', false ],
			[ true, 'file:///tmp/', Setting::TYPE_URL, [], null, true ],
			[ true, 'https://skyverge.com/', Setting::TYPE_URL, [ 'https://skyverge.com/', 'http://skyverge.com/' ], 'https://skyverge.com/', false ],
			[ true, 'https://google.com/', Setting::TYPE_URL, [ 'https://skyverge.com/', 'http://skyverge.com/' ], null, true ],

			[ true, 'test@example.com', Setting::TYPE_EMAIL, [], 'test@example.com', false ],
			[ true, 'not-an-email.com', Setting::TYPE_EMAIL, [], null, true ],
			[ true, 'test@example.com', Setting::TYPE_EMAIL, [ 'test@example.com' ], 'test@example.com', false ],
			[ true, 'another@example.com', Setting::TYPE_EMAIL, [ 'test@example.com' ], null, true ],

			[ true, 12345, Setting::TYPE_INTEGER, [], 12345, false ],
			[ true, 1.345, Setting::TYPE_INTEGER, [], null, true ],
			[ true, '234', Setting::TYPE_INTEGER, [], null, true ],
			[ true, 1, Setting::TYPE_INTEGER, [ 1, 2 ], 1, false ],
			[ true, 3, Setting::TYPE_INTEGER, [ 1, 2 ], null, true ],

			[ true, 12345, Setting::TYPE_FLOAT, [], 12345, false ],
			[ true, 1.345, Setting::TYPE_FLOAT, [], 1.345, false ],
			[ true, '234', Setting::TYPE_FLOAT, [], null, true ],
			[ true, 1.5, Setting::TYPE_FLOAT, [ 1.5, 2.5 ], 1.5, false ],
			[ true, 3.5, Setting::TYPE_FLOAT, [ 1.5, 2.5 ], null, true ],

			[ true, true, Setting::TYPE_BOOLEAN, [], true, false ],
			[ true, 'yes', Setting::TYPE_BOOLEAN, [], null, true ],
			[ true, 1, Setting::TYPE_BOOLEAN, [], null, true ],
			// it beats me why someone would have a boolean setting with only one option, but in theory it is possible
			[ true, true, Setting::TYPE_BOOLEAN, [ true ], true, false ],
			[ true, false, Setting::TYPE_BOOLEAN, [ true ], null, true ],
		];
	}


	/** @see Abstract_Settings::delete_value() */
	public function test_delete_value() {

		$setting = $this->get_settings_instance()->get_setting( 'test-setting-a' );

		$setting->set_value( 'something' );
		$this->get_settings_instance()->save( 'test-setting-a' );

		$option_name = $this->get_settings_instance()->get_option_name_prefix() . '_' . $setting->get_id();

		$this->assertNotEmpty( $setting->get_value() );
		$this->assertNotEmpty( get_option( $option_name ) );

		$this->get_settings_instance()->delete_value( $setting->get_id() );

		$this->assertNull( $setting->get_value() );
		$this->assertFalse( get_option( $option_name ) );
	}


	/** @see Abstract_Settings::save() */
	public function test_save() {

		$setting_a = $this->get_settings_instance()->get_setting( 'test-setting-a' );
		$setting_b = $this->get_settings_instance()->get_setting( 'test-setting-b' );

		$option_name_a = $this->get_settings_instance()->get_option_name_prefix() . '_' . $setting_a->get_id();
		update_option( $option_name_a, 'old value' );

		$option_name_b = $this->get_settings_instance()->get_option_name_prefix() . '_' . $setting_b->get_id();
		update_option( $option_name_b, - 1 );

		$setting_a->set_value( 'new value' );
		$setting_b->set_value( 2 );

		$this->assertEquals( 'new value', $setting_a->get_value() );
		$this->assertEquals( 'old value', get_option( $option_name_a ) );

		$this->assertEquals( 2, $setting_b->get_value() );
		$this->assertEquals( - 1, get_option( $option_name_b ) );

		$this->get_settings_instance()->save();

		$this->assertEquals( 'new value', $setting_a->get_value() );
		$this->assertEquals( 'new value', get_option( $option_name_a ) );
		$this->assertEquals( 2, $setting_b->get_value() );
		$this->assertEquals( 2, get_option( $option_name_b ) );
	}


	/** @see Abstract_Settings::save() */
	public function test_save_single_setting() {

		$setting_a = $this->get_settings_instance()->get_setting( 'test-setting-a' );
		$setting_b = $this->get_settings_instance()->get_setting( 'test-setting-b' );

		$option_name_a = $this->get_settings_instance()->get_option_name_prefix() . '_' . $setting_a->get_id();
		update_option( $option_name_a, 'old value' );

		$option_name_b = $this->get_settings_instance()->get_option_name_prefix() . '_' . $setting_b->get_id();
		update_option( $option_name_b, - 1 );

		$setting_a->set_value( 'new value' );
		$setting_b->set_value( 2 );

		$this->assertEquals( 'new value', $setting_a->get_value() );
		$this->assertEquals( 'old value', get_option( $option_name_a ) );

		$this->assertEquals( 2, $setting_b->get_value() );
		$this->assertEquals( - 1, get_option( $option_name_b ) );

		$this->get_settings_instance()->save( 'test-setting-a' );

		$this->assertEquals( 'new value', $setting_a->get_value() );
		$this->assertEquals( 'new value', get_option( $option_name_a ) );
		$this->assertEquals( 2, $setting_b->get_value() );
		$this->assertEquals( - 1, get_option( $option_name_b ) );
	}


	/** @see Abstract_Settings::delete_value() */
	public function test_delete_value_exception() {

		$this->expectException( Framework\SV_WC_Plugin_Exception::class );

		$this->get_settings_instance()->delete_value( 'not_a_setting' );
	}


	/**
	 * @see Abstract_Settings::get_value_for_database()
	 *
	 * @param string $type the setting type
	 * @param mixed $value the current setting value
	 * @param mixed $expected_value the converted value
	 *
	 * @dataProvider provider_get_value_for_database
	 */
	public function test_get_value_for_database( $type, $value, $expected_value ) {

		$setting = new Setting();

		$setting->set_type( $type );
		$setting->set_value( $value );

		$method  = new ReflectionMethod( Abstract_Settings::class, 'get_value_for_database' );
		$method->setAccessible( true );

		$this->assertSame( $expected_value, $method->invoke( $this->get_settings_instance(), $setting ) );
	}


	/** @see test_get_value_for_database() */
	public function provider_get_value_for_database() {

		return [
			'string'        => [ Setting::TYPE_STRING, 'hello', 'hello' ],
			'url'           => [ Setting::TYPE_URL, 'https://skyverge.com', 'https://skyverge.com' ],
			'email'         => [ Setting::TYPE_EMAIL, 'hello@example.com', 'hello@example.com' ],
			'integer'       => [ Setting::TYPE_INTEGER, 1234, 1234 ],
			'float'         => [ Setting::TYPE_FLOAT, 12.4, 12.4 ],
			'boolean true'  => [ Setting::TYPE_BOOLEAN, true, 'yes' ],
			'boolean false' => [ Setting::TYPE_BOOLEAN, false, 'no' ],
		];
	}


	/**
	 * @see Abstract_Settings::get_value_from_database()
	 *
	 * @param mixed $value the value stored in the database
	 * @param mixed $expected_value the converted value
	 * @param string $type the setting type
	 *
	 * @dataProvider provider_get_value_from_database
	 */
	public function test_get_value_from_database( $value, $expected_value, $type ) {

		$setting = new Setting();
		$setting->set_type( $type );

		$method  = new ReflectionMethod( Abstract_Settings::class, 'get_value_from_database' );
		$method->setAccessible( true );

		$this->assertSame( $expected_value, $method->invokeArgs( $this->get_settings_instance(), [ $value, $setting ] ) );
	}


	/** @see test_get_value_from_database() */
	public function provider_get_value_from_database() {

		require_once 'woocommerce/Settings_API/Setting.php';

		return [
			[ '12345', 12345, Setting::TYPE_INTEGER ],
			[ '12.45', 12,    Setting::TYPE_INTEGER ],
			[ '0',     0,     Setting::TYPE_INTEGER ],
			[ 'hello', null,  Setting::TYPE_INTEGER ],
			[ null,    null,  Setting::TYPE_INTEGER ],
			[ '',      null,  Setting::TYPE_INTEGER ],

			[ '12345', 12345.0, Setting::TYPE_FLOAT ],
			[ '12.45', 12.45,   Setting::TYPE_FLOAT ],
			[ '0',     0.0,     Setting::TYPE_FLOAT ],
			[ 'hello', null,    Setting::TYPE_FLOAT ],
			[ null,    null,    Setting::TYPE_FLOAT ],
			[ '',      null,    Setting::TYPE_FLOAT ],

			[ 'yes', true,  Setting::TYPE_BOOLEAN ],
			[ 'no',  false, Setting::TYPE_BOOLEAN ],
			[ '1',   true,  Setting::TYPE_BOOLEAN ],
			[ '0',   false, Setting::TYPE_BOOLEAN ],
			[ 'hey', false, Setting::TYPE_BOOLEAN ],
			[ null,  null,  Setting::TYPE_BOOLEAN ],
			[ '',    false, Setting::TYPE_BOOLEAN ],
		];
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


	/** @see Abstract_Settings::get_setting_control_types() */
	public function test_get_setting_control_types() {

		$setting = $this->get_settings_instance()->get_setting( 'test-setting-a' );

		$this->assertIsArray( $this->get_settings_instance()->get_setting_control_types( $setting ) );

		add_filter( "wc_{$this->get_settings_instance()->get_id()}_settings_api_setting_control_types", function() {

			return [ 'my_type' ];
		} );

		$this->assertEquals( [ 'my_type' ], $this->get_settings_instance()->get_setting_control_types( $setting ) );
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

					update_option( "{$this->get_option_name_prefix()}_{$this->settings['test-setting-a']->get_id()}", 'something' );
					update_option( "{$this->get_option_name_prefix()}_{$this->settings['test-setting-b']->get_id()}", '1729' );
					update_option( "{$this->get_option_name_prefix()}_{$this->settings['test-setting-c']->get_id()}", 'yes' );
				}


			};
		}

		return $this->settings;
	}


}
