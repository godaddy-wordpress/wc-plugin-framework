<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Helpers;

use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Handlers\Country_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;

class CountryHelperTest extends TestCase
{
	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Handlers\Country_Helper::convert_alpha_country_code()
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
