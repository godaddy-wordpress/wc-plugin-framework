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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Google-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Google_Pay;

use SkyVerge\WooCommerce\PluginFramework\v5_8_1\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_8_1\SV_WC_Payment_Gateway_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_8_1\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_8_1\\Payment_Gateway\\Google_Pay\\Google_Pay' ) ) :


/**
 * Sets up Google Pay support.
 *
 * @since 5.9.0-dev.1
 */
class Google_Pay {


	/** @var Admin the admin instance */
	protected $admin;

	/** @var Frontend the frontend instance */
	protected $frontend;

	/** @var SV_WC_Payment_Gateway_Plugin the plugin instance */
	protected $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the plugin instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->init();
	}


	/**
	 * Initializes the Google Pay handlers.
	 *
	 * @since 5.9.0-dev.1
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
	 * @since 5.9.0-dev.1
	 */
	protected function init_admin() {

		$this->admin = new Admin( $this );
	}


	/**
	 * Initializes the frontend handler.
	 *
	 * @since 5.9.0-dev.1
	 */
	protected function init_frontend() {

		$this->frontend = new Frontend( $this->get_plugin(), $this );
	}


	/**
	 * Determines if Google Pay is available.
	 *
	 * This does not indicate browser support or a user's ability, but rather
	 * that Google Pay is properly configured and ready to be initiated by the
	 * Google Pay JS.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return bool
	 */
	public function is_available() {

		$is_available = $this->is_configured();

		$accepted_currencies = $this->get_accepted_currencies();

		if ( ! empty( $accepted_currencies ) ) {

			$is_available = $is_available && in_array( get_woocommerce_currency(), $accepted_currencies, true );
		}

		/**
		 * Filters whether Google Pay should be made available to users.
		 *
		 * @since 5.9.0-dev.1
		 * @param bool $is_available
		 */
		return apply_filters( 'sv_wc_google_pay_is_available', $is_available );
	}


	/**
	 * Determines if Google Pay settings are properly configured.
	 *
	 * @since 5.9.0-dev.1
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
	 * Determines if Google Pay is enabled.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return 'yes' === get_option( 'sv_wc_google_pay_enabled' );
	}


	/**
	 * Determines if test mode is enabled.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return bool
	 */
	public function is_test_mode() {

		return 'yes' === get_option( 'sv_wc_google_pay_test_mode' );
	}


	/**
	 * Gets the currencies accepted by the gateway's Google Pay integration.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return array
	 */
	public function get_accepted_currencies() {

		$currencies = ( $this->get_processing_gateway() ) ? $this->get_processing_gateway()->get_google_pay_currencies() : array();

		/**
		 * Filters the currencies accepted by the gateway's Google Pay integration.
		 *
		 * @since 5.9.0-dev.1
		 * @return array
		 */
		return apply_filters( 'sv_wc_google_pay_accepted_currencies', $currencies );
	}


	/**
	 * Gets the supported networks for Google Pay.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return array
	 */
	public function get_supported_networks() {

		$accepted_card_types = ( $this->get_processing_gateway() ) ? $this->get_processing_gateway()->get_card_types() : array();

		$accepted_card_types = array_map( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_8_1\\SV_WC_Payment_Gateway_Helper::normalize_card_type', $accepted_card_types );

		$valid_networks = array(
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_AMEX       => 'AMEX',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_DISCOVER   => 'DISCOVER',
			'interac'                                          => 'INTERAC',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_JCB        => 'JCB',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD => 'MASTERCARD',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA       => 'VISA',
		);

		$networks = array_intersect_key( $valid_networks, array_flip( $accepted_card_types ) );

		/**
		 * Filters the supported Google Pay networks (card types).
		 *
		 * @since 5.9.0-dev.1
		 *
		 * @param array $networks the supported networks
		 * @param \SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Google_Pay\Google_Pay $handler the Google Pay handler
		 */
		return apply_filters( 'sv_wc_google_pay_supported_networks', array_values( $networks ), $this );
	}


	/**
	 * Gets the gateways that declare Google Pay support.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return array the supporting gateways as `$gateway_id => \SV_WC_Payment_Gateway`
	 */
	public function get_supporting_gateways() {

		$available_gateways  = $this->get_plugin()->get_gateways();
		$supporting_gateways = array();

		foreach ( $available_gateways as $key => $gateway ) {

			if ( $gateway->supports_google_pay() ) {
				$supporting_gateways[ $gateway->get_id() ] = $gateway;
			}
		}

		return $supporting_gateways;
	}


	/**
	 * Gets the gateway set to process Google Pay transactions.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return SV_WC_Payment_Gateway|null
	 */
	public function get_processing_gateway() {

		$gateways = $this->get_supporting_gateways();

		$gateway_id = get_option( 'sv_wc_google_pay_payment_gateway' );

		return isset( $gateways[ $gateway_id ] ) ? $gateways[ $gateway_id ] : null;
	}


	/**
	 * Gets the Google Pay button style.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return string
	 */
	public function get_button_style() {

		return get_option( 'sv_wc_google_pay_button_style', 'black' );
	}


	/**
	 * Gets the gateway plugin instance.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return SV_WC_Payment_Gateway_Plugin
	 */
	public function get_plugin() {

		return $this->plugin;
	}


}


endif;
