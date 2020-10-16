<?php

namespace Settings_API;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API\Control;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Exception;
use TypeError;

define( 'ABSPATH', true );

class ControlTest extends \Codeception\Test\Unit {


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once( 'woocommerce/class-sv-wc-plugin-exception.php' );
		require_once( 'woocommerce/class-sv-wc-helper.php' );
		require_once( 'woocommerce/Settings_API/Control.php' );
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see Control::get_setting_id() */
	public function test_get_setting_id() {

		$control = new Control();
		$control->set_setting_id( 'setting' );

		$this->assertSame( 'setting', $control->get_setting_id() );
	}


	/** @see Control::get_type() */
	public function test_get_type() {

		$control = new Control();
		$control->set_type( 'this-type' );

		$this->assertSame( 'this-type', $control->get_type() );
	}


	/** @see Control::get_name() */
	public function test_get_name() {

		$control = new Control();
		$control->set_name( 'Control name' );

		$this->assertSame( 'Control name', $control->get_name() );
	}


	/** @see Control::get_description() */
	public function test_get_description() {

		$control = new Control();
		$control->set_description( 'Control description' );

		$this->assertSame( 'Control description', $control->get_description() );
	}


	/** @see Control::get_options() */
	public function test_get_options() {

		$options = [
			'option-1' => 'Option 1',
			'option-2' => 'Option 2',
		];

		$control = new Control();
		$control->set_options( $options, [ 'option-1', 'option-2' ] );

		$this->assertSame( $options, $control->get_options() );
	}


	/**
	 * @see Control::set_setting_id()
	 *
	 * @param mixed $value value to pass to the method
	 * @param string $expected expected value
	 * @param bool $exception whether an exception is expected
	 * @throws SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_set_setting_id
	 */
	public function test_set_setting_id( $value, $expected, $exception = false ) {

		if ( $exception ) {
			$this->expectException( SV_WC_Plugin_Exception::class );
		}

		$control = new Control();
		$control->set_setting_id( $value );

		$this->assertSame( $expected, $control->get_setting_id() );
	}


	/** @see test_set_setting_id() */
	public function provider_set_setting_id() {

		return [
			[ 'yes', 'yes' ],
			[ '', '' ],
			[ false, '', true ],
		];
	}


	/**
	 * @see Control::set_type()
	 *
	 * @param mixed $value value to pass to the method
	 * @param array $allowed_types allowed control types
	 * @param string $expected expected value
	 * @param bool $exception whether an exception is expected
	 * @throws SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_set_type
	 */
	public function test_set_type( $value, array $allowed_types, $expected, $exception = false ) {

		if ( $exception ) {
			$this->expectException( SV_WC_Plugin_Exception::class );
		}

		$control = new Control();
		$control->set_type( $value, $allowed_types );

		$this->assertSame( $expected, $control->get_type() );
	}


	/** @see test_set_type() */
	public function provider_set_type() {

		return [
			[ 'yes', [ 'yes', 'maybe' ], 'yes' ],     // valid value
			[ 'no', [ 'yes', 'maybe' ], null, true ], // invalid value
			[ 'no', [], 'no' ],                       // no types to validate
		];
	}


	/**
	 * @see Control::set_name()
	 *
	 * @param mixed $value value to pass to the method
	 * @param string $expected expected value
	 * @param bool $exception whether an exception is expected
	 * @throws SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_set_name
	 */
	public function test_set_name( $value, $expected, $exception = false ) {

		if ( $exception ) {
			$this->expectException( SV_WC_Plugin_Exception::class );
		}

		$control = new Control();
		$control->set_name( $value );

		$this->assertSame( $expected, $control->get_name() );
	}


	/** @see test_set_name() */
	public function provider_set_name() {

		return [
			[ 'name', 'name' ],
			[ '', '' ],
			[ false, '', true ],
		];
	}


	/**
	 * @see Control::set_description()
	 *
	 * @param mixed $value value to pass to the method
	 * @param string $expected expected value
	 * @param bool $exception whether an exception is expected
	 * @throws SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_set_name
	 */
	public function test_set_description( $value, $expected, $exception = false ) {

		if ( $exception ) {
			$this->expectException( SV_WC_Plugin_Exception::class );
		}

		$control = new Control();
		$control->set_description( $value );

		$this->assertSame( $expected, $control->get_description() );
	}


	/** @see test_set_description() */
	public function provider_set_description() {

		return [
			[ 'description', 'description' ],
			[ '', '' ],
			[ false, '', true ],
		];
	}


	/**
	 * @see Control::set_options()
	 *
	 * @param mixed $options value to pass to the method
	 * @param mixed $valid_options valid option keys to check against
	 * @param array $expected expected value
	 * @param bool $exception whether an exception is expected
	 *
	 * @dataProvider provider_set_options
	 */
	public function test_set_options( $options, $valid_options, $expected, $exception = false ) {

		if ( $exception ) {
			$this->expectException( TypeError::class );
		}

		$control = new Control();
		$control->set_options( $options, $valid_options );

		$this->assertSame( $expected, $control->get_options() );
	}


	/** @see test_set_options() */
	public function provider_set_options() {

		return [
			[
				[],
				[ 'b', 'd' ],
				[],
				false
			],

			[
				[ 'a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D' ],
				[ 'b', 'd' ],
				[ 'b' => 'B', 'd' => 'D' ],
				false
			],

			[
				[ 'a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D' ],
				[ 'x', 'y' ],
				[],
				false
			],

			[
				[ 'a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D' ],
				[],
				[ 'a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D' ],
				false
			],

			[
				'a,b,c,d',
				[],
				[],
				true
			],

			[
				[ 'a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D' ],
				'a',
				[],
				true
			],
		];
	}


}
