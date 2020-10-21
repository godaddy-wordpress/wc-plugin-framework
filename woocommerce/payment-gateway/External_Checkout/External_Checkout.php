<?php

/**
 * WooCommerce Payment Gateway Framework
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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/External_Checkout
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\External_Checkout\\External_Checkout' ) ) :


/**
 * Sets up external checkout support.
 *
 * @since 5.10.0
 */
class External_Checkout {


	/** @var Frontend the frontend instance */
	protected $frontend;

	/** @var SV_WC_Payment_Gateway_Plugin the plugin instance */
	protected $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 5.10.0
	 *
 	 * @param SV_WC_Payment_Gateway_Plugin $plugin the plugin instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->init();
	}


	/**
	 * Initializes the external checkout handlers.
	 *
	 * @since 5.10.0
	 */
	protected function init() {

		if ( is_admin() && ! is_ajax() ) {
			$this->init_admin();
		} else {
			$this->init_frontend();
		}
	}


	/**
	 * Initializes the admin handler.
	 *
	 * @since 5.10.0
	 */
	protected function init_admin() {

		// TODO: add external checkout admin handler class
		// $this->admin = new Admin( $this );
	}


	/**
	 * Initializes the frontend handler.
	 *
	 * @since 5.10.0
	 */
	protected function init_frontend() {

		 $this->frontend = new Frontend( $this->get_plugin(), $this );
	}


	/**
	 * Gets all external checkout handlers.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	public function get_handlers() {

		/**
		 * Filters all external checkout handlers.
		 *
		 * @since 5.10.0
		 * @return array
		 */
		return apply_filters( 'sv_wc_external_checkout_handlers', [] );
	}


	/**
	 * Determines if any external checkout integration is available.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_available() {

		foreach ( $this->get_handlers() as $handler ) {
			if ( $handler->is_available() ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Gets the configured display locations for all external checkouts.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	public function get_display_locations() {

		$display_locations = [];

		foreach ( $this->get_handlers() as $handler ) {

			$display_locations= array_merge( $display_locations, $handler->get_display_locations() );
		}

		return $display_locations;
	}


	/**
	 * Gets the gateway plugin instance.
	 *
	 * @since 5.10.0
	 *
	 * @return SV_WC_Payment_Gateway_Plugin
	 */
	public function get_plugin() {

		return $this->plugin;
	}


}


endif;
