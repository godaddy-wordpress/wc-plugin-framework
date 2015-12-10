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
 * of WooCommerce and various extensions.
 *
 * The expected procedure is to remove methods from this class, using the
 * latest ones directly in code, as support for older versions of WooCommerce
 * are dropped.
 *
 * Current Compatibility
 * + Core 2.3.6 - 2.5.x
 * + Subscriptions 1.5.x - 2.0.x
 *
 * @since 2.0.0
 */
class SV_WC_Plugin_Compatibility {


	/**
	 * Backports WC_Product::get_id() method to 2.4.x and earlier
	 *
	 * @link https://github.com/woothemes/woocommerce/pull/9765
	 *
	 * @since 4.2.0-beta
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
	 * Helper method to get the version of the currently installed WooCommerce
	 *
	 * @since 3.0.0
	 * @return string woocommerce version number or null
	 */
	protected static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
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
	 * Returns true if the installed version of WooCommerce is 2.5 or greater
	 *
	 * @since 4.2.0-beta
	 * @return boolean true if the installed version of WooCommerce is 2.5 or greater
	 */
	public static function is_wc_version_gte_2_5() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.5', '>=' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is less than 2.5
	 *
	 * @since 4.2.0-beta
	 * @return boolean true if the installed version of WooCommerce is less than 2.5
	 */
	public static function is_wc_version_lt_2_5() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.5', '<' );
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
