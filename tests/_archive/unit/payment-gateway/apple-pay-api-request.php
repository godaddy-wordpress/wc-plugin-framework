<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit;

use \WP_Mock as Mock;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Unit tests for \SV_WC_Payment_Gateway_Apple_Pay_API_Request
 *
 * @since 4.7.0
 */
class Payment_Gateway_Apple_Pay_API_Request extends Test_Case {


	/**
	 * Tests for \SV_WC_Payment_Gateway_Apple_Pay_API_Request::set_merchant_data()
	 *
	 * @since 4.7.0
	 * @dataProvider provider_test_set_merchant_data
	 */
	public function test_set_merchant_data( $merchant_id, $domain_name, $display_name, $expected ) {

		$gateway = $this->getMockBuilder( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway' )->getMock();

		$request = new PluginFramework\SV_WC_Payment_Gateway_Apple_Pay_API_Request( $gateway );

		$request->set_merchant_data( $merchant_id, $domain_name, $display_name );

		$this->assertEquals( $expected, $request->to_string() );
	}


	/**
	 * Data provider for test_set_merchant_data()
	 *
	 * @since 4.5.0
	 * @return array
	 */
	public function provider_test_set_merchant_data() {

		return array(
			array(
				'merchant',
				'https://domain.com',
				'Domain',
				json_encode( array(
					'merchantIdentifier' => 'merchant',
					'domainName'         => 'domain.com',
					'displayName'        => 'Domain',
				) ),
			),
			array(
				'merchant',
				'http://domain.com',
				'Domain',
				json_encode( array(
					'merchantIdentifier' => 'merchant',
					'domainName'         => 'domain.com',
					'displayName'        => 'Domain',
				) ),
			),
		);
	}


}
