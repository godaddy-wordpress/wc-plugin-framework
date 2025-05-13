<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Settings_API;

use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Control;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;

class SettingTest extends TestCase
{
	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_id()
	 *
	 * @param string $input input ID
	 * @param string $expected expected return ID
	 *
	 * @dataProvider provider_set_id
	 */
	public function test_set_id(string $input, string $expected) : void
	{
		$setting = new Setting();
		$setting->set_id($input);

		$this->assertEquals($expected, $setting->get_id());
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_type()
	 *
	 * @param string $input input type
	 * @param string $expected expected return type
	 *
	 * @dataProvider provider_set_type
	 */
	public function test_set_type(string $input, string $expected) : void
	{
		$setting = new Setting();
		$setting->set_type($input);

		$this->assertEquals($expected, $setting->get_type());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_name()
	 *
	 * @param string $input input name
	 * @param string $expected expected return name
	 *
	 * @dataProvider provider_set_name
	 */
	public function test_set_name(string $input, string $expected) : void
	{
		$setting = new Setting();
		$setting->set_name($input);

		$this->assertEquals($expected, $setting->get_name());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_description()
	 *
	 * @param string $input input description
	 * @param string $expected expected return description
	 *
	 * @dataProvider provider_set_description
	 */
	public function test_set_description(string $input, string $expected) : void
	{

		$setting = new Setting();
		$setting->set_description($input);

		$this->assertEquals($expected, $setting->get_description());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_is_multi()
	 *
	 * @param bool $input input value
	 * @param bool $expected expected return value
	 *
	 * @dataProvider provider_set_is_multi
	 */
	public function test_set_is_multi(bool $input, bool $expected) : void
	{
		$setting = new Setting();
		$setting->set_is_multi($input);

		$this->assertEquals($expected, $setting->is_is_multi());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_options()
	 *
	 * @param array $input input options
	 * @param array $expected expected return options
	 *
	 * @dataProvider provider_set_options
	 */
	public function test_set_options(array $input, array $expected) : void
	{
		$setting = new Setting();
		$setting->set_options($input);

		$this->assertEquals($expected, $setting->get_options());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_default()
	 *
	 * @param int|float|string|bool|array $input input default value
	 * @param int|float|string|bool|array $expected expected return default value
	 *
	 * @dataProvider provider_set_default
	 */
	public function test_set_default($input, $expected) : void
	{
		$setting = new Setting();
		$setting->set_default($input);

		$this->assertEquals($expected, $setting->get_default());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_value()
	 *
	 * @param int|float|string|bool|array $input input value
	 * @param int|float|string|bool|array $expected expected return value
	 *
	 * @dataProvider provider_set_value
	 */
	public function test_set_value($input, $expected) : void
	{
		$setting = new Setting();
		$setting->set_value($input);

		$this->assertEquals($expected, $setting->get_value());
	}


	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API\Setting::set_control()
	 *
	 * @param Control $input input control
	 * @param Control $expected expected return control
	 *
	 * @dataProvider provider_set_control
	 */
	public function test_set_control(Control $input, Control $expected) : void
	{
		$setting = new Setting();
		$setting->set_control($input);

		$this->assertEquals($expected, $setting->get_control());
	}

	/**
	 * Provider for test_set_id()
	 *
	 * @return array
	 */
	public function provider_set_id() : array
	{
		return [
			['my-setting', 'my-setting'],
			['', ''],
		];
	}

	/**
	 * Provider for test_set_type()
	 *
	 * @return array
	 */
	public function provider_set_type() : array
	{
		return [
			['string', 'string'],
			['url', 'url'],
			['email', 'email'],
			['integer', 'integer'],
			['float', 'float'],
			['boolean', 'boolean'],
			['', ''],
		];
	}


	/**
	 * Provider for test_set_name()
	 *
	 * @return array
	 */
	public function provider_set_name() : array
	{
		return [
			['My Setting', 'My Setting'],
			['', ''],
		];
	}


	/**
	 * Provider for test_set_description()
	 *
	 * @return array
	 */
	public function provider_set_description() : array
	{
		return [
			['Use this setting to configure it', 'Use this setting to configure it'],
			['', ''],
		];
	}


	/**
	 * Provider for test_set_is_multi()
	 *
	 * @return array
	 */
	public function provider_set_is_multi() : array
	{
		return [
			[true, true],
			[false, false],
		];
	}


	/**
	 * Provider for test_set_options()
	 *
	 * @return array
	 */
	public function provider_set_options() : array
	{
		return [
			[['example 1', 'example 2'], ['example 1', 'example 2']],
			[[-1, 1, 2], [-1, 1, 2]],
			[[1.5, 2.5, -3], [1.5, 2.5, -3]],
			[[true, false], [true, false]],
		];
	}


	/**
	 * Provider for test_set_default()
	 *
	 * @return array
	 */
	public function provider_set_default() : array
	{
		return [
			['example', 'example'],
			['example.com', 'example.com'],
			['test@example.com', 'test@example.com'],
			[1, 1],
			[0.5, 0.5],
			[false, false],
			['', ''],
		];
	}


	/**
	 * Provider for test_set_value()
	 *
	 * @return array
	 */
	public function provider_set_value() : array
	{
		return [
			['example', 'example'],
			['example.com', 'example.com'],
			['test@example.com', 'test@example.com'],
			[1, 1],
			[0.5, 0.5],
			[false, false],
			['', ''],
		];
	}


	/**
	 * Provider for test_set_control()
	 *
	 * @return array
	 */
	public function provider_set_control() : array
	{
		$control = new Control();

		return [
			[$control, $control],
		];
	}
}
