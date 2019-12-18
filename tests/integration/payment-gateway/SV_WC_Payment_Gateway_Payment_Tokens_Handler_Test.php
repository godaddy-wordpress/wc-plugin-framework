<?php

use \SkyVerge\WooCommerce\PluginFramework\v5_5_1 as Framework;

/**
 * Tests for the payment tokens handler object
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_5_1\SV_WC_Payment_Gateway_Payment_Tokens_Handler
 */
class SV_WC_Payment_Gateway_Payment_Tokens_Handler_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	protected function _before() {


	}


	protected function _after() {


	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::get_user_meta_name()
	 *
	 * @dataProvider provider_get_user_meta_name
	 */
	public function test_get_user_meta_name( $expected, $environment_id ) {

		$this->assertEquals( $expected, $this->get_handler()->get_user_meta_name( $environment_id ) );
	}


	/**
	 * Provider for test_get_user_meta_name
	 * @return array
	 */
	public function provider_get_user_meta_name() {

		return [
			[ '_wc_test_gateway_payment_tokens', null ],
			[ '_wc_test_gateway_payment_tokens_test', 'test' ],
		];
	}


	/**
	 * @return Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler
	 */
	private function get_handler() {

		return sv_wc_test_plugin()->get_gateway()->get_payment_tokens_handler();
	}


}
