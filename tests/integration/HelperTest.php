<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

/**
 * Tests for the helper class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_11_0\SV_WC_Plugin_Compatibility
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
	 * Tests that can convert an array of IDs to a comma separated list.
	 *
	 * @dataProvider provider_get_escaped_string_list
	 *
	 * @param array $strings
	 * @param string $expected
	 */
	public function can_get_escaped_string_list( array $strings, string $expected ) {

		$this->assertSame( $expected, Framework\SV_WC_Helper::get_escaped_string_list( $strings ) );
	}


	/** @see can_get_escaped_string_list **/
	public function provider_get_escaped_string_list() : array
	{

		return [
			'Strings' => [['foo', 'bar', 'baz'], "'foo', 'bar', 'baz'"],
			'Mixed content' => [['foo', 0, 1, '2', -3, '-4.5'], "'foo', '0', '1', '2', '-3', '-4.5'"],
		];
	}


	/**
	 * Tests that can convert an array of IDs to a comma separated list.
	 *
	 * @dataProvider can_get_escaped_id_list
	 *
	 * @param array $ids
	 * @param string $expected
	 */
	public function can_get_escaped_id_list( array $ids, string $expected ) {

		$this->assertSame( $expected, Framework\SV_WC_Helper::get_escaped_id_list( $ids ) );
	}


	/** @see can_get_escaped_id_list **/
	public function provider_get_escaped_id_list() : array {

		return [
			'Non-numbers'                => [[null, false, true, 'test'], '0,1'],
			'Integers'                   => [[1, 2, 3], '1,2,3'],
			'Negative integers'          => [[-1, -2, -3], '-1,-2,-3'],
			'Numerical strings'          => [['1', '2', '3'], '1,2,3'],
			'Negative numerical strings' => [['-1', '-2', '-3'], '-1,-2,-3'],
		];
	}


	/**
	 * Tests {@see Framework\SV_WC_Helper::format_percentage()}.
	 *
	 * @dataProvider provider_format_percentage
	 *
	 * @param float|int|string $number the number to format as percentage
	 * @param string $expected result
	 * @param int|bool|null $decimal_points optional, the number of decimal points to use
	 */
	public function test_format_percentage( $number, string $expected, $decimal_points = false ) {

		$this->assertSame( $expected, Framework\SV_WC_Helper::format_percentage( $number, $decimal_points ) );
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
			[ 1.333333, '133.3333%' ],
			[ 1.333333, '133.33%', 2 ],
			[ 1.333333, '133%', null ],
		];
	}


}
