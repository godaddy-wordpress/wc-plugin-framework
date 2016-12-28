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
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Order_Compatibility' ) ) :

/**
 * WooCommerce order compatibility class.
 *
 * @since 4.6.0-dev
 */
class SV_WC_Order_Compatibility {


	/**
	 * Gets an order property.
	 *
	 * @see \WC_Abstract_Order for available properties
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param string $prop the property name
	 * @param string $context if 'view' then the value will be filtered
	 * @return string
	 */
	public static function get_prop( WC_Order $order, $prop, $context = 'view' ) {

		$value = '';

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			if ( is_callable( array( $order, "get_{$prop}" ) ) ) {
 				$value = $order->{"get_{$prop}"}( $context );
 			} else {
				$value = $order->get_prop( $prop, $context );
			}

		} else {

			$compat_props = self::get_compat_props();

			// convert the
			if ( isset( $compat_props[ $prop ] ) ) {
				$prop = $compat_props[ $prop ];
			}

			// special handling for the shipping total
			if ( 'order_shipping' === $prop && 'view' === $context ) {
				$prop = 'total_shipping';
			}

			// if this is the 'view' context and there is an accessor method, use it
			if ( is_callable( array( $order, "get_{$prop}" ) ) && 'view' === $context ) {
				$value = $order->{"get_{$prop}"}();
			} else {
				$value = $order->$prop;
			}
		}

		return $value;
	}


	/**
	 * Sets an order's properties.
	 *
	 * Note that this does not save any order data.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param array $props the order properties as $key => $value
	 * @return \WC_Order the order object
	 */
	public static function set_props( WC_Order $order, $props ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			$order->set_props( $props );

		} else {

			$compat_props = self::get_compat_props();

			foreach ( $props as $prop => $value ) {

				if ( isset( $compat_props[ $prop ] ) ) {
					$prop = $compat_props[ $prop ];
				}

				$order->$prop = $value;
			}
		}

		return $order;
	}


	/**
	 * Gets an order meta value.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param string $key the meta key
	 * @param bool $single whether to get the meta as a single item. Defaults to `true`
	 * @param string $context if 'view' then the value will be filtered
	 * @return string
	 */
	public static function get_meta( WC_Order $order, $key = '', $single = true, $context = 'view' ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {
			$value = $order->get_meta( $key, $single, $context );
		} else {
			$value = get_post_meta( $order->id, $key, $single );
		}

		return $value;
	}


	/**
	 * Adds an order meta value.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param string $key the meta key
	 * @param string $value the meta value
	 * @param strint $meta_id Optional. The specific meta ID to update
	 */
	public static function add_meta_data( WC_Order $order, $key, $value, $unique = false ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			$order->add_meta_data( $key, $value, $unique );

			$order->save_meta_data();

		} else {

			add_post_meta( $order->id, $key, $value, $unique );
		}
	}


	/**
	 * Updates an order meta value.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param string $key the meta key
	 * @param string $value the meta value
	 * @param strint $meta_id Optional. The specific meta ID to update
	 */
	public static function update_meta_data( WC_Order $order, $key, $value, $meta_id = '' ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			$order->update_meta_data( $key, $value, $meta_id );

			$order->save_meta_data();

		} else {

			update_post_meta( $order->id, $key, $value );
		}
	}


	/**
	 * Deletes an order meta value.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param string $key the meta key
	 */
	public static function delete_meta_data( WC_Order $order, $key ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_7() ) {

			$order->delete_meta_data( $key );

			$order->save_meta_data();

		} else {

			delete_post_meta( $order->id, $key );
		}
	}


	/**
	 * Order item CRUD compatibility method to add a coupon to an order.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param string $code the coupon code
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
	 * @param int $item_id the order item ID
	 * @param array $args {
	 *     The coupon item args.
	 *
	 *     @type string $code         the coupon code
	 *     @type float  $discount     the coupon discount amount
	 *     @type float  $discount_tax the coupon discount tax amount
	 * }
	 * @return int|bool the order item ID or false on failure
	 */
	public static function update_coupon( WC_Order $order, $item_id, $args ) {

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

			do_action( 'woocommerce_order_update_coupon', $order->get_id(), $item->get_id(), $args );

			return $item->get_id();

		} else {

			// convert 2.7.0+ args for backwards compatibility
			if ( isset( $args['discount'] ) ) {
				$args['discount_amount'] = $args['discount'];
			}
			if ( isset( $args['discount_tax'] ) ) {
				$args['discount_amount_tax'] = $args['discount_tax'];
			}

			return $order->update_coupon( $item_id, $args );
		}
	}


	/**
	 * Order item CRUD compatibility method to update an order fee.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @param int $item_id the order item ID
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
	public static function update_fee( WC_Order $order, $item_id, $args ) {

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

			do_action( 'woocommerce_order_update_fee', $order->get_id(), $item->get_id(), $args );

			return $item->get_id();

		} else {

			return $order->update_fee( $item_id, $args );
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


	/**
	 * Gets the property pairs for compatibility.
	 *
	 * @since 4.6.0-dev
	 * @return array $valid_key => $deprecated_prop
	 */
	protected function get_compat_props() {

		return array(
			'date_completed' => 'completed_date',
			'date_paid'      => 'paid_date',
			'date_modified'  => 'modified_date',
			'date_created'   => 'order_date',
			'customer_id'    => 'customer_user',
			'discount'       => 'cart_discount',
			'discount_tax'   => 'cart_discount_tax',
			'shipping_total' => 'order_shipping',
			'type'           => 'order_type',
			'currency'       => 'order_currency',
			'version'        => 'order_version',
		);
	}


}


endif; // Class exists check
