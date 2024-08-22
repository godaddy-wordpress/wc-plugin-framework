<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\Unit;

use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Country_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\TestCase;

class CountryHelperTest extends TestCase
{
	public function setUp() : void
	{
		parent::setUp();

		require_once PLUGIN_ROOT_DIR.'/woocommerce/Country_Helper.php';
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_1\Country_Helper::convert_alpha_country_code()
	 *
	 * @dataProvider provider_convert_alpha_country_code
	 *
	 * @param string $code input country code
	 * @param string $expected expected return value
	 */
	public function test_convert_alpha_country_code(string $code, string $expected) : void
	{
		$result = Country_Helper::convert_alpha_country_code($code);

		$this->assertEquals($expected, $result);
	}

	/**
	 * Provider for test_convert_alpha_country_code()
	 *
	 * @return array
	 */
	public function provider_convert_alpha_country_code() : array
	{
		return [
			['US', 'USA'],
			['USA', 'US'],
			['UNKNOWN', 'UNKNOWN'],
		];
	}
}
