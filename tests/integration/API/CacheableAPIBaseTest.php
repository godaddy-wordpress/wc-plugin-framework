<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_12 as Framework;
use SkyVerge\WooCommerce\PluginFramework\v5_10_12\API\Abstract_Cacheable_API_Base;
use SkyVerge\WooCommerce\PluginFramework\v5_10_12\API\Traits\Cacheable_Request_Trait;
use SkyVerge\WooCommerce\PluginFramework\v5_10_12\SV_WC_API_JSON_Request;
use SkyVerge\WooCommerce\PluginFramework\v5_10_12\SV_WC_API_Request;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', true );
}

class CacheableAPIBaseTest extends \Codeception\TestCase\WPTestCase {


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::do_remote_request()}.
	 *
	 * @dataProvider provider_do_remote_request
	 *
	 * @param bool $is_cacheable
	 * @param bool|null $force_refresh
	 * @param bool|null $cache_exists
	 * @param bool $should_load_from_cache
	 *
	 * @throws ReflectionException
	 */
	public function test_do_remote_request( bool $is_cacheable, bool $force_refresh = null, bool $cache_exists = null, bool $should_load_from_cache = false ) {

		$request = $this->get_new_request_instance( $is_cacheable );

		if ( $is_cacheable ) {
			$request->set_force_refresh( $force_refresh );
		}

		$api = $this->get_new_api_instance_with_request( $request, [ 'load_response_from_cache' ] );
		$api->method( 'load_response_from_cache' )->willReturn( $cache_exists ? [ 'foo' => 'bar' ] : null );

		$loaded_from_cache = new ReflectionProperty( get_class( $api ), 'response_loaded_from_cache' );
		$loaded_from_cache->setAccessible( true );

		$method = new ReflectionMethod( get_class( $api ), 'do_remote_request' );
		$method->setAccessible( true );

		$method->invoke( $api, 'foo', [] );

		$this->assertEquals( $should_load_from_cache, $loaded_from_cache->getValue( $api ) );
	}


	/**
	 * Data provider for {@see CacheableAPIBaseTest::test_do_remote_request()}.
	 *
	 * @return array[]
	 */
	public function provider_do_remote_request(): array {
		return [
			'cacheable, no refresh, cache exists'            => [ true, false, true, true ],
			'cacheable, no refresh, cache does not exist'    => [ true, false, false, false ],
			'cacheable, force refresh, cache exists'         => [ true, true, true, false ],
			'cacheable, force refresh, cache does not exist' => [ true, true, false, false ],
			'non-cacheable'                                  => [ false, null, null, false ],
		];
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::handle_response()}.
	 *
	 * @dataProvider provider_handle_response
	 *
	 * @param bool $is_cacheable
	 * @param bool|null $loaded_from_cache
	 * @param bool $should_save_response_to_cache
	 *
	 * @throws ReflectionException
	 */
	public function test_handle_response( bool $is_cacheable, bool $loaded_from_cache = false, bool $should_save_response_to_cache = false ) {

		$request = $this->get_new_request_instance( $is_cacheable );
		$api     = $this->get_new_api_instance_with_request( $request, [
			'is_response_loaded_from_cache',
			'get_response_handler',
			'save_response_to_cache'
		] );

		$api->method( 'get_response_handler' )->willReturn( new stdClass );
		$api->method( 'is_response_loaded_from_cache' )->willReturn( $loaded_from_cache );

		$method = new ReflectionMethod( get_class( $api ), 'handle_response' );
		$method->setAccessible( true );

		$api->expects( $should_save_response_to_cache ? $this->once() : $this->never() )->method( 'save_response_to_cache' );

		$method->invoke( $api, [] );
	}


	/**
	 * Data provider for {@see CacheableAPIBaseTest::test_handle_response()}.
	 *
	 * @return array[]
	 */
	public function provider_handle_response(): array {
		return [
			'cacheable, response loaded from cache'     => [ true, true, false ],
			'cacheable, response not loaded from cache' => [ true, false, true ],
			'non-cacheable'                             => [ false, false, false ],
		];
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::load_response_from_cache()}.
	 * @throws ReflectionException
	 */
	public function test_load_response_from_cache() {

		$api = $this->get_new_api_instance_with_request(
			$this->get_new_request_instance(),
			[ 'get_request_transient_key' ]
		);

		$api->method( 'get_request_transient_key' )->willReturn( 'foo' );

		set_transient( 'foo', 'bar' );

		$method = new ReflectionMethod( get_class( $api ), 'load_response_from_cache' );
		$method->setAccessible( true );

		$this->assertEquals( 'bar', $method->invoke( $api ) );
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::save_response_to_cache()}.
	 * @throws ReflectionException
	 */
	public function test_save_response_to_cache() {

		$api = $this->get_new_api_instance_with_request(
			$this->get_new_request_instance(),
			[ 'get_request_transient_key' ]
		);

		$api->method( 'get_request_transient_key' )->willReturn( 'foo' );

		$method = new ReflectionMethod( get_class( $api ), 'save_response_to_cache' );
		$method->setAccessible( true );

		$data = [ 'bar' => 'baz' ];

		$method->invoke( $api, $data );

		$this->assertEquals( get_transient( 'foo' ), $data );
		$this->assertNotFalse( get_option( '_transient_timeout_foo' ) ); // ensure a timeout was set
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::is_response_loaded_from_cache()}.
	 * @throws ReflectionException
	 */
	public function test_is_response_loaded_from_cache() {

		$api = $this->get_new_api_instance();

		$property = new ReflectionProperty( get_class( $api ), 'response_loaded_from_cache' );
		$property->setAccessible( true );

		$method = new ReflectionMethod( get_class( $api ), 'is_response_loaded_from_cache' );
		$method->setAccessible( true );
		$method->invoke( $api );

		$this->assertFalse( $method->invoke( $api ) );

		$property->setValue( $api, true );

		$this->assertTrue( $method->invoke( $api ) );
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::reset_response()}.
	 * @throws ReflectionException
	 */
	public function test_reset_response() {

		$api = $this->get_new_api_instance();

		$property = new ReflectionProperty( get_class( $api ), 'response_loaded_from_cache' );
		$property->setAccessible( true );

		$this->assertFalse( $property->getValue( $api ) );

		$property->setValue( $api, true );

		$this->assertTrue( $property->getValue( $api ) );

		$method = new ReflectionMethod( get_class( $api ), 'reset_response' );
		$method->setAccessible( true );
		$method->invoke( $api );

		$this->assertFalse( $property->getValue( $api ) );
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::get_request_transient_key()}.
	 *
	 * @dataProvider provider_get_request_transient_key
	 *
	 * @param string $uri request uri
	 * @param string $body request body
	 * @param int $lifetime request lifetime
	 *
	 * @throws ReflectionException
	 */
	public function test_get_request_transient_key( string $uri, string $body, int $lifetime ) {

		$api = $this->get_new_api_instance_with_request(
			$this->get_new_request_instance()->set_cache_lifetime( $lifetime ),
			[ 'get_request_uri', 'get_request_body' ]
		);

		$api->method( 'get_request_uri' )->willReturn( $uri );
		$api->method( 'get_request_body' )->willReturn( $body );

		$method = new ReflectionMethod( get_class( $api ), 'get_request_transient_key' );
		$method->setAccessible( true );

		$this->assertEquals(
			sprintf( 'wc_%s_api_response_%s', sv_wc_test_plugin()->get_id(), md5( implode( '_', [
				$uri,
				$body,
				$lifetime,
			] ) ) ),
			$method->invoke( $api )
		);
	}


	/**
	 * Data provider for {@see CacheableAPIBaseTest::_get_request_transient_key()}.
	 *
	 * @return array[]
	 */
	public function provider_get_request_transient_key(): array {
		return [
			[ 'foo', 'a=1', 100 ],
			[ 'foo', 'a=2', 100 ],
			[ 'foo', 'a=2', 200 ],
			[ 'bar', '', 100 ],
			[ 'bar/baz', '', 100 ],
		];
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::is_request_cacheable()}.
	 *
	 * @dataProvider provider_is_request_cacheable
	 *
	 * @param bool $is_cacheable whether to test with a cacheable request
	 * @param null|bool $filter_value when provided, will filter is_cacheable with the given value
	 * @param bool $expected expected return value
	 *
	 * @throws ReflectionException
	 */
	public function test_is_request_cacheable( bool $is_cacheable, $filter_value = null, bool $expected ) {

		$api = $this->get_new_api_instance_with_request( $this->get_new_request_instance( $is_cacheable ) );

		if ( is_bool( $filter_value ) ) {
			add_filter(
				'wc_plugin_' . sv_wc_test_plugin()->get_id() . '_api_request_is_cacheable',
				// the typehints in the closure ensure we're passing the correct arguments to the filter from `is_request_cacheable`
				static function ( bool $is_cacheable, SV_WC_API_Request $request ) use ( $filter_value ) {
					return $filter_value;
				}, 10, 2 );
		}

		$method = new ReflectionMethod( get_class( $api ), 'is_request_cacheable' );
		$method->setAccessible( true );

		$this->assertEquals( $expected, $method->invoke( $api ) );
	}


	/**
	 * Data provider for {@see CacheableAPIBaseTest::test_is_request_cacheable()}.
	 *
	 * @return array[]
	 */
	public function provider_is_request_cacheable(): array {
		return [
			'cacheable request, no filtering'          => [ true, null, true ],
			'non-cacheable request, no filtering'      => [ false, null, false ],
			'cacheable request, filtering to false'    => [ true, false, false ],
			'non-cacheable request, filtering to true' => [ false, true, false ],
		];
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::get_request_cache_lifetime()}.
	 *
	 * @dataProvider provider_get_request_cache_lifetime
	 *
	 * @param int $lifetime request cache lifetime
	 * @param null|int $filter_value when provided, will filter cache_lifetime with the given value
	 * @param int $expected expected return value
	 *
	 * @throws ReflectionException
	 */
	public function test_get_request_cache_lifetime( int $lifetime, $filter_value = null, int $expected ) {

		$api = $this->get_new_api_instance_with_request( $this->get_new_request_instance()->set_cache_lifetime( $lifetime ) );

		if ( is_int( $filter_value ) ) {
			add_filter(
				'wc_plugin_' . sv_wc_test_plugin()->get_id() . '_api_request_cache_lifetime',
				// the typehints in the closure ensure we're passing the correct arguments to the filter from `get_request_cache_lifetime`
				static function ( int $lifetime, SV_WC_API_Request $request ) use ( $filter_value ) {
					return $filter_value;
				}, 10, 2 );
		}

		$method = new ReflectionMethod( get_class( $api ), 'get_request_cache_lifetime' );
		$method->setAccessible( true );

		$this->assertEquals( $expected, $method->invoke( $api ) );
	}


	/**
	 * Data provider for {@see CacheableAPIBaseTest::test_get_request_cache_lifetime()}.
	 *
	 * @return array[]
	 */
	public function provider_get_request_cache_lifetime(): array {
		return [
			'non-filtered' => [ 100, null, 100 ],
			'filtered'     => [ 100, 200, 200 ],
		];
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::get_request_data_for_broadcast()}.
	 *
	 * @dataProvider provider_get_request_data_for_broadcast
	 *
	 * @param bool $is_cacheable
	 * @param bool|null $force_refresh
	 * @param bool|null $should_cache
	 *
	 * @throws ReflectionException
	 */
	public function test_get_request_data_for_broadcast( bool $is_cacheable, bool $force_refresh = null, bool $should_cache = null ) {

		$request = $this->get_new_request_instance( $is_cacheable );

		if ( $is_cacheable ) {
			$request->set_force_refresh( $force_refresh )->set_should_cache( $should_cache );
		}

		$api = $this->get_new_api_instance_with_request( $request );

		$method = new ReflectionMethod( get_class( $api ), 'get_request_data_for_broadcast' );
		$method->setAccessible( true );

		$request_data = $method->invoke( $api );

		if ( $is_cacheable ) {
			$keys = array_keys( $request_data );

			// ensure our keys are at the top of the array
			$this->assertEquals( 'force_refresh', $keys[0] );
			$this->assertEquals( 'should_cache', $keys[1] );
			$this->assertEquals( 'method', $keys[2] );

			$this->assertEquals( $force_refresh, $request_data['force_refresh'] );
			$this->assertEquals( $should_cache, $request_data['should_cache'] );

		} else {

			$this->assertArrayNotHasKey( 'force_refresh', $request_data );
			$this->assertArrayNotHasKey( 'should_cache', $request_data );
		}
	}

	/**
	 * Data provider for {@see CacheableAPIBaseTest::test_get_request_data_for_broadcast()}.
	 *
	 * @return array[]
	 */
	public function provider_get_request_data_for_broadcast(): array {
		return [
			'cacheable, no refresh, should cache'    => [ true, false, true ],
			'cacheable, force refresh, should cache' => [ true, true, true ],
			'cacheable, force refresh, no cache'     => [ true, true, false ],
			'non-cacheable'                          => [ false ],
		];
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::get_response_data_for_broadcast()}.
	 *
	 * @dataProvider provider_get_response_data_for_broadcast
	 *
	 * @param bool $is_cacheable
	 * @param bool|null $cache_exists
	 * @param bool|null $force_refresh
	 * @param bool|null $expected_from_cache
	 *
	 * @throws ReflectionException
	 */
	public function test_get_response_data_for_broadcast( bool $is_cacheable, bool $response_loaded_from_cache = false ) {

		$api = $this->get_new_api_instance_with_request(
			$this->get_new_request_instance( $is_cacheable ),
			[ 'is_response_loaded_from_cache' ]
		);

		$api->method( 'is_response_loaded_from_cache' )->willReturn( $response_loaded_from_cache );

		$method = new ReflectionMethod( get_class( $api ), 'get_response_data_for_broadcast' );
		$method->setAccessible( true );

		$response_data = $method->invoke( $api );

		if ( $is_cacheable ) {
			$keys = array_keys( $response_data );

			// ensure our keys are at the top of the array
			$this->assertEquals( 'from_cache', $keys[0] );
			$this->assertEquals( 'code', $keys[1] );

			$this->assertEquals( $response_loaded_from_cache, $response_data['from_cache'] );

		} else {

			$this->assertArrayNotHasKey( 'from_cache', $response_data );
		}
	}

	/**
	 * Data provider for {@see CacheableAPIBaseTest::test_get_response_data_for_broadcast()}.
	 *
	 * @return array[]
	 */
	public function provider_get_response_data_for_broadcast(): array {
		return [
			'cacheable, loading response from cache'     => [ true, true ],
			'cacheable, not loading response from cache' => [ true, false ],
			'non-cacheable'                              => [ false ],
		];
	}


	/**
	 * Gets a test request instance using the CacheableRequestTrait.
	 *
	 * @param bool $cacheable whether to return a cacheable or regular request
	 */
	protected function get_new_request_instance( bool $cacheable = true ) {
		return $cacheable
			? new class extends SV_WC_API_JSON_Request {
				use Cacheable_Request_Trait;
			}
			: $this->getMockForAbstractClass( SV_WC_API_JSON_Request::class );
	}


	/**
	 * Gets a test API instance extending Abstract_Cacheable_API_Base class.
	 *
	 * @param array $mockMethods additional methods to mock on the class
	 */
	protected function get_new_api_instance( array $mockMethods = [] ) {

		$api = $this->getMockForAbstractClass(
			Abstract_Cacheable_API_Base::class,
			[],
			'',
			true,
			true,
			true,
			$mockMethods
		);

		$api->method( 'get_plugin' )->willReturn( sv_wc_test_plugin() );

		return $api;
	}


	/**
	 * Gets a new API instance with the given request attached to it.
	 *
	 * @throws ReflectionException
	 */
	protected function get_new_api_instance_with_request( SV_WC_API_Request $request, array $mockApiMethods = [] ) {

		$api = $this->get_new_api_instance( $mockApiMethods );

		$property = new ReflectionProperty( get_class( $api ), 'request' );
		$property->setAccessible( true );
		$property->setValue( $api, $request );

		return $api;
	}
}

