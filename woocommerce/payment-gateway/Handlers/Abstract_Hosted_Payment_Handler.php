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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Admin
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Handlers;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as FrameworkBase;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\Handlers\\Abstract_Hosted_Payment_Handler' ) ) :


/**
 * The base hosted payment handler.
 *
 * Gateways can use this for common hosted response handling.
 *
 * @since 5.4.0
 */
abstract class Abstract_Hosted_Payment_Handler extends Abstract_Payment_Handler {


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.4.0
	 */
	protected function add_hooks() {

		parent::add_hooks();

		// renders the payment page
		add_action( 'woocommerce_receipt_' . $this->get_gateway()->get_id(), array( $this, 'payment_page' ) );

		// payment notification listener hook
		if ( ! has_action( 'woocommerce_api_' . $this->get_gateway()->get_id() . '_process_payment', array( $this, 'handle_transaction_response_request' ) ) ) {
			add_action( 'woocommerce_api_' . $this->get_gateway()->get_id() . '_process_payment', array( $this, 'handle_transaction_response_request' ) );
		}
	}


	/**
	 * Processes a new order payment.
	 *
	 * This simply gets the URL for a redirect.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @return array
	 * @throws FrameworkBase\SV_WC_Plugin_Exception
	 */
	public function process_order_payment( \WC_Order $order ) {

		if ( $this->is_redirect() ) {
			$payment_url = add_query_arg( $this->get_order_payment_params( $order ), $this->get_hosted_payment_url() );
		} else {
			$payment_url = $order->get_checkout_payment_url( true );
		}

		return array(
			'result'   => 'success',
			'redirect' => $payment_url,
		);
	}


	/**
	 * Renders the payment page.
	 *
	 * @since 5.4.0
	 *
	 * @param int $order_id order ID
	 */
	public function payment_page( $order_id ) {

		// stub
	}


	/**
	 * Gets payment params for the given order object.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @return array
	 */
	public function get_order_payment_params( \WC_Order $order ) {

		return array();
	}


	/**
	 * Gets the URL for the hosted payment page or form.
	 *
	 * @since 5.4.0
	 *
	 * @return string
	 */
	abstract protected function get_hosted_payment_url();


	/**
	 * Gets the response handler URL.
	 *
	 * @since 5.4.0
	 *
	 * @return string
	 */
	public function get_response_handler_url() {

		return add_query_arg( 'wc-api', $this->get_gateway()->get_id() . '_process_payment', home_url( '/' ) );
	}


	/**
	 * Handles a transaction response request via the wc-api endpoint.
	 *
	 * @since 5.4.0
	 */
	public function handle_transaction_response_request() {

		$order    = null;
		$response = null;

		try {

			// get the transaction response object for the current request
			$response = $this->get_transaction_response( $_REQUEST );

			// log the request
			$this->log_transaction_response_request( $response->to_string_safe() );

			// get the associated order, or die trying
			$order = $this->get_order_from_response( $response );

			// handle the order based on the response
			$this->process_transaction_response( $response, $order );

			$this->do_transaction_response_complete( $order, $response );

			// catch general gateway exceptions, which indicate payment processing failures where the order should be retried
		} catch ( FrameworkBase\SV_WC_Payment_Gateway_Exception $exception ) {

			// try and get a user-friendly message if available
			if ( ( $user_exception = $exception->getPrevious() ) && $user_exception->getMessage() ) {
				$user_message = $user_exception->getMessage();
			} else {
				$user_message = '';
			}

			$this->do_transaction_response_failed( $order, $exception->getMessage(), $user_message, $response );

			// catch other exceptions i.e. for malformed responses, where we don't want the customer to retry the order
		} catch ( \Exception $exception ) {

			if ( WC()->session ) {
				WC()->session->held_order_received_text = __( 'There was a problem processing your order and it is being placed on hold for review. Please contact us to complete the transaction.', 'woocommerce-plugin-framework' );
			}

			// bail out and don't add a customer-facing notice to avoid them resubmitting
			$this->do_transaction_response_invalid( $order, $exception->getMessage(), $response );
		}
	}


	/**
	 * Handles the response when processing is complete.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order|null $order order object, if any
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response API response object, if any
	 */
	protected function do_transaction_response_complete( \WC_Order $order = null, FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		$this->do_transaction_request_response( $response, $this->get_gateway()->get_return_url( $order ) );
	}


