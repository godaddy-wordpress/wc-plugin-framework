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
 * @copyright Copyright (c) 2013-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_0;

use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\Internal\Utilities\COTMigrationUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_11_0\\SV_WC_Order_Compatibility' ) ) :


/**
 * WooCommerce order compatibility class.
 *
 * @since 4.6.0
 */
class SV_WC_Order_Compatibility extends SV_WC_Data_Compatibility {


	/**
	 * Gets an order's created date.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_created( \WC_Order $order, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_date_created()' );

		return self::get_date_prop( $order, 'created', $context );
	}


	/**
	 * Gets an order's last modified date.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_modified( \WC_Order $order, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_date_modified()' );

		return self::get_date_prop( $order, 'modified', $context );
	}


	/**
	 * Gets an order's paid date.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_paid( \WC_Order $order, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_date_paid()' );

		return self::get_date_prop( $order, 'paid', $context );
	}


	/**
	 * Gets an order's completed date.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_completed( \WC_Order $order, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_date_completed()' );

		return self::get_date_prop( $order, 'completed', $context );
	}


	/**
	 * Gets an order date.
	 *
	 * This should only be used to retrieve WC core date properties.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $type type of date to get
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_prop( \WC_Order $order, $type, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order' );

		$prop = "date_{$type}";
		$date = is_callable( [ $order, "get_{$prop}" ] ) ? $order->{"get_{$prop}"}( $context ) : null;

		return $date;
	}


	/**
	 * Gets an order property.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $object the order object
	 * @param string $prop the property name
	 * @param string $context if 'view' then the value will be filtered
	 * @param array $compat_props compatibility arguments, unused since 5.5.0
	 * @return mixed
	 */
	public static function get_prop( $object, $prop, $context = 'edit', $compat_props = [] ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_prop()' );

		return parent::get_prop( $object, $prop, $context, self::$compat_props );
	}


	/**
	 * Sets an order's properties.
	 *
	 * Note that this does not save any data to the database.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $object the order object
	 * @param array $props the new properties as $key => $value
	 * @param array $compat_props compatibility arguments, unused since 5.5.0
	 * @return bool|\WP_Error
	 */
	public static function set_props( $object, $props, $compat_props = [] ) {

		return parent::set_props( $object, $props, self::$compat_props );
	}


	/**
	 * Adds a coupon to an order item.
	 *
	 * Order item CRUD compatibility method to add a coupon to an order.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 * @param array $code the coupon code
	 * @param int $discount the discount amount.
	 * @param int $discount_tax the discount tax amount.
	 * @return int the order item ID
	 */
	public static function add_coupon( \WC_Order $order, $code = [], $discount = 0, $discount_tax = 0 ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::add_item()' );

		$item = new \WC_Order_Item_Coupon();

		$item->set_props( [
			'code'         => $code,
			'discount'     => $discount,
			'discount_tax' => $discount_tax,
			'order_id'     => $order->get_id(),
		] );

		$item->save();

		$order->add_item( $item );

		return $item->get_id();
	}


	/**
	 * Adds a fee to an order.
	 *
	 * Order item CRUD compatibility method to add a fee to an order.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 * @param object $fee the fee to add
	 * @return int the order item ID
	 */
	public static function add_fee( \WC_Order $order, $fee ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::add_item()' );

		$item = new \WC_Order_Item_Fee();

		$item->set_props( [
			'name'      => $fee->name,
			'tax_class' => $fee->taxable ? $fee->tax_class : 0,
			'total'     => $fee->amount,
			'total_tax' => $fee->tax,
			'taxes'     => [
				'total' => $fee->tax_data,
			],
			'order_id'  => $order->get_id(),
		] );

		$item->save();

		$order->add_item( $item );

		return $item->get_id();
	}


	/**
	 * Adds shipping line to order.
	 *
	 * Order item CRUD compatibility method to add a shipping line to an order.
	 *
	 * @since 4.7.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param \WC_Shipping_Rate $shipping_rate shipping rate to add
	 * @return int the order item ID
	 */
	public static function add_shipping( \WC_Order $order, $shipping_rate ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::add_item()' );

		$item = new \WC_Order_Item_Shipping();

		$item->set_props( [
			'method_title' => $shipping_rate->label,
			'method_id'    => $shipping_rate->id,
			'total'        => wc_format_decimal( $shipping_rate->cost ),
			'taxes'        => $shipping_rate->taxes,
			'order_id'     => $order->get_id(),
		] );

		foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
			$item->save_meta_data();
		}

