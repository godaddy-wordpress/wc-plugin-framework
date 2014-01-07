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
 * @package   SkyVerge/WooCommerce/Plugin/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2014, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_Plugin_Compatibility' ) ) :

/**
 * WooCommerce Compatibility Utility Class
 *
 * The unfortunate purpose of this class is to provide a single point of
 * compatibility functions for dealing with supporting multiple versions
 * of WooCommerce.
 *
 * The expected procedure is to remove methods from this class, using the
 * latest ones directly in code, as support for older versions of WooCommerce
 * are dropped.
 *
 * Current Compatibility: 2.0.x - 2.1
 *
 * @since 1.0-1
 */
class SV_WC_Plugin_Compatibility {


	/**
	 * Compatibility function for outputting a woocommerce attribute label
	 *
	 * @since 1.0-1
	 * @param string $label the label to display
	 * @return string the label to display
	 */
	public static function wc_attribute_label( $label ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return wc_attribute_label( $label );
		} else {
			global $woocommerce;
			return $woocommerce->attribute_label( $label );
		}
	}


	/**
	 * Compatibility function to add and store a notice
	 *
	 * @since 1.0-1
	 * @param string $message The text to display in the notice.
	 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
	 */
	public static function wc_add_notice( $message, $notice_type = 'success' ) {

		if ( self::is_wc_version_gte_2_1() ) {
			wc_add_notice( $message, $notice_type );
		} else {
			global $woocommerce;

			if ( 'error' == $notice_type ) {
				$woocommerce->add_error( $message );
			} else {
				$woocommerce->add_message( $message );
			}
		}
	}


	/**
	 * Prints messages and errors which are stored in the session, then clears them.
	 *
	 * @since 1.0-1
	 */
	public static function wc_print_notices() {

		if ( self::is_wc_version_gte_2_1() ) {
			wc_print_notices();
		} else {
			global $woocommerce;
			$woocommerce->show_messages();
		}
	}


	/**
	 * Compatibility function to queue some JavaScript code to be output in the footer.
	 *
	 * @since 1.0-1
	 * @param string $code javascript
	 */
	public static function wc_enqueue_js( $code ) {

		if ( self::is_wc_version_gte_2_1() ) {
			wc_enqueue_js( $code );
		} else {
			global $woocommerce;
			$woocommerce->add_inline_js( $code );
		}
	}


	/**
	 * Forces the provided $content url to https protocol
	 *
	 * @since 1.0-1
	 * @param string $content the url
	 * @return string the url with https protocol
	 */
	public static function force_https_url( $content ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return WC_HTTPS::force_https_url( $content );
		} else {
			global $woocommerce;
			return $woocommerce->force_ssl( $content );
		}
	}


	/**
	 * Returns true if on the pay page, false otherwise
	 *
	 * @since 1.0-1
	 * @return boolean true if on the pay page, false otherwise
	 */
	public static function is_checkout_pay_page() {

		if ( self::is_wc_version_gte_2_1() ) {
			return is_checkout_pay_page();
		} else {
			return is_page( woocommerce_get_page_id( 'pay' ) );
		}
	}


	/**
	 * Returns the order_id if on the checkout pay page
	 *
	 * @since 1.0-1
	 * @return int order identifier
	 */
	public static function get_checkout_pay_page_order_id() {

		if ( self::is_wc_version_gte_2_1() ) {
			global $wp;
			return isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
		} else {
			return isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
		}
	}


	/**
	 * Returns the total shipping cost for the given order
	 *
	 * @since 1.0-1
	 * @return float the shipping total
	 */
	public static function get_total_shipping( $order ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return $order->get_total_shipping();
		} else {
			return $order->get_shipping();
		}
	}


	/**
	 * Returns the value of the custom field named $name, if any.  $name should
	 * not have a leading underscore
	 *
	 * @since 1.0-1
	 * @return mixed order custom field value for field named $name
	 */
	public static function get_order_custom_field( $order, $name ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return $order->$name;
		} else {
			return isset( $order->order_custom_fields[ '_' . $name ][0] ) && $order->order_custom_fields[ '_' . $name ][0] ? $order->order_custom_fields[ '_' . $name ][0] : null;
		}
	}


	/**
	 * Sets WooCommerce messages
	 *
	 * @since 1.0-1
	 */
	public static function set_messages() {

		if ( self::is_wc_version_gte_2_1() ) {
			// no-op in WC 2.1+
		} else {
			global $woocommerce;
			$woocommerce->set_messages();
		}
	}


	/**
	 * Returns a new instance of the woocommerce logger
	 *
	 * @since 1.0-1
	 * @return object logger
	 */
	public static function new_wc_logger() {

		if ( self::is_wc_version_gte_2_1() ) {
			return new WC_Logger();
		} else {
			global $woocommerce;
			return $woocommerce->logger();
		}
	}


	/**
	 * Returns the admin configuration url for the gateway with class name
	 * $gateway_class_name
	 *
	 * @since 1.0-1
	 * @param string $gateway_class_name the gateway class name
	 * @return string admin configuration url for the gateway
	 */
	public static function get_payment_gateway_configuration_url( $gateway_class_name ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway_class_name ) );
		} else {
			return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=' . $gateway_class_name );
		}
	}


	/**
	 * Returns true if the current page is the admin configuration page for the
	 * gateway with class name $gateway_class_name
	 *
	 * @since 1.0-1
	 * @param string $gateway_class_name the gateway class name
	 * @return boolean true if the current page is the admin configuration page for the gateway
	 */
	public static function is_payment_gateway_configuration_page( $gateway_class_name ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] &&
				isset( $_GET['tab'] ) && 'checkout' == $_GET['tab'] &&
				isset( $_GET['section'] ) && strtolower( $gateway_class_name ) == $_GET['section'];
		} else {
			return isset( $_GET['page'] ) && 'woocommerce_settings' == $_GET['page'] &&
				isset( $_GET['tab'] ) && 'payment_gateways' == $_GET['tab'] &&
				isset( $_GET['section'] ) && $gateway_class_name == $_GET['section'];
		}
	}


	/**
	 * Returns the admin configuration url for the shipping method with class name
	 * $gateway_class_name
	 *
	 * @since 1.0-1
	 * @param string $shipping_method_class_name the shipping method class name
	 * @return string admin configuration url for the shipping method
	 */
	public static function get_shipping_method_configuration_url( $shipping_method_class_name ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return admin_url( 'admin.php?page=wc-settings&tab=shipping&section=' . strtolower( $shipping_method_class_name ) );
		} else {
			return admin_url( 'admin.php?page=woocommerce_settings&tab=shipping&section=' . $shipping_method_class_name );
		}
	}


	/**
	 * Returns true if the current page is the admin configuration page for the
	 * shipping method with class name $shipping_method_class_name
	 *
	 * @since 1.0-1
	 * @param string $shipping_method_class_name the shipping method class name
	 * @return boolean true if the current page is the admin configuration page for the shipping method
	 */
	public static function is_shipping_method_configuration_page( $shipping_method_class_name ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] &&
				isset( $_GET['tab'] ) && 'shipping' == $_GET['tab'] &&
				isset( $_GET['section'] ) && strtolower( $shipping_method_class_name ) == $_GET['section'];
		} else {
			return isset( $_GET['page'] ) && 'woocommerce_settings' == $_GET['page'] &&
				isset( $_GET['tab'] ) && 'shipping' == $_GET['tab'] &&
				isset( $_GET['section'] ) && $shipping_method_class_name == $_GET['section'];
		}
	}


	/**
	 * Format decimal numbers ready for DB storage
	 *
	 * Sanitize, remove locale formatting, and optionally round + trim off zeros
	 *
	 * @since 1.0-1
	 * @param  float|string $number Expects either a float or a string with a decimal separator only (no thousands)
	 * @param  mixed $dp number of decimal points to use, blank to use woocommerce_price_num_decimals, or false to avoid all rounding.
	 * @param  boolean $trim_zeros from end of string
	 * @return string
	 */
	public static function wc_format_decimal( $number, $dp = false, $trim_zeros = false ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return wc_format_decimal( $number, $dp, $trim_zeros );
		} else {
			return woocommerce_format_total( $number );
		}
	}


	/**
	 * Get the count of notices added, either for all notices (default) or for one particular notice type specified
	 * by $notice_type.
	 *
	 * @since 1.0-1
	 * @param string $notice_type The name of the notice type - either error, success or notice. [optional]
	 * @return int the notice count
	 */
	public static function wc_notice_count( $notice_type = '' ) {

		if ( self::is_wc_version_gte_2_1() ) {
			return wc_notice_count( $notice_type );
		} else {
			global $woocommerce;

			if ( 'error' == $notice_type ) {
				return $woocommerce->error_count();
			} else {
				return $woocommerce->message_count();
			}
		}
	}


	/**
	 * Returns the array of shipping methods chosen during checkout
	 *
	 * @since 1.0-1
	 * @return array of chosen shipping method ids
	 */
	public static function get_chosen_shipping_methods() {

		if ( self::is_wc_version_gte_2_1() ) {
			$chosen_shipping_methods = self::WC()->session->get( 'chosen_shipping_methods' );
			return $chosen_shipping_methods ? $chosen_shipping_methods : array();
		} else {
			return array( self::WC()->session->get( 'chosen_shipping_method' ) );
		}
	}


	/**
	 * Returns an array of shipping methods used for the order.  This is analogous
	 * to but not a precise replacement for WC_Order::get_shipping_methods(), just
	 * because there can't be a direct equivalent for pre WC 2.1
	 *
	 * @since 1.0-1
	 * @return array of shipping method ids for $order
	 */
	public static function get_shipping_method_ids( $order ) {

		if ( self::get_order_custom_field( $order, 'shipping_method' ) ) {

			// pre WC 2.1 data
			return array( self::get_order_custom_field( $order, 'shipping_method' ) );

		} elseif ( self::is_wc_version_gte_2_1() ) {

			$shipping_method_ids = array();

			foreach ( $order->get_shipping_methods() as $shipping_method ) {
				$shipping_method_ids[] = $shipping_method['method_id'];
			}

			return $shipping_method_ids;
		}

		return array();
	}


	/**
	 * Returns true if the order has the given shipping method
	 *
	 * @since 1.0-1
	 * @return boolean true if $order is shipped by $method_id
	 */
	public static function has_shipping_method( $order, $method_id ) {

		if ( self::get_order_custom_field( $order, 'shipping_method' ) ) {
			// pre WC 2.1 data
			return $method_id == self::get_order_custom_field( $order, 'shipping_method' );
		} elseif ( self::is_wc_version_gte_2_1() ) {
			return $order->has_shipping_method( $method_id );
		}

		// default
		return false;
	}


	/**
	 * Compatibility function to use the new WC_Admin_Meta_Boxes class for the save_errors() function
	 *
	 * @since 1.0-1
	 * @return old save_errors function or new class
	 */
	public static function save_errors( ) {

		if ( self::is_wc_version_gte_2_1() ) {
				WC_Admin_Meta_Boxes::save_errors();
		} else {
				woocommerce_meta_boxes_save_errors();
		}
	}


	/**
	 * Compatibility function to load WooCommerec admin functions in the admin,
	 * primarily needed for woocommerce_admin_fields() and woocommerce_update_options()
	 *
	 * Note these functions have been refactored in the WC_Admin_Settings class in 2.1+,
	 * so plugins targeting those versions can safely use WC_Admin_Settings::output_fields()
	 * and WC_Admin_Settings::save_fields instead
	 *
	 * @since 1.0-1
	 */
	public static function load_wc_admin_functions() {

		if ( ! self::is_wc_version_gte_2_1() ) {

			// woocommerce_admin_fields()
			require_once( self::WC()->plugin_path() . '/admin/woocommerce-admin-settings.php' );

			// woocommerce_update_options()
			require_once( self::WC()->plugin_path() . '/admin/settings/settings-save.php' );

		} else {

			// in 2.1+, wc-admin-functions.php lazy loads the admin settings functions
		}
	}

	
	/**
	 * Compatibility function to get the version of the currently installed WooCommerce
	 *
	 * @since 1.0-1
	 * @return string woocommerce version number or null
	 */
	public static function get_wc_version() {

		// WOOCOMMERCE_VERSION is now WC_VERSION, though WOOCOMMERCE_VERSION is still available for backwards compatibility, we'll disregard it on 2.1+
		if ( defined( 'WC_VERSION' )          && WC_VERSION )          return WC_VERSION;
		if ( defined( 'WOOCOMMERCE_VERSION' ) && WOOCOMMERCE_VERSION ) return WOOCOMMERCE_VERSION;

		return null;
	}


	/**
	 * Returns the WooCommerce instance
	 *
	 * @since 1.0-1
	 * @return WooCommerce woocommerce instance
	 */
	public static function WC() {

		if ( self::is_wc_version_gte_2_1() ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}
	}


	/**
	 * Returns true if the WooCommerce plugin is loaded
	 *
	 * @since 1.0-1
	 * @return boolean true if WooCommerce is loaded
	 */
	public static function is_wc_loaded() {

		if ( self::is_wc_version_gte_2_1() ) {
			return class_exists( 'WooCommerce' );
		} else {
			return class_exists( 'Woocommerce' );
		}
	}


	/**
	 * Returns true if the installed version of WooCommerce is 2.1 or greater
	 *
	 * @since 1.0-1
	 * @return boolean true if the installed version of WooCommerce is 2.1 or greater
	 */
	public static function is_wc_version_gte_2_1() {

		// can't use gte 2.1 at the moment because 2.1-BETA < 2.1
		return self::is_wc_version_gt( '2.0.20' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is greater than $version
	 *
	 * @since 1.0-1
	 * @param string $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gt( $version ) {

		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
	}


}


endif; // Class exists check
