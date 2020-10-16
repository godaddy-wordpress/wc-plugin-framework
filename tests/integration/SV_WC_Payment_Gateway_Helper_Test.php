<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Helper;

/**
 * Tests for the Payment Gateway Helper class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Helper
 */
class SV_WC_Payment_Gateway_Helper_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Tests *********************************************************************************************************/


	/**
	 * @see SV_WC_Payment_Gateway_Helper::format_exp_year()
	 *
	 * @dataProvider provider_format_exp_year
	 */
	public function test_format_exp_year( $exp_year, $expected_result ) {

		$this->assertEquals( $expected_result, SV_WC_Payment_Gateway_Helper::format_exp_year( $exp_year ) );
	}


	public function provider_format_exp_year() {

		return [
			[ '2022', '22' ],
			[ '20',   '20' ],
		];
	}


}
