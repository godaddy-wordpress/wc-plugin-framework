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
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
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
 * + `get_hosted_pay_page_params()` - Return any hosted pay page parameters (optional)
 * + `get_transaction_response()` - Return the transaction response object on redirect-back/IPN
 *
 * @since 1.0.0
 */
abstract class SV_WC_Payment_Gateway_Hosted extends SV_WC_Payment_Gateway {


	/** @var string the WC API url, used for the IPN and/or redirect-back handler */
	protected $transaction_response_handler_url;


	/**
	 * Initialize the gateway
	 *
	 * See parent constructor for full method documentation
	 *
	 * @since 2.1.0
	 * @see SV_WC_Payment_Gateway::__construct()
	 * @param string $id the gateway id
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the parent plugin class
	 * @param array $args gateway arguments
	 */
	public function __construct( $id, $plugin, $args ) {

		// parent constructor
		parent::__construct( $id, $plugin, $args );

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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 * @see WC_Payment_Gateway::process_payment()
	 * @param int $order_id the order to process
	 * @return array with keys 'result' and 'redirect'
	 * @throws \SV_WC_Payment_Gateway_Exception if payment processing must be halted, and a message displayed to the customer
	 */
	public function process_payment( $order_id ) {

		$payment_url = $this->get_payment_url( $order_id );

		if ( ! $payment_url ) {
			// be sure to have either set a notice via `wc_add_notice` to be
			// displayed, or have thrown an exception with a message
			return array( 'result' => 'failure' );
		}

		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $payment_url,
		);
	}


	/**
	 * Gets the payment URL: the checkout pay page
	 *
	 * @since 2.1.0
	 * @param int $order_id the order id
	 * @return string the payment URL, or false if unavailable
	 */
	protected function get_payment_url( $order_id ) {

		if ( $this->use_form_post() ) {
			// the checkout pay page
			$order = wc_get_order( $order_id );
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
	 * @since 2.1.0
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
			$this->generate_pay_form( $order_id );
		}
	}


	/**
	 * Generates the POST pay form.  Some inline javascript will attempt to
	 * auto-submit this pay form, so as to make the checkout process as
	 * seamless as possile
	 *
	 * @since 2.1.0
	 * @param int $order_id the order identifier
	 */
	public function generate_pay_form( $order_id ) {

		// setup the order object
		$order = $this->get_order( $order_id );

		$request_params = $this->get_hosted_pay_page_params( $order );

		// standardized request data, for logging purposes
		$request = array(
			'method' => 'POST',
			'uri'    => $this->get_hosted_pay_page_url( $order ),
			'body'   => print_r( $request_params, true ),
		);

		// log the request
		$this->log_hosted_pay_page_request( $request );

		// render the appropriate content
		if ( $this->use_auto_form_post() ) {
			$this->render_auto_post_form( $order, $request_params );
		} else {
			$this->render_pay_page_form( $order, $request_params );
		}
	}


	/**
	 * Renders the gateway pay page direct post form.  This is used by gateways
	 * that collect some or all payment information on-site, and POST the
	 * entered information to a remote server for processing
	 *
	 * @since 2.2.0
	 * @see SV_WC_Payment_Gateway_Hosted::use_auto_form_post()
	 * @param WC_Order $order the order object
	 * @param array $request_params associative array of request parameters
	 */
	public function render_pay_page_form( $order, $request_params ) {
		// implemented by concrete class
	}


