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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Google-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Google_Pay;

use SkyVerge\WooCommerce\PluginFramework\v5_8_1\SV_WC_Payment_Gateway_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_8_1\SV_WC_Plugin_Compatibility;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_8_1\\Payment_Gateway\\Google_Pay\\Orders' ) ) :


/**
 * The Google Pay order handler.
 *
 * @since 5.9.0-dev.1
 */
class Orders {


	/**
	 * Creates an order from a cart.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @param \WC_Cart $cart cart object
	 * @return \WC_Order|void
	 * @throws SV_WC_Payment_Gateway_Exception
	 * @throws \Exception
	 */
	public static function create_order( \WC_Cart $cart ) {

		$cart->calculate_totals();

		try {

			if ( SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.6' ) ) {
				$cart_hash = $cart->get_cart_hash();
			} else {
				$cart_hash = md5( json_encode( wc_clean( $cart->get_cart_for_session() ) ) . $cart->get_total( 'edit' ) );
			}

			$order_data = [
				'status'      => apply_filters( 'woocommerce_default_order_status', 'pending' ),
				'customer_id' => apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() ),
				'cart_hash'   => $cart_hash,
				'created_via' => 'google_pay',
			];

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

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {
			throw $e;
		}
	}


}


endif;