	/**
	 * Handles the response when processing has failed.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order|null $order order object, if any
	 * @param string $message error message, for logging
	 * @param string $user_message user-facing message
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response API response object, if any
	 */
	protected function do_transaction_response_failed( \WC_Order $order = null, $message = '', $user_message = '', FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		$this->get_gateway()->add_debug_message( $message, 'error' );

		if ( ! $user_message || ! is_string( $user_message ) ) {
			$user_message = __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-plugin-framework' );
		}

		FrameworkBase\SV_WC_Helper::wc_add_notice( $user_message, 'error' );

		$this->do_transaction_request_response( $response, $order ? $order->get_checkout_payment_url() : '' );
	}


	/**
	 * Handles the response when the response data is invalid.
	 *
	 * This will trigger when there is no way to salvage the payment, i.e. when the response data is invalid.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order|null $order order object, if any
	 * @param string $message error message, for logging
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response API response object, if any
	 */
	protected function do_transaction_response_invalid( \WC_Order $order = null, $message = '', FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		$this->get_gateway()->add_debug_message( $message, 'error' );

		// if we have an order, mark it as held and add an order note
		if ( $order ) {

			if ( $order->is_paid() ) {
				$order->add_order_note( $message );
			} else {
				$this->mark_order_as_held( $order, $message, $response );
			}
		}

		$this->do_transaction_request_response( $response, $order ? $this->get_gateway()->get_return_url( $order ) : '' );
	}


	/**
	 * Handles the final payment request response.
	 *
	 * This is the final step after all payment verification and processing, and runs regardless of the transaction
	 * result.
	 *
	 * @since 5.4.0
	 *
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response
	 * @param string $url
	 */
	protected function do_transaction_request_response( FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null, $url = '' ) {

		// if this is an IPN handler
		if ( $this->is_ipn() ) {
			status_header( 200 );
			die;
		}

		wp_safe_redirect( $url ?: home_url() );
		exit;
	}


	/**
	 * Logs a transaction response request.
	 *
	 * @since 5.4.0
	 *
	 * @param string $request data to log
	 * @param string $message prefix message, like Request: or Response:
	 */
	protected function log_transaction_response_request( $request, $message = '' ) {

		// add log message to WC logger if log/both is enabled
		if ( $this->get_gateway()->debug_log() ) {

			// if a message wasn't provided, make our best effort
			if ( ! $message ) {
				$message = 'Request: %s';
			}

			$this->get_gateway()->get_plugin()->log( sprintf( $message, print_r( $request, true ) ), $this->get_gateway()->get_id() );
		}
	}


	/**
	 * Gets an order object from an API response.
	 *
	 * @since 5.4.0
	 *
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Payment_Notification_Response $response
	 * @return \WC_Order
	 * @throws \Exception
	 */
	protected function get_order_from_response( FrameworkBase\SV_WC_Payment_Gateway_API_Payment_Notification_Response $response ) {

		$order = wc_get_order( $response->get_order_id() );

		// if the order is invalid, bail
		if ( ! $order ) {

			throw new FrameworkBase\SV_WC_API_Exception( sprintf(
			/* translators: Placeholders: %s - a WooCommerce order ID */
				__( 'Could not find order %s', 'woocommerce-plugin-framework' ),
				$response->get_order_id()
			) );
		}

		$order = $this->get_gateway()->get_order( $order );

		$order->payment->account_number = $response->get_account_number();

		if ( $response instanceof FrameworkBase\SV_WC_Payment_Gateway_API_Payment_Notification_Credit_Card_Response ) {

			$order->payment->exp_month = $response->get_exp_month();
			$order->payment->exp_year  = $response->get_exp_year();
			$order->payment->card_type = $response->get_card_type();

		} elseif ( $response instanceof FrameworkBase\SV_WC_Payment_Gateway_API_Payment_Notification_eCheck_Response ) {

			$order->payment->account_type = $response->get_account_type();
			$order->payment->check_number = $response->get_check_number();
		}

		return $order;
	}


	/**
	 * Gets an API response object for the given data.
	 *
	 * @since 5.4.0
	 *
	 * @param array $request_response_data the current request response data
	 * @return FrameworkBase\SV_WC_Payment_Gateway_API_Payment_Notification_Response API response object
	 * @throws FrameworkBase\SV_WC_API_Exception
	 */
	abstract protected function get_transaction_response( $request_response_data );


	/** Conditional methods *******************************************************************************************/


	/**
	 * Determines whether the payment response is IPN.
	 *
	 * @since 5.4.0
	 *
	 * @return bool
	 */
	public function is_ipn() {

		return false;
	}


	/**
	 * Determines whether this is a redirect hosted form.
	 *
	 * @since 5.4.0
	 *
	 * @return bool
	 */
	public function is_redirect() {

		return false;
	}


}


endif;
