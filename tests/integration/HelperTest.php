<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * Tests for the helper class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Compatibility
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


}
