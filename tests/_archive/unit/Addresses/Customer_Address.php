<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit\Addresses;

use SkyVerge\WooCommerce\PluginFramework\Tests\Unit;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Unit tests for PluginFramework\Addresses\Address
 */
class Customer_Address extends Address {


	public function setUp() {

		parent::setUp();

		// define WC as 3.0 to use order getter methods
		if ( ! defined( 'WC_VERSION' ) ) {
			define( 'WC_VERSION', '3.0' );
		}
	}


	/** Getter Methods ************************************************************************************************/


	/**
	 * Tests the get_first_name() method.
	 */
	public function test_get_first_name() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_first_name() );
	}


	/**
	 * Tests the get_last_name() method.
	 */
	public function test_get_last_name() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_last_name() );
	}


	/** Setter Methods ************************************************************************************************/


	/**
	 * Tests the set_first_name() method.
	 */
	public function test_set_first_name() {

		$address = $this->get_address();

		$address->set_first_name( 'Herman' );

		$this->assertEquals( 'Herman', $address->get_first_name() );
	}


	/**
	 * Tests the set_last_name() method.
	 */
	public function test_set_last_name() {

		$address = $this->get_address();

		$address->set_last_name( 'Munster' );

		$this->assertEquals( 'Munster', $address->get_last_name() );
	}


	/**
	 * Tests the set_from_order() method.
	 *
	 * Uses the order billing address.
	 */
	public function test_set_from_order_billing() {

		$address = $this->get_address();

		$address->set_from_order( $this->get_order() );

		$this->assertEquals( 'Herman', $address->get_first_name() );
		$this->assertEquals( 'Munster', $address->get_last_name() );
		$this->assertEquals( '1313 Mockingbird Lane', $address->get_line_1() );
		$this->assertEquals( 'Suite 0', $address->get_line_2() );
		$this->assertEquals( '', $address->get_line_3() ); // WC Orders have no line 3
		$this->assertEquals( 'Mockingbird Heights', $address->get_locality() );
		$this->assertEquals( 'CA', $address->get_region() );
		$this->assertEquals( 'USA', $address->get_country() );
		$this->assertEquals( '90000', $address->get_postcode() );
	}


	/**
	 * Tests the set_from_order() method.
	 *
	 * Uses the order shipping address.
	 */
	public function test_set_from_order_shipping() {

		$address = $this->get_address();

		$address->set_from_order( $this->get_order(), 'shipping' );

		$this->assertEquals( 'Eddie', $address->get_first_name() );
		$this->assertEquals( 'Munster', $address->get_last_name() );
		$this->assertEquals( '1313 Mockingbird Lane', $address->get_line_1() );
		$this->assertEquals( 'Suite 1', $address->get_line_2() );
		$this->assertEquals( '', $address->get_line_3() ); // WC Orders have no line 3
		$this->assertEquals( 'Mockingbird Heights', $address->get_locality() );
		$this->assertEquals( 'CA', $address->get_region() );
		$this->assertEquals( 'USA', $address->get_country() );
		$this->assertEquals( '90000', $address->get_postcode() );
	}


	/** Utility Methods ***********************************************************************************************/


	/**
	 * Gets a mock order with billing and shipping address info.
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function get_order() {

		// create a mock class for WC_Order
		$order = $this->getMockBuilder( 'WC_Order' )->setMethods( [
			'get_billing_first_name',
			'get_billing_last_name',
			'get_billing_address_1',
			'get_billing_address_2',
			'get_billing_city',
			'get_billing_state',
			'get_billing_country',
			'get_billing_postcode',
			'get_shipping_first_name',
			'get_shipping_last_name',
			'get_shipping_address_1',
			'get_shipping_address_2',
			'get_shipping_city',
			'get_shipping_state',
			'get_shipping_country',
			'get_shipping_postcode',
		] )->getMock();

		$order->method( 'get_billing_first_name' )->willReturn( 'Herman' );
		$order->method( 'get_billing_last_name' )->willReturn( 'Munster' );
		$order->method( 'get_billing_address_1' )->willReturn( '1313 Mockingbird Lane' );
		$order->method( 'get_billing_address_2' )->willReturn( 'Suite 0' );
		$order->method( 'get_billing_city' )->willReturn( 'Mockingbird Heights' );
		$order->method( 'get_billing_state' )->willReturn( 'CA' );
		$order->method( 'get_billing_country' )->willReturn( 'USA' );
		$order->method( 'get_billing_postcode' )->willReturn( '90000' );
		$order->method( 'get_shipping_first_name' )->willReturn( 'Eddie' );
		$order->method( 'get_shipping_last_name' )->willReturn( 'Munster' );
		$order->method( 'get_shipping_address_1' )->willReturn( '1313 Mockingbird Lane' );
		$order->method( 'get_shipping_address_2' )->willReturn( 'Suite 1' );
		$order->method( 'get_shipping_city' )->willReturn( 'Mockingbird Heights' );
		$order->method( 'get_shipping_state' )->willReturn( 'CA' );
		$order->method( 'get_shipping_country' )->willReturn( 'USA' );
		$order->method( 'get_shipping_postcode' )->willReturn( '90000' );

		return $order;
	}


	protected function get_address() {

		return new PluginFramework\Addresses\Customer_Address();
	}


}
