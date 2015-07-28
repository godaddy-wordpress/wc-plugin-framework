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
 * @copyright Copyright (c) 2013-2015, SkyVerge, Inc.
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
 * Current Compatibility: 2.2.x - 2.4.x
 *
 * @since 2.0.0
 */
class SV_WC_Plugin_Compatibility {


	/**
	 * Get the page permalink
	 *
	 * Backports wc_page_page_permalink to WC 2.3.3 and lower
	 *
	 * @link https://github.com/woothemes/woocommerce/pull/7438
	 *
	 * @since 4.0.0
	 * @param string $page page - myaccount, edit_address, shop, cart, checkout, pay, view_order, terms
	 * @return string
	 */
	public static function wc_get_page_permalink( $page ) {

		if ( self::is_wc_version_gt( '2.3.3' ) ) {

			return wc_get_page_permalink( $page );

		} else {

			$permalink = get_permalink( wc_get_page_id( $page ) );

			return apply_filters( 'woocommerce_get_' . $page . '_page_permalink', $permalink );
		}
	}


	/**
	 * Get the raw (unescaped) cancel-order URL
	 *
	 * Backports WC_Order::get_cancel_order_url_raw() to WC 2.3.5 and lower
	 *
	 * @since 3.1.1
	 * @param \WC_Order $order
	 * @return string The unescaped cancel-order URL
	 */
	public static function get_cancel_order_url_raw( WC_Order $order, $redirect = '' ) {

		if ( self::is_wc_version_gt( '2.3.5' ) ) {

			return $order->get_cancel_order_url_raw( $redirect );

		} else {

			// Get cancel endpoint
			$cancel_endpoint = self::wc_get_page_permalink( 'cart' );
			if ( ! $cancel_endpoint ) {
				$cancel_endpoint = home_url();
			}

			if ( false === strpos( $cancel_endpoint, '?' ) ) {
				$cancel_endpoint = trailingslashit( $cancel_endpoint );
			}

			return apply_filters( 'woocommerce_get_cancel_order_url_raw', add_query_arg( array(
				'cancel_order' => 'true',
				'order'        => $order->order_key,
				'order_id'     => $order->id,
				'redirect'     => $redirect,
				'_wpnonce'     => wp_create_nonce( 'woocommerce-cancel_order' )
			), $cancel_endpoint ) );
		}
	}


	/**
	 * Helper method to get the version of the currently installed WooCommerce
	 *
	 * @since 3.0.0
	 * @return string woocommerce version number or null
	 */
	protected static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}


	/**
	 * Returns true if the installed version of WooCommerce is 2.3 or greater
	 *
	 * @since 3.1.0
	 * @return boolean true if the installed version of WooCommerce is 2.3 or greater
	 */
	public static function is_wc_version_gte_2_3() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.3', '>=' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is less than 2.3
	 *
	 * @since 3.1.0
	 * @return boolean true if the installed version of WooCommerce is less than 2.3
	 */
	public static function is_wc_version_lt_2_3() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.3', '<' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is 2.4 or greater
	 *
	 * @since 4.0.0
	 * @return boolean true if the installed version of WooCommerce is 2.3 or greater
	 */
	public static function is_wc_version_gte_2_4() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.4', '>=' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is less than 2.4
	 *
	 * @since 4.0.0
	 * @return boolean true if the installed version of WooCommerce is less than 2.4
	 */
	public static function is_wc_version_lt_2_4() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.4', '<' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is greater than $version
	 *
	 * @since 2.0.0
	 * @param string $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gt( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
	}


}


endif; // Class exists check
