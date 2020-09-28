<?php

use SkyVerge\WooCommerce\PluginFramework\v5_8_1 as Framework;

/**
 * Tests for the compatibility class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_8_1\SV_WC_Plugin_Compatibility
 */
class CompatibilityTest extends \Codeception\TestCase\WPTestCase {


	public function test_is_rest_api_request() {

		$is_api_request = Framework\SV_WC_Plugin_Compatibility::is_rest_api_request();

		$this->assertIsBool( $is_api_request );
		$this->assertFalse( $is_api_request );
	}


}
