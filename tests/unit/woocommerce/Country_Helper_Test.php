<?php

use \SkyVerge\WooCommerce\PluginFramework\v5_5_1 as Framework;

define( 'ABSPATH', true );

/** @see Framework\Country_Helper */
class Country_Helper_Test extends \Codeception\Test\Unit {


	/** @var \UnitTester */
	protected $tester;


	protected function _before() {

		require_once( 'woocommerce/Country_Helper.php' );
	}


	protected function _after() {

	}


	/**
	 * @see Framework\Country_Helper::convert_alpha_country_code()
	 *
	 * @param string $code input country code
	 * @param string $expected expected return value
	 *
	 * @dataProvider provider_convert_alpha_country_code
	 */
	public function test_convert_alpha_country_code( $code, $expected ) {

		$result = Framework\Country_Helper::convert_alpha_country_code( $code );

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