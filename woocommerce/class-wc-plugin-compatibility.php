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
 * @copyright Copyright (c) 2013, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_Plugin' ) ) :

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

		if ( self::is_wc_version_gt( '2.0.20' ) ) {
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

		if ( self::is_wc_version_gt( '2.0.20' ) ) {
			wc_add_notice( $message, $notice_type );
		} else {
			global $woocommerce;
			$woocommerce->add_error( $message );
		}
	}


	/**
	 * Compatibility function to queue some JavaScript code to be output in the footer.
	 *
	 * @since 1.0-1
	 * @param string $code javascript
	 */
	public static function wc_enqueue_js( $code ) {

		// can't use gte 2.1 at the moment because 2.1-BETA < 2.1
		if ( self::is_wc_version_gt( '2.0.20' ) ) {
			wc_enqueue_js( $code );
		} else {
			global $woocommerce;
			$woocommerce->add_inline_js( $code );
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
