<?php
/**
 * WooCommerce Payment Gateway Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/Payment_Gateway/External_Checkout
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( __NAMESPACE__ . '\\Orders' ) ) :

/**
 * A helper that handles order creation outside the regular WC checkout process.
 *
 * @see \WC_Checkout
 *
 * @since 5.10.0
 */
class Orders {


	/**
	 * Creates an order from a cart.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @param array $order_data order data
	 * @return \WC_Order
	 * @throws Framework\SV_WC_Payment_Gateway_Exception
	 * @throws \Exception
	 */
	public static function create_order( \WC_Cart $cart, $order_data = [] ) {

		$cart->calculate_totals();

		try {

			$order_data = wp_parse_args( $order_data, [
				'status'      => apply_filters( 'woocommerce_default_order_status', 'pending' ),
				'customer_id' => apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() ),
				'cart_hash'   => self::get_cart_hash( $cart ),
			] );

			$order = self::get_order_object( $order_data );

			$order->add_meta_data( 'is_vat_exempt', $cart->get_customer()->get_is_vat_exempt() ? 'yes' : 'no' );

			$checkout = WC()->checkout();

			$checkout->create_order_line_items( $order, $cart );
			$checkout->create_order_coupon_lines( $order, $cart );
			$checkout->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods', [] ), WC()->shipping()->get_packages() );
			$checkout->create_order_fee_lines( $order, $cart );
			$checkout->create_order_tax_lines( $order, $cart );

			/** This action is documented by WooCommerce in includes/class-wc-checkout.php */
			do_action( 'woocommerce_checkout_create_order', $order, [] );

			$order->save();

			$order->update_taxes();

			$order->calculate_totals( false ); // false to skip recalculating taxes

			/** This action is documented by WooCommerce in includes/class-wc-checkout.php */
			do_action( 'woocommerce_checkout_update_order_meta', $order->get_id(), [] );

			return $order;

		} catch ( Framework\SV_WC_Payment_Gateway_Exception $e ) {

			throw $e;
		}
	}


	/**
	 * Gets the hash of the cart based on the cart contents.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @return string
	 */
	private static function get_cart_hash( \WC_Cart $cart ) {

		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.6' ) ) {
			return $cart->get_cart_hash();
		}

		return md5( json_encode( wc_clean( $cart->get_cart_for_session() ) ) . $cart->get_total( 'edit' ) );
	}


	/**
	 * Gets an order object for payment.
	 *
	 * @since 5.10.0
	 *
	 * @see \WC_Checkout::create_order()
	 *
	 * @param array $order_data the order data
	 * @return \WC_Order
	 * @throws Framework\SV_WC_Payment_Gateway_Exception
	 */
	public static function get_order_object( $order_data ) {

		$order_id = (int) WC()->session->get( 'order_awaiting_payment', 0 );
		$order    = $order_id ? wc_get_order( $order_id ) : null;

		if ( $order && $order->has_cart_hash( $order_data['cart_hash'] ) && $order->has_status( [ 'pending', 'failed' ] ) ) {

			$order_data['order_id'] = $order_id;

			$order = wc_update_order( $order_data );

			if ( is_wp_error( $order ) ) {
				throw new Framework\SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 522 ) );
			}

			/** This action is documented by WooCommerce in includes/class-wc-checkout.php */
			do_action( 'woocommerce_resume_order', $order_id );

			$order->remove_order_items();

		} else {

			$order = wc_create_order( $order_data );

			if ( is_wp_error( $order ) ) {
				throw new Framework\SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 520 ) );
			}

			if ( false === $order ) {
				throw new Framework\SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 521 ) );
			}

			// set the new order ID so it can be resumed in case of failure
			WC()->session->set( 'order_awaiting_payment', $order->get_id() );
		}

		return $order;
	}


}


endif;
