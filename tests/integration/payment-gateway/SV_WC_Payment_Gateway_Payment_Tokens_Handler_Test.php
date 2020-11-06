<?php

use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * Tests for the payment tokens handler object
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Payment_Tokens_Handler
 */
class SV_WC_Payment_Gateway_Payment_Tokens_Handler_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	protected function _before() {


	}


	protected function _after() {


	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::add_token()
	 */
	public function test_add_token() {

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $this->get_legacy_token_data() );

		$token_id = $this->get_handler()->add_token( 1, $token );

		$core_token = \WC_Payment_Tokens::get( $token_id );

		$this->assertInstanceOf( \WC_Payment_Token::class, $core_token );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::add_token()
	 */
	public function test_add_token_set_default() {

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $this->get_legacy_token_data() );
		$token->set_default( true );

		$this->get_handler()->add_token( 1, $token );

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '67890', $this->get_legacy_token_data() );
		$token->set_default( true );

		$this->get_handler()->add_token( 1, $token );

		$token = $this->get_handler()->get_token( 1, '12345' );

		$this->assertFalse( $token->is_default() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::update_token()
	 */
	public function test_update_token() {

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $this->get_legacy_token_data() );

		$core_token_id = $token->save();

		$token->set_exp_month( '02' );

		$this->get_handler()->update_token( 1, $token );

		$core_token = \WC_Payment_Tokens::get( $core_token_id );

		$this->assertEquals( '02', $core_token->get_expiry_month() );
	}


	/**
	 * Tests that the local cache is updated when a token is updated.
	 *
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::update_token()
	 */
	public function test_update_token_cache() {

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $this->get_legacy_token_data() );

		// store the token initially
		$this->get_handler()->update_token( 1, $token );

		// prime the cache
		$this->get_handler()->get_tokens( 1 );

		$token->set_exp_month( '02' );

		$this->get_handler()->update_token( 1, $token );

		$token = $this->get_handler()->get_token( 1, $token->get_id() );

		$this->assertEquals( '02', $token->get_exp_month() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::delete_token()
	 */
	public function test_delete_token() {

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $this->get_legacy_token_data() );

		$token->set_user_id( 1 );
		$token->set_gateway_id( 'test_gateway' );

		$token_id = $token->save();

		$this->get_handler()->delete_token( 1, $token );

		$this->assertNull( \WC_Payment_Tokens::get( $token_id ) );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::delete_token()
	 */
	public function test_delete_token_marks_another_token_as_default() {

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
	 * Ensures legacy token data is deleted when a core token is deleted.
	 *
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::delete_token()
	 */
	public function test_delete_token_legacy() {

		$token_data = $this->get_legacy_token_data();

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $token_data );

		// store in user meta (legacy)
		$this->get_handler()->update_legacy_token( 1, $token );

		// store in core
		$token->set_user_id( 1 );
		$token->set_gateway_id( 'test_gateway' );
		$token->save();

		// delete from core
		$this->get_handler()->delete_token( 1, $token );

		$this->assertEmpty( $this->get_handler()->get_legacy_tokens( 1 ) );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler::get_tokens()
	 *
	 * @dataProvider provider_get_tokens
	 */
	public function test_get_tokens_retrieves_core_tokens( $token_environment, $requested_environment, array $found_tokens_ids ) {

		// store a test core token
		$core_token = new WC_Payment_Token_CC();
		$user_id    = 1;

		$core_token->set_user_id( $user_id );
		$core_token->set_gateway_id( sv_wc_test_plugin()->get_gateway()->get_id() );
		$core_token->set_token( '12345' );
		$core_token->set_last4( '1111' );
		$core_token->set_expiry_year( '2022' );
		$core_token->set_expiry_month( '08' );
		$core_token->set_card_type( Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA );

		$core_token->add_meta_data( 'environment', $token_environment );

		$core_token->save();

		// use a new instance of the payment tokens handler to bypass the handler's internal cache
		$handler = new Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler( sv_wc_test_plugin()->get_gateway() );

		$tokens = $handler->get_tokens( $user_id, [ 'environment_id' => $requested_environment ] );

		$this->assertEquals( array_keys( $tokens ), $found_tokens_ids );

		/** test that found tokens are instances of Framework\SV_WC_Payment_Gateway_Payment_Token */
		foreach ( $found_tokens_ids as $found_token_id ) {
			$this->assertInstanceOf( Framework\SV_WC_Payment_Gateway_Payment_Token::class, $tokens[ $found_token_id ] );
		}
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
