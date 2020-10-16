<?php

use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * Tests for the payment token object
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Payment_Token
 */
class SV_WC_Payment_Gateway_Payment_Token_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	protected function _before() {


	}


	protected function _after() {


	}


	/** Tests *********************************************************************************************************/


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::read()
	 *
	 * @dataProvider provider_read_sets_token_type
	 */
	public function test_read_sets_token_type( $core_token, $expected_type ) {

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $core_token );

		$this->assertEquals( $expected_type, $token->get_type() );
		$this->assertTrue( $token->{"is_$expected_type"}() );
	}


	/**
	 * Provides test data for test_read_sets_token_type()
	 */
	public function provider_read_sets_token_type() {

		return [
			'credit_card' => [ new WC_Payment_Token_CC(), 'credit_card' ],
			'echeck'      => [ new WC_Payment_Token_ECheck(), 'echeck' ],
		];
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::read()
	 *
	 * @dataProvider provider_read_sets_core_token_metadata
	 */
	public function test_read_sets_core_token_metadata( $meta_key, $meta_value, $method_name ) {

		$core_token = $this->get_new_woocommerce_credit_card_token();

		$core_token->add_meta_data( $meta_key, $meta_value );

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $core_token );

		$this->assertEquals( $meta_value, $token->{$method_name}() );
	}


	/**
	 * Provides test data for test_read_sets_core_token_metadata()
	 */
	public function provider_read_sets_core_token_metadata() {
		return [
			'nickname'     => [ 'nickname', 'personal card', 'get_nickname' ],
			'billing_hash' => [ 'billing_hash', 'a5df', 'get_billing_hash' ],
			'account_type' => [ 'account_type', 'savings', 'get_account_type' ],
		];
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_id()
	 */
	public function test_get_id() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( '12345', $token->get_id() );

		$token = $this->get_new_credit_card_token( 12345 );

		$this->assertIsString( $token->get_id() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_gateway_id()
	 */
	public function test_get_gateway_id() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( 'test_gateway', $token->get_gateway_id() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_gateway_id()
	 */
	public function test_set_gateway_id() {

		$token = $this->get_new_credit_card_token();

		$token->set_gateway_id( 'another_gateway' );

		$this->assertEquals( 'another_gateway', $token->get_gateway_id() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_user_id()
	 */
	public function test_get_user_id() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( 1, $token->get_user_id() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_user_id()
	 *
	 * @dataProvider provider_set_user_id
	 */
	public function test_set_user_id( $value, $expected ) {

		$token = $this->get_new_credit_card_token();

		$token->set_user_id( $value );

		$this->assertEquals( $expected, $token->get_user_id() );
	}


	/**
	 * Provider for test_set_user_id().
	 *
	 * @return array
	 */
	public function provider_set_user_id() {

		return [
			[ 2, 2 ],
			[ 'invalid', 0 ],
		];
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::is_default()
	 */
	public function test_is_default() {

		$token = $this->get_new_credit_card_token();

		$this->assertTrue( $token->is_default() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_default()
	 */
	public function test_set_default() {

		$token = $this->get_new_credit_card_token();

		$token->set_default( false );

		$this->assertFalse( $token->is_default() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::is_credit_card()
	 */
	public function test_is_credit_card() {

		$token = $this->get_new_credit_card_token();

		$this->assertTrue( $token->is_credit_card() );

		$token = $this->get_new_echeck_token();

		$this->assertFalse( $token->is_credit_card() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::is_echeck()
	 */
	public function test_is_echeck() {

		$token = $this->get_new_echeck_token();

		$this->assertTrue( $token->is_echeck() );

		$token = $this->get_new_credit_card_token();

		$this->assertFalse( $token->is_echeck() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_type()
	 */
	public function test_get_type() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( 'credit_card', $token->get_type() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_card_type()
	 */
	public function test_get_card_type() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA, $token->get_card_type() );

		$token = $this->get_new_echeck_token();

		$this->assertNull( $token->get_card_type() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_card_type()
	 */
	public function test_set_card_type() {

		$token = $this->get_new_credit_card_token();

		$token->set_card_type( Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD );

		$this->assertEquals( Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD, $token->get_card_type() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_account_type()
	 */
	public function test_get_account_type() {

		$token = $this->get_new_echeck_token();

		$this->assertEquals( 'checking', $token->get_account_type() );

		$token = $this->get_new_credit_card_token();

		$this->assertNull( $token->get_account_type() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_account_type()
	 */
	public function test_set_account_type() {

		$token = $this->get_new_echeck_token();

		$token->set_account_type( 'savings' );

		$this->assertEquals( 'savings', $token->get_account_type() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_type_full()
	 */
	public function test_get_type_full() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( 'Visa', $token->get_type_full() );

		$token = $this->get_new_echeck_token();

		$this->assertEquals( 'Checking Account', $token->get_type_full() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_last_four()
	 */
	public function test_get_last_four() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( '1111', $token->get_last_four() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_last_four()
	 */
	public function test_set_last_four() {

		$token = $this->get_new_credit_card_token();

		$token->set_last_four( '4242' );

		$this->assertEquals( '4242', $token->get_last_four() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_exp_month()
	 */
	public function test_get_exp_month() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( '01', $token->get_exp_month() );

		$token = $this->get_new_echeck_token();

		$this->assertNull( $token->get_exp_month() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_exp_month()
	 */
	public function test_set_exp_month() {

		$token = $this->get_new_credit_card_token();

		$token->set_exp_month( '02' );

		$this->assertEquals( '02', $token->get_exp_month() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_exp_year()
	 */
	public function test_get_exp_year() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( '2020', $token->get_exp_year() );

		$token = $this->get_new_echeck_token();

		$this->assertNull( $token->get_exp_year() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_exp_year()
	 */
	public function test_set_exp_year() {

		$token = $this->get_new_credit_card_token();

		$token->set_exp_year( '2021' );

		$this->assertEquals( '2021', $token->get_exp_year() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_exp_date()
	 */
	public function test_get_exp_date() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( '01/20', $token->get_exp_date() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_image_url()
	 */
	public function test_set_image_url() {

		$token = $this->get_new_credit_card_token();

		$token->set_image_url( 'https://example.com/image.png' );

		$this->assertEquals( 'https://example.com/image.png', $token->get_image_url() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_nickname()
	 */
	public function test_get_nickname() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( '', $token->get_nickname() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_nickname()
	 */
	public function test_set_nickname() {

		$token = $this->get_new_credit_card_token();

		$token->set_nickname( 'Work' );

		$this->assertEquals( 'Work', $token->get_nickname() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_billing_hash()
	 */
	public function test_get_billing_hash() {

		$token = $this->get_new_credit_card_token();

		$this->assertEquals( '', $token->get_billing_hash() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_billing_hash()
	 */
	public function test_set_billing_hash() {

		$token = $this->get_new_credit_card_token();

		$token->set_billing_hash( 'asdf' );

		$this->assertEquals( 'asdf', $token->get_billing_hash() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_environment()
	 *
	 * @dataProvider provider_get_environment
	 */
	public function test_get_environment( $stored_environment, $expected_environment ) {

		$woocommerce_token = $this->get_new_woocommerce_credit_card_token();

		$woocommerce_token->add_meta_data( 'environment', $stored_environment );

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', $woocommerce_token );

		$this->assertSame( $expected_environment, $token->get_environment() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::get_environment()
	 *
	 * @dataProvider provider_get_environment
	 */
	public function test_get_environment_set_using_legacy_data( $stored_environment, $expected_environment ) {

		$token = new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', array_merge(
			$this->get_legacy_credit_card_token_data(),
			[ 'environment' => $stored_environment ]
		) );

		$this->assertSame( $expected_environment, $token->get_environment() );
	}


	/**
	 * Provides test data for test_get_environment().
	 *
	 * @return array
	 */
	public function provider_get_environment() {

		return [
			'metadata is set'  => [ 'test_environment', 'test_environment' ],
			'empty metadata'   => [ '', '' ],
			'metadata not set' => [ null, '' ],
		];
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::set_environment()
	 */
	public function test_set_environment() {

		$token = $this->get_new_credit_card_token();

		$token->set_environment( 'test' );

		$this->assertEquals( 'test', $token->get_environment() );
	}


	/**
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Token::is_migrated()
	 */
	public function test_is_migrated() {

		$token = $this->get_new_credit_card_token();

		$token->set_migrated( true );

		$this->assertTrue( $token->is_migrated() );
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Gets a new credit card payment token object.
	 *
	 * @param string|int $token_id a token id (normally a string), will default to "12345"
	 * @return Framework\SV_WC_Payment_Gateway_Payment_Token
	 */
	private function get_new_credit_card_token( $token_id = '12345' ) {

		return new Framework\SV_WC_Payment_Gateway_Payment_Token( $token_id, $this->get_legacy_credit_card_token_data() );
	}


	/**
	 * Gets legacy credit card token array data, as it would exist in user meta.
	 *
	 * @return array
	 */
	private function get_legacy_credit_card_token_data() {

		return [
			'type'       => 'credit_card',
			'user_id'    => 1,
			'gateway_id' => 'test_gateway',
			'default'    => true,
			'last_four'  => '1111',
			'card_type'  => Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA,
			'exp_month'  => '01',
			'exp_year'   => '2020',
		];
	}


	/**
	 * Gets a new eCheck payment token object.
	 *
	 * @return Framework\SV_WC_Payment_Gateway_Payment_Token
	 */
	private function get_new_echeck_token() {

		return new Framework\SV_WC_Payment_Gateway_Payment_Token( '12345', [
			'type'         => 'echeck',
			'user_id'      => 1,
			'gateway_id'   => 'test_gateway',
			'default'      => true,
			'last_four'    => '1111',
			'account_type' => 'checking',
		] );
	}


	/**
	 * Gets a new \WC_Payment_Token_CC object.
	 *
	 * @return \WC_Payment_Token_CC
	 */
	private function get_new_woocommerce_credit_card_token() {

		$token = new WC_Payment_Token_CC();

		$token->set_user_id( 1 );
		$token->set_token( '12345' );
		$token->set_last4( '1111' );
		$token->set_expiry_year( '2022' );
		$token->set_expiry_month( '08' );
		$token->set_card_type( Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA );

		/** necessary so that \WC_Data::get_data() returns the props set above */
		$token->save();

		return $token;
	}


}
