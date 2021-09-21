<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10\SV_WC_API_JSON_Request;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10\API\Abstract_Cacheable_API_Base;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10\API\Traits\Cacheable_Request_Trait;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10\SV_WC_API_Request;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', true );
}

class CacheableAPIBaseTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::load_response_from_cache()}.
	 * @throws ReflectionException
	 */
	public function test_load_response_from_cache() {

		$api     = $this->get_new_api_instance(['get_request_transient_key']);
		$request = $this->get_new_request_instance();

		$property = new ReflectionProperty( get_class( $api ), 'request' );
		$property->setAccessible( true );
		$property->setValue( $api, $request );

		$api->method('get_request_transient_key')->willReturn( 'foo' );

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

		$api     = $this->get_new_api_instance(['get_request_transient_key']);
		$request = $this->get_new_request_instance();

		$property = new ReflectionProperty( get_class( $api ), 'request' );
		$property->setAccessible( true );
		$property->setValue( $api, $request );

		$api->method('get_request_transient_key')->willReturn( 'foo' );

		$method = new ReflectionMethod( get_class( $api ), 'save_response_to_cache' );
		$method->setAccessible( true );

		$data = ['bar' => 'baz'];

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
	 * @throws ReflectionException
	 */
	public function test_get_request_transient_key( string $uri, string $body, int $lifetime ) {

		$api = $this->get_new_api_instance(['get_request_uri', 'get_request_body']);
		$request = $this->get_new_request_instance()->set_cache_lifetime( $lifetime );

		$api->method('get_request_uri')->willReturn( $uri );
		$api->method('get_request_body')->willReturn( $body );

		$property = new ReflectionProperty( get_class( $api ), 'request' );
		$property->setAccessible( true );
		$property->setValue( $api, $request );

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
	public function provider_get_request_transient_key() : array {
		return [
			['foo', 'a=1', 100],
			['foo', 'a=2', 100],
			['foo', 'a=2', 200],
			['bar', '', 100],
			['bar/baz', '', 100],
		];
	}


	/**
	 * Tests {@see Framework\API\Abstract_Cacheable_API_Base::is_request_cacheable()}.
	 *
	 * @dataProvider provider_is_request_cacheable
	 *
	 * @param bool $cacheable whether to test with a cacheable request
	 * @param null|bool $filter_value when provided, will filter is_cacheable with the given value
	 * @param bool $expected expected return value
	 * @throws ReflectionException
	 */
	public function test_is_request_cacheable( bool $cacheable, $filter_value = null, bool $expected ) {
		$api = $this->get_new_api_instance();

		$property = new ReflectionProperty( get_class( $api ), 'request' );
		$property->setAccessible( true );
		$property->setValue( $api, $this->get_new_request_instance( $cacheable ) );

		if ( is_bool( $filter_value ) ) {
			add_filter(
				'wc_plugin_' . sv_wc_test_plugin()->get_id() . '_api_request_is_cacheable',
				// the typehints in the closure ensure we're passing the correct arguments to the filter from `is_request_cacheable`
				static function( bool $is_cacheable, SV_WC_API_Request $request ) use ( $filter_value ) {
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
	public function provider_is_request_cacheable() : array {
		return [
			'cacheable request, no filtering'          => [true, null, true],
			'non-cacheable request, no filtering'      => [false, null, false],
			'cacheable request, filtering to false'    => [true, false, false],
			'non-cacheable request, filtering to true' => [false, true, false],
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
	 * @throws ReflectionException
	 */
	public function test_get_request_cache_lifetime( int $lifetime, $filter_value = null, int $expected ) {
		$api = $this->get_new_api_instance();
		$request = $this->get_new_request_instance()->set_cache_lifetime( $lifetime );

		$property = new ReflectionProperty( get_class( $api ), 'request' );
		$property->setAccessible( true );
		$property->setValue( $api, $request );

		if ( is_int( $filter_value ) ) {
			add_filter(
				'wc_plugin_' . sv_wc_test_plugin()->get_id() . '_api_request_cache_lifetime',
				// the typehints in the closure ensure we're passing the correct arguments to the filter from `is_request_cacheable`
				static function( int $lifetime, SV_WC_API_Request $request ) use ( $filter_value ) {
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
	public function provider_get_request_cache_lifetime() : array {
		return [
			'non-filtered' => [100, null, 100],
			'filtered'     => [100, 200, 200],
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

		$api->method('get_plugin')->willReturn( sv_wc_test_plugin() );

		return $api;
	}
}

