<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit;

use \WP_Mock as Mock;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Unit tests for \SV_WC_Payment_Gateway_Apple_Pay_API_Response
 *
 * @since 4.7.0
 */
class Payment_Gateway_Apple_Pay_API_Response extends Test_Case {


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_status_code()
	 *
	 * @since 4.7.0
	 */
	public function test_get_status_code() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_API_Response( $this->get_error_response_data() );

		$this->assertEquals( '123', $response->get_status_code() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_status_code()
	 *
	 * @since 4.7.0
	 */
	public function test_get_status_code_blank() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_API_Response( '' );

		$this->assertNull( $response->get_status_code() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_status_message()
	 *
	 * @since 4.7.0
	 */
	public function test_get_status_message() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_API_Response( $this->get_error_response_data() );

		$this->assertEquals( 'Error', $response->get_status_message() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_status_message()
	 *
	 * @since 4.7.0
	 */
	public function test_get_status_message_blank() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_API_Response( '' );

		$this->assertNull( $response->get_status_message() );
	}


	/**
	 * Test for \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_merchant_session()
	 *
	 * @since 4.7.0
	 */
	public function test_get_merchant_session() {

		$data = json_encode( array( 'response' ) );

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_API_Response( $data );

		$this->assertEquals( $data, $response->get_merchant_session() );
	}


	/**
	 * Test for blank \SV_WC_Payment_Gateway_Apple_Pay_Payment_Response::get_merchant_session()
	 *
	 * @since 4.7.0
	 */
	public function test_get_merchant_session_blank() {

		$response = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_API_Response( '' );

		$this->assertEquals( '', $response->get_merchant_session() );
	}


	/**
	 * Gets an example error response.
	 *
	 * @since 4.7.0
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
