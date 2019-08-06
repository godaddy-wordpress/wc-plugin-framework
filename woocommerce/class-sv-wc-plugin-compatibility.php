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
 * @copyright Copyright (c) 2013-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_4_1;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_4_1\\SV_WC_Plugin_Compatibility' ) ) :

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
 * + Core 2.6.14 - 3.3.x
 * + Subscriptions 2.2.x
 *
 * // TODO: move to /compatibility
 *
 * @since 2.0.0
 */
class SV_WC_Plugin_Compatibility {


	/**
	 * Gets the statuses that are considered "paid".
	 *
	 * @since 5.1.0
	 *
	 * @return array
	 */
	public static function wc_get_is_paid_statuses() {

		if ( self::is_wc_version_gte_3_0() ) {
			return wc_get_is_paid_statuses();
		} else {
			return (array) apply_filters( 'woocommerce_order_is_paid_statuses', array( 'processing', 'completed' ) );
		}
	}


	/**
	 * Logs a doing_it_wrong message.
	 *
	 * Backports wc_doing_it_wrong() to WC 2.6.
	 *
	 * @since 5.0.1
	 *
	 * @param string $function function used
	 * @param string $message message to log
	 * @param string $version version the message was added in
	 */
	public static function wc_doing_it_wrong( $function, $message, $version ) {

		if ( self::is_wc_version_gte( '3.0' ) ) {

			wc_doing_it_wrong( $function, $message, $version );

		} else {

			$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

			if ( is_ajax() ) {

				do_action( 'doing_it_wrong_run', $function, $message, $version );
				error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );

			} else {

				_doing_it_wrong( $function, $message, $version );
			}
		}
	}


	/**
	 * Formats a date for output.
	 *
	 * Backports WC 3.0.0's wc_format_datetime() to older versions.
	 *
	 * @since  4.6.0
	 *
	 * @param \WC_DateTime|SV_WC_DateTime $date date object
	 * @param string $format date format
	 * @return string
	 */
	public static function wc_format_datetime( $date, $format = '' ) {

		if ( self::is_wc_version_gte_3_0() ) {

			return wc_format_datetime( $date, $format );

		} else {

			if ( ! $format ) {
				$format = wc_date_format();
			}

			if ( ! is_a( $date, '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_4_1\\SV_WC_DateTime' ) ) { // TODO: verify this {CW 2017-07-18}
				return '';
			}

			return $date->date_i18n( $format );
		}
	}


	/**
	 * Logs a deprecated function notice.
	 *
	 * @since  5.0.0
	 *
	 * @param  string $function deprecated function name
	 * @param  string $version deprecated-since version
	 * @param  string $replacement replacement function name
	 */
	public static function wc_deprecated_function( $function, $version, $replacement = null ) {

		if ( self::is_wc_version_gte_3_0() ) {

			wc_deprecated_function( $function, $version, $replacement );

		} else {

			if ( is_ajax() ) {
				do_action( 'deprecated_function_run', $function, $replacement, $version );
				$log_string  = "The {$function} function is deprecated since version {$version}.";
				$log_string .= $replacement ? " Replace with {$replacement}." : '';
				error_log( $log_string );
			} else {
				_deprecated_function( $function, $version, $replacement );
			}
		}
	}


	/**
	 * Retrieves a list of the latest available WooCommerce versions.
	 *
	 * Excludes betas, release candidates and development versions.
	 * Versions are sorted from most recent to least recent.
	 *
	 * @since 5.4.1
	 *
	 * @return string[] array of semver strings
	 */
	public static function get_latest_wc_versions() {

		$latest_wc_versions = get_transient( 'sv_wc_plugin_wc_versions' );

		if ( ! is_array( $latest_wc_versions ) ) {

			/** @link https://codex.wordpress.org/WordPress.org_API */
			$wp_org_request = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.0/woocommerce.json', [ 'timeout' => 1 ] );

			if ( is_array( $wp_org_request ) && isset( $wp_org_request['body'] ) ) {

				$plugin_info = json_decode( $wp_org_request['body'], true );

				if ( is_array( $plugin_info ) && ! empty( $plugin_info['versions'] ) && is_array( $plugin_info['versions'] ) ) {

					$latest_wc_versions = [];

					// reverse array as WordPress supplies oldest version first, newest last
					foreach ( array_keys( array_reverse( $plugin_info['versions'] ) ) as $wc_version ) {

						// skip trunk, release candidates, betas and other non-final or irregular versions
						if (
							   is_string( $wc_version )
							&& '' !== $wc_version
							&& is_numeric( $wc_version[0] )
							&& false === strpos( $wc_version, '-' )
						) {
							$latest_wc_versions[] = $wc_version;
						}
					}

					set_transient( 'sv_wc_plugin_wc_versions', $latest_wc_versions, WEEK_IN_SECONDS );
				}
			}
		}

		return is_array( $latest_wc_versions ) ? $latest_wc_versions : [];
	}


	/**
	 * Helper method to get the version of the currently installed WooCommerce
	 *
	 * @since 3.0.0
	 * @return string woocommerce version number or null
	 */
	public static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}


	/**
	 * Determines if the installed version of WooCommerce is 3.0 or greater.
	 *
	 * @since 4.6.0
	 * @return bool
	 */
	public static function is_wc_version_gte_3_0() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '3.0', '>=' );
	}


	/**
	 * Determines if the installed version of WooCommerce is less than 3.0.
	 *
	 * @since 4.6.0
	 * @return bool
	 */
	public static function is_wc_version_lt_3_0() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '3.0', '<' );
	}


	/**
	 * Determines if the installed version of WooCommerce is 3.1 or greater.
	 *
	 * @since 4.6.5
	 * @return bool
	 */
	public static function is_wc_version_gte_3_1() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '3.1', '>=' );
	}


	/**
	 * Determines if the installed version of WooCommerce is less than 3.1.
	 *
	 * @since 4.6.5
	 * @return bool
	 */
	public static function is_wc_version_lt_3_1() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '3.1', '<' );
	}


	/**
	 * Determines if the installed version of WooCommerce meets or exceeds the
	 * passed version.
	 *
	 * @since 4.7.3
	 *
	 * @param string $version version number to compare
	 * @return bool
	 */
	public static function is_wc_version_gte( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
	}


	/**
	 * Determines if the installed version of WooCommerce is lower than the
	 * passed version.
	 *
	 * @since 4.7.3
	 *
	 * @param string $version version number to compare
	 * @return bool
	 */
	public static function is_wc_version_lt( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '<' );
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


	/** WordPress core ******************************************************/


	/**
	 * Normalizes a WooCommerce page screen ID.
	 *
	 * Needed because WordPress uses a menu title (which is translatable), not slug, to generate screen ID.
	 * See details in: https://core.trac.wordpress.org/ticket/21454
	 * TODO: Add WP version check when https://core.trac.wordpress.org/ticket/18857 is addressed {BR 2016-12-12}
	 *
	 * @since 4.6.0
	 * @param string $slug slug for the screen ID to normalize (minus `woocommerce_page_`)
	 * @return string normalized screen ID
	 */
	public static function normalize_wc_screen_id( $slug = 'wc-settings' ) {

		// The textdomain usage is intentional here, we need to match the menu title.
		$prefix = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );

		return $prefix . '_page_' . $slug;
	}


	/**
	 * Converts a shorthand byte value to an integer byte value.
	 *
	 * Wrapper for wp_convert_hr_to_bytes(), moved to load.php in WordPress 4.6 from media.php
	 *
	 * Based on ActionScheduler's compat wrapper for the same function:
	 * ActionScheduler_Compatibility::convert_hr_to_bytes()
	 *
	 * @link https://secure.php.net/manual/en/function.ini-get.php
	 * @link https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
	 *
	 * @since 5.3.1
	 *
	 * @param string $value A (PHP ini) byte value, either shorthand or ordinary.
	 * @return int An integer byte value.
	 */
	public static function convert_hr_to_bytes( $value ) {

		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {

			return wp_convert_hr_to_bytes( $value );
		}

		$value = strtolower( trim( $value ) );
		$bytes = (int) $value;

		if ( false !== strpos( $value, 'g' ) ) {

			$bytes *= GB_IN_BYTES;

		} elseif ( false !== strpos( $value, 'm' ) ) {

			$bytes *= MB_IN_BYTES;

		} elseif ( false !== strpos( $value, 'k' ) ) {

			$bytes *= KB_IN_BYTES;
		}

		// deal with large (float) values which run into the maximum integer size
		return min( $bytes, PHP_INT_MAX );
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
	 * @return string WooCommerce Subscriptions version number or null if not found.
	 */
	protected static function get_wc_subscriptions_version() {

		return class_exists( 'WC_Subscriptions' ) && ! empty( \WC_Subscriptions::$version ) ? \WC_Subscriptions::$version : null;
	}


}


endif; // Class exists check
