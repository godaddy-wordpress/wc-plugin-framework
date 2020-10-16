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
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_Hosted' ) ) :


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

		// payment notification listener hook
		if ( ! has_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'handle_transaction_response_request' ) ) ) {
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'handle_transaction_response_request' ) );
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
	 * Processes the payment by redirecting customer to the WooCommerce pay page or the gateway hosted pay page.
	 *
	 * @see \WC_Payment_Gateway::process_payment()
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id the order to process
	 * @return array with keys 'result' and 'redirect'
	 */
	public function process_payment( $order_id ) {

		$payment_url = $this->get_payment_url( $order_id );

		if ( ! $payment_url ) {
			// be sure to have either set a notice via `wc_add_notice` to be
			// displayed, or have thrown an exception with a message
			return array( 'result' => 'failure' );
		}

		if ( $this->empty_cart_before_redirect() && is_callable( array( WC()->cart, 'empty_cart' ) ) ) {
			WC()->cart->empty_cart();
		}

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
	 * Determines if the customers cart should be emptied before redirecting to the payment form, after the order is created.
	 *
	 * Gateways can set this to false if they want the cart to remain intact until a successful payment is made.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	protected function empty_cart_before_redirect() {

		return true;
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
	 * @see SV_WC_Payment_Gateway_Hosted::use_auto_form_post()
	 *
	 * @since 2.2.0
	 *
	 * @param \WC_Order $order the order object
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
	 *
	 * @param \WC_Order $order the order object
	 * @param array $request_params associative array of request parameters
	 */
	public function render_auto_post_form( \WC_Order $order, $request_params ) {

		$args = $this->get_auto_post_form_args( $order );

		// attempt to automatically submit the form and redirect
		wc_enqueue_js('
			$( "body" ).block( {
					message: "<img src=\"' . esc_url( $this->get_plugin()->get_framework_assets_url() . '/images/ajax-loader.gif' ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />' . esc_html( $args['thanks_message'] ) . '",
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

		echo '<p>' . esc_html( $args['message'] ) . '</p>';
		echo '<form action="' . esc_url( $args['submit_url'] ) . '" method="post">';

			// Output the param inputs
			echo $this->get_auto_post_form_params_html( $request_params );

			echo '<input type="submit" class="button alt button-alt" id="submit_' . $this->get_id() . '_payment_form" value="' . esc_attr( $args['button_text'] ) . '" />';
			echo '<a class="button cancel" href="' . esc_url( $args['cancel_url'] ) . '">' . esc_html( $args['cancel_text'] ) . '</a>';

		echo '</form>';
	}


	/**
	 * Get the auto post form display arguments.
	 *
	 * @since 4.3.0
	 * @see SV_WC_Payment_Gateway_Hosted::render_auto_post_form() for args
	 *
	 * @param \WC_Order $order the order object
	 * @return array
	 */
	protected function get_auto_post_form_args( \WC_Order $order ) {

		$args = array(
			'submit_url'     => $this->get_hosted_pay_page_url( $order ),
			'cancel_url'     => $order->get_cancel_order_url(),
			'message'        => __( 'Thank you for your order, please click the button below to pay.', 'woocommerce-plugin-framework' ),
			'thanks_message' => __( 'Thank you for your order. We are now redirecting you to complete payment.', 'woocommerce-plugin-framework' ),
			'button_text'    => __( 'Pay Now', 'woocommerce-plugin-framework' ),
			'cancel_text'    => __( 'Cancel Order', 'woocommerce-plugin-framework' ),
		);

		/**
		 * Filter the auto post form display arguments.
		 *
		 * @since 4.3.0
		 * @param array $args {
		 *     The form display arguments.
		 *
		 *     @type string $submit_url     Form submit URL
		 *     @type string $cancel_url     Cancel payment URL
		 *     @type string $message        The message before the form
		 *     @type string $thanks_message The message displayed when the form is submitted
		 *     @type string $button_text    Submit button text
		 *     @type string $cancel_text    Cancel link text
		 * }
		 * @param \WC_Order $order the order object
		 */
		return (array) apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_auto_post_form_args', $args, $order );
	}


	/**
	 * Get the auto post form params HTML.
	 *
	 * This can be overridden by concrete gateways to support more complex param arrays.
	 *
	 * @since 4.3.0
	 * @param array $request_params The request params
	 * @return string
	 */
	protected function get_auto_post_form_params_html( $request_params = array() ) {

		$html = '';

		foreach ( $request_params as $key => $value ) {

			foreach ( (array) $value as $field_value ) {
				$html .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $field_value ) . '" />';
			}
		}

		return $html;
	}


	/**
	 * Returns the gateway hosted pay page parameters, if any
	 *
	 * @since 2.1.0
	 *
	 * @param \WC_Order $order the order object
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
	 *
	 * @see SV_WC_Payment_Gateway_Hosted::get_hosted_pay_page_params()
	 * @param \WC_Order $order optional order object, defaults to null
	 * @return string hosted pay page url, or false if it could not be determined
	 */
	abstract public function get_hosted_pay_page_url( $order = null );


	/**
	 * Handle a payment notification request.
	 *
	 * @since 4.3.0
	 *
	 * @throws \Exception
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

			// Validate the response data such as order ID and payment status
			$this->validate_transaction_response( $order, $response );

			// Handle the order based on the response
			$this->process_transaction_response( $order, $response );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			if ( $order && $order->needs_payment() ) {
				$this->mark_order_as_failed( $order, $e->getMessage(), $response );
			}

			if ( $this->debug_log() ) {

				$this->get_plugin()->log(
					/* translators: Placeholders: %1$s - transaction request type such as IPN or Redirect-back, %2$s - the error message */
					sprintf( '%1$s processing error: %2$s',
					( $response && $response->is_ipn() ) ? 'IPN' : 'Redirect-back',
					$e->getMessage()
				), $this->get_id() );
			}

			$this->do_invalid_transaction_response( $order, $response );
		}
	}


	/**
	 * Gets the order object with transaction data.
	 *
	 * @since 5.0.0
	 *
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response $response response object
	 * @return \WC_Order
	 * @throws SV_WC_Payment_Gateway_Exception
	 * @throws \Exception
	 */
	protected function get_order_from_response( $response ) {

		$order = wc_get_order( $response->get_order_id() );

		// If the order is invalid, bail
		if ( ! $order ) {

			throw new SV_WC_Payment_Gateway_Exception( sprintf(
				/* translators: Placeholders: %s - a WooCommerce order ID */
				__( 'Could not find order %s', 'woocommerce-plugin-framework' ),
				$response->get_order_id()
			) );
		}

		$order = $this->get_order( $order );

		$order->payment->account_number = $response->get_account_number();

		if ( self::PAYMENT_TYPE_CREDIT_CARD == $response->get_payment_type() ) {

			$order->payment->exp_month = $response->get_exp_month();
			$order->payment->exp_year  = $response->get_exp_year();
			$order->payment->card_type = $response->get_card_type();

		} elseif ( self::PAYMENT_TYPE_ECHECK == $response->get_payment_type() ) {

			$order->payment->account_type = $response->get_account_type();
			$order->payment->check_number = $response->get_check_number();
		}

		return $order;
	}


	/**
	 * Gets the order object with payment data added.
	 *
	 * @since 5.0.0
	 * @see SV_WC_Payment_Gateway::get_order()
	 *
	 * @param int|\WC_Order $order_id order ID or object
	 * @return \WC_Order
	 */
	public function get_order( $order_id ) {

		$order = parent::get_order( $order_id );

		/**
		 * Filters the order object after adding gateway data.
		 *
		 * @since 5.0.0
		 *
		 * @param \WC_Order $order order object
		 * @param SV_WC_Payment_Gateway $gateway gateway object
		 */
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_get_order', $order, $this );
	}


	/**
	 * Validate a transaction response.
	 *
	 * @since 4.3.0
	 * @param \WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response the response object
	 * @throws SV_WC_Payment_Gateway_Exception
	 */
	protected function validate_transaction_response( $order, $response ) {

		// If the order has already been completed, bail
		if ( ! $order->needs_payment() ) {

			/* translators: Placeholders: %s - payment gateway title (such as Authorize.net, Braintree, etc) */
			$order->add_order_note( sprintf( esc_html__( '%s duplicate transaction received', 'woocommerce-plugin-framework' ), $this->get_method_title() ) );

			throw new SV_WC_Payment_Gateway_Exception( sprintf(
				__( 'Order %s is already paid for.', 'woocommerce-plugin-framework' ),
				$order->get_order_number()
			) );
		}
	}


	/**
	 * Process the transaction response for the given order
	 *
	 * @since 2.1.0
	 *
	 * @param \WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response $response transaction response
	 * @throws \Exception
	 */
	protected function process_transaction_response( $order, $response ) {

		if ( $response->transaction_approved() || $response->transaction_held() ) {

			// if tokenization is supported, process any token data that might have come back in the response
			if ( $this->supports_tokenization() && $this->tokenization_enabled() && $response instanceof SV_WC_Payment_Gateway_Payment_Notification_Tokenization_Response ) {
				$order = $this->process_tokenization_response( $order, $response );
			}

			// always add transaction data to the order for approved and held transactions
			$this->add_transaction_data( $order, $response );

			// let gateways easily add their own data
			$this->add_payment_gateway_transaction_data( $order, $response );

			// handle the order status, etc...
			$this->complete_payment( $order, $response );

			// do the final transaction action, like a redirect
			if ( $response->transaction_approved() ) {
				$this->do_transaction_approved( $order, $response );
			} elseif ( $response->transaction_held() ) {
				$this->do_transaction_held( $order, $response );
			}

		} elseif ( $response->transaction_cancelled() ) {

			$this->mark_order_as_cancelled( $order, $response->get_status_message(), $response );

			$this->do_transaction_cancelled( $order, $response );

		} else {

			// Add the order note and debug info
			$this->do_transaction_failed_result( $order, $response );

			$this->do_transaction_failed( $order, $response );
		}
	}


	/**
	 * Processes a transaction response's token data, if any.
	 *
	 * @since 5.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param SV_WC_Payment_Gateway_Payment_Notification_Tokenization_Response $response response object
	 * @return \WC_Order order object
	 * @throws \Exception
	 */
	protected function process_tokenization_response( \WC_Order $order, $response ) {

		if ( is_callable( array( $response, 'get_customer_id' ) ) && $response->get_customer_id() ) {
			$order->customer_id = $response->get_customer_id();
		}

		$token = $response->get_payment_token();

		if ( $order->get_user_id() ) {

			if ( $response->payment_method_tokenized() ) {

				if ( $response->tokenization_successful() && $this->get_payment_tokens_handler()->add_token( $order->get_user_id(), $token ) ) {

					// order note based on gateway type
					if ( $token->is_credit_card() ) {

						/* translators: Placeholders: %1$s - payment gateway title (such as Authorize.net, Braintree, etc), %2$s - payment method name (mastercard, bank account, etc), %3$s - last four digits of the card/account, %4$s - card/account expiry date */
						$order->add_order_note( sprintf( __( '%1$s Payment Method Saved: %2$s ending in %3$s (expires %4$s)', 'woocommerce-plugin-framework' ),
							$this->get_method_title(),
							$token->get_type_full(),
							$token->get_last_four(),
							$token->get_exp_date()
						) );

					} elseif ( $token->is_echeck() ) {

						// account type (checking/savings) may or may not be available, which is fine
						/* translators: Placeholders: %1$s - payment gateway title (such as CyberSouce, NETbilling, etc), %2$s - account type (checking/savings - may or may not be available), %3$s - last four digits of the account */
						$order->add_order_note( sprintf( __( '%1$s eCheck Payment Method Saved: %2$s account ending in %3$s', 'woocommerce-plugin-framework' ),
							$this->get_method_title(),
							$token->get_account_type(),
							$token->get_last_four()
						) );

					} else {

						/* translators: Placeholders: %s - payment gateway title (such as CyberSouce, NETbilling, etc) */
						$order->add_order_note( sprintf( __( '%s Payment Method Saved', 'woocommerce-plugin-framework' ),
							$this->get_method_title()
						) );
					}

				} else {

					$message = sprintf(
						/* translators: Placeholders: %s - a failed tokenization API error */
						__( 'Tokenization failed. %s', 'woocommerce-plugin-framework' ),
						$response->get_tokenization_message()
					);

					$this->mark_order_as_held( $order, $message, $response );
				}
			}

			// get a fresh copy of the token object just in case the response doesn't include all of the method details
			$token = $this->get_payment_tokens_handler()->get_token( $order->get_user_id(), $token->get_id() );
		}

		// add the payment method order data
		if ( $token ) {

			$order->payment->token          = $token->get_id();
			$order->payment->account_number = $token->get_last_four();
			$order->payment->last_four      = $token->get_last_four();

			if ( $token->is_credit_card() ) {

				$order->payment->exp_month = $token->get_exp_month();
				$order->payment->exp_year  = $token->get_exp_year();
				$order->payment->card_type = $token->get_card_type();

			} elseif ( $token->is_echeck() ) {

				$order->payment->account_type = $token->get_account_type();
				$order->payment->check_number = $token->get_check_number();
			}
		}

		// remove any tokens that were deleted on the hosted pay page
		foreach ( $response->get_deleted_payment_tokens() as $token_id ) {

			$tokens = $this->get_payment_tokens_handler()->get_tokens( $order->get_user_id() );

			unset( $tokens[ $token_id ] );

			$this->get_payment_tokens_handler()->update_tokens( $order->get_user_id(), $tokens );
		}

		return $order;
	}


	/**
	 * Adds an order note, along with anything else required after an approved
	 * transaction.  This is a generic, default approved handler.
	 *
	 * @since 2.1.0
	 *
	 * @param \WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response $response the response object
	 */
	protected function do_transaction_approved( \WC_Order $order, $response ) {

		// Die or redirect
		if ( $response->is_ipn() ) {

			status_header( 200 );
			die;

		} else {

			wp_redirect( $this->get_return_url( $order ) );
			exit;
		}
	}


	/**
	 * Handle a held transaction response.
	 *
	 * @since 4.3.0
	 *
	 * @param \WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response $response the response object
	 */
	protected function do_transaction_held( \WC_Order $order, $response ) {

		if ( $response->is_ipn() ) {

			status_header( 200 );
			die;

		} else {

			wp_redirect( $this->get_return_url( $order ) );
			exit;
		}
	}


	/**
	 * Handles a cancelled transaction response.
	 *
	 * @since 4.3.0
	 *
	 * @param \WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response $response the response object
	 */
	protected function do_transaction_cancelled( \WC_Order $order, $response ) {

		if ( $response->is_ipn() ) {

			status_header( 200 );
			die;

		} else {

			wp_redirect( $order->get_cancel_order_url() );
			exit;
		}
	}


	/**
	 * Handles a failed transaction response.
	 *
	 * @since 4.3.0
	 *
	 * @param \WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response $response the response object
	 */
	protected function do_transaction_failed( \WC_Order $order, $response ) {

		if ( $response->is_ipn() ) {

			status_header( 200 );
			die;

		} else {

			wp_redirect( $order->get_checkout_payment_url( $this->use_form_post() && ! $this->use_auto_form_post() ) );
			exit;
		}
	}


	/**
	 * Handles an invalid transaction response.
	 *
	 * i.e. the order has already been paid or was not found
	 *
	 * @since 4.3.0
	 *
	 * @param \WC_Order $order Optional. The order object
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response $response the response object
	 */
	protected function do_invalid_transaction_response( $order = null, $response ) {

		if ( $response->is_ipn() ) {

			status_header( 200 );
			die();

		} else {

			if ( $order ) {
				wp_redirect( $this->get_return_url( $order ) );
				exit;
			} else {
				wp_redirect( get_home_url( null, '' ) );
				exit;
			}
		}
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
		if ( wc_checkout_is_https() ) {
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
	 * Logs pay page form submission request.
	 *
	 * @since 2.1.0
	 *
	 * @param array $request the request data associative array, which should include members 'method', 'uri', 'body'
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
	 * Logs IPN/redirect-back transaction response request to the log file.
	 *
	 * @since 2.1.0
	 *
	 * @param array|string $response the request data
	 * @param string $message optional message string with a %s to hold the
	 *        response data.  Defaults to 'Request %s'
	 * $response
	 */
	public function log_transaction_response_request( $response, $message = null ) {

		// add log message to WC logger if log/both is enabled
		if ( $this->debug_log() ) {

			// if a message wasn't provided, make our best effort
			if ( is_null( $message ) ) {
				$message = 'Request: %s';
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


}


endif;
