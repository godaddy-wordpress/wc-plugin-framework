<?php

namespace API;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10\API\CacheableRequestTrait;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10\SV_WC_API_JSON_Request;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10\SV_WC_API_Request;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', true );
}

class CacheableRequestTraitTest extends \Codeception\Test\Unit
{


	/**
	 * Runs before each test.
	 */
	protected function _before()
	{

		require_once('woocommerce/api/interface-sv-wc-api-request.php');
		require_once('woocommerce/api/abstract-sv-wc-api-json-request.php');
		require_once('woocommerce/api/CacheableRequestTrait.php');
	}

	/** @see CacheableRequest::get_cache_lifetime() */
	public function test_get_cache_lifetime() {
		$request = $this->get_test_request_instance();

		$this->assertSame( 0, $request->get_cache_lifetime() );
	}

	/** @see CacheableRequest::set_cache_lifetime() */
	public function test_set_cache_lifetime() {
		$request = $this->get_test_request_instance();

		$this->assertSame( $request, $request->set_cache_lifetime( 1000 ) );
		$this->assertSame( 1000, $request->get_cache_lifetime() );
	}

	/** @see CacheableRequest::should_refresh() */
	public function test_should_refresh() {
		$request = $this->get_test_request_instance();

		$this->assertFalse( $request->should_refresh() );
	}

	/** @see CacheableRequest::set_force_refresh() */
	public function test_set_force_refresh() {
		$request = $this->get_test_request_instance();

		$this->assertSame( $request, $request->set_force_refresh( true ) );
		$this->assertTrue( $request->should_refresh() );
	}

	/**
	 * Gets a test request instance using the CacheableRequestTrait.
	 *
	 * @return SV_WC_API_JSON_Request
	 */
	protected function get_test_request_instance() : SV_WC_API_JSON_Request {
		return new class extends SV_WC_API_JSON_Request {
			use CacheableRequestTrait;
		};
	}
}