	/**
	 * Renders the gateway auto post form.  This is used for gateways that
	 * collect no payment information on-site, but must POST parameters to a
	 * hosted payment page where payment information is entered.
	 *
	 * @since 2.2.0
	 * @see SV_WC_Payment_Gateway_Hosted::use_auto_form_post()
	 * @param WC_Order $order the order object
	 * @param array $request_params associative array of request parameters
	 */
	public function render_auto_post_form( $order, $request_params ) {

		// attempt to automatically submit the form and redirect
		wc_enqueue_js('
			$( "body" ).block( {
					message: "<img src=\"' . esc_url( $this->get_plugin()->get_framework_assets_url() . '/images/ajax-loader.gif' ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />' . esc_html__( 'Thank you for your order. We are now redirecting you to complete payment.', 'woocommerce-plugin-framework' ) . '",
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

		echo '<p>' . esc_html__( 'Thank you for your order, please click the button below to pay.', 'woocommerce-plugin-framework' ) . '</p>' .
			'<form action="' . esc_url( $this->get_hosted_pay_page_url( $order ) ) . '" method="post">' .
				implode( '', $request_arg_fields ) .
				'<input type="submit" class="button alt button-alt" id="submit_' . $this->get_id() . '_payment_form" value="' . esc_attr__( 'Pay Now', 'woocommerce-plugin-framework' ) . '" />' .
				/* translators: Order as in e-commerce */
				'<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . esc_html__( 'Cancel Order', 'woocommerce-plugin-framework' ) . '</a>' .
			'</form>';
	}


	/**
	 * Returns the gateway hosted pay page parameters, if any
	 *
	 * @since 2.1.0
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
	 * @since 2.1.0
	 * @see SV_WC_Payment_Gateway_Hosted::get_hosted_pay_page_params()
	 * @param WC_Order $order optional order object, defaults to null
	 * @return string hosted pay page url, or false if it could not be determined
	 */
	abstract public function get_hosted_pay_page_url( $order = null );


	/**
	 * Process IPN request
	 *
	 * @since 2.1.0
	 */
	public function process_ipn() {

		// log the IPN request
		$this->log_transaction_response_request( $_REQUEST );

		$response = null;

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

				/* translators: IPN: https://en.wikipedia.org/wiki/Instant_payment_notification, %s: payment gateway title (such as Authorize.net, Braintree, etc) */
				$order_note = sprintf( esc_html__( 'IPN processing error: %s duplicate transaction received', 'woocommerce-plugin-framework' ), $this->get_method_title() );
				$order->add_order_note( $order_note );

				status_header( 200 );
				die;
			}

			if ( $this->process_transaction_response( $order, $response ) ) {

				if ( $order->has_status( 'on-hold' ) ) {
					$order->reduce_order_stock(); // reduce stock for held orders, but don't complete payment
				} elseif ( ! $order->has_status( 'cancelled' ) ) {
					$order->payment_complete(); // mark order as having received payment
				}
			}

		} catch ( SV_WC_Plugin_Exception $e ) {
			// failure

			if ( isset( $order ) && $order ) {
				$this->mark_order_as_failed( $order, $e->getMessage(), $response );
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
	 * @since 2.1.0
	 */
	public function process_redirect_back() {

		// log the redirect back request
		$this->log_transaction_response_request( $_REQUEST );

		$response = null;

		try {

			// get the transaction response object for the current request
			$response = $this->get_transaction_response( $_REQUEST );

			// get the associated order, or die trying
			$order = $response->get_order();

			if ( ! $order || ! $order->id ) {

				$this->add_debug_message( sprintf( "Order %s not found", $response->get_order_id() ), 'error' );

				// if an order could not be determined, there's not a whole lot
				// we can do besides redirecting back to the home page
				return wp_redirect( get_home_url( null, '' ) );
			}

			// check for duplicate order processing
			if ( ! $order->needs_payment() ) {

				$this->add_debug_message( sprintf( "Order '%s' has already been processed", $order->get_order_number() ), 'error' );

				/* translators: Placeholders: %s - payment gateway title (such as Authorize.net, Braintree, etc) */
				$order_note = sprintf( esc_html__( '%s duplicate transaction received', 'woocommerce-plugin-framework' ), $this->get_method_title() );
				$order->add_order_note( $order_note );

				// since the order has already been paid for, redirect to the 'thank you' page
				return wp_redirect( $this->get_return_url( $order ) );
			}

			if ( $this->process_transaction_response( $order, $response ) ) {

				if ( $order->has_status( 'on-hold' ) ) {
					$order->reduce_order_stock(); // reduce stock for held orders, but don't complete payment
				} elseif ( ! $order->has_status( 'cancelled' ) ) {
					$order->payment_complete(); // mark order as having received payment
				}

				// finally, redirect to the 'thank you' page
				return wp_redirect( $this->get_return_url( $order ) );
			} else {
				// failed response, redirect back to pay page
				return wp_redirect( $order->get_checkout_payment_url( $this->use_form_post() && ! $this->use_auto_form_post() ) );
			}

		} catch( SV_WC_Payment_Gateway_Exception $e ) {
			// failure

			if ( isset( $order ) && $order ) {
				$this->mark_order_as_failed( $order, $e->getMessage(), $response );
				return wp_redirect( $order->get_checkout_payment_url( $this->use_form_post() && ! $this->use_auto_form_post() ) );
			}

			// otherwise, if no order is available, log the issue and redirect to home
			$this->add_debug_message( 'Redirect-back error: ' . $e->getMessage(), 'error' );
			return wp_redirect( get_home_url( null, '' ) );
		}
	}


	/**
	 * Process the transaction response for the given order
	 *
	 * @since 2.1.0
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response transaction response
	 * @return boolean true if transaction did not fail, false otherwise
	 */
	protected function process_transaction_response( $order, $response ) {

		// handle the response
		if ( $response->transaction_approved() || $response->transaction_held() ) {

			if ( $response->transaction_approved() ) {

				if ( self::PAYMENT_TYPE_CREDIT_CARD == $response->get_payment_type() ) {
					$this->do_credit_card_transaction_approved( $order, $response );
				} elseif ( self::PAYMENT_TYPE_ECHECK == $response->get_payment_type() ) {
					$this->do_check_transaction_approved( $order, $response );
				} else {
					// generic transaction approved message (likely to be overridden by the concrete gateway implementation)
					$this->do_transaction_approved( $order, $response );
				}
			}

			$this->add_transaction_data( $order, $response );

			$this->add_payment_gateway_transaction_data( $order, $response );

			// if the transaction was held (ie fraud validation failure) mark it as such
			if ( $response->transaction_held() ) {
				$this->mark_order_as_held( $order, $response->get_status_message(), $response );
			}

			return true;

		} elseif ( $response->transaction_cancelled() ) {

			$this->mark_order_as_cancelled( $order, $response->get_status_message(), $response );

			return true;

		} else { // failure

			return $this->do_transaction_failed_result( $order, $response );
		}
	}


	/**
	 * Adds the standard transaction data to the order
	 *
	 * @since 2.2.0
	 * @see SV_WC_Payment_Gateway::add_transaction_data()
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response|null $response optional transaction response
	 */
	public function add_transaction_data( $order, $response = null ) {

		// add parent transaction data
		parent::add_transaction_data( $order, $response );

		// account number
		if ( $response->get_account_number() ) {
			$this->update_order_meta( $order->id, 'account_four', substr( $response->get_account_number(), -4 ) );
		}

		if ( self::PAYMENT_TYPE_CREDIT_CARD == $response->get_payment_type() ) {

			if ( $response->get_authorization_code() ) {
				$this->update_order_meta( $order->id, 'authorization_code', $response->get_authorization_code() );
			}

			if ( $order->get_total() > 0 ) {
				// mark as captured
				if ( $response->is_charge() ) {
					$captured = 'yes';
				} else {
					$captured = 'no';
				}
				$this->update_order_meta( $order->id, 'charge_captured', $captured );
			}

			if ( $response->get_exp_month() && $response->get_exp_year() ) {
				$this->update_order_meta( $order->id, 'card_expiry_date', $response->get_exp_year() . '-' . $response->get_exp_month() );
			}

			if ( $response->get_card_type() ) {
				$this->update_order_meta( $order->id, 'card_type', $response->get_card_type() );
			}

		} elseif ( self::PAYMENT_TYPE_ECHECK == $response->get_payment_type() ) {

			// optional account type (checking/savings)
			if ( $response->get_account_type() ) {
				$this->update_order_meta( $order->id, 'account_type', $response->get_account_type() );
			}

			// optional check number
			if ( $response->get_check_number() ) {
				$this->update_order_meta( $order->id, 'check_number', $response->get_check_number() );
			}
		}
	}


	/**
	 * Adds an order note, along with anything else required after an approved
	 * credit card transaction
	 *
	 * @since 2.2.0
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Credit_Card_Response transaction response
	 */
	protected function do_credit_card_transaction_approved( $order, $response ) {

		$last_four = substr( $response->get_account_number(), -4 );

		$transaction_type = '';
		if ( $response->is_authorization() ) {
			$transaction_type = esc_html_x( 'Authorization', 'credit card transaction type', 'woocommerce-plugin-framework' );
		} elseif ( $response->is_charge() ) {
			$transaction_type = esc_html_x( 'Charge', 'noun, credit card transaction type', 'woocommerce-plugin-framework' );
		}

		// credit card order note
		$message = sprintf(
			/* translators: Placeholders: %1$s - payment method title, %2$s - environment ("Test"), %3$s - transaction type (authorization/charge), %4$s - card type (mastercard, visa, ...), %5$s - last four digits of the card, %6$s - expiry date */
			esc_html__( '%1$s %2$s %3$s Approved: %4$s ending in %5$s (expires %6$s)', 'woocommerce-plugin-framework' ),
			$this->get_method_title(),
			$this->is_test_environment() ? esc_html_x( 'Test', 'noun, software environment', 'woocommerce-plugin-framework' ) : '',
			$transaction_type,
			SV_WC_Payment_Gateway_Helper::payment_type_to_name( ( $response->get_card_type() ? $response->get_card_type() : 'card' ) ),
			$last_four,
			$response->get_exp_month() . '/' . substr( $response->get_exp_year(), -2 )
		);

		// adds the transaction id (if any) to the order note
		if ( $response->get_transaction_id() ) {
				/* translators: Placeholders: %s - transaction ID */
			$message .= ' ' . sprintf( esc_html__( '(Transaction ID %s)', 'woocommerce-plugin-framework' ), $response->get_transaction_id() );
		}

		$order->add_order_note( $message );
	}


	/**
	 * Adds an order note, along with anything else required after an approved
	 * echeck transaction
	 *
	 * @since 2.2.0
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response transaction response
	 */
	protected function do_check_transaction_approved( $order, $response ) {

		$last_four = substr( $response->get_account_number(), -4 );

		// credit card order note
		$message = sprintf(
			/* translators: Placeholders: %1$s - payment method title, %2$s - environment ("Test"), %3$s - card type (mastercard, visa, ...), %4$s - last four digits of the card */
			esc_html__( '%1$s %2$s Transaction Approved: %3$s ending in %4$s', 'woocommerce-plugin-framework' ),
			$this->get_method_title(),
			$this->is_test_environment() ? esc_html_x( 'Test', 'noun, software environment', 'woocommerce-plugin-framework' ) : '',
			SV_WC_Payment_Gateway_Helper::payment_type_to_name( ( $response->get_account_type() ? $response->get_account_type() : 'bank' ) ),
			$last_four
		);

		// adds the check number (if any) to the order note
		if ( $response->get_check_number() ) {
			/* translators: Placeholders: %s - check number */
			$message .= ' ' . sprintf( esc_html__( '(check number %s)', 'woocommerce-plugin-framework' ), $response->get_check_number() );
		}

		// adds the transaction id (if any) to the order note
		if ( $response->get_transaction_id() ) {
			$message .= ' ' . sprintf( esc_html__( '(Transaction ID %s)', 'woocommerce-plugin-framework' ), $response->get_transaction_id() );
		}

		$order->add_order_note( $message );
	}


	/**
	 * Adds an order note, along with anything else required after an approved
	 * transaction.  This is a generic, default approved handler
	 *
	 * @since 2.1.0
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response transaction response
	 */
	protected function do_transaction_approved( $order, $response ) {

		// generic approve order note.  This is likely to be overwritten by a concrete payment gateway implementation
		$message = sprintf(
			/* translators: Placeholders: %1$s - payment method title, %2$s - environment ("Test") */
			esc_html__( '%1$s %2$s Transaction Approved', 'woocommerce-plugin-framework' ),
			$this->get_method_title(),
			$this->is_test_environment() ? esc_html_x( 'Test', 'noun, software environment', 'woocommerce-plugin-framework' ) : ''
		);

		// adds the transaction id (if any) to the order note
		if ( $response->get_transaction_id() ) {
			$message .= ' ' . sprintf( esc_html__( '(Transaction ID %s)', 'woocommerce-plugin-framework' ), $response->get_transaction_id() );
		}

		$order->add_order_note( $message );
	}


	/**
	 * Returns an API response object for the current response request
	 *
	 * @since 2.1.0
	 * @param array $request_response_data the current request response data
	 * @return SV_WC_Payment_Gateway_API_Payment_Notification_Response the response object
	 */
	abstract protected function get_transaction_response( $request_response_data );


	/** Helper methods ******************************************************/


	/**
	 * Returns the WC API URL for this gateway, based on the current protocol
	 *
	 * @since 2.1.0
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
	 * @since 2.1.0
	 * @return boolean true if currently doing a transaction response request
	 */
	public function doing_transaction_response_handler() {
		return isset( $_REQUEST['wc-api'] ) && get_class( $this ) == $_REQUEST['wc-api'];
	}


	/**
	 * Log pay page form submission request
	 *
	 * @since 2.1.0
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
			'message'
		);
	}


	/**
	 * Log IPN/redirect-back transaction response request to the log file
	 *
	 * @since 2.1.0
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
	 * @since 1.0.0
	 * @return boolean true if this is a hosted payment gateway
	 */
	public function is_hosted_gateway() {
		return true;
	}


	/**
	 * Returns true if this gateway uses a form-post from the pay
	 * page to "redirect" to a hosted payment page
	 *
	 * @since 2.1.0
	 * @return boolean true if this gateway uses a form post, false if it
	 *         redirects directly to the hosted pay page from checkout
	 */
	public function use_form_post() {
		return false;
	}


	/**
	 * Returns true if this gateway uses an automatic form-post from the pay
	 * page to "redirect" to the hosted payment page where payment information
	 * is securely entered.  Return false if payment information is collected
	 * on the pay page and then posted to a remote server.
	 *
	 * This method has no effect if use_form_post() returns false
	 *
	 * @since 2.2.0
	 * @see SV_WC_Payment_Gateway_Hosted::use_form_post()
	 * @return boolean true if this gateway automatically posts to the remote
	 *         processor server from the pay page
	 */
	public function use_auto_form_post() {
		return $this->use_form_post() && true;
	}


	/**
	 * Returns true if this gateway uses an Instant Payment Notification (IPN).
	 * If not, the transaction results are expected to be found in the redirect
	 * of the client back to the site.
	 *
	 * @since 2.1.0
	 * @return boolean true if this is a gateway uses an IPN
	 */
	public function has_ipn() {
		return true;
	}

}

endif;  // class exists check
