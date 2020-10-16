<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit;

use \WP_Mock as Mock;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Unit tests for \SV_WC_Payment_Gateway_Helper
 *
 * @since 4.5.0
 */
class Payment_Gateway_Helper extends Test_Case {


	/**
	 * Test true for \SV_WC_Payment_Gateway_Helper::luhn_check()
	 *
	 * @since 4.5.0
	 */
	public function test_luhn_check_true() {

		$this->assertTrue( PluginFramework\SV_WC_Payment_Gateway_Helper::luhn_check( '4222222222222' ) );
	}


	/**
	 * Test false for \SV_WC_Payment_Gateway_Helper::luhn_check()
	 *
	 * @since 4.5.0
	 */
	public function test_luhn_check_false() {

		$this->assertFalse( PluginFramework\SV_WC_Payment_Gateway_Helper::luhn_check( '4222222222222222' ) );
	}


	/**
	 * Tests for \SV_WC_Payment_Gateway_Helper::normalize_card_type()
	 *
	 * @since 4.5.0
	 * @dataProvider provider_test_normalize_card_type
	 */
	public function test_normalize_card_type( $card_type, $expected ) {

		Mock::wpPassthruFunction( 'esc_html_x' );

		Mock::wpFunction( 'wp_list_pluck', array(
			'return' => array(
				'mastercard' => array( 'mc' ),
				'amex'       => array( 'americanexpress' ),
			),
		) );

		$this->assertEquals( $expected, PluginFramework\SV_WC_Payment_Gateway_Helper::normalize_card_type( $card_type ) );
	}


	/**
	 * Data provider for test_normalize_card_type()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test_normalize_card_type() {

		return array(
			array( 'visa',            'visa' ),       // nothing to do
			array( 'VISA',            'visa' ),       // captalization
			array( 'mc',              'mastercard' ), // existing variation
			array( 'americanexpress', 'amex' ),
			array( 'space-buck',      'space-buck' ), // unknown card type
		);
	}


	/**
	 * Tests for \SV_WC_Payment_Gateway_Helper::card_type_from_account_number()
	 *
	 * @since 4.5.0
	 * @dataProvider provider_test_card_type_from_account_number
	 */
	public function test_card_type_from_account_number( $account_number, $card_type ) {

		$this->assertEquals( $card_type, PluginFramework\SV_WC_Payment_Gateway_Helper::card_type_from_account_number( $account_number ) );
	}


	/**
	 * Data provider for test_card_type_from_account_number()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test_card_type_from_account_number() {

		return array(
			array( '4222222222222',    'visa' ),
			array( '5555555555554444', 'mastercard' ),
			array( '2223000048400011', 'mastercard' ),
			array( '371449635398431',  'amex' ),
			array( '6011111111111117', 'discover' ),
			array( '38520000023237',   'dinersclub' ),
			array( '3566002020360505', 'jcb' ),
			array( '6759649826438453', 'maestro' ),
			array( '1234567890123456', null ), // unknown type
		);
	}


	/**
	 * Tests for \SV_WC_Payment_Gateway_Helper::payment_type_to_name()
	 *
	 * @since 4.5.0
	 * @dataProvider provider_test_payment_type_to_name
	 */
	public function test_payment_type_to_name( $type, $expected ) {

		Mock::wpPassthruFunction( 'esc_html__' );
		Mock::wpPassthruFunction( 'esc_html_x' );

		Mock::wpFunction( 'wp_list_pluck', array(
			'return_in_order' => array(
				array(
					'mastercard' => array( 'mc' ),
				),
				array(
					'visa'       => 'Visa',
					'mastercard' => 'MasterCard',
				),
			),
		) );

		$this->assertEquals( $expected, PluginFramework\SV_WC_Payment_Gateway_Helper::payment_type_to_name( $type ) );
	}


	/**
	 * Data provider for test_payment_type_to_name()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test_payment_type_to_name() {

		return array(
			array( 'visa',       'Visa' ),
			array( 'VISA',       'Visa' ),
			array( 'mc',         'MasterCard' ),
			array( 'mc',         'MasterCard' ),
			array( '',           'Account' ),
			array( 'space-buck', 'Space Buck' ),
		);
	}


	/**
	 * Tests the returned array structure of \SV_WC_Payment_Gateway_Helper::get_card_types()
	 *
	 * @since 4.5.0
	 */
	public function test_get_card_types_structure() {

		foreach ( PluginFramework\SV_WC_Payment_Gateway_Helper::get_card_types() as $card_type ) {
			$this->assertArrayHasKey( 'name',       $card_type );
			$this->assertArrayHasKey( 'variations', $card_type );
		}
	}


	/**
	 * Tests that \SV_WC_Payment_Gateway_Helper::get_card_types() contains the known types.
	 *
	 * @since 4.5.0
	 * @dataProvider provider_test_get_card_types_roll_call
	 */
	public function test_get_card_types_roll_call( $card_type ) {

		$this->assertArrayHasKey( $card_type, PluginFramework\SV_WC_Payment_Gateway_Helper::get_card_types() );
	}


	/**
	 * Data provider for test_get_card_types_roll_call()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test_get_card_types_roll_call() {

		return array(
			array( 'visa' ),
			array( 'mastercard' ),
			array( 'amex' ),
			array( 'dinersclub' ),
			array( 'discover' ),
			array( 'jcb' ),
			array( 'cartebleue' ),
			array( 'maestro' ),
			array( 'laser' ),
		);
	}


}
