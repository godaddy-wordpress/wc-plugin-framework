<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit;

use \WP_Mock as Mock;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Unit tests for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response
 *
 * @since 4.7.0
 */
class Payment_Gateway_Apple_Pay_Payment_Response extends Test_Case {


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_payment_data()
	 *
	 * @since 4.7.0
	 */
	public function test_get_payment_data() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $this->get_valid_response_data() );

		$this->assertEquals( array( 'This is the payment data.' ), $response->get_payment_data() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_payment_data()
	 *
	 * @since 4.7.0
	 */
	public function test_get_payment_data_blank() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( '' );

		$this->assertEquals( array(), $response->get_payment_data() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_transaction_id()
	 *
	 * @since 4.7.0
	 */
	public function test_get_transaction_id() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $this->get_valid_response_data() );

		$this->assertEquals( '12345', $response->get_transaction_id() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_transaction_id()
	 *
	 * @since 4.7.0
	 */
	public function test_get_transaction_id_blank() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( '' );

		$this->assertEquals( '', $response->get_transaction_id() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_card_type()
	 *
	 * @since 4.7.0
	 */
	public function test_get_card_type() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $this->get_valid_response_data() );

		$this->assertEquals( 'visa', $response->get_card_type() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_card_type()
	 *
	 * @since 4.7.0
	 */
	public function test_get_card_type_blank() {

		Mock::wpFunction( 'wp_list_pluck', array(
			'return' => array(
				'visa' => array( 'Visa' ),
			),
		) );

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( '' );

		$this->assertEquals( 'card', $response->get_card_type() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_last_four()
	 *
	 * @since 4.7.0
	 */
	public function test_get_last_four() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $this->get_valid_response_data() );

		$this->assertEquals( '1234', $response->get_last_four() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_last_four()
	 *
	 * @since 4.7.0
	 */
	public function test_get_last_four_blank() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( '' );

		$this->assertEquals( '', $response->get_last_four() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_billing_address()
	 *
	 * @since 4.7.0
	 */
	public function test_get_billing_address() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $this->get_valid_response_data() );

		$expected = array(
			'first_name' => 'Lloyd',
			'last_name'  => 'Christmas',
			'address_1'  => '333 E Wonderview Ave.',
			'address_2'  => 'Room 217',
			'city'       => 'Estes Park',
			'state'      => 'CO',
			'postcode'   => '80517',
			'country'    => 'US',
			'email'      => 'lloyd@igotworms.com',
			'phone'      => '(123) 555-1234',
		);

		$this->assertEquals( $expected, $response->get_billing_address() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_billing_address()
	 *
	 * @since 4.7.0
	 */
	public function test_get_billing_address_blank() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( '' );

		$expected = array(
			'first_name' => '',
			'last_name'  => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		);

		$this->assertEquals( $expected, $response->get_billing_address() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_shipping_address()
	 *
	 * @since 4.7.0
	 */
	public function test_get_shipping_address() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $this->get_valid_response_data() );

		$expected = array(
			'first_name' => 'Mary',
			'last_name'  => 'Swanson',
			'address_1'  => '2250 Deer Valley Drive',
			'address_2'  => '',
			'city'       => 'Aspen',
			'state'      => 'CO',
			'postcode'   => '81611',
			'country'    => 'US',
		);

		$this->assertEquals( $expected, $response->get_shipping_address() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_shipping_address()
	 *
	 * @since 4.7.0
	 */
	public function test_get_shipping_address_blank() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( '' );

		$expected = array(
			'first_name' => '',
			'last_name'  => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		);

		$this->assertEquals( $expected, $response->get_shipping_address() );
	}


	private function get_valid_response_data() {

		$data = array(
			'token' => array(
				'paymentData'           => array( 'This is the payment data.' ),
				'transactionIdentifier' => '12345',
				'paymentMethod'         => array(
					'network'     => 'Visa',
					'displayName' => 'Visa 1234',
				),
			),
			'billingContact' => array(
				'givenName'    => 'Lloyd',
				'familyName'   => 'Christmas',
				'addressLines' => array(
					'333 E Wonderview Ave.',
					'Room 217',
				),
				'locality'           => 'Estes Park',
				'administrativeArea' => 'CO',
				'postalCode'         => '80517',
				'countryCode'        => 'us', // Apple returns this as lowercase
			),
			'shippingContact' => array(
				'givenName'    => 'Mary',
				'familyName'   => 'Swanson',
				'addressLines' => array(
					'2250 Deer Valley Drive',
				),
				'locality'           => 'Aspen',
				'administrativeArea' => 'CO',
				'postalCode'         => '81611',
				'countryCode'        => 'us',
				'emailAddress'       => 'lloyd@igotworms.com', // The shipping address contains the email & phone
				'phoneNumber'        => '(123) 555-1234',      // The tests ensure they end up with the billing address as per WC standards
			),
		);

		return json_encode( $data );
	}


}
