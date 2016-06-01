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
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Plugin_Compatibility' ) ) :

/**
 * WooCommerce Compatibility Utility Class
 *
 * The unfortunate purpose of this class is to provide a single point of
 * compatibility functions for dealing with supporting multiple versions
 * of WooCommerce and various extensions.
 *
 * The expected procedure is to remove methods from this class, using the
 * latest ones directly in code, as support for older versions of WooCommerce
 * are dropped.
 *
 * Current Compatibility
 * + Core 2.4.13 - 2.6.x
 * + Subscriptions 1.5.x - 2.0.x
 *
 * @since 2.0.0
 */
class SV_WC_Plugin_Compatibility {


	/**
	 * Backports wc_checkout_is_https() to 2.4.x
	 *
	 * @since  4.3.0-beta
	 * @return bool
	 */
	public static function wc_checkout_is_https() {

		if ( self::is_wc_version_gte_2_5() ) {

			return wc_checkout_is_https();

		} else {

			return wc_site_is_https() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) || class_exists( 'WordPressHTTPS' ) || strstr( wc_get_page_permalink( 'checkout' ), 'https:' );
		}
	}


	/**
	 * Backports WC_Product::get_id() method to 2.4.x
	 *
	 * @link https://github.com/woothemes/woocommerce/pull/9765
	 *
	 * @since 4.2.0
	 * @param \WC_Product $product product object
	 * @return string|int product ID
	 */
	public static function product_get_id( WC_Product $product ) {

		if ( self::is_wc_version_gte_2_5() ) {

			return $product->get_id();

		} else {

			return $product->is_type( 'variation' ) ? $product->variation_id : $product->id;
		}
	}


	/**
	 * Backports wc_help_tip() to WC 2.4.x
	 *
	 * @link https://github.com/woothemes/woocommerce/pull/9417
	 *
	 * @since 4.2.0
	 * @param string $tip help tip content, HTML allowed if $has_html is true
	 * @param bool $has_html false by default, true to indicate tip content has HTML
	 * @return string help tip HTML, a <span> in WC 2.5, <img> in WC 2.4
	 */
	public static function wc_help_tip( $tip, $has_html = false ) {

		if ( self::is_wc_version_gte_2_5() ) {

			return wc_help_tip( $tip, $has_html );

		} else {

			$tip = $has_html ? wc_sanitize_tooltip( $tip ) : esc_attr( $tip );

			return sprintf( '<img class="help_tip" data-tip="%1$s" src="%2$s" height="16" width="16" />', $tip, esc_url( WC()->plugin_url() ) . '/assets/images/help.png' );
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
	 * Returns true if the installed version of WooCommerce is 2.5 or greater
	 *
	 * @since 4.2.0
	 * @return boolean true if the installed version of WooCommerce is 2.5 or greater
	 */
	public static function is_wc_version_gte_2_5() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.5', '>=' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is less than 2.5
	 *
	 * @since 4.2.0
	 * @return boolean true if the installed version of WooCommerce is less than 2.5
	 */
	public static function is_wc_version_lt_2_5() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.5', '<' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is 2.6 or greater
	 *
	 * @since 4.4.0
	 * @return boolean true if the installed version of WooCommerce is 2.6 or greater
	 */
	public static function is_wc_version_gte_2_6() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.6', '>=' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is less than 2.6
	 *
	 * @since 4.4.0
	 * @return boolean true if the installed version of WooCommerce is less than 2.6
	 */
	public static function is_wc_version_lt_2_6() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.6', '<' );
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


	/** Subscriptions *********************************************************/


	/**
	 * Returns true if the installed version of WooCommerce Subscriptions is
	 * 2.0.0 or greater
	 *
	 * @since 4.1.0
	 * @return boolean
	 */
	public static function is_wc_subscriptions_version_gte_2_0() {

		return self::get_wc_subscriptions_version() && version_compare( self::get_wc_subscriptions_version(), '2.0-beta-1', '>=' );
	}


	/**
	 * Helper method to get the version of the currently installed WooCommerce
	 * Subscriptions
	 *
	 * @since 4.1.0
	 * @return string woocommerce version number or null
	 */
	protected static function get_wc_subscriptions_version() {

		return class_exists( 'WC_Subscriptions' ) && ! empty( WC_Subscriptions::$version ) ? WC_Subscriptions::$version : null;
	}


}


endif; // Class exists check
