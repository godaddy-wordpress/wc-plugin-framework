<?php
/**
 * WooCommerce Plugin Framework
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
 * @package   SkyVerge/WooCommerce/Compatibility
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Order_Compatibility' ) ) :

/**
 * WooCommerce order compatibility class.
 *
 * @since 4.6.0-dev
 */
class SV_WC_Order_Compatibility extends SV_WC_Data_Compatibility {


	/** @var array mapped compatibility properties, as `$new_prop => $old_prop` */
	protected static $compat_props = array(
		'date_completed' => 'completed_date',
		'date_paid'      => 'paid_date',
		'date_modified'  => 'modified_date',
		'date_created'   => 'order_date',
		'customer_id'    => 'customer_user',
		'discount'       => 'cart_discount',
		'discount_tax'   => 'cart_discount_tax',
		'shipping_total' => 'total_shipping',
		'type'           => 'order_type',
		'currency'       => 'order_currency',
		'version'        => 'order_version',
	);


	/**
	 * Gets an order property.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $object the order object
	 * @param string $prop the property name
	 * @param string $context if 'view' then the value will be filtered
	 * @return mixed
	 */
	public static function get_prop( $object, $prop, $context = 'edit', $compat_props = array() ) {

		// backport a few specific properties to pre-2.7
		if ( SV_WC_Plugin_Compatibility::is_wc_version_lt_2_7() ) {

			// converge the shipping total prop for the raw context
			if ( 'shipping_total' === $prop && 'view' !== $context ) {

				$prop = 'order_shipping';

			// get the post_parent and bail early
			} elseif ( 'parent_id' === $prop ) {

				return $object->post->post_parent;
			}
		}

		$value = parent::get_prop( $object, $prop, $context, self::$compat_props );

		// 2.7+ date getters return a timestamp, where previously MySQL date strings were returned
		if ( SV_WC_Plugin_Compatibility::is_wc_version_lt_2_7() && in_array( $prop, array( 'date_completed', 'date_paid', 'date_modified', 'date_created' ), true ) && ! is_numeric( $value ) ) {
			$value = strtotime( $value );
		}

		return $value;
	}


	/**
	 * Sets an order's properties.
	 *
	 * Note that this does not save any data to the database.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $object the order object
	 * @param array $props the new properties as $key => $value
	 * @return \WC_Order
	 */
	public static function set_props( $object, $props, $compat_props = array() ) {

		return parent::set_props( $object, $props, self::$compat_props );
	}


	/**
	 * Order item CRUD compatibility method to add a coupon to an order.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param array $code the coupon code
	 * @param int $discount the discount amount.
	 * @param int $discount_tax the discount tax amount.
	 * @return int the order item ID
	 */
	public static function add_coupon( WC_Order $order, $code = array(), $discount = 0, $discount_tax = 0 ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			$item = new WC_Order_Item_Coupon();

			$item->set_props( array(
				'code'         => $code,
				'discount'     => $discount,
				'discount_tax' => $discount_tax,
				'order_id'     => $order->get_id(),
			) );

			$item->save();

			$order->add_item( $item );

			return $item->get_id();

		} else {

			return $order->add_coupon( $code, $discount, $discount_tax );
		}
	}


	/**
	 * Order item CRUD compatibility method to add a fee to an order.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param object $fee the fee to add
	 * @return int the order item ID
	 */
	public static function add_fee( WC_Order $order, $fee ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			$item = new WC_Order_Item_Fee();

			$item->set_props( array(
				'name'      => $fee->name,
				'tax_class' => $fee->taxable ? $fee->tax_class : 0,
				'total'     => $fee->amount,
				'total_tax' => $fee->tax,
				'taxes'     => array(
					'total' => $fee->tax_data,
				),
				'order_id'  => $order->get_id(),
			) );

			$item->save();

			$order->add_item( $item );

			return $item->get_id();

		} else {

			return $order->add_fee( $fee );
		}
	}


	/**
	 * Order item CRUD compatibility method to update an order coupon.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param int|\WC_Order_Item $item the order item ID
	 * @param array $args {
	 *     The coupon item args.
	 *
	 *     @type string $code         the coupon code
	 *     @type float  $discount     the coupon discount amount
	 *     @type float  $discount_tax the coupon discount tax amount
	 * }
	 * @return int|bool the order item ID or false on failure
	 */
	public static function update_coupon( WC_Order $order, $item, $args ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			if ( is_numeric( $item ) ) {
				$item = $order->get_item( $item );
			}

			if ( ! is_object( $item ) || ! $item->is_type( 'coupon' ) ) {
				return false;
			}

			if ( ! $order->get_id() ) {
				$order->save();
			}

			$item->set_order_id( $order->get_id() );
			$item->set_props( $args );
			$item->save();

			return $item->get_id();

		} else {

			// convert 2.7.0+ args for backwards compatibility
			if ( isset( $args['discount'] ) ) {
				$args['discount_amount'] = $args['discount'];
			}
			if ( isset( $args['discount_tax'] ) ) {
				$args['discount_amount_tax'] = $args['discount_tax'];
			}

			return $order->update_coupon( $item, $args );
		}
	}


	/**
	 * Order item CRUD compatibility method to update an order fee.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param int|\WC_Order_Item $item the order item ID
	 * @param array $args {
	 *     The fee item args.
	 *
	 *     @type string $name       the fee name
	 *     @type string $tax_class  the fee's tax class
	 *     @type float  $line_total the fee total amount
	 *     @type float  $line_tax   the fee tax amount
	 * }
	 * @return int|bool the order item ID or false on failure
	 */
	public static function update_fee( WC_Order $order, $item, $args ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			if ( is_numeric( $item ) ) {
				$item = $order->get_item( $item );
			}

			if ( ! is_object( $item ) || ! $item->is_type( 'fee' ) ) {
				return false;
			}

			if ( ! $order->get_id() ) {
				$order->save();
			}

			$item->set_order_id( $order->get_id() );
			$item->set_props( $args );
			$item->save();

			return $item->get_id();

		} else {

			return $order->update_fee( $item, $args );
		}
	}


	/**
	 * Backports wc_reduce_stock_levels() to pre-2.7.0
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 */
	public static function reduce_stock_levels( WC_Order $order ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {
			wc_reduce_stock_levels( $order->get_id() );
		} else {
			$order->reduce_order_stock();
		}
	}


	/**
	 * Backports wc_update_total_sales_counts() to pre-2.7.0
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 */
	public static function update_total_sales_counts( WC_Order $order ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {
			wc_update_total_sales_counts( $order->get_id() );
		} else {
			$order->record_product_sales();
		}
	}


}


endif; // Class exists check
