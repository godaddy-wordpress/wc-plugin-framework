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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Visa-Checkout
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Visa_Checkout;

use SkyVerge\WooCommerce\PluginFramework\v5_8_1 as Framework;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( __NAMESPACE__ . '\\Visa_Checkout' ) ) :

/**
 * Sets up Visa Checkout support.
 *
 * @since 5.10.0-dev.1
 */
class Visa_Checkout {


	/** @var string option used to store the ID of the gateway configured to process Visa Checkout transactionns */
	const OPTION_PROCESSING_GATEWAY = 'sv_wc_visa_checkout_payment_gateway';


	/**
	 * Constructs the class.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin plugin instance
	 */
	public function __construct( Framework\SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;
	}


	/**
	 * Gets the gateways that declare Visa Checkout support.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return array the supporting gateways as `$gateway_id => \SV_WC_Payment_Gateway`
	 */
	public function get_supporting_gateways() {

		return array_filter( $this->get_plugin()->get_gateways(), function ( $gateway ) {
			return $gateway->supports_visa_checkout();
		} );
	}


	/**
	 * Gets the gateway set to process Visa Checkout transactions.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return SV_WC_Payment_Gateway|null
	 */
	public function get_processing_gateway() {

		$gateways = $this->get_supporting_gateways();

		$gateway_id = get_option( self::OPTION_PROCESSING_GATEWAY );

		return isset( $gateways[ $gateway_id ] ) ? $gateways[ $gateway_id ] : null;
	}


	/**
	 * Gets the gateway plugin instance.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return SV_WC_Payment_Gateway_Plugin
	 */
	public function get_plugin() {

		return $this->plugin;
	}


}


endif;
