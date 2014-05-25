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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2014, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_Payment_Gateway_Hosted' ) ) :

/**
 * # WooCommerce Payment Gateway Framework Hosted Gateway
 *
 * Implement the following methods:
 *
 * + `get_hosted_pay_page_url()` - Return the hosted pay page url
 * + `get_hosted_pay_page_params()` - Return any hosted pay page parameters
 * + `get_transaction_response()` - Return the transaction response object on redirect-back/IPN
 *
 * @since 1.0
 */
abstract class SV_WC_Payment_Gateway_Hosted extends SV_WC_Payment_Gateway {


	/** @var string the WC API url, used for the IPN and/or redirect-back handler */
	protected $transaction_response_handler_url;


	/**
	 * Initialize the gateway
	 *
	 * See parent constructor for full method documentation
	 *
	 * @since 2.1
	 * @see SV_WC_Payment_Gateway::__construct()
	 * @param string $id the gateway id
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the parent plugin class
	 * @param string $text_domain the plugin text domain
	 * @param array $args gateway arguments
	 */
	public function __construct( $id, $plugin, $text_domain, $args ) {

		// parent constructor
		parent::__construct( $id, $plugin, $text_domain, $args );

		// IPN or redirect-back
		if ( $this->has_ipn() ) {
			$api_method_name = 'process_ipn';
		} else {
			$api_method_name = 'process_redirect_back';
		}

		// payment notification listener hook
		if ( ! has_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, $api_method_name ) ) ) {
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, $api_method_name ) );
		}
	}


	/**
	 * Display the payment fields on the checkout page
	 *
	 * @since 1.0
	 * @see WC_Payment_Gateway::payment_fields()
	 */
	public function payment_fields() {

		parent::payment_fields();
		?><style type="text/css">#payment ul.payment_methods li label[for='payment_method_<?php echo $this->get_id(); ?>'] img:nth-child(n+2) { margin-left:1px; }</style><?php
	}


	/**
	 * Process the payment by redirecting customer to the WooCommerce pay page
	 * or the gatway hosted pay page
	 *
	 * @since 1.0
	 * @see WC_Payment_Gateway::process_payment()
	 * @param int $order_id the order to process
	 * @return array with keys 'result' and 'redirect'
	 * @throws Exception if payment processing must be halted, and a message displayed to the customer
	 */
	public function process_payment( $order_id ) {

		$payment_url = $this->get_payment_url( $order_id );

		if ( ! $payment_url ) {
			// be sure to have either set a notice via `wc_add_notice` to be
			// displayed, or have thrown an exception with a message
			return array( 'result' => 'failure' );
		}

		SV_WC_Plugin_Compatibility::WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $payment_url,
		);
	}


	/**
	 * Gets the payment URL: the checkout pay page
	 *
	 * @since 2.1
	 * @param int $order_id the order id
	 * @return string the payment URL, or false if unavailable
	 */
	protected function get_payment_url( $order_id ) {

		if ( $this->use_form_post() ) {
			// the checkout pay page
			$order = new WC_Order( $order_id );
			return $order->get_checkout_payment_url( true );
		} else {

			// setup the order object
			$order = $this->get_order( $order_id );

			// direct-redirect, so append the hosted pay page params to the hosted pay page url
			$pay_page_url = $this->get_hosted_pay_page_url( $order );

			if ( $pay_page_url ) {
				return add_query_arg( $this->get_hosted_pay_page_params( $order ), $pay_page_url );
			}
		}

		return false;
	}


	/**
	 * Render the payment page for gateways that use a form post method
	 *
	 * @since 2.1
	 * @see SV_WC_Payment_Gateway::payment_page()
	 * @see SV_WC_Payment_Gateway_Hosted::use_form_post()
	 * @see SV_WC_Payment_Gateway_Hosted::add_pay_page_handler()
	 * @param int $order_id identifies the order
	 */
	public function payment_page( $order_id ) {

		if ( ! $this->use_form_post() ) {
			// default behavior: pay page is not used, direct-redirect from checkout
			parent::payment_page( $order_id );
		} else {
			echo '<p>' . __( 'Thank you for your order, please click the button below to pay.', $this->text_domain ) . '</p>';

			echo $this->generate_pay_form( $order_id );
		}
	}


	/**
	 * Generates the POST pay form.  Some inline javascript will attempt to
	 * auto-submit this pay form, so as to make the checkout process as
	 * seamless as possile
	 *
	 * @since 2.1
	 * @param int $order_id the order identifier
	 * @return string payment page POST form
	 */
	public function generate_pay_form( $order_id ) {

		// setup the order object
		$order = $this->get_order( $order_id );

		$request_params = $this->get_hosted_pay_page_params( $order );

		// standardized request data, for logging purposes
		$request = array(
			'method' => 'POST',
			'uri'    => $this->get_hosted_pay_page_url( $order ),
			'body'   => json_encode( $request_params ),
		);

		// log the request
		$this->log_hosted_pay_page_request( $request );

		// attempt to automatically submit the form and bring them to the payza paymen site
		SV_WC_Plugin_Compatibility::wc_enqueue_js('
			$( "body" ).block( {
					message: "<img src=\"' . esc_url( SV_WC_Plugin_Compatibility::WC()->plugin_url() ) . '/assets/images/ajax-loader.gif\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />' . __( 'Thank you for your order. We are now redirecting you to complete payment.', $this->text_domain ) . '",
					overlayCSS: {
						background: "#fff",
						opacity: 0.6
					},
					css: {
						padding:         20,
						textAlign:       "center",
						color:           "#555",
						border:          "3px solid #aaa",
						backgroundColor: "#fff",
						cursor:          "wait",
						lineHeight:      "32px"
					}
				} );

			$( "#submit_' . $this->get_id() . '_payment_form" ).click();
		');

		$request_arg_fields = array();

		foreach ( $request_params as $key => $value ) {
			$request_arg_fields[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}

		return '<form action="' . esc_url( $this->get_hosted_pay_page_url( $order ) ) . '" method="post">' .
				implode( '', $request_arg_fields ) .
				'<input type="submit" class="button-alt" id="submit_' . $this->get_id() . '_payment_form" value="' . __( 'Pay Now', $this->text_domain ) . '" />' .
				'<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel Order', $this->text_domain ) . '</a>' .
			'</form>';
	}


	/**
	 * Returns the gateway hosted pay page parameters, if any
	 *
	 * @since 2.1
	 * @param WC_Order $order the order object
	 * @return array associative array of name-value parameters
	 */
	protected function get_hosted_pay_page_params( $order ) {
		// stub method
		return array();
	}


	/**
	 * Gets the hosted pay page url to redirect to, to allow the customer to
	 * remit payment.  This is generally the bare URL, without any query params.
	 *
	 * This method may be called more than once during a single request.
	 *
	 * @since 2.1
	 * @see SV_WC_Payment_Gateway_Hosted::get_hosted_pay_page_params()
	 * @param WC_Order $order the order object
	 * @return string hosted pay page url, or false if it could not be determined
	 */
	public function get_hosted_pay_page_url( $order ) {
		// TODO: make me abstract with the next breaking compatiblity framework update
	}


	/**
	 * Process IPN request
	 *
	 * @since 2.1
	 */
	public function process_ipn() {

		// log the IPN request
		$this->log_transaction_response_request( $_REQUEST );

		try {

			// get the transaction response object for the current request
			$response = $this->get_transaction_response( $_REQUEST );

			// get the associated order, or die trying
			$order = $response->get_order();

			if ( ! $order || ! $order->id ) {
				// if an order could not be determined, there's not a whole lot
				// we can do besides logging the issue

				if ( $this->debug_log() ) {
					$this->get_plugin()->log( sprintf( 'IPN processing error: Could not find order %s', $response->get_order_id() ), $this->get_id() );
				}

				status_header( 200 );
				die;
			}

			// verify order has not already been completed
			if ( ! $order->needs_payment() ) {

				if ( $this->debug_log() ) {
					$this->get_plugin()->log( sprintf( "IPN processing error: Order %s is already paid for.", $order->get_order_number() ), $this->get_id() );
				}

				$order_note = sprintf( __( 'IPN processing error: %s duplicate transaction received', $this->text_domain ), $this->get_method_title() );
				$order->add_order_note( $order_note );

				status_header( 200 );
				die;
			}

			if ( $this->process_transaction_response( $order, $response ) ) {

				if ( 'on-hold' == $order->status ) {
					$order->reduce_order_stock(); // reduce stock for held orders, but don't complete payment
				} elseif ( 'cancelled' != $order->status ) {
					$order->payment_complete(); // mark order as having received payment
				}
			}

		} catch ( Exception $e ) {
			// failure

			if ( isset( $order ) && $order ) {
				$this->mark_order_as_failed( $order, $e->getMessage() );
			}

			if ( $this->debug_log() ) {
				$this->get_plugin()->log( sprintf( 'IPN processing error: %s', $e->getMessage() ), $this->get_id() );
			}
		}

		// reply success
		status_header( 200 );
		die;
	}


	/**
	 * Process redirect back (non-IPN gateway)
	 *
	 * @since 2.1
	 */
	public function process_redirect_back() {

		// log the redirect back request
		$this->log_transaction_response_request( $_REQUEST );

		try {

			// get the transaction response object for the current request
			$response = $this->get_transaction_response( $_REQUEST );

			// get the associated order, or die trying
			$order = $response->get_order();

			if ( ! $order || ! $order->id ) {

				$this->add_debug_message( sprintf( "Order %s not found", $response->get_order_id() ), 'error' );

				// if an order could not be determined, there's not a whole lot
				// we can do besides redirecting back to the home page
				return wp_redirect( $this->get_plugin()->get_home_url() );
			}

			// check for duplicate order processing
			if ( ! $order->needs_payment() ) {

				$this->add_debug_message( sprintf( "Order '%s' has already been processed", $order->get_order_number() ), 'error' );

				$order_note = sprintf( __( '%s duplicate transaction received', $this->text_domain ), $this->get_method_title() );
				$order->add_order_note( $order_note );

				// since the order has already been paid for, redirect to the 'thank you' page
				return wp_redirect( $this->get_return_url( $order ) );
			}

			if ( $this->process_transaction_response( $order, $response ) ) {

				if ( 'on-hold' == $order->status ) {
					$order->reduce_order_stock(); // reduce stock for held orders, but don't complete payment
				} elseif ( 'cancelled' != $order->status ) {
					$order->payment_complete(); // mark order as having received payment
				}

				// finally, redirect to the 'thank you' page
				return wp_redirect( $this->get_return_url( $order ) );
			} else {
				// failed response, redirect back to pay page
				return wp_redirect( $order->get_checkout_payment_url( ! $this->use_form_post() ) );
			}

		} catch( Exception $e ) {
			// failure

			if ( isset( $order ) && $order ) {
				$this->mark_order_as_failed( $order, $e->getMessage() );
				return wp_redirect( $order->get_checkout_payment_url( ! $this->use_form_post() ) );
			}

			// otherwise, if no order is available, log the issue and redirect to home
			$this->add_debug_message( 'Redirect-back error: ' . $e->getMessage(), 'error' );
			return wp_redirect( $this->get_plugin()->get_home_url() );
		}
	}


	/**
	 * Process the transaction response for the given order
	 *
	 * @since 2.1
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response transaction response
	 * @return boolean true if transaction did not fail, false otherwise
	 */
	protected function process_transaction_response( $order, $response ) {

		// handle the response
		if ( $response->transaction_approved() || $response->transaction_held() ) {

			if ( $response->transaction_approved() ) {
				$this->do_transaction_approved( $order, $response );
			}

			$this->add_transaction_data( $order, $response );

			$this->add_payment_gateway_transaction_data( $order, $response );

			// if the transaction was held (ie fraud validation failure) mark it as such
			if ( $response->transaction_held() ) {
				$this->mark_order_as_held( $order, $response->get_status_message() );
			}

			return true;

		} elseif ( $response->transaction_cancelled() ) {

			$this->mark_order_as_cancelled( $order, $response->get_status_message() );

			return true;

		} else { // failure

			return $this->do_transaction_failed_result( $order, $response );
		}
	}


	/**
	 * Adds an order note, along with anything else required after an approved
	 * transaction
	 *
	 * @since 2.1
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response transaction response
	 */
	protected function do_transaction_approved( $order, $response ) {

		// generic approve order note.  This is likely to be overwritten by a concrete payment gateway implementation
		$message = sprintf(
			__( '%s %sTransaction Approved', $this->text_domain ),
			$this->get_method_title(),
			$this->is_test_environment() ? __( 'Test', $this->text_domain ) . ' ' : ''
		);

		// adds the transaction id (if any) to the order note
		if ( $response->get_transaction_id() ) {
			$message .= ' ' . sprintf( __( '(Transaction ID %s)', $this->text_domain ), $response->get_transaction_id() );
		}

		$order->add_order_note( $message );
	}


	/**
	 * Returns an API response object for the current response request
	 *
	 * @since 2.1
	 * @param array $request_response_data the current request response data
	 * @return SV_WC_Payment_Gateway_API_Payment_Notification_Response the response object
	 */
	protected function get_transaction_response( $request_response_data ) {
		// TODO: make me abstract with the next breaking compatiblity framework update
	}


	/** Helper methods ******************************************************/


	/**
	 * Returns the WC API URL for this gateway, based on the current protocol
	 *
	 * @since 2.1
	 * @return string the WC API URL for this server
	 */
	public function get_transaction_response_handler_url() {

		if ( $this->transaction_response_handler_url ) {
			return $this->transaction_response_handler_url;
		}

		$this->transaction_response_handler_url = add_query_arg( 'wc-api', get_class( $this ), home_url( '/' ) );

		// make ssl if needed
		if ( ( is_ssl() && ! is_admin() ) || 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) ) {
			$this->transaction_response_handler_url = str_replace( 'http:', 'https:', $this->transaction_response_handler_url );
		}

		return $this->transaction_response_handler_url;
	}


	/**
	 * Returns true if currently doing a transaction response request
	 *
	 * @since 2.1
	 * @return boolean true if currently doing a transaction response request
	 */
	public function doing_transaction_response_handler() {
		return isset( $_REQUEST['wc-api'] ) && get_class( $this ) == $_REQUEST['wc-api'];
	}


	/**
	 * Log pay page form submission request
	 *
	 * @since 2.1
	 * @param array $request the request data associative array, which should
	 *        include members 'method', 'uri', 'body'
	 * @param object $response optional response object
	 */
	public function log_hosted_pay_page_request( $request ) {

		$this->add_debug_message(
			sprintf( "Request Method: %s\nRequest URI: %s\nRequest Body: %s",
				$request['method'],
				$request['uri'],
				$request['body']
			),
			'message',
			true
		);
	}


	/**
	 * Log IPN/redirect-back transaction response request to the log file
	 *
	 * @since 2.1
	 * @param array $response the request data
	 * @param string $message optional message string with a %s to hold the
	 *        response data.  Defaults to 'IPN Request %s' or 'Redirect-back
	 *        Request %s' based on the result of `has_ipn()`
	 * $response
	 */
	public function log_transaction_response_request( $response, $message = null ) {

		// add log message to WC logger if log/both is enabled
		if ( $this->debug_log() ) {

			// if a message wasn't provided, make our best effort
			if ( is_null( $message ) ) {
				$message = ( $this->has_ipn() ? 'IPN' : 'Redirect-back' ) . ' Request: %s';
			}

			$this->get_plugin()->log( sprintf( $message, print_r( $response, true ) ), $this->get_id() );
		}
	}


	/** Getters ******************************************************/


	/**
	 * Returns true if this is a hosted type gateway
	 *
	 * @since 1.0
	 * @return boolean true if this is a hosted payment gateway
	 */
	public function is_hosted_gateway() {
		return true;
	}


	/**
	 * Returns true if this gateway uses an automatic form-post from the pay
	 * page to "redirect" to the hosted payment page
	 *
	 * @since 2.1
	 * @return boolean true if this gateway uses a form post, false if it
	 *         redirects directly to the hosted pay page from checkout
	 */
	public function use_form_post() {
		return false;
	}


	/**
	 * Returns true if this gateway uses an Instant Payment Notification (IPN).
	 * If not, the transaction results are expected to be found in the redirect
	 * of the client back to the site.
	 *
	 * @since 2.1
	 * @return boolean true if this is a gateway uses an IPN
	 */
	public function has_ipn() {
		return true;
	}

}

endif;  // class exists check
