<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Settings_API;

use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Plugin_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use TypeError;

class ControlTest extends TestCase
{
	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::get_setting_id()
	 * @throws SV_WC_Plugin_Exception
	 */
	public function test_get_setting_id() : void
	{
		$control = new Control();
		$control->set_setting_id('setting');

		$this->assertSame('setting', $control->get_setting_id());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::get_type()
	 * @throws SV_WC_Plugin_Exception
	 */
	public function test_get_type() : void
	{

		$control = new Control();
		$control->set_type('this-type');

		$this->assertSame('this-type', $control->get_type());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::get_name()
	 * @throws SV_WC_Plugin_Exception
	 */
	public function test_get_name() : void
	{
		$control = new Control();
		$control->set_name('Control name');

		$this->assertSame('Control name', $control->get_name());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::get_description()
	 * @throws SV_WC_Plugin_Exception
	 */
	public function test_get_description() : void
	{
		$control = new Control();
		$control->set_description('Control description');

		$this->assertSame('Control description', $control->get_description());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::get_options()
	 */
	public function test_get_options() : void
	{
		$options = [
			'option-1' => 'Option 1',
			'option-2' => 'Option 2',
		];

		$control = new Control();
		$control->set_options($options, ['option-1', 'option-2']);

		$this->assertSame($options, $control->get_options());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::set_setting_id()
	 *
	 * @param mixed $value value to pass to the method
	 * @param string $expected expected value
	 * @param bool $exception whether an exception is expected
	 * @throws SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_set_setting_id
	 */
	public function test_set_setting_id($value, string $expected, bool $exception = false) : void
	{
		if ($exception) {
			$this->expectException(SV_WC_Plugin_Exception::class);
		}

		$control = new Control();
		$control->set_setting_id($value);

		$this->assertSame($expected, $control->get_setting_id());
	}


	/** @see test_set_setting_id() */
	public function provider_set_setting_id() : array
	{
		return [
			['yes', 'yes'],
			['', ''],
			[false, '', true],
		];
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::set_type()
	 *
	 * @param mixed $value value to pass to the method
	 * @param array $allowed_types allowed control types
	 * @param string|null $expected expected value
	 * @param bool $exception whether an exception is expected
	 * @throws SV_WC_Plugin_Exception
	 * @dataProvider provider_set_type
	 */
	public function test_set_type($value, array $allowed_types, ?string $expected, bool $exception = false) : void
	{
		if ($exception) {
			$this->expectException(SV_WC_Plugin_Exception::class);
		}

		$control = new Control();
		$control->set_type($value, $allowed_types);

		$this->assertSame($expected, $control->get_type());
	}


	/** @see test_set_type() */
	public function provider_set_type() : array
	{
		return [
			['yes', ['yes', 'maybe'], 'yes'],     // valid value
			['no', ['yes', 'maybe'], null, true], // invalid value
			['no', [], 'no'],                       // no types to validate
		];
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::set_name()
	 *
	 * @param mixed $value value to pass to the method
	 * @param string $expected expected value
	 * @param bool $exception whether an exception is expected
	 * @throws SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_set_name
	 */
	public function test_set_name($value, string $expected, bool $exception = false) : void
	{

		if ($exception) {
			$this->expectException(SV_WC_Plugin_Exception::class);
		}

		$control = new Control();
		$control->set_name($value);

		$this->assertSame($expected, $control->get_name());
	}


	/** @see test_set_name() */
	public function provider_set_name() : array
	{
		return [
			['name', 'name'],
			['', ''],
			[false, '', true],
		];
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::set_description()
	 *
	 * @param mixed $value value to pass to the method
	 * @param string $expected expected value
	 * @param bool $exception whether an exception is expected
	 * @throws SV_WC_Plugin_Exception
	 *
	 * @dataProvider provider_set_name
	 */
	public function test_set_description($value, string $expected, bool $exception = false) : void
	{
		if ($exception) {
			$this->expectException(SV_WC_Plugin_Exception::class);
		}

		$control = new Control();
		$control->set_description($value);

		$this->assertSame($expected, $control->get_description());
	}


	/** @see test_set_description() */
	public function provider_set_description() : array
	{
		return [
			['description', 'description'],
			['', ''],
			[false, '', true],
		];
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control::set_options()
	 *
	 * @param mixed $options value to pass to the method
	 * @param mixed $valid_options valid option keys to check against
	 * @param array $expected expected value
	 * @param bool $exception whether an exception is expected
	 *
	 * @dataProvider provider_set_options
	 */
	public function test_set_options($options, $valid_options, array $expected, bool $exception = false) : void
	{
		if ($exception) {
			$this->expectException(TypeError::class);
		}

		$control = new Control();
		$control->set_options($options, $valid_options);

		$this->assertSame($expected, $control->get_options());
	}


	/** @see test_set_options() */
	public function provider_set_options() : array
	{
		return [
			[
				[],
				['b', 'd'],
				[],
				false,
			],

			[
				['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
				['b', 'd'],
				['b' => 'B', 'd' => 'D'],
				false,
			],

			[
				['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
				['x', 'y'],
				[],
				false,
			],

			[
				['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
				[],
				['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
				false,
			],

			[
				'a,b,c,d',
				[],
				[],
				true,
			],

			[
				['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
				'a',
				[],
				true,
			],
		];
	}

}
