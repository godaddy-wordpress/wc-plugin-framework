<?php
/**
 * Dynamic property storage handler for WooCommerce order objects.
 *
 * Provides a PHP 8.2+ compatible way to store dynamic properties on order objects
 * while maintaining backwards compatibility with PHP 7.4+.
 *
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Classes
 * @since     x.x.x
 */

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_0\Payment_Gateway;

/**
 * Dynamic property storage handler for WooCommerce order objects.
 *
 * This class provides a way to store dynamic properties on order objects without using
 * dynamic properties (deprecated in PHP 8.2+) while maintaining backwards compatibility
 * with PHP 7.4+. It uses WeakMap for PHP 8.0+ and falls back to dynamic properties
 * for PHP 7.4+.
 *
 * @since x.x.x
 *
 * @example
 * ```php
 * // Store properties
 * Dynamic_Props::set($order, 'customer_id', 123);
 * Dynamic_Props::set($order, 'payment_total', 99.99);
 *
 * // Retrieve properties
 * $customer_id = Dynamic_Props::get($order, 'customer_id');
 * $total       = Dynamic_Props::get($order, 'payment_total');
 * ```
 */
class Dynamic_Props {
	/**
	 * Storage container for dynamic properties using WeakMap in PHP 8.0+.
	 *
	 * Uses WeakMap to store properties without memory leaks, as WeakMap allows garbage
	 * collection of its keys when they're no longer referenced elsewhere.
	 *
	 * @since x.x.x
	 * @var   \WeakMap<object, \stdClass>|null
	 */
	private static ?\WeakMap $map = null;

	/**
	 * Sets a property on the order object.
	 *
	 * Stores a dynamic property either using WeakMap (PHP 8.0+) or direct property
	 * access (PHP 7.4+). The storage method is automatically determined based on
	 * PHP version and WeakMap availability.
	 *
	 * @since  x.x.x
	 *
	 * @param  \WC_Order $order The order object to store data on.
	 * @param  string    $key   The property key.
	 * @param  mixed     $value The value to store.
	 * @return void
	 *
	 * @example
	 * ```php
	 * Dynamic_Props::set($order, 'customer_id', 123);
	 * Dynamic_Props::set($order, 'payment_total', '99.99');
	 * ```
	 */
	public static function set( \WC_Order &$order, string $key, mixed $value ): void {
		if ( self::use_weak_map() ) {
			self::init_weak_map();
			if ( ! isset( self::$map[ $order ] ) ) {
				self::$map[ $order ] = new \stdClass();
			}
			self::$map[ $order ]->{ $key } = $value;
		} else {
			$order->{ $key } = $value;
		}
	}

	/**
	 * Gets a property from the order object.
	 *
	 * Retrieves a stored dynamic property using the appropriate storage method
	 * based on PHP version. Supports nested property access.
	 *
	 * @since  x.x.x
	 *
	 * @param  \WC_Order $order      The order object to retrieve data from.
	 * @param  string    $key        The property key.
	 * @param  string    $nested_key Optional. The nested property key. Default null.
	 * @param  mixed     $default    Optional. Default value if not found. Default null.
	 * @return mixed The stored value or default if not found.
	 *
	 * @example
	 * ```php
	 * $customer_id = Dynamic_Props::get($order, 'customer_id');
	 * $total       = Dynamic_Props::get($order, 'payment_total');
	 * $token       = Dynamic_Props::get($order, 'payment', 'token', 'DEFAULT_TOKEN');
	 * ```
	 */
	public static function get( \WC_Order $order, string $key, $nested_key = null, $default = null ): mixed {
		if ( self::use_weak_map() ) {
			self::init_weak_map();
			if ( is_null( $nested_key ) ) {
				return self::$map[ $order ]->{ $key } ?? $default;
			} else {
				return self::$map[ $order ]->{ $key }->{ $nested_key } ?? $default;
			}
		}
		if ( is_null( $nested_key ) ) {
			return $order->{ $key } ?? $default;
		} else {
			return $order->{ $key }->{ $nested_key } ?? $default;
		}
	}

	/**
	 * Unsets a property on the order object.
	 *
	 * Removes a stored dynamic property using the appropriate storage method
	 * based on PHP version.
	 *
	 * @since  x.x.x
	 *
	 * @param  \WC_Order $order The order object to unset data from.
	 * @param  string    $key   The property key to remove.
	 * @return void
	 */
	public static function unset( \WC_Order &$order, string $key ): void {
		if ( self::use_weak_map() ) {
			self::init_weak_map();
			unset( self::$map[ $order ]->{ $key } );
		} else {
			unset( $order->{ $key } );
		}
	}

	/**
	 * Checks if WeakMap should be used based on PHP version.
	 *
	 * Determines whether to use WeakMap storage based on PHP version (8.0+)
	 * and WeakMap class availability. Result is cached for performance.
	 *
	 * @since  x.x.x
	 * @return bool True if WeakMap should be used, false otherwise.
	 */
	private static function use_weak_map(): bool {
		static $use_weak_map = null;

		if ( null === $use_weak_map ) {
			$use_weak_map = version_compare( PHP_VERSION, '8.0', '>=' ) && class_exists( '\WeakMap' );
		}

		return $use_weak_map;
	}

	/**
	 * Initializes WeakMap storage if not already initialized.
	 *
	 * Ensures the WeakMap storage is initialized only once when needed.
	 * This lazy initialization helps with performance and memory usage.
	 *
	 * @since  x.x.x
	 * @return void
	 */
	private static function init_weak_map(): void {
		if ( null === self::$map ) {
			self::$map = new \WeakMap();
		}
	}
}
