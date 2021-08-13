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

	protected function _after() {

	}

	/**
	 * Tests \SkyVerge\WooCommerce\PluginFramework\v5_10_8\SV_WC_Helper::get_placeholder_list()
	 *
	 * @param array $array input country code
	 * @param string $expected expected return value
	 *
	 * @dataProvider provider_can_get_placeholder_list
	 */
	public function test_can_get_placeholder_list( array $array, $expected, $placeholder ) {

		$result = \SkyVerge\WooCommerce\PluginFramework\v5_10_8\SV_WC_Helper::get_placeholder_list( $array, $placeholder );
		$this->assertEquals( $expected, $result );
	}


	/**
	 * Provider for test_can_get_placeholder_list()
	 *
	 * @return array
	 */
	public function provider_can_get_placeholder_list() {

		return [
			'Floating point values' => [ [1.1, 2.1], '%f, %f', '%f' ],
			'Integer values' => [ [1, 2], '%d, %d', '%d' ],
			'String values' => [ ['yes', 'no'], '%s, %s', '%s' ],
		];
	}


}
