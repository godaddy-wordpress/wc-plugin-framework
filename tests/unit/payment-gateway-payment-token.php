<?php

namespace SkyVerge\WC_Plugin_Framework\Unit_Tests;

use \WP_Mock as Mock;

/**
 * Unit tests for \SV_WC_Payment_Gateway_Payment_Token
 *
 * @since 4.5.0
 */
class Payment_Gateway_Payment_Token extends Test_Case {


	/**
	 * Tests for \SV_WC_Payment_Gateway_Helper::__construct()
	 *
	 * Simply tests that the token ID and data are set.
	 *
	 * @since 4.5.0
	 */
	public function test__construct() {

		$data = array(
			'type' => 'credit_card',
		);

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', $data );

		$this->assertEquals( 'mock-token',  $token->get_id() );
		$this->assertEquals( $data, $token->to_datastore_format() );
	}


	/**
	 * Tests for \SV_WC_Payment_Gateway_Helper::__construct()
	 *
	 * Tests that the card type is correctly set in all situations.
	 *
	 * @since 4.5.0
	 * @dataProvider provider_test__construct_set_card_type
	 */
	public function test__construct_set_card_type( $data, $expected ) {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', $data );

		$this->assertEquals( $expected, $token->get_card_type() );
	}


	/**
	 * Data provider for test__construct_set_card_type()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test__construct_set_card_type() {

		return [
			[ array( 'type' => 'not_credit_card' ),                                  null ],   // non-credit card type
			[ array( 'type' => 'credit_card'     ),                                  null ],   // credit card, but no card info
			[ array( 'type' => 'credit_card', 'account_number' => '4222222222222' ), 'visa' ], // set card type by card number
			[ array( 'type' => 'credit_card', 'card_type'      => 'VISA' ),          'visa' ], // be sure card type is normalized
			[ array(),                                                               null ],   // type not set
		];
	}


	/**
	 * Tests \SV_WC_Payment_Gateway_Helper::set_default()
	 *
	 * Also provides coverage for \SV_WC_Payment_Gateway_Helper::is_default()
	 *
	 * @since 4.5.0
	 */
	public function test_set_default() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array() );

		$this->assertFalse( $token->is_default() );

		$token->set_default( true );

		$this->assertTrue( $token->is_default() );
	}


	/**
	 * Tests true for \SV_WC_Payment_Gateway_Helper::is_credit_card()
	 *
	 * @since 4.5.0
	 */
	public function test_is_credit_card_true() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array(
			'type' => 'credit_card',
		) );

		$this->assertTrue( $token->is_credit_card() );
	}


	/**
	 * Tests false for \SV_WC_Payment_Gateway_Helper::is_credit_card()
	 *
	 * @since 4.5.0
	 */
	public function test_is_credit_card_false() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array(
			'type' => 'check',
		) );

		$this->assertFalse( $token->is_credit_card() );
	}


	/**
	 * Tests true for \SV_WC_Payment_Gateway_Helper::is_echeck()
	 *
	 * @since 4.5.0
	 */
	public function test_is_echeck_true() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array(
			'type' => 'check',
		) );

		$this->assertTrue( $token->is_echeck() );
	}


	/**
	 * Tests false for \SV_WC_Payment_Gateway_Helper::is_check()
	 *
	 * @since 4.5.0
	 */
	public function test_is_echeck_false() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array(
			'type' => 'credit_card',
		) );

		$this->assertFalse( $token->is_echeck() );
	}


	/**
	 * Tests \SV_WC_Payment_Gateway_Helper::set_card_type()
	 *
	 * Also provides coverage for \SV_WC_Payment_Gateway_Helper::get_account_type()
	 *
	 * @since 4.5.0
	 */
	public function test_set_card_type() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array() );

		$this->assertNull( $token->get_card_type() );

		$token->set_card_type( 'visa' );

		$this->assertEquals( 'visa', $token->get_card_type() );
	}


	/**
	 * Tests \SV_WC_Payment_Gateway_Helper::set_account_type()
	 *
	 * Also provides coverage for \SV_WC_Payment_Gateway_Helper::get_account_type()
	 *
	 * @since 4.5.0
	 */
	public function test_set_account_type() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array() );

		$this->assertNull( $token->get_account_type() );

		$token->set_account_type( 'checking' );

		$this->assertEquals( 'checking', $token->get_account_type() );
	}


	/**
	 * Tests for \SV_WC_Payment_Gateway_Helper::get_type_full()
	 *
	 * @since 4.5.0
	 * @dataProvider provider_test_get_type_full
	 */
	public function test_get_type_full( $data, $expected ) {

		Mock::wpFunction( 'wp_list_pluck', array(
			'return' => array(),
		) );

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', $data );

		Mock::wpPassthruFunction( 'esc_html__' );
		Mock::wpPassthruFunction( 'esc_html_x' );

		$this->assertEquals( $expected, $token->get_type_full() );
	}


	/**
	 * Data provider for test_get_type_full()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test_get_type_full() {

		return [
			[ array( 'type' => 'credit_card' ), 'Credit / Debit Card' ],
			[ array( 'type' => 'credit_card', 'card_type' => 'visa' ), 'Visa' ],
		];
	}


	/**
	 * Tests \SV_WC_Payment_Gateway_Helper::set_last_four()
	 *
	 * Also provides coverage for \SV_WC_Payment_Gateway_Helper::get_last_four()
	 *
	 * @since 4.5.0
	 */
	public function test_set_last_four() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array() );

		$this->assertNull( $token->get_last_four() );

		$token->set_last_four( '1234' );

		$this->assertEquals( '1234', $token->get_last_four() );
	}


	/**
	 * Tests \SV_WC_Payment_Gateway_Helper::set_exp_month()
	 *
	 * Also provides coverage for \SV_WC_Payment_Gateway_Helper::get_exp_month()
	 *
	 * @since 4.5.0
	 */
	public function test_set_exp_month() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array() );

		$this->assertNull( $token->get_exp_month() );

		$token->set_exp_month( '01' );

		$this->assertEquals( '01', $token->get_exp_month() );
	}


	/**
	 * Tests \SV_WC_Payment_Gateway_Helper::set_exp_year()
	 *
	 * Also provides coverage for \SV_WC_Payment_Gateway_Helper::get_exp_year()
	 *
	 * @since 4.5.0
	 */
	public function test_set_exp_year() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array() );

		$this->assertNull( $token->get_exp_year() );

		$token->set_exp_year( '85' );

		$this->assertEquals( '85', $token->get_exp_year() );
	}


	/**
	 * Tests \SV_WC_Payment_Gateway_Helper::get_exp_date()
	 *
	 * @since 4.5.0
	 */
	public function test_get_exp_date() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array(
			'exp_month' => '01',
			'exp_year'  => '1985',
		) );

		$this->assertEquals( '01/85', $token->get_exp_date() );
	}


	/**
	 * Tests \SV_WC_Payment_Gateway_Helper::set_image_url()
	 *
	 * Also provides coverage for \SV_WC_Payment_Gateway_Helper::get_image_url()
	 *
	 * @since 4.5.0
	 */
	public function test_set_image_url() {

		$token = new \SV_WC_Payment_Gateway_Payment_Token( 'mock-token', array() );

		$token->set_image_url( 'http://example.com/1234.jpg' );

		$this->assertEquals( 'http://example.com/1234.jpg', $token->get_image_url() );
	}


}
