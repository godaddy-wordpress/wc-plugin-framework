<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit;

use \WP_Mock as Mock;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Unit tests for \SV_WC_Payment_Gateway_API_Response_Message_Helper
 *
 * @since 4.5.0
 */
class Payment_Gateway_API_Response_Message_Helper extends Test_Case {


	/**
	 * Tests for \SV_WC_Payment_Gateway_API_Response_Message_Helper::get_user_messages()
	 *
	 * @since 4.5.0
	 * @dataProvider provider_test_get_user_messages
	 */
	public function test_get_user_messages( $message_ids, $expected ) {

		$helper = new PluginFramework\SV_WC_Payment_Gateway_API_Response_Message_Helper;

		Mock::wpPassthruFunction( 'esc_html__' );

		$this->assertEquals( $expected, $helper->get_user_messages( $message_ids ) );
	}


	/**
	 * Data provider for test_get_user_messages()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test_get_user_messages() {

		return [
			[ array( 'error' ), 'An error occurred, please try again or try an alternate form of payment' ], // single known message
			[ array( 'unkown' ), null ], // unknown message
			[ array( 'error', 'card_number_missing' ), 'An error occurred, please try again or try an alternate form of payment Please enter your card number and try again.' ], // test concat
			[ array( 'card_number_missing', 'unkown' ), 'Please enter your card number and try again.' ], // test concat with one unknown
		];
	}


	/**
	 * Tests for \SV_WC_Payment_Gateway_API_Response_Message_Helper::get_user_message()
	 *
	 * @since 4.5.0
	 * @dataProvider provider_test_get_user_message
	 */
	public function test_get_user_message( $message_id, $expected ) {

		$helper = new PluginFramework\SV_WC_Payment_Gateway_API_Response_Message_Helper;

		Mock::wpPassthruFunction( 'esc_html__' );

		$this->assertEquals( $expected, $helper->get_user_message( $message_id ) );
	}


	/**
	 * Data provider for test_get_user_message()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test_get_user_message() {

		return [
			[ 'error', 'An error occurred, please try again or try an alternate form of payment' ], // known message
			[ 'unkown', null ], // unknown message
		];
	}


	// TODO: Test the `wc_payment_gateway_transaction_response_user_message` filter once \WP_Mock::onFilter() is sorted {CW 2016-10-21}


}
