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
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::delete_token()
	 */
	public function test_delete_token() {

		// store a test token
		$framework_token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', [
			'type' => 'credit_card',
			'last_four' => '1111',
			'exp_month' => '01',
			'exp_year'  => '20',
			'card_type' => 'visa',
		] );

		$this->get_handler()->update_tokens( 1, [ $framework_token->get_id() => $framework_token ] );

		// prepare a mock token with the same ID of the test token
		$token = \Codeception\Stub::make(
			Framework\SV_WC_Payment_Gateway_Payment_Token::class,
			[
				'get_id' => $framework_token->get_id(),
				'delete' => \Codeception\Stub\Expected::once(),
			],
			$this
		);

		// test that the token's delete method is called
		$this->get_handler()->delete_token( 1, $token );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::update_tokens()
	 */
	public function test_update_tokens() {

		$tokens = [
			new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', [
				'type' => 'credit_card',
				'last_four' => '1111',
				'exp_month' => '01',
				'exp_year'  => '20',
				'card_type' => 'visa',
			] )
		];

		$this->get_handler()->update_tokens( 1, $tokens );

		$this->assertIsArray( \WC_Payment_Tokens::get_customer_tokens( 1, 'test_gateway' ) );
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
