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

namespace SkyVerge\WooCommerce\PluginFramework\v5_12_1;

use Automattic\WooCommerce\Admin\Overrides\Order;
use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Utilities\COTMigrationUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_12_1\\SV_WC_Order_Compatibility' ) ) :


/**
 * WooCommerce order compatibility class.
 *
 * @since 4.6.0
 */
#[\AllowDynamicProperties]
class SV_WC_Order_Compatibility extends SV_WC_Data_Compatibility {


	/**
	 * Gets the formatted metadata for an order item.
	 *
	 * @since 4.6.5
	 * @deprecated 5.11.0 prefer using {@see \WC_Order_Item::get_formatted_meta_data()}
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
	 * @since 5.11.0
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
	 * @NOTE consider using {@see \WC_Order::get_edit_order_url()} whenever possible
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
	 * @since 5.11.0
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
			&& $_GET['page'] === 'wc-orders'
			&& ! static::is_order_edit_screen();
	}


	/**
	 * Determines if the current orders screen is for orders of a specific status.
	 *
	 * @since 5.11.0
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
	 * @since 5.11.0
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
	 * Determines if the current admin page is for any kind of order screen.
	 *
	 * @since 5.11.0
	 *
	 * @return bool
	 */
	public static function is_order_screen() : bool {

		return static::is_orders_screen()
			|| static::is_order_edit_screen();
	}


	/**
	 * Gets the ID of the order for the current edit screen.
	 *
	 * @since 5.11.0
	 *
	 * @return int|null
	 */
	public static function get_order_id_for_order_edit_screen() : ?int {
		global $post, $theorder;

		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			return $theorder instanceof \WC_Abstract_Order && ! $theorder instanceof \WC_Subscription && static::is_order_edit_screen()
				? $theorder->get_id()
				: null;
		}

		return $post->ID ?? null;
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
	 * @since 5.11.0
	 *
	 * @return string
	 */
	public static function get_order_screen_id() : string {

		if ( is_callable( OrderUtil::class . '::get_order_admin_screen' ) ) {
			return OrderUtil::get_order_admin_screen();
		} elseif ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'woocommerce_page_wc-orders';
		}

		return 'shop_order';
	}


	/**
	 * Gets the orders table.
	 *
	 * @return string
	 */
	public static function get_orders_table() : string
	{
		global $wpdb;

		return SV_WC_Plugin_Compatibility::is_hpos_enabled()
			? OrdersTableDataStore::get_orders_table_name()
			: $wpdb->posts;
	}


	/**
	 * Gets the orders meta table.
	 *
	 * @return string
	 */
	public static function get_orders_meta_table() : string
	{
		global $wpdb;

		return SV_WC_Plugin_Compatibility::is_hpos_enabled()
			? OrdersTableDataStore::get_meta_table_name()
			: $wpdb->postmeta;
	}


	/**
	 * Determines whether a given identifier is a WooCommerce order or not, according to HPOS availability.
	 *
	 * @see OrderUtil::get_order_type()
	 *
	 * @since 5.11.0
	 *
	 * @param int|\WP_Post|\WC_Order|null $post_order_or_id identifier of a possible order
	 * @param string|string[] $order_type the order type, defaults to shop_order, can specify multiple types
	 * @return bool
	 */
	public static function is_order( $post_order_or_id, $order_type = 'shop_order' ) : bool {

		if ( ! $post_order_or_id ) {
			return false;
		}

		if ( $post_order_or_id instanceof \WC_Subscription ) {

			return false;

		} elseif ( $post_order_or_id instanceof \WC_Abstract_Order ) {

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
	 * @since 5.11.0
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
	 * @since 5.11.0
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
	 * @since 5.11.0
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
	 * @since 5.11.0
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
	 * @since 5.11.0
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
	 * @since 5.11.0
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


	/**
	 * Gets the list of order post types.
	 *
	 * @since 5.11.6
	 *
	 * @return string[]
	 */
	public static function get_order_post_types(): array {

		$order_post_types = ['shop_order'];

		/** @see \Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer */
		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			$order_post_types[] = 'shop_order_placehold';
		}

		return $order_post_types;
	}

}


endif;
