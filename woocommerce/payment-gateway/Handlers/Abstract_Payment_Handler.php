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

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\Handlers\\Abstract_Payment_Handler' ) ) :


/**
 * The base payment handler class.
 *
 * This acts as an abstracted handler for processing payments, regardless of their front-end or API implementation.
 * Both direct and hosted gateways' transactions end up as the same response object, which this class handles for order
 * updating.
 *
 * @see Abstract_Hosted_Payment_Handler
 *
 * @since 5.4.0
 */
abstract class Abstract_Payment_Handler {


	/** the success result code */
	const RESULT_CODE_SUCCESS = 'success';

	/** the failure result code */
	const RESULT_CODE_FAILURE = 'failure';

	/** @var FrameworkBase\SV_WC_Payment_Gateway gateway instance */
	protected $gateway;


	/**
	 * Constructs the class.
	 *
	 * @since 5.4.0
	 *
	 * @param FrameworkBase\SV_WC_Payment_Gateway $gateway
	 */
	public function __construct( FrameworkBase\SV_WC_Payment_Gateway $gateway ) {

		$this->gateway = $gateway;

		$this->add_hooks();
	}


	/**
	 * Adds any action and filter hooks required by the handler.
	 *
	 * @since 5.4.0
	 */
	protected function add_hooks() {

		// filter order received text for held orders
		add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'maybe_render_held_order_received_text' ), 10, 2 );
	}


	/**
	 * Renders a custom held order message if available.
	 *
	 * @since 5.4.0
	 *
	 * @param string $text default text
	 * @param \WC_Order $order order object
	 *
	 * @return mixed
	 */
	public function maybe_render_held_order_received_text( $text, $order ) {

		if ( $order && isset( WC()->session->held_order_received_text ) ) {

			$text = WC()->session->held_order_received_text;

			unset( WC()->session->held_order_received_text );
		}

		return $text;
	}


	/**
	 * Processes payment for an order.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @return array
	 * @throws FrameworkBase\SV_WC_Plugin_Exception
	 */
	abstract public function process_order_payment( \WC_Order $order );


	/**
	 * Processes a gateway API payment response and handles the order accordingly.
	 *
	 * @since 5.4.0
	 *
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response $response
	 * @param \WC_Order $order
	 * @throws FrameworkBase\SV_WC_Payment_Gateway_Exception for payment failures
	 * @throws FrameworkBase\SV_WC_Plugin_Exception for other validation errors
	 */
	protected function process_transaction_response( FrameworkBase\SV_WC_Payment_Gateway_API_Response $response, \WC_Order $order ) {

		// validate the response data such as order ID and payment status
		$this->validate_transaction_response( $order, $response );

		try {

			if ( $response->transaction_approved() || $response->transaction_held() ) {

				if ( $response->transaction_held() || ( $this->get_gateway()->supports_credit_card_authorization() && $this->get_gateway()->perform_credit_card_authorization( $order ) ) ) {
					$this->process_order_transaction_held( $order, $response );
				} elseif ( $response->transaction_approved() ) {
					$this->process_order_transaction_approved( $order, $response );
				}

				$this->mark_order_as_paid( $order, $response );

			} else {

				$message = '';

				// build the order note with what data we have
				if ( $response->get_status_code() && $response->get_status_message() ) {
					/* translators: Placeholders: %1$s - status code, %2$s - status message */
					$message = sprintf( esc_html__( 'Status code %1$s: %2$s', 'woocommerce-plugin-framework' ), $response->get_status_code(), $response->get_status_message() );
				} elseif ( $response->get_status_code() ) {
					/* translators: Placeholders: %s - status code */
					$message = sprintf( esc_html__( 'Status code: %s', 'woocommerce-plugin-framework' ), $response->get_status_code() );
				} elseif ( $response->get_status_message() ) {
					/* translators: Placeholders; %s - status message */
					$message = sprintf( esc_html__( 'Status message: %s', 'woocommerce-plugin-framework' ), $response->get_status_message() );
				}

				// add transaction id if there is one
				if ( $response->get_transaction_id() ) {
					$message .= ' ' . sprintf( esc_html__( 'Transaction ID %s', 'woocommerce-plugin-framework' ), $response->get_transaction_id() );
				}

				if ( $response->get_user_message() && $this->get_gateway()->is_detailed_customer_decline_messages_enabled() ) {
					$user_exception = new FrameworkBase\SV_WC_Payment_Gateway_Exception( $response->get_user_message() );
				} else {
					$user_exception = null;
				}

				throw new FrameworkBase\SV_WC_Payment_Gateway_Exception( $message, null, $user_exception );
			}

			// add an order note for all exceptions and rethrow
		} catch ( FrameworkBase\SV_WC_Payment_Gateway_Exception $exception ) {

			$this->process_order_transaction_failed( $order, $exception->getMessage(), $response );

			// one can not simply throw $exception or the previous (user-friendly) exception message won't make it through
			throw new FrameworkBase\SV_WC_Payment_Gateway_Exception( $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
		}
	}


	/**
	 * Validates a transaction response & its order.
	 *
	 * This ensures duplicate or fraudulent responses aren't processed. Implementations can add exceptions to this for
	 * things like invalid hashes, etc...
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response $response API response object
	 * @throws FrameworkBase\SV_WC_API_Exception
	 */
	protected function validate_transaction_response( \WC_Order $order, FrameworkBase\SV_WC_Payment_Gateway_API_Response $response ) {

		// if the order has already been completed, bail
		if ( ! $order->needs_payment() ) {

			/* translators: Placeholders: %s - payment gateway title (such as Authorize.net, Braintree, etc) */
			$order->add_order_note( sprintf( esc_html__( '%s duplicate transaction received', 'woocommerce-plugin-framework' ), $this->get_gateway()->get_method_title() ) );

			throw new FrameworkBase\SV_WC_API_Exception( sprintf(
				__( 'Order %s is already paid for.', 'woocommerce-plugin-framework' ),
				$order->get_order_number()
			) );
		}
	}


	/**
	 * Handles actions after an approved transaction.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response $response API response object
	 */
	protected function process_order_transaction_approved( \WC_Order $order, FrameworkBase\SV_WC_Payment_Gateway_API_Response $response ) {

		try {

			$message = '';

			if ( FrameworkBase\SV_WC_Payment_Gateway::PAYMENT_TYPE_CREDIT_CARD === $response->get_payment_type() ) {
				$message = $this->get_gateway()->get_credit_card_transaction_approved_message( $order, $response );
			} elseif ( FrameworkBase\SV_WC_Payment_Gateway::PAYMENT_TYPE_ECHECK === $response->get_payment_type() ) {
				$message = $this->get_gateway()->get_echeck_transaction_approved_message( $order, $response );
			} else {

				$message_method = 'get_' . $response->get_payment_type() . '_transaction_approved_message';

				if ( is_callable( array( $this->get_gateway(), $message_method ) ) ) {
					$message = $this->get_gateway()->$message_method( $order, $response );
				}
			}

			$this->mark_order_as_approved( $order, $message, $response );

		} catch ( \Exception $exception ) {

			// TODO
		}
	}


	/**
	 * Handles actions after a held transaction.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response $response API response object
	 */
	protected function process_order_transaction_held( \WC_Order $order, FrameworkBase\SV_WC_Payment_Gateway_API_Response $response ) {

		$user_message = '';

		if ( $this->get_gateway()->is_detailed_customer_decline_messages_enabled() ) {
			$user_message = $response->get_user_message();
		}

		if ( ! $user_message || ( $this->get_gateway()->supports_credit_card_authorization() && $this->get_gateway()->perform_credit_card_authorization( $order ) ) ) {
			$user_message = __( 'Your order has been received and is being reviewed. Thank you for your business.', 'woocommerce-plugin-framework' );
		}

		if ( null !== WC()->session ) {
			WC()->session->held_order_received_text = $user_message;
		}

		$note_message = $this->get_gateway()->supports_credit_card_authorization() && $this->get_gateway()->perform_credit_card_authorization( $order ) ? __( 'Authorization only transaction', 'woocommerce-plugin-framework' ) : $response->get_status_message();

		$this->mark_order_as_held( $order, $note_message, $response );
	}


	/**
	 * Handles actions after a failed transaction.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $message failure message
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response response object
	 */
	protected function process_order_transaction_failed( \WC_Order $order, $message = '', FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		$this->mark_order_as_failed( $order, $message, $response );
	}


	/** Order marking methods *****************************************************************************************/


	/**
	 * Marks an order as paid.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Customer_Response|FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response API response object
	 */
	public function mark_order_as_paid( \WC_Order $order, FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		$this->get_gateway()->add_transaction_data( $order, $response );

		// let gateways easily add their own data
		$this->get_gateway()->add_payment_gateway_transaction_data( $order, $response );

		if ( $order->has_status( $this->get_held_order_status( $order, $response ) ) ) {
			// reduce stock for held orders, but don't complete payment (pass order ID so WooCommerce fetches fresh order object with reduced_stock meta set on order status change)
			wc_reduce_stock_levels( $order->get_id() );
		} else {
			// mark order as having received payment
			$order->payment_complete();
		}

		/**
		 * Payment Gateway Payment Processed Action.
		 *
		 * Fired when a payment is processed for an order.
		 *
		 * @since 4.1.0
		 *
		 * @param \WC_Order $order order object
		 * @param FrameworkBase\SV_WC_Payment_Gateway_Direct $this instance
		 */
		do_action( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_payment_processed', $order, $this->get_gateway() );
	}


	/**
	 * Marks an order as approved.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $message message for the order note
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response API response object
	 */
	public function mark_order_as_approved( \WC_Order $order, $message = '', FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		$order->add_order_note( $message );
	}


	/**
	 * Marks an order as held for review.
	 *
	 * Adds an order note and transitions to a held status.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $message order note message
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response
	 */
	public function mark_order_as_held( \WC_Order $order, $message = '', FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		/* translators: Placeholders: %s - payment gateway title */
		$order_note = sprintf( __( '%s Transaction Held for Review', 'woocommerce-plugin-framework' ), $this->get_gateway()->get_method_title() );

		if ( $message ) {
			$order_note .= " ({$message})";
		}

		$order_status = $this->get_held_order_status( $order, $response );

		// mark order as held
		if ( ! $order->has_status( $order_status ) ) {
			$order->update_status( $order_status, $order_note );
		} else {
			$order->add_order_note( $order_note );
		}
	}


	/**
	 * Gets the order status used for held orders.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response API response object
	 *
	 * @return string
	 */
	public function get_held_order_status( \WC_Order $order, $response = null ) {

		/**
		 * Held Order Status Filter.
		 *
		 * This filter is deprecated. Use wc_<gateway_id>_held_order_status instead.
		 *
		 * @since 4.0.1
		 * @deprecated 5.3.0
		 *
		 * @param string $order_status 'on-hold' by default
		 * @param \WC_Order $order WC order
		 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response API response object, if any
		 * @param FrameworkBase\SV_WC_Payment_Gateway $gateway gateway instance
		 */
		$status = apply_filters( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_held_order_status', 'on-hold', $order, $response, $this->get_gateway() );

		/**
		 * Filters the order status that's considered to be "held".
		 *
		 * @since 5.3.0
		 *
		 * @param string $status held order status
		 * @param \WC_Order $order order object
		 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response API response object, if any
		 */
		$status = apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_held_order_status', $status, $order, $response );

		return (string) $status;
	}


	/**
	 * Marks an order as failed.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $message order note message
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response
	 */
	public function mark_order_as_failed( \WC_Order $order, $message = '', FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		/* translators: Placeholders: %s - payment gateway title */
		$order_note = sprintf( esc_html__( '%s Payment Failed', 'woocommerce-plugin-framework' ), $this->get_gateway()->get_method_title() );

		if ( $message ) {
			$order_note .= " ({$message})";
		}

		// Mark order as failed if not already set, otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
		if ( ! $order->has_status( 'failed' ) ) {
			$order->update_status( 'failed', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}
	}


	/**
	 * Marks an order as cancelled.
	 *
	 * @since 5.4.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $message order note message
	 * @param FrameworkBase\SV_WC_Payment_Gateway_API_Response|null $response
	 */
	public function mark_order_as_cancelled( \WC_Order $order, $message, FrameworkBase\SV_WC_Payment_Gateway_API_Response $response = null ) {

		/* translators: Placeholders: %s - payment gateway title */
		$order_note = sprintf( __( '%s Transaction Cancelled', 'woocommerce-plugin-framework' ), $this->get_gateway()->get_method_title() );

		if ( $message ) {
			$order_note .= " ({$message})";
		}

		// Mark order as cancelled if not already set
		if ( ! $order->has_status( 'cancelled' ) ) {
			$order->update_status( 'cancelled', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}
	}


	/** Conditional methods *******************************************************************************************/





	/** Getter methods ************************************************************************************************/


	/**
	 * Gets the gateway object.
	 *
	 * @since 5.4.0
	 *
	 * @return FrameworkBase\SV_WC_Payment_Gateway
	 */
	public function get_gateway() {

		return $this->gateway;
	}


	/** Setter methods ************************************************************************************************/


}


endif;
