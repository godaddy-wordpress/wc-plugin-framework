<?php
/**
 * Helper class for managing WooCommerce order properties.
 *
 * This class provides helper methods for getting and setting order properties
 * in a standardized way using the Dynamic_Props class. It handles payment details,
 * customer information, and custom order properties.
 *
 * @package   SkyVerge/WooCommerce/Helpers
 * @since     x.x.x
 */

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_0\Helpers;

use SkyVerge\WooCommerce\PluginFramework\v6_0_0\Payment_Gateway\Dynamic_Props;

/**
 * OrderHelper class
 *
 * @since     x.x.x
 */
class OrderHelper {

	/**
	 * Gets the payment object associated with an order.
	 *
	 * Retrieves the payment details stored as a dynamic property on the order object.
	 * If no payment object exists, returns an empty stdClass instance.
	 *
	 * @since x.x.x
	 *
	 * @param \WC_Order $order The order object.
	 * @return \stdClass The payment object containing payment details.
	 */
	public static function getPayment( \WC_Order $order ) {
		return Dynamic_Props::get( $order, 'payment', null, new \stdClass() );
	}

	/**
	 * Gets the payment total for an order.
	 *
	 * Retrieves the total payment amount stored as a dynamic property on the order.
	 *
	 * @since x.x.x
	 *
	 * @param \WC_Order $order The order object.
	 * @return mixed The payment total amount, or null if not set.
	 */
	public static function getPaymentTotal( \WC_Order $order ) {
		return Dynamic_Props::get( $order, 'payment_total' );
	}

	/**
	 * Gets the customer ID associated with an order.
	 *
	 * Retrieves the customer ID stored as a dynamic property on the order.
	 *
	 * @since x.x.x
	 *
	 * @param \WC_Order $order The order object.
	 * @return mixed The customer ID, or null if not set.
	 */
	public static function getCustomerId( \WC_Order $order ) {
		return Dynamic_Props::get( $order, 'customer_id' );
	}

	/**
	 * Gets a dynamic property from an order.
	 *
	 * Provides a generic way to retrieve any dynamic property stored on an order object.
	 * Supports nested properties through the optional nested_key parameter.
	 *
	 * @since x.x.x
	 *
	 * @param \WC_Order $order      The order object.
	 * @param string    $key        The property key to retrieve.
	 * @param string    $nested_key Optional. A nested key within the property. Default null.
	 * @param mixed     $default    Optional. The default value if the property doesn't exist. Default null.
	 * @return mixed The property value if found, or the default value if not found.
	 */
	public static function getProperty( \WC_Order $order, string $key, $nested_key = null, $default = null ): mixed {
		return Dynamic_Props::get( $order, $key, $nested_key, $default );
	}

	/**
	 * Sets the payment object for an order.
	 *
	 * Stores payment details as a dynamic property on the order object.
	 * This method uses pass-by-reference to modify the order object directly.
	 *
	 * @since x.x.x
	 *
	 * @param \WC_Order  $order   The order object (passed by reference).
	 * @param \stdClass  $payment The payment object containing payment details.
	 */
	public static function setPayment( \WC_Order &$order, \stdClass $payment ) {
		Dynamic_Props::set( $order, 'payment', $payment );
	}

	/**
	 * Sets the payment total for an order.
	 *
	 * Stores the payment total as a dynamic property on the order object.
	 * This method uses pass-by-reference to modify the order object directly.
	 *
	 * @since x.x.x
	 *
	 * @param \WC_Order     $order         The order object (passed by reference).
	 * @param float|string  $payment_total The payment total amount.
	 */
	public static function setPaymentTotal( \WC_Order &$order, $payment_total ) {
		Dynamic_Props::set( $order, 'payment_total', $payment_total );
	}

	/**
	 * Sets the customer ID for an order.
	 *
	 * Stores the customer ID as a dynamic property on the order object.
	 * This method uses pass-by-reference to modify the order object directly.
	 *
	 * @since x.x.x
	 *
	 * @param \WC_Order $order       The order object (passed by reference).
	 * @param mixed     $customer_id The customer ID to set.
	 */
	public static function setCustomerId( \WC_Order &$order, $customer_id ) {
		Dynamic_Props::set( $order, 'customer_id', $customer_id );
	}

	/**
	 * Sets a dynamic property on an order.
	 *
	 * Provides a generic way to store any dynamic property on an order object.
	 * This method uses pass-by-reference to modify the order object directly.
	 *
	 * @since x.x.x
	 *
	 * @param \WC_Order $order The order object (passed by reference).
	 * @param string    $key   The property key to set.
	 * @param mixed     $value The value to set for the property.
	 */
	public static function setProperty( \WC_Order &$order, string $key, $value ) {
		Dynamic_Props::set( $order, $key, $value );
	}
}
