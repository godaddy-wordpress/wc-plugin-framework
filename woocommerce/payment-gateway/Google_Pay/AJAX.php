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

use SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Google_Pay;
use SkyVerge\WooCommerce\PluginFramework\v5_8_1\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_8_1\SV_WC_Payment_Gateway_Exception;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_8_1\\Payment_Gateway\\Google_Pay\\AJAX' ) ) :


/**
 * The Google Pay AJAX handler.
 *
 * @since 5.9.0-dev.1
 */
class AJAX {


	/** @var \SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Google_Pay $handler the Google Pay handler instance */
	protected $handler;


	/**
	 * Constructs the class.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @param \SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Google_Pay $handler the Google Pay handler instance
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
	 * @since 5.9.0-dev.1
	 */
	protected function add_hooks() {

		$gateway_id = $this->get_handler()->get_processing_gateway()->get_id();

		add_action( "wp_ajax_wc_{$gateway_id}_google_pay_get_transaction_info",        [ $this, 'get_transaction_info' ] );
		add_action( "wp_ajax_nopriv_wc_{$gateway_id}_google_pay_get_transaction_info", [ $this, 'get_transaction_info' ] );

		// process the payment
		add_action( "wp_ajax_wc_{$gateway_id}_google_pay_process_payment",        [ $this, 'process_payment' ] );
		add_action( "wp_ajax_nopriv_wc_{$gateway_id}_google_pay_process_payment", [ $this, 'process_payment' ] );
	}


	/**
	 * Gets Google transaction info based on WooCommerce cart data.
	 *
	 * @internal
	 *
	 * @since 5.9.0-dev.1
	 */
	public function get_transaction_info() {

		$this->get_handler()->log( 'Getting Google transaction info' );

		try {

			$transaction_info = $this->get_handler()->get_transaction_info( WC()->cart );

			$this->get_handler()->log( "Google transaction info:\n" . print_r( $transaction_info, true ) );

			wp_send_json_success( json_encode( $transaction_info ) );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			$this->get_handler()->log( 'Could not build transaction info. ' . $e->getMessage() );

			wp_send_json_error( array(
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			) );
		}
	}


	/**
	 * Processes the payment after the Google Pay authorization.
	 *
	 * @internal
	 *
	 * @since 5.9.0-dev.1
	 */
	public function process_payment() {

		$this->get_handler()->log( 'Processing payment' );

		check_ajax_referer( 'wc_' . $this->get_handler()->get_processing_gateway()->get_id() . '_google_pay_process_payment', 'nonce' );

		$payment_method_data = stripslashes( SV_WC_Helper::get_posted_value( 'paymentMethod' ) );

		try {

			$result = $this->get_handler()->process_payment( $payment_method_data );

			wp_send_json_success( $result );

		} catch ( \Exception $e ) {

			$this->get_handler()->log( 'Payment failed. ' . $e->getMessage() );

			wp_send_json_error( array(
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			) );
		}
	}


	/**
	 * Gets the Google Pay handler instance.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @return Google_Pay
	 */
	protected function get_handler() {

		return $this->handler;
	}


}


endif;