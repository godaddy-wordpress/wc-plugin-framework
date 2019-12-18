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
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::delete_token()
	 */
	public function test_delete_token_marks_another_token_as_deafult() {

		$token_id         = '12345';
		$default_token_id = '45678';

		// store test tokens
		$tokens = [
			'12345' => new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', [
				'type' => 'credit_card',
				'last_four' => '1111',
				'exp_month' => '01',
				'exp_year'  => '20',
				'card_type' => 'visa',
				'default'   => true,
			] ),
			'45678' => new Framework\SV_WC_Payment_Gateway_Payment_Token( '45678', [
				'type' => 'credit_card',
				'last_four' => '2222',
				'exp_month' => '01',
				'exp_year'  => '20',
				'card_type' => 'visa',
			] ),
		];

		$this->get_handler()->update_tokens( 1, $tokens );

		$this->get_handler()->delete_token( 1, $this->get_handler()->get_token( 1, $token_id ) );

		$this->assertNull( $this->get_handler()->get_token( 1, $token_id ) );

		$default_token = $this->get_handler()->get_token( 1, $default_token_id );

		$this->assertInstanceOf( Framework\SV_WC_Payment_Gateway_Payment_Token::class, $default_token );
		$this->assertTrue( $default_token->is_default() );

		$core_token = $default_token->get_woocommerce_payment_token();

		$this->assertTrue( $core_token->get_is_default() );
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