		$item->save();

		$order->add_item( $item );

		return $item->get_id();
	}


	/**
	 * Adds tax line to an order.
	 *
	 * Order item CRUD compatibility method to add a tax line to an order.
	 *
	 * @since 4.7.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param int $tax_rate_id tax rate ID
	 * @param int|float $tax_amount cart tax amount
	 * @param int|float $shipping_tax_amount shipping tax amount
	 * @return int order item ID
	 * @throws \WC_Data_Exception
	 *
	 */
	public static function add_tax( \WC_Order $order, $tax_rate_id, $tax_amount = 0, $shipping_tax_amount = 0 ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::add_item()' );

		$item = new \WC_Order_Item_Tax();

		$item->set_props( [
			'rate_id'            => $tax_rate_id,
			'tax_total'          => $tax_amount,
			'shipping_tax_total' => $shipping_tax_amount,
		] );

		$item->set_rate( $tax_rate_id );
		$item->set_order_id( $order->get_id() );
		$item->save();

		$order->add_item( $item );

		return $item->get_id();
	}


	/**
	 * Updates an order coupon.
	 *
	 * Order item CRUD compatibility method to update an order coupon.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
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
	 * @throws \WC_Data_Exception
	 */
	public static function update_coupon( \WC_Order $order, $item, $args ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order_Item_Coupon' );

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
	}


	/**
	 * Updates an order fee.
	 *
	 * Order item CRUD compatibility method to update an order fee.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
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
	 * @throws \WC_Data_Exception
	 */
	public static function update_fee( \WC_Order $order, $item, $args ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order_Item_Fee' );

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
	}


	/**
	 * Reduces stock levels for products in order.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 */
	public static function reduce_stock_levels( \WC_Order $order ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_reduce_stock_levels()' );

		wc_reduce_stock_levels( $order->get_id() );
	}


	/**
	 * Updates total product sales count for a given order.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 */
	public static function update_total_sales_counts( \WC_Order $order ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_update_total_sales_counts()' );

		wc_update_total_sales_counts( $order->get_id() );
	}


	/**
	 * Determines if an order has an available shipping address.
	 *
	 * @since 4.6.1
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @return bool
	 */
	public static function has_shipping_address( \WC_Order $order ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::has_shipping_address()' );

		return $order->has_shipping_address();
	}


	/**
	 * Gets the formatted meta data for an order item.
	 *
	 * @since 4.6.5
	 *
	 * @param \WC_Order_Item $item order item object
	 * @param string $hide_prefix prefix for meta that is considered hidden
	 * @param bool $include_all whether to include all meta (attributes, etc...), or just custom fields
	 * @return array $item_meta {
	 *     @type string $label meta field label
	 *     @type mixed $value meta value
 	 * }
	 */
	public static function get_item_formatted_meta_data( $item, $hide_prefix = '_', $include_all = false ) {

		if ( $item instanceof \WC_Order_Item && SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.1' ) ) {

			$meta_data = $item->get_formatted_meta_data( $hide_prefix, $include_all );
			$item_meta = [];

			foreach ( $meta_data as $meta ) {

				$item_meta[] = array(
					'label' => $meta->display_key,
					'value' => $meta->value,
				);
			}

		} else {

			$item_meta = new \WC_Order_Item_Meta( $item );
			$item_meta = $item_meta->get_formatted( $hide_prefix );
		}

		return $item_meta;
	}


	/**
	 * Gets the orders screen admin URL according to HPOS availability.
	 *
	 * @return string
	 */
	public static function get_orders_screen_url() : string {

		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			return admin_url( 'admin.php?page=wc-orders' );
		}

		return admin_url( 'edit.php?post_type=shop_order' );
	}


	/**
	 * Gets the admin Edit screen URL for an order according to HPOS compatibility.
	 *
	 * @see OrderUtil::get_order_admin_edit_url()
	 * @see PageController::get_edit_url()
	 *
	 * @since 5.0.1
	 *
	 * @param \WC_Order|int $order order object or ID
	 * @return string
	 */
	public static function get_edit_order_url( $order ) : string {

		$order_id = $order instanceof \WC_Order ? $order->get_id() : $order;
		$order_id = max((int) $order_id, 0);

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.3' ) ) {
			$order_url = OrderUtil::get_order_admin_edit_url( $order_id );
		} else {
			$order_url = apply_filters( 'woocommerce_get_edit_order_url', admin_url( 'post.php?post=' . absint( $order_id ) ) . '&action=edit', $order );
		}

		return $order_url;
	}


	/**
	 * Determines if the current admin screen is for the orders.
	 *
	 * @since x.y.z
	 *
	 * @return bool
	 */
	public static function is_orders_screen() : bool {

		$current_screen = SV_WC_Helper::get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		if ( ! SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			return 'edit-shop_order' === $current_screen->id;
		}

		return static::get_order_screen_id() === $current_screen->id
			&& isset( $_GET['page'] )
			&& $_GET['page'] === 'wc-orders';
	}


	/**
	 * Determines if the current orders screen is for orders of a specific status.
	 *
	 * @since x.y.z
	 *
	 * @param string|string[] $status one or more statuses to compare
	 * @return bool
	 */
	public static function is_orders_screen_for_status( $status ) : bool {
		global $post_type, $post_status;

		if ( ! SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {

			if ( 'shop_order' !== $post_type ) {
				return false;
			}

			return empty( $status ) || in_array( $post_status, (array) $status, true );
		}

		if ( ! static::is_orders_screen() ) {
			return false;
		}

		return empty( $status ) || ( isset( $_GET['status'] ) && in_array( $_GET['status'], (array) $status, true ) );
	}


	/**
	 * Determines if the current admin screen is for adding or editing an order.
	 *
	 * @since x.y.z
	 *
	 * @return bool
	 */
	public static function is_order_edit_screen() : bool {

		$current_screen = SV_WC_Helper::get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		if ( ! SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			return 'shop_order' === $current_screen->id;
		}

		return static::get_order_screen_id() === $current_screen->id
			&& isset( $_GET['page'], $_GET['action'] )
			&& $_GET['page'] === 'wc-orders'
			&& in_array( $_GET['action'], [ 'new', 'edit' ], true );
	}


	/**
	 * Gets the admin screen ID for orders.
	 *
	 * This method detects the expected orders screen ID according to HPOS availability.
	 * `shop_order` as a registered post type as the screen ID is no longer used when HPOS is active.
	 *
	 * @see OrderUtil::get_order_admin_screen()
	 * @see COTMigrationUtil::get_order_admin_screen()
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	public static function get_order_screen_id( ) : string {

		return SV_WC_Plugin_Compatibility::is_hpos_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';
	}


	/**
	 * Determines whether a given identifier is a WooCommerce order or not, according to HPOS availability.
	 *
	 * @see OrderUtil::get_order_type()
	 *
	 * @since x.y.z
	 *
	 * @param int|\WP_Post|\WC_Order|null $post_order_or_id identifier of a possible order
	 * @param string|string[] $order_type the order type, defaults to shop_order, can specify multiple types
	 * @return bool
	 */
	public static function is_order( $post_order_or_id, $order_type = 'shop_order' ) : bool {

		if ( ! $post_order_or_id ) {
			return false;
		}

		if ( $post_order_or_id instanceof \WC_Abstract_Order ) {

			$found_type = $post_order_or_id->get_type();

		} elseif ( ! SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {

			$found_type = is_numeric( $post_order_or_id ) || $post_order_or_id instanceof \WP_Post ? get_post_type( $post_order_or_id ) : null;

		} else {

			$found_type = OrderUtil::get_order_type( $post_order_or_id );
		}

		return $found_type && in_array( $found_type, (array) $order_type, true );
	}


	/**
	 * Determines whether a given identifier is a WooCommerce refund or not, according to HPOS availability.
	 *
	 * @since x.y.z
	 *
	 * @param int|\WP_Post|\WC_Order|null $order_post_or_id identifier of a possible order
	 * @return bool
	 */
	public static function is_refund( $order_post_or_id ) : bool {

		return static::is_order( $order_post_or_id, 'shop_order_refund' );
	}


	/**
	 * Gets the order meta according to HPOS availability.
	 *
	 * Uses {@see \WC_Order::get_meta()} if HPOS is enabled, otherwise it uses the WordPress {@see get_post_meta()} function.
	 *
	 * @since x.y.z
	 *
	 * @param int|\WC_Order $order order ID or object
	 * @param string $meta_key meta key
	 * @param bool $single return the first found meta with key (true), or all meta sharing the same key (default true)
	 * @return mixed
	 */
	public static function get_order_meta( $order, string $meta_key, bool $single = true ) {

		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {

			$value = $single ? '' : [];
			$order = is_numeric( $order ) && $order > 0 ? wc_get_order( (int) $order ) : $order;

			if ( $order instanceof \WC_Order ) {
				$value = $order->get_meta( $meta_key, $single );
			}

		} else {

			$order_id = $order instanceof \WC_Order ? $order->get_id() : $order;

			$value = is_numeric( $order_id ) && $order_id > 0 ? get_post_meta( (int) $order_id, $meta_key, $single ) : false;
		}

		return $value;
	}


	/**
	 * Updates the order meta according to HPOS availability.
	 *
	 * Uses {@see \WC_Order::update_meta_data()} if HPOS is enabled, otherwise it uses the WordPress {@see update_meta_data()} function.
	 *
	 * @since x.y.z
	 *
	 * @param int|\WC_Order $order order ID or object
	 * @param string $meta_key meta key
	 * @param mixed $meta_value meta value
	 */
	public static function update_order_meta( $order, string $meta_key, $meta_value ) {

		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {

			$order = is_numeric( $order ) && $order > 0 ? wc_get_order( (int) $order ) : $order;

			if ( $order instanceof \WC_Order ) {
				$order->update_meta_data( $meta_key, $meta_value );
				$order->save_meta_data();
			}

		} else {

			$order_id = $order instanceof \WC_Order ? $order->get_id() : $order;

			if ( is_numeric( $order_id ) && $order_id > 0 ) {
				update_post_meta( (int) $order_id, $meta_key, $meta_value );
			}
		}
	}


	/**
	 * Adds the order meta according to HPOS availability.
	 *
	 * Uses {@see \WC_Order::add_meta_data()} if HPOS is enabled, otherwise it uses the WordPress {@see add_meta_data()} function.
	 *
	 * @since x.y.z
	 *
	 * @param int|\WC_Order $order order ID or object
	 * @param string $meta_key meta key
	 * @param mixed $meta_value meta value
	 * @param bool $unique optional - whether the same key should not be added (default false)
	 */
	public static function add_order_meta( $order, string $meta_key, $meta_value, bool $unique = false ) {

		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {

			$order = is_numeric( $order ) && $order > 0 ? wc_get_order( (int) $order ) : $order;

			if ( $order instanceof \WC_Order ) {
				$order->add_meta_data( $meta_key, $meta_value, $unique );
				$order->save_meta_data();
			}

		} else {

			$order_id = $order instanceof \WC_Order ? $order->get_id() : $order;

			if ( is_numeric( $order_id ) && $order_id > 0 ) {
				add_post_meta( (int) $order_id, $meta_key, $meta_value, $unique );
			}
		}
	}


	/**
	 * Deletes the order meta according to HPOS availability.
	 *
	 * Uses {@see \WC_Order::delete_meta_data()} if HPOS is enabled, otherwise it uses the WordPress {@see delete_meta_data()} function.
	 *
	 * @since x.y.z
	 *
	 * @param int|\WC_Order $order order ID or object
	 * @param string $meta_key meta key
	 * @param mixed $meta_value optional (applicable if HPOS is inactive)
	 */
	public static function delete_order_meta( $order, string $meta_key, $meta_value = '' ) {

		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {

			$order = is_numeric( $order ) && $order > 0 ? wc_get_order( (int) $order ) : $order;

			if ( $order instanceof \WC_Order ) {
				$order->delete_meta_data( $meta_key);
				$order->save_meta_data();
			}

		} else {

			$order_id = $order instanceof \WC_Order ? $order->get_id() : $order;

			if ( is_numeric( $order_id ) && $order_id > 0 ) {
				delete_post_meta( (int) $order_id, $meta_key, $meta_value );
			}
		}
	}


	/**
	 * Determines if an order meta exists according to HPOS availability.
	 *
	 * Uses {@see \WC_Order::meta_exists()} if HPOS is enabled, otherwise it uses the WordPress {@see metadata_exists()} function.
	 *
	 * @since x.y.z
	 *
	 * @param int|\WC_Order $order order ID or object
	 * @param string $meta_key meta key
	 * @return bool
	 */
	public static function order_meta_exists( $order, string $meta_key ) : bool {

		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {

			$order = is_numeric( $order ) && $order > 0 ? wc_get_order( (int) $order ) : $order;

			if ( $order instanceof \WC_Order ) {
				return $order->meta_exists( $meta_key );
			}

		} else {

			$order_id = $order instanceof \WC_Order ? $order->get_id() : $order;

			if ( is_numeric( $order_id ) && $order_id > 0 ) {
				return metadata_exists( 'post', (int) $order_id, $meta_key );
			}
		}

		return false;
	}


}


endif;
