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

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( __NAMESPACE__ . '\\External_Checkout' ) ) :

/**
 * Base class to set up an external checkout integration.
 *
 * @since 5.10.0
 */
abstract class External_Checkout {


	/** @var string external checkout ID */
	protected $id;

	/** @var string external checkout human-readable label (used in notices and log entries) */
	protected $label;

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
	 * Initializes the handlers.
	 *
	 * @since 5.10.0
	 */
	protected function init() {

		if ( is_admin() && ! is_ajax() ) {
			$this->init_admin();
		} elseif ( $this->get_processing_gateway() && $this->get_plugin()->get_id() === $this->get_processing_gateway()->get_plugin()->get_id() ) {
			$this->init_ajax();
			$this->init_frontend();
		}
	}


	/**
	 * Initializes the admin handler.
	 *
	 * @since 5.10.0
	 */
	abstract protected function init_admin();


	/**
	 * Initializes the AJAX handler.
	 *
	 * @since 5.10.0
	 */
	abstract protected function init_ajax();


	/**
	 * Initializes the frontend handler.
	 *
	 * @since 5.10.0
	 */
	abstract protected function init_frontend();


	/**
	 * Checks if the external checkout provides the customer billing address to WC before payment confirmation.
	 *
	 * Each external checkout handler should implement this method according to the external checkout behavior.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	abstract public function is_billing_address_available_before_payment();


	/**
	 * Gets the configured display locations.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	public function get_display_locations() {

		return get_option( "sv_wc_{$this->id}_display_locations", [] );
	}


	/**
	 * Adds a log entry to the gateway's debug log.
	 *
	 * @since 5.10.0
	 *
	 * @param string $message the log message to add
	 */
	public function log( $message ) {

		/** @var SV_WC_Payment_Gateway $gateway */
		$gateway = $this->get_processing_gateway();

		if ( ! $gateway ) {
			return;
		}

		if ( $gateway->debug_log() ) {
			$gateway->get_plugin()->log( "[{$this->label}] $message", $gateway->get_id() );
		}
	}


	/**
	 * Simulates a successful gateway payment response.
	 *
	 * This provides an easy way for merchants to test that their settings are correctly configured and communicating
	 * with the external checkout provider without processing actual payments to test.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Order $order order object
	 * @return array
	 */
	protected function process_test_payment( \WC_Order $order ) {

		$order->payment_complete();

		WC()->cart->empty_cart();

		return [
			'result'   => 'success',
			'redirect' => $this->get_processing_gateway()->get_return_url( $order ),
		];
	}


	/**
	 * Determines if the external checkout is available.
	 *
	 * Each handler can override this method to add availability requirements.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_available() {

		$is_available = $this->is_configured();

		$accepted_currencies = $this->get_accepted_currencies();

		if ( ! empty( $accepted_currencies ) ) {

			$is_available = $is_available && in_array( get_woocommerce_currency(), $accepted_currencies, true );
		}

		return $is_available;
	}


	/**
	 * Determines if the external checkout settings are properly configured.
	 *
	 * Each handler can override this method to add configuration requirements.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_configured() {

		if ( ! $this->get_processing_gateway() ) {
			return false;
		}

		return $this->is_enabled() && $this->get_processing_gateway()->is_enabled();
	}


	/**
	 * Determines if the external checkout is enabled.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return 'yes' === get_option( "sv_wc_{$this->id}_enabled" );
	}


	/**
	 * Determines if test mode is enabled.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_test_mode() {

		return 'yes' === get_option( "sv_wc_{$this->id}_test_mode" );
	}


	/**
	 * Gets the gateways that declare support for this external checkout flow.
	 *
	 * @since 5.10.0
	 *
	 * @return array the supporting gateways as `$gateway_id => \SV_WC_Payment_Gateway`
	 */
	public function get_supporting_gateways() {

		$available_gateways  = WC()->payment_gateways->get_available_payment_gateways();
		$supporting_gateways = [];

		foreach ( $available_gateways as $key => $gateway ) {

			$method_name = "supports_{$this->id}";

			if ( method_exists( $gateway, $method_name ) && $gateway->$method_name() ) {
				$supporting_gateways[ $gateway->get_id() ] = $gateway;
			}
		}

		return $supporting_gateways;
	}


	/**
	 * Gets the gateway set to process transactions for this external checkout flow.
	 *
	 * @since 5.10.0
	 *
	 * @return SV_WC_Payment_Gateway|null
	 */
	public function get_processing_gateway() {

		$gateways = $this->get_supporting_gateways();

		$gateway_id = get_option( "sv_wc_{$this->id}_payment_gateway" );

		return isset( $gateways[ $gateway_id ] ) ? $gateways[ $gateway_id ] : null;
	}


	/**
	 * Gets the external checkout button style.
	 *
	 * @since 5.10.0
	 *
	 * @return string
	 */
	public function get_button_style() {

		return get_option( "sv_wc_{$this->id}_button_style", 'black' );
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


	/**
	 * Gets the external checkout label.
	 *
	 * @since 5.10.0
	 *
	 * @return string
	 */
	public function get_label() {

		return $this->label;
	}


}

endif;
