<?php

namespace SkyVerge\WC_Plugin_Framework\Unit_Tests;

use \WP_Mock as Mock;

/**
 * Unit tests for \SV_WC_Payment_Gateway_Apple_Pay_API_Response
 *
 * @since 4.7.0-dev
 */
class Payment_Gateway_Apple_Pay_API_Response extends Test_Case {


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_status_code()
	 *
	 * @since 4.7.0-dev
	 */
	public function test_get_status_code() {

		$response = new \SV_WC_Payment_Gateway_Apple_Pay_API_Response( $this->get_error_response_data() );

		$this->assertEquals( '123', $response->get_status_code() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_status_code()
	 *
	 * @since 4.7.0-dev
	 */
	public function test_get_status_code_blank() {

		$response = new \SV_WC_Payment_Gateway_Apple_Pay_API_Response( '' );

		$this->assertNull( $response->get_status_code() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_status_message()
	 *
	 * @since 4.7.0-dev
	 */
	public function test_get_status_message() {

		$response = new \SV_WC_Payment_Gateway_Apple_Pay_API_Response( $this->get_error_response_data() );

		$this->assertEquals( 'Error', $response->get_status_message() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_status_message()
	 *
	 * @since 4.7.0-dev
	 */
	public function test_get_status_message_blank() {

		$response = new \SV_WC_Payment_Gateway_Apple_Pay_API_Response( '' );

		$this->assertNull( $response->get_status_message() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_merchant_session()
	 *
	 * @since 4.7.0-dev
	 */
	public function test_get_merchant_session() {

		$data = json_encode( array( 'response' ) );

		$response = new \SV_WC_Payment_Gateway_Apple_Pay_API_Response( $data );

		$this->assertEquals( $data, $response->get_merchant_session() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_merchant_session()
	 *
	 * @since 4.7.0-dev
	 */
	public function test_get_merchant_session_blank() {

		$response = new \SV_WC_Payment_Gateway_Apple_Pay_API_Response( '' );

		$this->assertEquals( '', $response->get_merchant_session() );
	}


	/**
	 * Gets an example error response.
	 *
	 * @since 4.7.0-dev
	 * @return string
	 */
	private function get_error_response_data() {

		$data = array(
			'statusCode'    => '123',
			'statusMessage' => 'Error',
		);

		return json_encode( $data );
	}


}
