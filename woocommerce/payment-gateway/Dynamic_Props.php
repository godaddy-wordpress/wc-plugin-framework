<?php
/**
 * Class for storing dynamic properties for order object.
 *
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Classes
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_12\Payment_Gateway;

/**
 * Class for storing dynamic properties for order object.
 *
 * This class provides a way to store dynamic properties on order objects without using
 * dynamic properties (deprecated in PHP 8.2+) while maintaining backwards compatibility
 * with PHP 7.4+.
 *
 * @since x.x.x
 *
 * Example usage:
 * ```php
 * // Store properties
 * Dynamic_Props::set($order, 'customer_id', 123);
 * Dynamic_Props::set($order, 'payment_total', 99.99);
 *
 * // Retrieve properties
 * $customer_id = Dynamic_Props::get($order, 'customer_id');
 * $total       = Dynamic_Props::get($order, 'payment_total');
 */
class Dynamic_Props {
	/**
	 * Storage for PHP 8.0+ using WeakMap.
	 *
	 * @var \WeakMap<object, \stdClass>|null
	 */
	private static ?\WeakMap $map = null;

	/**
	 * Sets a property on the order object.
	 *
	 * ```php
	 * Dynamic_Props::set($order, 'customer_id', 123);
	 * Dynamic_Props::set($order, 'payment_total', '99.99');
	 * ```
	 *
	 * @param \WC_Order $order The order object to store data on.
	 * @param string    $key   The property key.
	 * @param mixed     $value The value to store.
	 *
	 * @return void
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
	 * ```php
	 * $customer_id = Dynamic_Props::get($order, 'customer_id');
	 * $total       = Dynamic_Props::get($order, 'payment_total');
	 * $token       = Dynamic_Props::get($order, 'payment', 'token', 'DEFAULT_TOKEN');
	 * ```
	 *
	 * @param \WC_Order $order      The order object to retrieve data from.
	 * @param string    $key        The property key.
	 * @param string    $nested_key The nested property key.
	 * @param mixed     $default    Default value if not found.
	 *
	 * @return mixed The stored value or default if not found.
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
	 * @param \WC_Order $order The order object to unset data from.
	 * @param string    $key   The property key.
	 *
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
	 * @return void
	 */
	private static function init_weak_map(): void {
		if ( null === self::$map ) {
			self::$map = new \WeakMap();
		}
	}
}
