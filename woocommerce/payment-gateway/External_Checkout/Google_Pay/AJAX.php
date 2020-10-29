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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/External_Checkout/Google-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Google_Pay;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Exception;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\External_Checkout\\Google_Pay\\AJAX' ) ) :


/**
 * The Google Pay AJAX handler.
 *
 * @since 5.10.0
 */
class AJAX {


	/** @var Google_Pay $handler the Google Pay handler instance */
	protected $handler;


	/**
	 * Constructs the class.
	 *
	 * @since 5.10.0
	 *
	 * @param Google_Pay $handler the Google Pay handler instance
	 */
	public function __construct( Google_Pay $handler ) {

		$this->handler = $handler;

		if ( $this->get_handler()->is_available() ) {
			$this->add_hooks();
		}
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.10.0
	 */
	protected function add_hooks() {

		$gateway_id = $this->get_handler()->get_processing_gateway()->get_id();

		add_action( "wp_ajax_wc_{$gateway_id}_google_pay_get_transaction_info",        [ $this, 'get_transaction_info' ] );
		add_action( "wp_ajax_nopriv_wc_{$gateway_id}_google_pay_get_transaction_info", [ $this, 'get_transaction_info' ] );

		// recalculate the totals after selecting an address or shipping method
		add_action( "wp_ajax_wc_{$gateway_id}_google_pay_recalculate_totals",        [ $this, 'recalculate_totals' ] );
		add_action( "wp_ajax_nopriv_wc_{$gateway_id}_google_pay_recalculate_totals", [ $this, 'recalculate_totals' ] );

		// process the payment
		add_action( "wp_ajax_wc_{$gateway_id}_google_pay_process_payment",        [ $this, 'process_payment' ] );
		add_action( "wp_ajax_nopriv_wc_{$gateway_id}_google_pay_process_payment", [ $this, 'process_payment' ] );
	}


	/**
	 * Gets Google transaction info based on WooCommerce cart data.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 */
	public function get_transaction_info() {

		$this->get_handler()->log( 'Getting Google transaction info' );

		try {

			$product_id = wc_clean( SV_WC_Helper::get_posted_value( 'productID' ) );

			$transaction_info = $this->get_handler()->get_transaction_info( WC()->cart, $product_id );

			$this->get_handler()->log( "Google transaction info:\n" . print_r( $transaction_info, true ) );

			wp_send_json_success( json_encode( $transaction_info ) );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			$this->get_handler()->log( 'Could not build transaction info. ' . $e->getMessage() );

			wp_send_json_error( [
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			] );
		}
	}


	/**
	 * Recalculates the totals after selecting an address or shipping method.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 */
	public function recalculate_totals() {

		$this->get_handler()->log( 'Recalculating totals' );

		check_ajax_referer( 'wc_' . $this->get_handler()->get_processing_gateway()->get_id() . '_google_pay_recalculate_totals', 'nonce' );

		try {

			// if a shipping address is passed, set the shipping address data
			$shipping_address = SV_WC_Helper::get_posted_value( 'shippingAddress' );
			if ( ! empty( $shipping_address ) && is_array( $shipping_address ) ) {

				$shipping_address = wp_parse_args( $shipping_address, [
					'administrativeArea' => null,
					'countryCode'        => null,
					'locality'           => null,
					'postalCode'         => null,
				] );

				$state    = $shipping_address['administrativeArea'];
				$country  = $shipping_address['countryCode'];
				$city     = $shipping_address['locality'];
				$postcode = $shipping_address['postalCode'];

				WC()->customer->set_shipping_city( $city );
				WC()->customer->set_shipping_state( $state );
				WC()->customer->set_shipping_country( $country );
				WC()->customer->set_shipping_postcode( $postcode );

				if ( $country ) {
					WC()->customer->set_calculated_shipping( true );
				}
			}

			$chosen_shipping_methods = ( $method = SV_WC_Helper::get_posted_value( 'shippingMethod' ) ) ? [ wc_clean( $method ) ] : [];

			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

			$product_id = wc_clean( SV_WC_Helper::get_posted_value( 'productID' ) );

			$payment_totals = $this->get_handler()->recalculate_totals( wc_clean( $method ), $product_id );

			$this->get_handler()->log( "New totals:\n" . print_r( $payment_totals, true ) );

			wp_send_json_success( json_encode( $payment_totals ) );

		} catch ( \Exception $e ) {

			$this->get_handler()->log( $e->getMessage() );

			wp_send_json_error( [
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			] );
		}
	}


	/**
	 * Processes the payment after the Google Pay authorization.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 */
	public function process_payment() {

		$this->get_handler()->log( 'Processing payment' );

		check_ajax_referer( 'wc_' . $this->get_handler()->get_processing_gateway()->get_id() . '_google_pay_process_payment', 'nonce' );

		$payment_data = stripslashes( SV_WC_Helper::get_posted_value( 'paymentData' ) );
		$product_id   = wc_clean( SV_WC_Helper::get_posted_value( 'productID' ) );

		try {

			$result = $this->get_handler()->process_payment( $payment_data, $product_id );

			wp_send_json_success( $result );

		} catch ( \Exception $e ) {

			$this->get_handler()->log( 'Payment failed. ' . $e->getMessage() );

			wp_send_json_error( [
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			] );
		}
	}


	/**
	 * Gets the Google Pay handler instance.
	 *
	 * @since 5.10.0
	 *
	 * @return Google_Pay
	 */
	protected function get_handler() {

		return $this->handler;
	}


}


endif;
