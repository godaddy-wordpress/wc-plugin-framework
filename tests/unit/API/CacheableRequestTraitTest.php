<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\API;

use SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Traits\Cacheable_Request_Trait;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;

class CacheableRequestTraitTest extends TestCase
{
	public function setUp() : void
	{
		parent::setUp();

		require_once PLUGIN_ROOT_DIR.'/woocommerce/api/interface-sv-wc-api-request.php';
		require_once PLUGIN_ROOT_DIR.'/woocommerce/api/abstract-sv-wc-api-json-request.php';
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Traits\Cacheable_Request_Trait::get_cache_lifetime()
	 */
	public function test_get_cache_lifetime() : void
	{
		$request = $this->get_test_request_instance();

		$this->assertSame(86400, $request->get_cache_lifetime());
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Traits\Cacheable_Request_Trait::set_cache_lifetime()
	 */
	public function test_set_cache_lifetime() : void
	{
		$request = $this->get_test_request_instance();

		$this->assertSame($request, $request->set_cache_lifetime(1000));
		$this->assertSame(1000, $request->get_cache_lifetime());
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Traits\Cacheable_Request_Trait::should_refresh()
	 */
	public function test_should_refresh() : void
	{
		$request = $this->get_test_request_instance();

		$this->assertFalse($request->should_refresh());
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Traits\Cacheable_Request_Trait::set_force_refresh()
	 */
	public function test_set_force_refresh() : void
	{
		$request = $this->get_test_request_instance();

		$this->assertSame($request, $request->set_force_refresh(true));
		$this->assertTrue($request->should_refresh());
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Traits\Cacheable_Request_Trait::should_refresh()
	 */
	public function test_should_cache() : void
	{
		$request = $this->get_test_request_instance();

		$this->assertTrue($request->should_cache());
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Traits\Cacheable_Request_Trait::set_should_cache()
	 */
	public function test_set_should_cache() : void
	{
		$request = $this->get_test_request_instance();

		$this->assertSame($request, $request->set_should_cache(false));
		$this->assertFalse($request->should_cache());
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Traits\Cacheable_Request_Trait::bypass_cache()
	 */
	public function bypass_cache() : void
	{
		$request = $this->get_test_request_instance();

		$this->assertSame($request, $request->bypass_cache());
		$this->assertTrue($request->should_refresh());
		$this->assertFalse($request->should_cache());
	}

	/**
	 * Gets a test request instance using the CacheableRequestTrait.
	 */
	protected function get_test_request_instance()
	{
		return $this->getMockForTrait(Cacheable_Request_Trait::class);
	}
}
