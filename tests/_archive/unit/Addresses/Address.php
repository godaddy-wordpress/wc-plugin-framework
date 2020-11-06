<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit\Addresses;

use SkyVerge\WooCommerce\PluginFramework\Tests\Unit;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Unit tests for PluginFramework\Addresses\Address
 */
class Address extends Unit\Test_Case {


	/** Getter Methods ************************************************************************************************/


	/**
	 * Tests the get_line_1() method.
	 */
	public function test_get_line_1() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_line_1() );
	}


	/**
	 * Tests the get_line_2() method.
	 */
	public function test_get_line_2() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_line_2() );
	}


	/**
	 * Tests the get_line_3() method.
	 */
	public function test_get_line_3() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_line_3() );
	}


	/**
	 * Tests the get_locality() method.
	 */
	public function test_get_locality() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_locality() );
	}


	/**
	 * Tests the get_region() method.
	 */
	public function test_get_region() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_region() );
	}


	/**
	 * Tests the get_country() method.
	 */
	public function test_get_country() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_country() );
	}


	/**
	 * Tests the get_postcode() method.
	 */
	public function test_get_postcode() {

		$address = $this->get_address();

		$this->assertEquals( '', $address->get_postcode() );
	}


	/** Setter Methods ************************************************************************************************/


	/**
	 * Tests the set_line_1() method.
	 */
	public function test_set_line_1() {

		$address = $this->get_address();

		$address->set_line_1( '1313 Mockingbird Lane' );

		$this->assertEquals( '1313 Mockingbird Lane', $address->get_line_1() );
	}


	/**
	 * Tests the set_line_2() method.
	 */
	public function test_set_line_2() {

		$address = $this->get_address();

		$address->set_line_2( 'Suite 0' );

		$this->assertEquals( 'Suite 0', $address->get_line_2() );
	}


	/**
	 * Tests the set_line_3() method.
	 */
	public function test_set_line_3() {

		$address = $this->get_address();

		$address->set_line_3( 'c/o Herman Munster' );

		$this->assertEquals( 'c/o Herman Munster', $address->get_line_3() );
	}


	/**
	 * Tests the set_locality() method.
	 */
	public function test_set_locality() {

		$address = $this->get_address();

		$address->set_locality( 'Mockingbird Heights' );

		$this->assertEquals( 'Mockingbird Heights', $address->get_locality() );
	}


	/**
	 * Tests the set_region() method.
	 */
	public function test_set_region() {

		$address = $this->get_address();

		$address->set_region( 'CA' );

		$this->assertEquals( 'CA', $address->get_region() );
	}


	/**
	 * Tests the set_country() method.
	 */
	public function test_set_country() {

		$address = $this->get_address();

		$address->set_country( 'USA' );

		$this->assertEquals( 'USA', $address->get_country() );
	}


	/**
	 * Tests the set_postcode() method.
	 */
	public function test_set_postcode() {

		$address = $this->get_address();

		$address->set_postcode( '90000' );

		$this->assertEquals( '90000', $address->get_postcode() );
	}


	/** Utility Methods ***********************************************************************************************/


	/**
	 * Gets a new address object without data populated.
	 *
	 * @since 5.3.0-dev
	 *
	 * @return PluginFramework\Addresses\Address
	 */
	protected function get_address() {

		return new PluginFramework\Addresses\Address();
	}


}
