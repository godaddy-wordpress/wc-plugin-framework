<?php

define( 'ABSPATH', true );

class CountryHelperTest extends \Codeception\Test\Unit {

	/**
	* @var \UnitTester
	*/
	protected $tester;

	protected function _before() {

		require_once( 'woocommerce/Country_Helper.php' );
	}

	protected function _after() {

	}

	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Country_Helper::convert_alpha_country_code()
	 *
	 * @param string $code input country code
	 * @param string $expected expected return value
	 *
	 * @dataProvider provider_convert_alpha_country_code
	 */
	public function test_convert_alpha_country_code( $code, $expected ) {

		$result = \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Country_Helper::convert_alpha_country_code( $code );

		$this->assertEquals( $expected, $result );
	}


	/**
	 * Provider for test_convert_alpha_country_code()
	 *
	 * @return array
	 */
	public function provider_convert_alpha_country_code() {

		return [
			[ 'US', 'USA' ],
			[ 'USA', 'US' ],
			[ 'UNKNOWN', 'UNKNOWN' ],
		];
	}


}
