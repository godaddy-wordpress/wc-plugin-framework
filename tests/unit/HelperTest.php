<?php

define( 'ABSPATH', true );

class HelperTest extends \Codeception\Test\Unit {

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	protected function _before() {

		require_once( 'woocommerce/class-sv-wc-helper.php' );
	}

	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_8\SV_WC_Helper::get_placeholder_list()
	 *
	 * @param array $array values to generate list from
	 * @param string $expected expected return value
	 * @param string $placeholder placeholder to be used in list
	 *
	 * @dataProvider provider_can_get_placeholder_list
	 */
	public function test_can_get_placeholder_list( array $array, string $expected, string $placeholder ) {

		$this->assertEquals( $expected, \SkyVerge\WooCommerce\PluginFramework\v5_10_8\SV_WC_Helper::get_placeholder_list( $array, $placeholder ) );
	}


	/**
	 * Provider for test_can_get_placeholder_list()
	 *
	 * @return array
	 */
	public function provider_can_get_placeholder_list() : array {
		return [
			'Floating point values' => [ [1.1, 2.1], '%f, %f', '%f' ],
			'Integer values' => [ [1, 2], '%d, %d', '%d' ],
			'String values' => [ ['yes', 'no'], '%s, %s', '%s' ],
		];
	}


}
