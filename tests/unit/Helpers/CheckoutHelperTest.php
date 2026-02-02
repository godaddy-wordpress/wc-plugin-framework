<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Tests\unit\Helpers;

use Generator;
use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Helpers\CheckoutHelper;
use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Tests\TestCase;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_0_1\Helpers\CheckoutHelper
 */
final class CheckoutHelperTest extends TestCase
{
	/**
	 * @covers ::isCountryAllowedToOrder
	 * @dataProvider countryCodeProvider
	 */
	public function testCanDetermineIsCountryAllowedToOrder(
		string $countryCode,
		bool $wcCountriesAvailable,
		array $allowedCountries,
		bool $expected
	) {
		$wcMock = Mockery::mock('WooCommerce');

		$countriesMock = Mockery::mock('WC_Countries');
		$wcMock->countries = $wcCountriesAvailable ? $countriesMock : null;

		WP_Mock::userFunction('WC')
			->andReturn($wcMock);

		$countriesMock->allows('get_allowed_countries')
			->andReturn($allowedCountries);

		$this->assertSame($expected, CheckoutHelper::isCountryAllowedToOrder($countryCode));
	}

	/**
	 * @covers ::isCountryAllowedForShipping
	 * @dataProvider countryCodeProvider
	 */
	public function testCanDetermineIsCountryAllowedForShipping(
		string $countryCode,
		bool $wcCountriesAvailable,
		array $allowedCountries,
		bool $expected
	) {
		$wcMock = Mockery::mock('WooCommerce');

		$countriesMock = Mockery::mock('WC_Countries');
		$wcMock->countries = $wcCountriesAvailable ? $countriesMock : null;

		WP_Mock::userFunction('WC')
			->andReturn($wcMock);

		$countriesMock->allows('get_shipping_countries')
			->andReturn($allowedCountries);

		$this->assertSame($expected, CheckoutHelper::isCountryAllowedForShipping($countryCode));
	}

	/**
	 * @see testCanDetermineIsCountryAllowedToOrder
	 */
	public function countryCodeProvider() : Generator
	{
		yield 'empty country code' => [
			'countryCode' => '',
			'wcCountriesAvailable' => false,
			'allowedCountries' => [],
			'expected' => false,
		];

		yield 'WC countries not available' => [
			'countryCode' => 'GB',
			'wcCountriesAvailable' => false,
			'allowedCountries' => [],
			'expected' => true,
		];

		yield 'not on allow list' => [
			'countryCode' => 'GB',
			'wcCountriesAvailable' => true,
			'allowedCountries' => ['US' => 'United States', 'FR' => 'France'],
			'expected' => false,
		];

		yield 'is on allow list' => [
			'countryCode' => 'GB',
			'wcCountriesAvailable' => true,
			'allowedCountries' => ['US' => 'United States', 'FR' => 'France', 'GB' => 'Great Britain'],
			'expected' => true,
		];
	}
}
