<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_9 as Framework;

/**
 * Tests for the helper class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_9\SV_WC_Plugin_Compatibility
 */
class HelperTest extends \Codeception\TestCase\WPTestCase {


	/**
	 * Tests {@see Framework\SV_WC_Helper::is_rest_api_request()}.
	 *
	 * @param string $route endpoint
	 * @param bool $expected result
	 *
	 * @dataProvider provider_is_rest_api_request
	 */
	public function test_is_rest_api_request( $route, $expected ) {

		$_SERVER['REQUEST_URI'] = $route;

		$is_api_request = Framework\SV_WC_Helper::is_rest_api_request();

		$this->assertSame( $is_api_request, $expected );
	}


	/**
	 * Data provider for {@see HelperTest::test_is_rest_api_request()}.
	 *
	 * @return array[]
	 */
	public function provider_is_rest_api_request() {

		return [
			[ '/wp-json/', true ],
			[ '/', false ],
		];
	}


	/**
	 * Tests {@see Framework\SV_WC_Helper::format_percentage()}.
	 *
	 * @param float|int|string $number the number to format as percentage
	 * @param string $expected result
	 *
	 * @dataProvider provider_format_percentage
	 */
	public function test_format_percentage( $number, string $expected ) {

		$this->assertSame( $expected, Framework\SV_WC_Helper::format_percentage( $number ) );
	}


	/**
	 * Data provider for {@see HelperTest::test_format_percentage()}.
	 *
	 * @return array[]
	 */
	public function provider_format_percentage() {

		return [
			[ 0.5, '50%' ],
			[ '0.5', '50%' ],
			[ 0, '0%' ],
			[ 1, '100%' ],
			[ 1.333333, '133.33%' ],
		];
	}
}
