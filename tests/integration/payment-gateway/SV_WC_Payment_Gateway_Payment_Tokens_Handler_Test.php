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

		$token_id = '12345';

		// store a test token
		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( $token_id, [
			'type' => 'credit_card',
			'last_four' => '1111',
			'exp_month' => '01',
			'exp_year'  => '20',
			'card_type' => 'visa',
			'default'   => true,
		] );

		$this->get_handler()->update_tokens( 1, [ $token_id => $token ] );

		$core_token_id = $token->get_woocommerce_payment_token()->get_id();

		$this->get_handler()->delete_token( 1, $token );

		$this->assertNull( $this->get_handler()->get_token( 1, $token_id ) );
		$this->assertNull( \WC_Payment_Tokens::get( $core_token_id ) );
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

		$default_token = $this->get_handler()->get_token( 1, $default_token_id );

		$this->assertTrue( $default_token->is_default() );

		$core_token = $default_token->get_woocommerce_payment_token();

		$this->assertTrue( $core_token->get_is_default() );
	}


	/**
	 * Provides test data for test_get_tokens_retrieves_core_tokens()
	 *
	 * @return array
	 */
	public function provider_get_tokens() {

		$token_id = '12345';

		return [
			'same environment'      => [ 'test_environment_a', 'test_environment_a', [ $token_id ] ],
			'different environment' => [ 'test_environment_a', 'test_environment_b', [] ],
		];
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::update_tokens()
	 */
	public function test_update_tokens() {

		$tokens = [
			new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $this->get_legacy_token_data() )
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


	/** Legacy token tests ********************************************************************************************/


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::get_legacy_tokens()
	 *
	 * @dataProvider provider_legacy_tokens
	 */
	public function test_get_legacy_tokens( $environment_id ) {

		$user_id = 1;

		// add fake user meta
		update_user_meta( $user_id, $this->get_handler()->get_user_meta_name( $environment_id ), [
			'12345' => $this->get_legacy_token_data(),
		] );

		$tokens = $this->get_handler()->get_legacy_tokens( $user_id, $environment_id );

		$this->assertArrayHasKey( '12345', $tokens );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::update_legacy_token()
	 *
	 * @dataProvider provider_legacy_tokens
	 */
	public function test_update_legacy_token( $environment_id ) {

		$user_id = 1;

		// add fake user meta
		update_user_meta( $user_id, $this->get_handler()->get_user_meta_name( $environment_id ), [
			'12345' => $this->get_legacy_token_data(),
		] );

		// change a property of the token
		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $this->get_legacy_token_data() );
		$token->set_card_type( 'mastercard' );

		$this->get_handler()->update_legacy_token( $user_id, $token, $environment_id );

		// get the latest data
		$tokens = $this->get_handler()->get_legacy_tokens( $user_id, $environment_id );

		$token = current( $tokens );

		// check that the token was updated
		$this->assertEquals( 'mastercard', $token->get_card_type() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::delete_legacy_token()
	 *
	 * @dataProvider provider_legacy_tokens
	 */
	public function test_delete_legacy_token( $environment_id ) {

		$user_id = 1;

		// add fake user meta
		update_user_meta( $user_id, $this->get_handler()->get_user_meta_name( $environment_id ), [
			'12345' => $this->get_legacy_token_data(),
		] );

		// get the latest data
		$tokens = $this->get_handler()->get_legacy_tokens( $user_id, $environment_id );

		$token = current( $tokens );

		$this->get_handler()->delete_legacy_token( $user_id, $token, $environment_id );

		// get the latest data
		$tokens = $this->get_handler()->get_legacy_tokens( $user_id, $environment_id );

		// check that the token was updated
		$this->assertArrayNotHasKey( '12345', $tokens );
	}


	/**
	 * Provider for test_get_user_meta_name
	 * @return array
	 */
	public function provider_legacy_tokens() {

		return [
			'production environment'     => [ null ],
			'non-production environment' => [ 'test' ],
		];
	}


	/**
	 * Gets legacy token array data, as it would exist in user meta.
	 *
	 * @return array
	 */
	private function get_legacy_token_data() {

		return [
			'type'      => 'credit_card',
			'last_four' => '1111',
			'exp_month' => '01',
			'exp_year'  => '20',
			'card_type' => 'visa',
			'custom'    => 'custom_value',
		];
	}


	/**
	 * @return Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler
	 */
	private function get_handler() {

		return sv_wc_test_plugin()->get_gateway()->get_payment_tokens_handler();
	}


}
