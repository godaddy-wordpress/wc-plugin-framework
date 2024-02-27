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

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_12_1\\SV_WC_Subscription_Compatibility' ) ) :

/**
 * WooCommerce subscription compatibility class.
 *
 * @since 5.11.1
 */
#[\AllowDynamicProperties]
class SV_WC_Subscription_Compatibility extends SV_WC_Data_Compatibility {

	/**
	 * Gets the admin screen ID for subscriptions.
	 *
	 * @since 5.11.1
	 *
	 * @return string
	 */
	public static function get_subscripton_screen_id() : string {

		if ( SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-subscription' ) : 'woocommerce_page_wc-orders--shop_subscription';
		}

		return 'shop_subscription';
	}


	/**
	 * Determines if the current admin screen is for adding or editing a subscription.
	 *
	 * @since 5.11.1
	 *
	 * @return bool
	 */
	public static function is_subscription_edit_screen() : bool {

		$current_screen = SV_WC_Helper::get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		if ( ! SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			return 'shop_subscription' === $current_screen->id;
		}

		return static::get_subscripton_screen_id() === $current_screen->id
			&& isset( $_GET['page'], $_GET['action'] )
			&& $_GET['page'] === 'wc-orders--shop_subscription'
			&& in_array( $_GET['action'], [ 'new', 'edit' ], true );
	}

	/**
	 * Determines if the current admin screen is for the subscriptions.
	 *
	 * @since 5.11.1
	 *
	 * @return bool
	 */
	public static function is_subscriptions_screen() : bool {

		$current_screen = SV_WC_Helper::get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		if ( ! SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {
			return 'edit-shop_subscription' === $current_screen->id;
		}

		return static::get_subscripton_screen_id() === $current_screen->id
			&& isset( $_GET['page'] )
			&& $_GET['page'] === 'wc-orders--shop_subscription'
			&& ! static::is_subscription_edit_screen();
	}


	/**
	 * Determines if the current admin page is for any kind of subscription screen.
	 *
	 * @since 5.11.1
	 *
	 * @return bool
	 */
	public static function is_subscription_screen() : bool {

		return static::is_subscriptions_screen()
			|| static::is_subscription_edit_screen();
	}


	/**
	 * Determines whether a given identifier is a WooCommerce subscription or not, according to HPOS availability.
	 *
	 * @since 5.11.1
	 *
	 * @param int|\WP_Post|\WC_Subscription|null $post_subscription_or_id identifier of a possible subcription
	 * @return bool
	 */
	public static function is_subscription( $post_subscription_or_id ) : bool {

		if ( $post_subscription_or_id instanceof \WC_Subscription ) {
			return true;
		}

		return SV_WC_Order_Compatibility::is_order( $post_subscription_or_id, 'shop_subscription' );
	}


}

endif;
