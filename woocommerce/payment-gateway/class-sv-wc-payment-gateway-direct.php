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
 * @copyright Copyright (c) 2013-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_Payment_Gateway_Direct' ) ) :

/**
 * # WooCommerce Payment Gateway Framework Direct Gateway
 *
 * @since 1.0.0
 */
abstract class SV_WC_Payment_Gateway_Direct extends SV_WC_Payment_Gateway {


	/** Add new payment method feature */
	const FEATURE_ADD_PAYMENT_METHOD = 'add_payment_method';

	/** Subscriptions feature */
	const FEATURE_SUBSCRIPTIONS = 'subscriptions';

	/** Subscription payment method change feature */
	const FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE = 'subscription_payment_method_change';

	/** Pre-orders feature */
	const FEATURE_PRE_ORDERS = 'pre-orders';

	/** @var array array of cached user id to array of SV_WC_Payment_Gateway_Payment_Token token objects */
	protected $tokens;


	/**
	 * Initialize the gateway
	 *
	 * See parent constructor for full method documentation
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::__construct()
	 * @param string $id the gateway id
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the parent plugin class
	 * @param string $text_domain the plugin text domain
	 * @param array $args gateway arguments
	 */
	public function __construct( $id, $plugin, $text_domain, $args ) {

		// parent constructor
		parent::__construct( $id, $plugin, $text_domain, $args );

		// watch for subscriptions support
		if ( $this->get_plugin()->is_subscriptions_active() ) {

			$subscription_support_hook               = 'wc_payment_gateway_' . $this->get_id() . '_supports_' . self::FEATURE_SUBSCRIPTIONS;
			$subscription_payment_method_change_hook = 'wc_payment_gateway_' . $this->get_id() . '_supports_' . self::FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE;

			if ( ! has_action( $subscription_support_hook ) ) {
				add_action( $subscription_support_hook, array( $this, 'add_subscriptions_support' ) );
			}

			if ( ! has_action( $subscription_payment_method_change_hook ) ) {
				add_action( $subscription_payment_method_change_hook, array( $this, 'add_subscription_payment_method_change_support' ) );
			}
		}

		// watch for pre-orders support
		if ( $this->get_plugin()->is_pre_orders_active() ) {

			$pre_orders_support_hook = 'wc_payment_gateway_' . $this->get_id() . '_supports_' . str_replace( '-', '_', self::FEATURE_PRE_ORDERS );

			if ( ! has_action( $pre_orders_support_hook ) ) {
				add_action( $pre_orders_support_hook, array( $this, 'add_pre_orders_support' ) );
			}
		}
	}


	/**
	 * Validate the payment fields when processing the checkout
	 *
	 * NOTE: if we want to bring billing field validation (ie length) into the
	 * fold, see the Elavon VM Payment Gateway for a sample implementation
	 *
	 * @since 1.0.0
	 * @see WC_Payment_Gateway::validate_fields()
	 * @return bool true if fields are valid, false otherwise
	 */
	public function validate_fields() {

		$is_valid = parent::validate_fields();

		if ( $this->supports_tokenization() ) {

			// tokenized transaction?
			if ( SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) {

				// unknown token?
				if ( ! $this->has_payment_token( get_current_user_id(), SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) ) {
					SV_WC_Helper::wc_add_notice( _x( 'Payment error, please try another payment method or contact us to complete your transaction.', 'Supports tokenization', $this->text_domain ), 'error' );
					$is_valid = false;
				}

				// no more validation to perform
				return $is_valid;
			}
		}

		// validate remaining payment fields
		if ( $this->is_credit_card_gateway() ) {
			return $this->validate_credit_card_fields( $is_valid );
		} elseif ( $this->is_echeck_gateway() ) {
			return $this->validate_check_fields( $is_valid );
		} else {
			$method_name = 'validate_' . str_replace( '-', '_', strtolower( $this->get_payment_type() ) ) . '_fields';
			if ( method_exists( $this, $method_name ) ) {
				return $this->$method_name( $is_valid );
			}
		}
		
		// no more validation to perform. Return the parent method's outcome.
		return $is_valid;

	}


	/**
	 * Returns true if the posted credit card fields are valid, false otherwise
	 *
	 * @since 1.0.0
	 * @param boolean $is_valid true if the fields are valid, false otherwise
	 * @return boolean true if the fields are valid, false otherwise
	 */
	protected function validate_credit_card_fields( $is_valid ) {

		$account_number   = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-account-number' );
		$expiration_month = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-exp-month' );
		$expiration_year  = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-exp-year' );
		$expiry           = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-expiry' );
		$csc              = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-csc' );

		// handle single expiry field formatted like "MM / YY" or "MM / YYYY"
		if ( ! $expiration_month & ! $expiration_year && $expiry ) {
			list( $expiration_month, $expiration_year ) = array_map( 'trim', explode( '/', $expiry ) );
		}

		$is_valid = $this->validate_credit_card_account_number( $account_number ) && $is_valid;

		$is_valid = $this->validate_credit_card_expiration_date( $expiration_month, $expiration_year ) && $is_valid;

		// validate card security code
		if ( $this->csc_enabled() ) {
			$is_valid = $this->validate_csc( $csc ) && $is_valid;
		}

		return $is_valid;
	}


	/**
	 * Validates the provided credit card expiration date
	 *
	 * @since 2.1.0
	 * @param string $expiration_month the credit card expiration month
	 * @param string $expiration_year the credit card expiration month
	 * @return boolean true if the card expiration date is valid, false otherwise
	 */
	protected function validate_credit_card_expiration_date( $expiration_month, $expiration_year ) {

		$is_valid = true;

		if ( 2 === strlen( $expiration_year ) ) {
			$expiration_year = '20' . $expiration_year;
		}

		// validate expiration data
		$current_year  = date( 'Y' );
		$current_month = date( 'n' );

		if ( ! ctype_digit( $expiration_month ) || ! ctype_digit( $expiration_year ) ||
			$expiration_month > 12 ||
			$expiration_month < 1 ||
			$expiration_year < $current_year ||
			( $expiration_year == $current_year && $expiration_month < $current_month ) ||
			$expiration_year > $current_year + 20
		) {
			SV_WC_Helper::wc_add_notice( _x( 'Card expiration date is invalid', 'Supports direct credit card', $this->text_domain ), 'error' );
			$is_valid = false;
		}

		return $is_valid;
	}


	/**
	 * Validates the provided credit card account number
	 *
	 * @since 2.1.0
	 * @param string $account_number the credit card account number
	 * @return boolean true if the card account number is valid, false otherwise
	 */
	protected function validate_credit_card_account_number( $account_number ) {

		$is_valid = true;

		// validate card number
		$account_number = str_replace( array( ' ', '-' ), '', $account_number );

		if ( empty( $account_number ) ) {

			SV_WC_Helper::wc_add_notice( _x( 'Card number is missing', 'Supports direct credit card', $this->text_domain ), 'error' );
			$is_valid = false;

		} else {

			if ( strlen( $account_number ) < 12 || strlen( $account_number ) > 19 ) {
				SV_WC_Helper::wc_add_notice( _x( 'Card number is invalid (wrong length)', 'Supports direct credit card', $this->text_domain ), 'error' );
				$is_valid = false;
			}

			if ( ! ctype_digit( $account_number ) ) {
				SV_WC_Helper::wc_add_notice( _x( 'Card number is invalid (only digits allowed)', 'Supports direct credit card', $this->text_domain ), 'error' );
				$is_valid = false;
			}

			if ( ! SV_WC_Payment_Gateway_Helper::luhn_check( $account_number ) ) {
				SV_WC_Helper::wc_add_notice( _x( 'Card number is invalid', 'Supports direct credit card', $this->text_domain ), 'error' );
				$is_valid = false;
			}

		}

		return $is_valid;
	}


	/**
	 * Validates the provided Card Security Code, adding user error messages as
	 * needed
	 *
	 * @since 1.0.0
	 * @param string $csc the customer-provided card security code
	 * @return boolean true if the card security code is valid, false otherwise
	 */
	protected function validate_csc( $csc ) {

		$is_valid = true;

		// validate security code
		if ( empty( $csc ) ) {

			SV_WC_Helper::wc_add_notice( _x( 'Card security code is missing', 'Supports direct credit card', $this->text_domain ), 'error' );
			$is_valid = false;

		} else {

			// digit validation
			if ( ! ctype_digit( $csc ) ) {
				SV_WC_Helper::wc_add_notice( _x( 'Card security code is invalid (only digits are allowed)', 'Supports direct credit card', $this->text_domain ), 'error' );
				$is_valid = false;
			}

			// length validation
			if ( strlen( $csc ) < 3 || strlen( $csc ) > 4 ) {
				SV_WC_Helper::wc_add_notice( _x( 'Card security code is invalid (must be 3 or 4 digits)', 'Supports direct credit card', $this->text_domain ), 'error' );
				$is_valid = false;
			}

		}

		return $is_valid;
	}


	/**
	 * Returns true if the posted echeck fields are valid, false otherwise
	 *
	 * @since 1.0.0
	 * @param bool $is_valid true if the fields are valid, false otherwise
	 * @return bool
	 */
	protected function validate_check_fields( $is_valid ) {

		$account_number         = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-account-number' );
		$routing_number         = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-routing-number' );

		// optional fields (excluding account type for now)
		$drivers_license_number = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-drivers-license-number' );
		$check_number           = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-check-number' );

		// routing number exists?
		if ( empty( $routing_number ) ) {

			SV_WC_Helper::wc_add_notice( _x( 'Routing Number is missing', 'Supports direct cheque', $this->text_domain ), 'error' );
			$is_valid = false;

		} else {

			// routing number digit validation
			if ( ! ctype_digit( $routing_number ) ) {
				SV_WC_Helper::wc_add_notice( _x( 'Routing Number is invalid (only digits are allowed)', 'Supports direct cheque', $this->text_domain ), 'error' );
				$is_valid = false;
			}

			// routing number length validation
			if ( 9 != strlen( $routing_number ) ) {
				SV_WC_Helper::wc_add_notice( _x( 'Routing number is invalid (must be 9 digits)', 'Supports direct cheque', $this->text_domain ), 'error' );
				$is_valid = false;
			}

		}

		// account number exists?
		if ( empty( $account_number ) ) {

			SV_WC_Helper::wc_add_notice( _x( 'Account Number is missing', 'Supports direct cheque', $this->text_domain ), 'error' );
			$is_valid = false;

		} else {

			// account number digit validation
			if ( ! ctype_digit( $account_number ) ) {
				SV_WC_Helper::wc_add_notice( _x( 'Account Number is invalid (only digits are allowed)', 'Supports direct cheque', $this->text_domain ), 'error' );
				$is_valid = false;
			}

			// account number length validation
			if ( strlen( $account_number ) < 5 || strlen( $account_number ) > 17 ) {
				SV_WC_Helper::wc_add_notice( _x( 'Account number is invalid (must be between 5 and 17 digits)', 'Supports direct cheque', $this->text_domain ), 'error' );
				$is_valid = false;
			}
		}

		// optional drivers license number validation
		if ( ! empty( $drivers_license_number ) &&  preg_match( '/^[a-zA-Z0-9 -]+$/', $drivers_license_number ) ) {
			SV_WC_Helper::wc_add_notice( _x( 'Drivers license number is invalid', 'Supports direct cheque', $this->text_domain ), 'error' );
			$is_valid = false;
		}

		// optional check number validation
		if ( ! empty( $check_number ) && ! ctype_digit( $check_number ) ) {
			SV_WC_Helper::wc_add_notice( _x( 'Check Number is invalid (only digits are allowed)', 'Supports direct cheque', $this->text_domain ), 'error' );
			$is_valid = false;
		}

		return $is_valid;
	}


	/**
	 * Returns true if tokenization takes place prior authorization/charge
	 * transaction.
	 *
	 * Defaults to false but can be overridden by child gateway class
	 *
	 * @since 2.1.0
	 * @return boolean true if there is a tokenization request that is issued
	 *         before a authorization/charge transaction
	 */
	public function tokenize_before_sale() {
		return false;
	}


	/**
	 * Returns true if authorization/charge requests also tokenize the payment
	 * method.  False if this gateway has a separate "tokenize" method which
	 * is always used.
	 *
	 * Defaults to false but can be overridden by child gateway class
	 *
	 * @since 2.0.0
	 * @return boolean true if tokenization is combined with sales, false if
	 *         there is a special request for tokenization
	 */
	public function tokenize_with_sale() {
		return false;
	}


	/**
	 * Returns true if tokenization takes place after an authorization/charge
	 * transaction.
	 *
	 * Defaults to false but can be overridden by child gateway class
	 *
	 * @since 2.1.0
	 * @return boolean true if there is a tokenization request that is issued
	 *         after an authorization/charge transaction
	 */
	public function tokenize_after_sale() {
		return false;
	}


	/**
	 * Handles payment processing
	 *
	 * @since 1.0.0
	 * @see WC_Payment_Gateway::process_payment()
	 * @param int|string $order_id
	 */
	public function process_payment( $order_id ) {

		// give other actors an opportunity to intercept and implement the process_payment() call for this transaction
		if ( true !== ( $result = apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_process_payment', true, $order_id, $this ) ) ) {
			return $result;
		}

		// add payment information to order
		$order = $this->get_order( $order_id );

		try {

			// registered customer checkout (already logged in or creating account at checkout)
			if ( $this->supports_tokenization() && 0 != $order->get_user_id() && $this->should_tokenize_payment_method() &&
				( 0 == $order->payment_total || $this->tokenize_before_sale() ) ) {
				$order = $this->create_payment_token( $order );
			}

			// payment failures are handled internally by do_transaction()
			// the order amount will be $0 if a WooCommerce Subscriptions free trial product is being processed
			// note that customer id & payment token are saved to order when create_payment_token() is called
			if ( ( 0 == $order->payment_total && ! $this->transaction_forced() ) || $this->do_transaction( $order ) ) {

				// add transaction data for zero-dollar "orders"
				if ( 0 == $order->payment_total ) {
					$this->add_transaction_data( $order );
				}

				if ( $order->has_status( 'on-hold' ) ) {
					$order->reduce_order_stock(); // reduce stock for held orders, but don't complete payment
				} else {
					$order->payment_complete(); // mark order as having received payment
				}

				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}

		} catch ( SV_WC_Plugin_Exception $e ) {

			$this->mark_order_as_failed( $order, $e->getMessage() );

		}
	}


	/**
	 * Add payment and transaction information as class members of WC_Order
	 * instance.  The standard information that can be added includes:
	 *
	 * $order->payment_total           - the payment total
	 * $order->customer_id             - optional payment gateway customer id (useful for tokenized payments for certain gateways, etc)
	 * $order->payment->account_number - the credit card or checking account number
	 * $order->payment->last_four      - the last four digits of the account number
	 * $order->payment->card_type      - the card type (e.g. visa) derived from the account number
	 * $order->payment->routing_number - account routing number (check transactions only)
	 * $order->payment->account_type   - optional type of account one of 'checking' or 'savings' if type is 'check'
	 * $order->payment->card_type      - optional card type, ie one of 'visa', etc
	 * $order->payment->exp_month      - the credit card expiration month (for credit card gateways)
	 * $order->payment->exp_year       - the credit card expiration year (for credit card gateways)
	 * $order->payment->csc            - the card security code (for credit card gateways)
	 * $order->payment->check_number   - optional check number (check transactions only)
	 * $order->payment->drivers_license_number - optional driver license number (check transactions only)
	 * $order->payment->drivers_license_state  - optional driver license state code (check transactions only)
	 * $order->payment->token          - payment token (for tokenized transactions)
	 *
	 * Note that not all gateways will necessarily pass or require all of the
	 * above.  These represent the most common attributes used among a variety
	 * of gateways, it's up to the specific gateway implementation to make use
	 * of, or ignore them, or add custom ones by overridding this method.
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::get_order()
	 * @param int|\WC_Order $order_id order ID being processed
	 * @return WC_Order object with payment and transaction information attached
	 */
	protected function get_order( $order_id ) {

		$order = parent::get_order( $order_id );

		// payment info
		if ( SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-account-number' ) && ! SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) {

			// common attributes
			$order->payment->account_number = str_replace( array( ' ', '-' ), '', SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-account-number' ) );
			$order->payment->last_four = substr( $order->payment->account_number, -4 );

			if ( $this->is_credit_card_gateway() ) {

				// credit card specific attributes
				$order->payment->card_type      = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-card-type' );
				$order->payment->exp_month      = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-exp-month' );
				$order->payment->exp_year       = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-exp-year' );

				// add card type for gateways that don't require it displayed at checkout
				if ( empty( $order->payment->card_type ) ) {
					$order->payment->card_type = SV_WC_Payment_Gateway_Helper::card_type_from_account_number( $order->payment->account_number );
				}

				// handle single expiry field formatted like "MM / YY" or "MM / YYYY"
				if ( SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-expiry' ) ) {
					list( $order->payment->exp_month, $order->payment->exp_year ) = array_map( 'trim', explode( '/', SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-expiry' ) ) );
				}

				// add CSC if enabled
				if ( $this->csc_enabled() ) {
					$order->payment->csc        = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-csc' );
				}

			} elseif ( $this->is_echeck_gateway() ) {

				// echeck specific attributes
				$order->payment->routing_number         = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-routing-number' );
				$order->payment->account_type           = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-account-type' );
				$order->payment->check_number           = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-check-number' );
				$order->payment->drivers_license_number = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-drivers-license-number' );
				$order->payment->drivers_license_state  = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-drivers-license-state' );

			}

		} elseif ( SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) {

			// paying with tokenized payment method (we've already verified that this token exists in the validate_fields method)
			$token = $this->get_payment_token( $order->get_user_id(), SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) );

			$order->payment->token          = $token->get_token();
			$order->payment->account_number = $token->get_last_four();
			$order->payment->last_four      = $token->get_last_four();

			if ( $this->is_credit_card_gateway() ) {

				// credit card specific attributes
				$order->payment->card_type = $token->get_card_type();
				$order->payment->exp_month = $token->get_exp_month();
				$order->payment->exp_year  = $token->get_exp_year();

				if ( $this->csc_enabled() ) {
					$order->payment->csc      = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-csc' );
				}

			} elseif ( $this->is_echeck_gateway() ) {

				// echeck specific attributes
				$order->payment->account_type = $token->get_account_type();

			}

			// make this the new default payment token
			$this->set_default_payment_token( $order->get_user_id(), $token );
		}

		// allow other actors to modify the order object
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_get_order', $order, $this );
	}


	/**
	 * Add payment and transaction information as class members of WC_Order
	 * instance for use in credit card capture transactions.  Standard information
	 * can include:
	 *
	 * $order->capture_total - the capture total
	 *
	 * @since 2.0.0
	 * @param WC_Order $order order being processed
	 * @return WC_Order object with payment and transaction information attached
	 */
	protected function get_order_for_capture( $order ) {

		// set capture total here so it can be modified later as needed prior to capture
		$order->capture_total = number_format( $order->get_total(), 2, '.', '' );

		// capture-specific order description
		$order->description = sprintf( _x( '%s - Capture for Order %s', 'Capture order description', $this->text_domain ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() );

		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_get_order_for_capture', $order, $this );
	}


	/**
	 * Performs a check transaction for the given order and returns the
	 * result
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order object
	 * @return SV_WC_Payment_Gateway_API_Response the response
	 * @throws SV_WC_Payment_Gateway_Exception network timeouts, etc
	 */
	protected function do_check_transaction( $order ) {

		$response = $this->get_api()->check_debit( $order );

		// success! update order record
		if ( $response->transaction_approved() ) {

			$last_four = substr( $order->payment->account_number, -4 );

			// check order note.  there may not be an account_type available, but that's fine
			$message = sprintf( _x( '%s Check Transaction Approved: %s account ending in %s', 'Supports direct cheque', $this->text_domain ), $this->get_method_title(), $order->payment->account_type, $last_four );

			// optional check number
			if ( ! empty( $order->payment->check_number ) ) {
				$message .= '. ' . sprintf( _x( 'Check number %s', 'Supports direct cheque', $this->text_domain ), $order->payment->check_number );
			}

			// adds the transaction id (if any) to the order note
			if ( $response->get_transaction_id() ) {
				$message .= ' ' . sprintf( _x( '(Transaction ID %s)', 'Supports direct cheque', $this->text_domain ), $response->get_transaction_id() );
			}

			$order->add_order_note( $message );

		}

		return $response;

	}


	/**
	 * Performs a credit card transaction for the given order and returns the
	 * result
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response $response optional credit card transaction response
	 * @return SV_WC_Payment_Gateway_API_Response the response
	 * @throws SV_WC_Payment_Gateway_Exception network timeouts, etc
	 */
	protected function do_credit_card_transaction( $order, $response = null ) {

		if ( is_null( $response ) ) {
			if ( $this->perform_credit_card_charge() ) {
				$response = $this->get_api()->credit_card_charge( $order );
			} else {
				$response = $this->get_api()->credit_card_authorization( $order );
			}
		}

		// success! update order record
		if ( $response->transaction_approved() ) {

			$last_four = substr( $order->payment->account_number, -4 );

			// use direct card type if set, or try to guess it from card number
			if ( ! empty( $order->payment->card_type ) ) {
				$card_type = $order->payment->card_type;
			} elseif ( $first_four = substr( $order->payment->account_number, 0, 4 ) ) {
				$card_type = SV_WC_Payment_Gateway_Helper::card_type_from_account_number( $first_four );
			} else {
				$card_type = 'card';
			}

			// credit card order note
			$message = sprintf(
				_x( '%s %s %s Approved: %s ending in %s (expires %s)', 'Supports direct credit card', $this->text_domain ),
				$this->get_method_title(),
				$this->is_test_environment() ? _x( 'Test', 'Supports direct credit card', $this->text_domain ) : '',
				$this->perform_credit_card_authorization() ? 'Authorization' : 'Charge',
				SV_WC_Payment_Gateway_Helper::payment_type_to_name( $card_type ),
				$last_four,
				$order->payment->exp_month . '/' . substr( $order->payment->exp_year, -2 )
			);

			// adds the transaction id (if any) to the order note
			if ( $response->get_transaction_id() ) {
				$message .= ' ' . sprintf( _x( '(Transaction ID %s)', 'Supports direct credit card', $this->text_domain ), $response->get_transaction_id() );
			}

			$order->add_order_note( $message );

		}

		return $response;

	}


	/**
	 * Create a transaction
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order object
	 * @return bool true if transaction was successful, false otherwise
	 * @throws SV_WC_Payment_Gateway_Exception network timeouts, etc
	 */
	protected function do_transaction( $order ) {

		// perform the credit card or check transaction
		if ( $this->is_credit_card_gateway() ) {
			$response = $this->do_credit_card_transaction( $order );
		} elseif ( $this->is_echeck_gateway() ) {
			$response = $this->do_check_transaction( $order );
		} else {
			$do_payment_type_transaction = 'do_' . $this->get_payment_type() . '_transaction';
			$response = $this->$do_payment_type_transaction( $order );
		}

		// handle the response
		if ( $response->transaction_approved() || $response->transaction_held() ) {

			if ( $this->supports_tokenization() && 0 != $order->get_user_id() && $this->should_tokenize_payment_method() &&
				( $order->payment_total > 0 && ( $this->tokenize_with_sale() || $this->tokenize_after_sale() ) ) ) {

				try {
					$order = $this->create_payment_token( $order, $response );
				} catch ( SV_WC_Plugin_Exception $e ) {

					// handle the case of a "tokenize-after-sale" request failing by marking the order as on-hold with an explanatory note
					if ( ! $response->transaction_held() && ! ( $this->supports( self::FEATURE_CREDIT_CARD_AUTHORIZATION ) && $this->perform_credit_card_authorization() ) ) {

						// transaction has already been successful, but we've encountered an issue with the post-tokenization, add an order note to that effect and continue on
						$message = sprintf(
							__( 'Tokenization Request Failed: %s', $this->text_domain ),
							$e->getMessage()
						);

						$this->mark_order_as_held( $order, $message, $response );

					} else {

						// transaction has already been successful, but we've encountered an issue with the post-tokenization, add an order note to that effect and continue on
						$message = sprintf(
							__( '%s Tokenization Request Failed: %s', $this->text_domain ),
							$this->get_method_title(),
							$e->getMessage()
						);

						$order->add_order_note( $message );
					}
				}
			}

			// add the standard transaction data
			$this->add_transaction_data( $order, $response );

			// allow the concrete class to add any gateway-specific transaction data to the order
			$this->add_payment_gateway_transaction_data( $order, $response );

			// if the transaction was held (ie fraud validation failure) mark it as such
			// TODO: consider checking whether the response *was* an authorization, rather than blanket-assuming it was because of the settings.  There are times when an auth will be used rather than charge, ie when performing in-plugin AVS handling (moneris)
			if ( $response->transaction_held() || ( $this->supports( self::FEATURE_CREDIT_CARD_AUTHORIZATION ) && $this->perform_credit_card_authorization() ) ) {
				// TODO: need to make this more flexible, and not force the message to 'Authorization only transaction' for auth transactions (re moneris efraud handling)
				$this->mark_order_as_held( $order, $this->supports( self::FEATURE_CREDIT_CARD_AUTHORIZATION ) && $this->perform_credit_card_authorization() ? _x( 'Authorization only transaction', 'Supports credit card authorization', $this->text_domain ) : $response->get_status_message(), $response );
			}

			return true;

		} else { // failure

			return $this->do_transaction_failed_result( $order, $response );

		}
	}


	/**
	 * Perform a credit card capture for the given order
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order
	 * @return null|SV_WC_Payment_Gateway_API_Response the response of the capture attempt
	 */
	public function do_credit_card_capture( $order ) {

		$order = $this->get_order_for_capture( $order );

		try {

			$response = $this->get_api()->credit_card_capture( $order );

			if ( $response->transaction_approved() ) {

				$message = sprintf(
					_x( '%s Capture of %s Approved', 'Supports capture charge', $this->text_domain ),
					$this->get_method_title(),
					get_woocommerce_currency_symbol() . wc_format_decimal( $order->capture_total )
				);

				// adds the transaction id (if any) to the order note
				if ( $response->get_transaction_id() ) {
					$message .= ' ' . sprintf( _x( '(Transaction ID %s)', 'Supports capture charge', $this->text_domain ), $response->get_transaction_id() );
				}

				$order->add_order_note( $message );

				// prevent stock from being reduced when payment is completed as this is done when the charge was authorized
				add_filter( 'woocommerce_payment_complete_reduce_order_stock', '__return_false', 100 );

				// complete the order
				$order->payment_complete();

				// add the standard capture data to the order
				$this->add_capture_data( $order, $response );

				// let payment gateway implementations add their own data
				$this->add_payment_gateway_capture_data( $order, $response );

			} else {

				$message = sprintf(
					_x( '%s Capture Failed: %s - %s', 'Supports capture charge', $this->text_domain ),
					$this->get_method_title(),
					$response->get_status_code(),
					$response->get_status_message()
				);

				$order->add_order_note( $message );

			}

			return $response;

		} catch ( SV_WC_Plugin_Exception $e ) {

			$message = sprintf(
				_x( '%s Capture Failed: %s', 'Supports capture charge', $this->text_domain ),
				$this->get_method_title(),
				$e->getMessage()
			);

			$order->add_order_note( $message );

			return null;
		}
	}


	/**
	 * Adds the standard transaction data to the order
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::add_transaction_data()
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response|null $response optional transaction response
	 */
	protected function add_transaction_data( $order, $response = null ) {

		// add parent transaction data
		parent::add_transaction_data( $order, $response );

		// payment info
		if ( isset( $order->payment->token ) && $order->payment->token ) {
			$this->update_order_meta( $order->id, 'payment_token', $order->payment->token );
		}

		// account number
		if ( isset( $order->payment->account_number ) && $order->payment->account_number ) {
			$this->update_order_meta( $order->id, 'account_four', substr( $order->payment->account_number, -4 ) );
		}

		if ( $this->is_credit_card_gateway() ) {

			// credit card gateway data
			if ( $response && $response instanceof SV_WC_Payment_Gateway_API_Authorization_Response ) {

				if ( $response->get_authorization_code() ) {
					$this->update_order_meta( $order->id, 'authorization_code', $response->get_authorization_code() );
				}

				if ( $order->payment_total > 0 ) {
					// mark as captured
					if ( $this->perform_credit_card_charge() ) {
						$captured = 'yes';
					} else {
						$captured = 'no';
					}
					$this->update_order_meta( $order->id, 'charge_captured', $captured );
				}

			}

			if ( isset( $order->payment->exp_year ) && $order->payment->exp_year && isset( $order->payment->exp_month ) && $order->payment->exp_month ) {
				$this->update_order_meta( $order->id, 'card_expiry_date', $order->payment->exp_year . '-' . $order->payment->exp_month );
			}

			if ( isset( $order->payment->card_type ) && $order->payment->card_type ) {
				$this->update_order_meta( $order->id, 'card_type', $order->payment->card_type );
			}

		} elseif ( $this->is_echeck_gateway() ) {

			// checking gateway data

			// optional account type (checking/savings)
			if ( isset( $order->payment->account_type ) && $order->payment->account_type ) {
				$this->update_order_meta( $order->id, 'account_type', $order->payment->account_type );
			}

			// optional check number
			if ( isset( $order->payment->check_number ) && $order->payment->check_number ) {
				$this->update_order_meta( $order->id, 'check_number', $order->payment->check_number );
			}
		}
	}


	/**
	 * Adds the standard capture data to the order
	 *
	 * @since 2.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response $response transaction response
	 */
	protected function add_capture_data( $order, $response ) {

		// mark the order as captured
		$this->update_order_meta( $order->id, 'charge_captured', 'yes' );

		// add capture transaction ID
		if ( $response && $response->get_transaction_id() ) {
			$this->update_order_meta( $order->id, 'capture_trans_id', $response->get_transaction_id() );
		}
	}


	/**
	 * Adds any gateway-specific data to the order after a capture is performed
	 *
	 * @since 2.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response $response the transaction response
	 */
	protected function add_payment_gateway_capture_data( $order, $response ) {
		// Optional method
	}


	/** Subscriptions feature ******************************************************/


	/**
	 * Returns true if this gateway with its current configuration supports subscriptions
	 *
	 * @since 1.0.0
	 * @return boolean true if the gateway supports subscriptions
	 */
	public function supports_subscriptions() {
		return $this->supports( self::FEATURE_SUBSCRIPTIONS ) && $this->supports_tokenization() && $this->tokenization_enabled();
	}


	/**
	 * Adds support for subscriptions by hooking in some necessary actions
	 *
	 * @since 1.0.0
	 */
	public function add_subscriptions_support() {

		// bail if subscriptions are not supported by this gateway or its current configuration
		if ( ! $this->supports_subscriptions() ) {
			// note: no longer throwing an exception due to a weird race condition with multiple instances
			// of this class, with action callbacks getting mixed up, and stale data causing problems
			return;
		}

		// force tokenization when needed
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_tokenization_forced', array( $this, 'subscriptions_tokenization_forced' ) );

		// add subscriptions data to the order object
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_get_order', array( $this, 'subscriptions_get_order' ) );

		// process scheduled subscription payments
		add_action( 'scheduled_subscription_payment_' . $this->get_id(), array( $this, 'process_subscription_renewal_payment' ), 10, 3 );

		// prevent unnecessary order meta from polluting parent renewal orders
		add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'remove_subscription_renewal_order_meta' ), 10, 4 );

		// display the current payment method used for a subscription in the "My Subscriptions" table
		add_filter( 'woocommerce_my_subscriptions_recurring_payment_method', array( $this, 'maybe_render_subscription_payment_method' ), 10, 3 );
	}


	/**
	 * Force tokenization for subscriptions, this can be forced either during checkout
	 * or when the payment method for a subscription is being changed
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::tokenization_forced()
	 * @param bool $force_tokenization whether tokenization should be forced
	 * @return bool true if tokenization should be forced, false otherwise
	 */
	public function subscriptions_tokenization_forced( $force_tokenization ) {

		// pay page with subscription?
		$pay_page_subscription = false;
		if ( $this->is_pay_page_gateway() ) {

			$order_id = $this->get_checkout_pay_page_order_id();

			if ( $order_id ) {
				$pay_page_subscription = WC_Subscriptions_Order::order_contains_subscription( $order_id );
			}

		}

		if ( WC_Subscriptions_Cart::cart_contains_subscription() ||
			WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ||
			$pay_page_subscription ) {
			$force_tokenization = true;
		}

		return $force_tokenization;
	}


	/**
	 * Adds subscriptions data to the order object
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::get_order()
	 * @param WC_Order $order the order
	 * @return WC_Order the orders
	 */
	public function subscriptions_get_order( $order ) {

		// bail if the gateway doesn't support subscriptions or the order doesn't contain a subscription
		if ( ! $this->supports_subscriptions() || ! WC_Subscriptions_Order::order_contains_subscription( $order->id ) )
			return $order;

		// subscriptions total, ensuring that we have a decimal point, even if it's 1.00
		$order->payment_total = number_format( (double) WC_Subscriptions_Order::get_total_initial_payment( $order ), 2, '.', '' );

		// load any required members that we might not have
		if ( ! isset( $order->payment->token ) || ! $order->payment->token )
			$order->payment->token = $this->get_order_meta( $order->id, 'payment_token' );

		if ( ! isset( $order->customer_id ) || ! $order->customer_id )
			$order->customer_id = $this->get_order_meta( $order->id, 'customer_id' );

		// ensure the payment token is still valid
		if ( ! $this->has_payment_token( $order->get_user_id(), $order->payment->token ) ) {
			$order->payment->token = null;
		} else {

			// get the token object
			$token = $this->get_payment_token( $order->get_user_id(), $order->payment->token );

			if ( ! isset( $order->payment->account_number ) || ! $order->payment->account_number )
				$order->payment->account_number = $token->get_last_four();

			if ( $this->is_credit_card_gateway() ) {

				// credit card token

				if ( ! isset( $order->payment->card_type ) || ! $order->payment->card_type )
					$order->payment->card_type = $token->get_card_type();

				if ( ! isset( $order->payment->exp_month ) || ! $order->payment->exp_month )
					$order->payment->exp_month = $token->get_exp_month();

				if ( ! isset( $order->payment->exp_year ) || ! $order->payment->exp_year )
					$order->payment->exp_year = $token->get_exp_year();

			} elseif ( $this->is_echeck_gateway() ) {

				// check token

				if ( ! isset( $order->payment->account_type ) || ! $order->payment->account_type )
					$order->payment->account_type = $token->get_account_type();

			}

		}

		return $order;
	}


	/**
	 * Process subscription renewal
	 *
	 * @since 1.0.0
	 * @param float $amount_to_charge subscription amount to charge, could include multiple renewals if they've previously failed and the admin has enabled it
	 * @param WC_Order $order original order containing the subscription
	 * @param int $product_id the subscription product id
	 */
	public function process_subscription_renewal_payment( $amount_to_charge, $order, $product_id ) {

		try {

			// set order defaults
			$order = $this->get_order( $order->id );

			// zero-dollar subscription renewal.  weird, but apparently it happens
			if ( 0 == $amount_to_charge ) {

				// add order note
				$order->add_order_note( sprintf( _x( '%s0 Subscription Renewal Approved', 'Supports direct credit card subscriptions', $this->text_domain ), get_woocommerce_currency_symbol() ) );

				// update subscription
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $order, $product_id );

				return;
			}

			// set the amount to charge, ensuring that we have a decimal point, even if it's 1.00
			$order->payment_total = number_format( (double) $amount_to_charge, 2, '.', '' );

			// required
			if ( ! $order->payment->token || ! $order->get_user_id() ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Subscription Renewal: Payment Token or User ID is missing/invalid.' );
			}

			// get the token, we've already verified it's good
			$token = $this->get_payment_token( $order->get_user_id(), $order->payment->token );

			// perform the transaction
			if ( $this->is_credit_card_gateway() ) {

				if ( $this->perform_credit_card_charge() ) {
					$response = $this->get_api()->credit_card_charge( $order );
				} else {
					$response = $this->get_api()->credit_card_authorization( $order );
				}

			} elseif ( $this->is_echeck_gateway() ) {
				$response = $this->get_api()->check_debit( $order );
			}

			// check for success
			if ( $response->transaction_approved() ) {

				// order note based on gateway type
				if ( $this->is_credit_card_gateway() ) {
					$message = sprintf(
						_x( '%s %s Subscription Renewal Payment Approved: %s ending in %s (expires %s)', 'Supports direct credit card subscriptions', $this->text_domain ),
						$this->get_method_title(),
						$this->perform_credit_card_authorization() ? 'Authorization' : 'Charge',
						$token->get_card_type() ? $token->get_type_full() : 'card',
						$token->get_last_four(),
						$token->get_exp_month() . '/' . $token->get_exp_year()
					);
				} elseif ( $this->is_echeck_gateway() ) {

					// there may or may not be an account type (checking/savings) available, which is fine
					$message = sprintf( _x( '%s Check Subscription Renewal Payment Approved: %s account ending in %s', 'Supports direct cheque subscriptions', $this->text_domain ), $this->get_method_title(), $token->get_account_type(), $token->get_last_four() );
				}

				// add order note
				$order->add_order_note( $message );

				// update subscription
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $order, $product_id );

			} else {

				// failure
				throw new SV_WC_Payment_Gateway_Exception( sprintf( '%s: %s', $response->get_status_code(), $response->get_status_message() ) );
			}

		} catch ( SV_WC_Plugin_Exception $e ) {

			$this->mark_order_as_failed( $order, $e->getMessage() );

			// update subscription
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		}
	}


	/**
	 * Don't copy over gateway-specific order meta when creating a parent renewal order.
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::get_remove_subscription_renewal_order_meta_fragment()
	 * @param array $order_meta_query MySQL query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return string MySQL meta query for pulling the metadata, excluding data added by this gateway
	 */
	public function remove_subscription_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

		// all required and optional
		if ( 'parent' == $new_order_role ) {
			$order_meta_query .= $this->get_remove_subscription_renewal_order_meta(
				array(
					$this->get_order_meta_prefix() . 'trans_id',
					$this->get_order_meta_prefix() . 'trans_date',
					$this->get_order_meta_prefix() . 'payment_token',
					$this->get_order_meta_prefix() . 'account_four',
					$this->get_order_meta_prefix() . 'card_expiry_date',
					$this->get_order_meta_prefix() . 'card_type',
					$this->get_order_meta_prefix() . 'authorization_code',
					$this->get_order_meta_prefix() . 'auth_can_be_captured',
					$this->get_order_meta_prefix() . 'charge_captured',
					$this->get_order_meta_prefix() . 'capture_trans_id',
					$this->get_order_meta_prefix() . 'account_type',
					$this->get_order_meta_prefix() . 'check_number',
					$this->get_order_meta_prefix() . 'environment',
					$this->get_order_meta_prefix() . 'customer_id',
					$this->get_order_meta_prefix() . 'retry_count',
				)
			);
		}

		return $order_meta_query;
	}


	/**
	 * Returns the query fragment to remove the given subscription renewal order meta
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::remove_subscription_renewal_order_meta()
	 * @param array $meta_names array of string meta names to remove
	 * @return string query fragment
	 */
	protected function get_remove_subscription_renewal_order_meta_fragment( $meta_names ) {

		return " AND `meta_key` NOT IN ( '" . join( "', '", $meta_names ) . "' )";
	}


	/**
	 * Adds support for subscriptions by hooking in some necessary actions
	 *
	 * @since 1.0.0
	 */
	public function add_subscription_payment_method_change_support() {

		// bail if subscriptions or subscription payment method changes are not supported by this gateway or its current configuration
		if ( ! $this->supports_subscriptions() || ! $this->supports( self::FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE ) ) {
			return;
		}

		// update the customer/token ID on the original order when making payment for a failed automatic renewal order
		add_action( 'woocommerce_subscriptions_changed_failing_payment_method_' . $this->get_id(), array( $this, 'update_failing_payment_method' ), 10, 2 );
	}


	/**
	 * Update the payment token and optional customer ID for a subscription after a customer
	 * uses this gateway to successfully complete the payment for an automatic
	 * renewal payment which had previously failed.
	 *
	 * @since 1.0.0
	 * @param WC_Order $original_order the original order in which the subscription was purchased.
	 * @param WC_Order $renewal_order the order which recorded the successful payment (to make up for the failed automatic payment).
	 */
	public function update_failing_payment_method( WC_Order $original_order, WC_Order $renewal_order ) {

		if ( $this->get_order_meta( $renewal_order->id, 'customer_id' ) ) {
			$this->update_order_meta( $original_order->id, 'customer_id',   $this->get_order_meta( $renewal_order->id, 'customer_id' ) );
		}

		$this->update_order_meta( $original_order->id, 'payment_token', $this->get_order_meta( $renewal_order->id, 'payment_token' ) );
	}


	/**
	 * Render the payment method used for a subscription in the "My Subscriptions" table
	 *
	 * @since 1.0.0
	 * @param string $payment_method_to_display the default payment method text to display
	 * @param array $subscription_details the subscription details
	 * @param WC_Order $order the order containing the subscription
	 * @return string the subscription payment method
	 */
	public function maybe_render_subscription_payment_method( $payment_method_to_display, $subscription_details, WC_Order $order ) {

		// bail for other payment methods
		if ( $this->get_id() !== $order->recurring_payment_method )
			return $payment_method_to_display;

		$token = $this->get_payment_token( $order->get_user_id(), $this->get_order_meta( $order->id, 'payment_token' ) );

		if ( is_object( $token )  )
			$payment_method_to_display = sprintf( _x( 'Via %s ending in %s', 'Supports direct payment method subscriptions', $this->text_domain ), $token->get_type_full(), $token->get_last_four() );

		return $payment_method_to_display;
	}


	/** Pre-Orders feature ******************************************************/


	/**
	 * Returns true if this gateway with its current configuration supports pre-orders
	 *
	 * @since 1.0.0
	 * @return boolean true if the gateway supports pre-orders
	 */
	public function supports_pre_orders() {

		return $this->supports( self::FEATURE_PRE_ORDERS ) && $this->supports_tokenization() && $this->tokenization_enabled();

	}


	/**
	 * Adds support for pre-orders by hooking in some necessary actions
	 *
	 * @since 1.0.0
	 */
	public function add_pre_orders_support() {

		// bail
		if ( ! $this->supports_pre_orders() ) {
			return;
		}

		// force tokenization when needed
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_tokenization_forced', array( $this, 'pre_orders_tokenization_forced' ) );

		// add pre-orders data to the order object
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_get_order', array( $this, 'pre_orders_get_order' ) );

		// process pre-order initial payment as needed
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_process_payment', array( $this, 'process_pre_order_payment' ), 10, 2 );

		// process batch pre-order payments
		add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->get_id(), array( $this, 'process_pre_order_release_payment' ) );

	}

	/**
	 * Force tokenization for pre-orders
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::tokenization_forced()
	 * @param boolean $force_tokenization whether tokenization should be forced
	 * @return boolean true if tokenization should be forced, false otherwise
	 */
	public function pre_orders_tokenization_forced( $force_tokenization ) {

		// pay page with pre-order?
		$pay_page_pre_order = false;
		if ( $this->is_pay_page_gateway() ) {

			$order_id  = $this->get_checkout_pay_page_order_id();

			if ( $order_id ) {
				$pay_page_pre_order = WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) && WC_Pre_Orders_Product::product_is_charged_upon_release( WC_Pre_Orders_Order::get_pre_order_product( $order_id ) );
			}

		}

		if ( ( WC_Pre_Orders_Cart::cart_contains_pre_order() && WC_Pre_Orders_Product::product_is_charged_upon_release( WC_Pre_Orders_Cart::get_pre_order_product() ) ) ||
			$pay_page_pre_order ) {

			// always tokenize the card for pre-orders that are charged upon release
			$force_tokenization = true;

		}

		return $force_tokenization;

	}


	/**
	 * Adds pre-orders data to the order object.  Filtered onto SV_WC_Payment_Gateway::get_order()
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::get_order()
	 * @param WC_Order $order the order
	 * @return WC_Order the orders
	 */
	public function pre_orders_get_order( $order ) {

		// bail if order doesn't contain a pre-order
		if ( ! WC_Pre_Orders_Order::order_contains_pre_order( $order ) )
			return $order;

		if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order ) ) {

			// normally a guest user wouldn't be assigned a customer id, but for a pre-order requiring tokenization, it might be
			if ( 0 == $order->get_user_id() && false !== ( $customer_id = $this->get_guest_customer_id( $order ) ) )
				$order->customer_id = $customer_id;

		} elseif ( WC_Pre_Orders_Order::order_has_payment_token( $order ) ) {

			// if this is a pre-order release payment with a tokenized payment method, get the payment token to complete the order

			// retrieve the payment token
			$order->payment->token = $this->get_order_meta( $order->id, 'payment_token' );

			// retrieve the optional customer id
			$order->customer_id = $this->get_order_meta( $order->id, 'customer_id' );

			// verify that this customer still has the token tied to this order.
			if ( ! $this->has_payment_token( $order->get_user_id(), $order->payment->token ) ) {

				$order->payment->token = null;

			} else {
				// Push expected payment data into the order, from the payment token when possible,
				//  or from the order object otherwise.  The theory is that the token will have the
				//  most up-to-date data, while the meta attached to the order is a second best

				// for a guest transaction with a gateway that doesn't support "tokenization get" this will return null and the token data will be pulled from the order meta
				$token = $this->get_payment_token( $order->get_user_id(), $order->payment->token );

				// account last four
				$order->payment->account_number = $token && $token->get_last_four() ? $token->get_last_four() : $this->get_order_meta( $order->id, 'account_four' );

				if ( $this->is_credit_card_gateway() ) {

					$order->payment->card_type = $token && $token->get_card_type() ? $token->get_card_type() : $this->get_order_meta( $order->id, 'card_type' );

					if ( $token && $token->get_exp_month() && $token->get_exp_year() ) {
						$order->payment->exp_month  = $token->get_exp_month();
						$order->payment->exp_year   = $token->get_exp_year();
					} else {
						list( $exp_year, $exp_month ) = explode( '-', $this->get_order_meta( $order->id, 'card_expiry_date' ) );
						$order->payment->exp_month  = $exp_month;
						$order->payment->exp_year   = $exp_year;
					}

				} elseif ( $this->is_echeck_gateway() ) {

					// set the account type if available (checking/savings)
					$order->payment->account_type = $token && $token->get_account_type ? $token->get_account_type() : $this->get_order_meta( $order->id, 'account_type' );
				}
			}
		}

		return $order;
	}


	/**
	 * Handle the pre-order initial payment/tokenization, or defer back to the normal payment
	 * processing flow
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::process_payment()
	 * @param boolean $result the result of this pre-order payment process
	 * @param int $order_id the order identifier
	 * @return true|array true to process this payment as a regular transaction, otherwise
	 *         return an array containing keys 'result' and 'redirect'
	 */
	public function process_pre_order_payment( $result, $order_id ) {

		assert( $this->supports_pre_orders() );

		if ( WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) &&
			WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) ) {

			$order = $this->get_order( $order_id );

			try {

				// using an existing tokenized payment method
				if ( isset( $order->payment->token ) && $order->payment->token ) {

					// save the tokenized card info for completing the pre-order in the future
					$this->add_transaction_data( $order );

				} else {

					// otherwise tokenize the payment method
					$order = $this->create_payment_token( $order );
				}

				// mark order as pre-ordered / reduce order stock
				WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

				// empty cart
				WC()->cart->empty_cart();

				// redirect to thank you page
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			} catch( SV_WC_Payment_Gateway_Exception $e ) {

				$this->mark_order_as_failed( $order, sprintf( _x( 'Pre-Order Tokenization attempt failed (%s)', 'Supports direct payment method pre-orders', $this->text_domain ), $this->get_method_title(), $e->getMessage() ) );

			}
		}

		// processing regular product
		return $result;
	}


	/**
	 * Process a pre-order payment when the pre-order is released
	 *
	 * @since 1.0.0
	 * @param WC_Order $order original order containing the pre-order
	 */
	public function process_pre_order_release_payment( $order ) {

		try {

			// set order defaults
			$order = $this->get_order( $order->id );

			// order description
			$order->description = sprintf( _x( '%s - Pre-Order Release Payment for Order %s', 'Supports direct payment method pre-orders', $this->text_domain ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() );

			// token is required
			if ( ! $order->payment->token ) {
				throw new SV_WC_Payment_Gateway_Exception( _x( 'Payment token missing/invalid.', 'Supports direct payment method pre-orders', $this->text_domain ) );
			}

			// perform the transaction
			if ( $this->is_credit_card_gateway() ) {

				if ( $this->perform_credit_card_charge() ) {
					$response = $this->get_api()->credit_card_charge( $order );
				} else {
					$response = $this->get_api()->credit_card_authorization( $order );
				}

			} elseif ( $this->is_echeck_gateway() ) {
				$response = $this->get_api()->check_debit( $order );
			}

			// success! update order record
			if ( $response->transaction_approved() ) {

				$last_four = substr( $order->payment->account_number, -4 );

				// order note based on gateway type
				if ( $this->is_credit_card_gateway() ) {

					$message = sprintf(
						_x( '%s %s Pre-Order Release Payment Approved: %s ending in %s (expires %s)', 'Supports direct payment method pre-orders', $this->text_domain ),
						$this->get_method_title(),
						$this->perform_credit_card_authorization() ? 'Authorization' : 'Charge',
						SV_WC_Payment_Gateway_Helper::payment_type_to_name( ( ! empty( $order->payment->card_type ) ? $order->payment->card_type : 'card' ) ),
						$last_four,
						$order->payment->exp_month . '/' . substr( $order->payment->exp_year, -2 )
					);

				} elseif ( $this->is_echeck_gateway() ) {

					// account type (checking/savings) may or may not be available, which is fine
					$message = sprintf( _x( '%s eCheck Pre-Order Release Payment Approved: %s ending in %s', 'Supports direct payment method pre-orders', $this->text_domain ), $this->get_method_title(), SV_WC_Payment_Gateway_Helper::payment_type_to_name( ( ! empty( $order->payment->account_type ) ? $order->payment->account_type : 'bank' ) ), $last_four );

				}

				// adds the transaction id (if any) to the order note
				if ( $response->get_transaction_id() ) {
					$message .= ' ' . sprintf( _x( '(Transaction ID %s)', 'Supports direct payment method pre-orders', $this->text_domain ), $response->get_transaction_id() );
				}

				$order->add_order_note( $message );
			}

			if ( $response->transaction_approved() || $response->transaction_held() ) {

				// add the standard transaction data
				$this->add_transaction_data( $order, $response );

				// allow the concrete class to add any gateway-specific transaction data to the order
				$this->add_payment_gateway_transaction_data( $order, $response );

				// if the transaction was held (ie fraud validation failure) mark it as such
				if ( $response->transaction_held() || ( $this->supports( self::FEATURE_CREDIT_CARD_AUTHORIZATION ) && $this->perform_credit_card_authorization() ) ) {

					$this->mark_order_as_held( $order, $this->supports( self::FEATURE_CREDIT_CARD_AUTHORIZATION ) && $this->perform_credit_card_authorization() ? _x( 'Authorization only transaction', 'Supports direct payment method pre-orders', $this->text_domain ) : $response->get_status_message(), $response );
					$order->reduce_order_stock(); // reduce stock for held orders, but don't complete payment

				} else {
					// otherwise complete the order
					$order->payment_complete();
				}

			} else {

				// failure
				throw new SV_WC_Payment_Gateway_Exception( sprintf( '%s: %s', $response->get_status_code(), $response->get_status_message() ) );

			}

		} catch ( SV_WC_Plugin_Exception $e ) {

			// Mark order as failed
			$this->mark_order_as_failed( $order, sprintf( _x( 'Pre-Order Release Payment Failed: %s', 'Supports direct payment method pre-orders', $this->text_domain ), $e->getMessage() ) );

		}
	}


	/** Tokenization feature ******************************************************/


	/**
	 * A factory method to build and return a payment token object for the
	 * gateway.  Concrete classes can override this method to return a custom
	 * payment token implementation.
	 *
	 * Payment token data can include:
	 *
	 * + `default`   - boolean optional indicates this is the default payment token
	 * + `type`      - string one of 'credit_card' or 'check'
	 * + `last_four` - string last four digits of account number
	 * + `card_type` - string credit card type (visa, mc, amex, disc, diners, jcb) or echeck
	 * + `exp_month` - string optional expiration month (credit card only)
	 * + `exp_year`  - string optional expiration year (credit card only)
	 *
	 * @since 1.0.0
	 * @param string $token payment token
	 * @param array $data payment token data
	 * @return SV_WC_Payment_Gateway_Payment_Token payment token
	 */
	public function build_payment_token( $token, $data ) {

		assert( $this->supports_tokenization() );

		return new SV_WC_Payment_Gateway_Payment_Token( $token, $data );

	}


	/**
	 * Tokenizes the current payment method and adds the standard transaction
	 * data to the order post record.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Create_Payment_Token_Response $response optional create payment token response, or null if the tokenize payment method request should be made
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return WC_Order the order object
	 * @throws SV_WC_Payment_Gateway_Exception on network error or request error
	 */
	protected function create_payment_token( $order, $response = null, $environment_id = null ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		// perform the API request to tokenize the payment method if needed
		if ( ! $response || $this->tokenize_after_sale() ) {
			$response = $this->get_api()->tokenize_payment_method( $order );
		}

		if ( $response->transaction_approved() ) {

			// add the token to the order object for processing
			$token                 = $response->get_payment_token();
			$order->payment->token = $token->get_token();

			// for credit card transactions add the card type, if known (some gateways return the credit card type as part of the response, others may require it as part of the request, and still others it may never be known)
			if ( $this->is_credit_card_gateway() && $token->get_card_type() ) {
				$order->payment->card_type = $token->get_card_type();
			}

			// checking/savings, if known
			if ( $this->is_echeck_gateway() && $token->get_account_type() ) {
				$order->payment->account_type = $token->get_account_type();
			}

			// set the token to the user account
			if ( $order->get_user_id() ) {
				$this->add_payment_token( $order->get_user_id(), $token, $environment_id );
			}

			// order note based on gateway type
			if ( $this->is_credit_card_gateway() ) {
				$message = sprintf( _x( '%s Payment Method Saved: %s ending in %s (expires %s)', 'Supports direct credit card tokenization', $this->text_domain ),
					$this->get_method_title(),
					$token->get_type_full(),
					$token->get_last_four(),
					$token->get_exp_date()
				);
			} elseif ( $this->is_echeck_gateway() ) {
				// account type (checking/savings) may or may not be available, which is fine
				$message = sprintf( _x( '%s eCheck Payment Method Saved: %s account ending in %s', 'Supports direct cheque tokenization', $this->text_domain ),
					$this->get_method_title(),
					$token->get_account_type(),
					$token->get_last_four()
				);
			}

			$order->add_order_note( $message );

			// add the standard transaction data
			$this->add_transaction_data( $order, $response );

			// clear any cached tokens
			if ( $transient_key = $this->get_payment_tokens_transient_key( $order->get_user_id() ) ) {
				delete_transient( $transient_key );
			}

		} else {

			if ( $response->get_status_code() && $response->get_status_message() ) {
				$message = sprintf( 'Status code %s: %s', $response->get_status_code(), $response->get_status_message() );
			} elseif ( $response->get_status_code() ) {
				$message = sprintf( 'Status code: %s', $response->get_status_code() );
			} elseif ( $response->get_status_message() ) {
				$message = sprintf( 'Status message: %s', $response->get_status_message() );
			} else {
				$message = 'Unknown Error';
			}

			// add transaction id if there is one
			if ( $response->get_transaction_id() ) {
				$message .= ' ' . sprintf( __( 'Transaction ID %s', $this->text_domain ), $response->get_transaction_id() );
			}

			throw new SV_WC_Payment_Gateway_Exception( $message );
		}

		return $order;
	}


	/**
	 * Returns true if tokenization should be forced on the checkout page,
	 * false otherwise.  This is most useful to force tokenization for a
	 * subscription or pre-orders initial transaction.
	 *
	 * @since 1.0.0
	 * @return boolean true if tokenization should be forced on the checkout page, false otherwise
	 */
	public function tokenization_forced() {

		assert( $this->supports_tokenization() );

		// otherwise generally no need to force tokenization
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_tokenization_forced', false, $this );
	}


	/**
	 * Returns true if the current payment method should be tokenized: whether
	 * requested by customer or otherwise forced.  This parameter is passed from
	 * the checkout page/payment form.
	 *
	 * @since 1.0.0
	 * @return boolean true if the current payment method should be tokenized
	 */
	protected function should_tokenize_payment_method() {

		assert( $this->supports_tokenization() );

		return SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-tokenize-payment-method' ) && ! SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' );

	}


	/**
	 * Returns the payment token user meta name for persisting the payment tokens.
	 * Defaults to _wc_{gateway id}_payment_tokens for the production environment,
	 * and _wc_{gateway id}_payment_tokens_{environment} for any other environment.
	 *
	 * NOTE: the gateway id, rather than plugin id, is used by default to create
	 * the meta key for this setting, because it's assumed that in the case of a
	 * plugin having multiple gateways (ie credit card and eCheck) the payment
	 * tokens will be distinct between them
	 *
	 * @since 1.0.0
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return string payment token user meta name
	 */
	public function get_payment_token_user_meta_name( $environment_id = null ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		// leading underscore since this will never be displayed to an admin user in its raw form
		return $this->get_order_meta_prefix() . 'payment_tokens' . ( ! $this->is_production_environment( $environment_id ) ? '_' . $environment_id : '' );
	}


	/**
	 * Get the available payment tokens for a user as an associative array of
	 * payment token to SV_WC_Payment_Gateway_Payment_Token
	 *
	 * @since 1.0.0
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param array $args optional arguments, can include
	 *  	`customer_id` - if not provided, this will be looked up based on $user_id
	 *  	`environment_id` - defaults to plugin current environment
	 * @return array associative array of string token to SV_WC_Payment_Gateway_Payment_Token object
	 */
	public function get_payment_tokens( $user_id, $args = array() ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( ! isset( $args['environment_id'] ) ) {
			$args['environment_id'] = $this->get_environment();
		}

		if ( ! isset( $args['customer_id'] ) ) {
			$args['customer_id'] = $this->get_customer_id( $user_id, array( 'environment_id' => $args['environment_id'] ) );
		}

		$environment_id = $args['environment_id'];
		$customer_id    = $args['customer_id'];
		$transient_key  = $this->get_payment_tokens_transient_key( $user_id );

		// return tokens cached during a single request
		if ( isset( $this->tokens[ $environment_id ][ $user_id ] ) ) {
			return $this->tokens[ $environment_id ][ $user_id ];
		}

		// return tokens cached in transient
		if ( $transient_key && ( false !== ( $this->tokens[ $environment_id ][ $user_id ] = get_transient( $transient_key ) ) ) ) {
			return $this->tokens[ $environment_id ][ $user_id ];
		}

		$this->tokens[ $environment_id ][ $user_id ] = array();
		$tokens = array();

		// retrieve the datastore persisted tokens first, so we have them for
		// gateways that don't support fetching them over an API, as well as the
		// default token for those that do
		if ( $user_id ) {

			$_tokens = get_user_meta( $user_id, $this->get_payment_token_user_meta_name( $environment_id ), true );

			// from database format
			if ( is_array( $_tokens ) ) {
				foreach ( $_tokens as $token => $data ) {
					$tokens[ $token ] = $this->build_payment_token( $token, $data );
				}
			}

			$this->tokens[ $environment_id ][ $user_id ] = $tokens;
		}

		// if the payment gateway API supports retrieving tokens directly, do so as it's easier to stay synchronized
		if ( $this->get_api()->supports_get_tokenized_payment_methods() && $customer_id ) {

			try {

				// retrieve the payment method tokes from the remote API
				$response = $this->get_api()->get_tokenized_payment_methods( $customer_id );
				$this->tokens[ $environment_id ][ $user_id ] = $response->get_payment_tokens();

				// check for a default from the persisted set, if any
				$default_token = null;
				foreach ( $tokens as $default_token ) {
					if ( $default_token->is_default() ) {
						break;
					}
				}

				// mark the corresponding token from the API as the default one
				if ( $default_token && $default_token->is_default() && isset( $this->tokens[ $environment_id ][ $user_id ][ $default_token->get_token() ] ) ) {
					$this->tokens[ $environment_id ][ $user_id ][ $default_token->get_token() ]->set_default( true );
				}

				// merge local token data with remote data, sometimes local data is more robust
				$this->tokens[ $environment_id ][ $user_id ] = $this->merge_payment_token_data( $tokens, $this->tokens[ $environment_id ][ $user_id ] );

				// persist locally after merging
				$this->update_payment_tokens( $user_id, $this->tokens[ $environment_id ][ $user_id ], $environment_id );

			} catch( SV_WC_Plugin_Exception $e ) {

				// communication or other error

				$this->add_debug_message( $e->getMessage(), 'error' );

				$this->tokens[ $environment_id ][ $user_id ] = $tokens;
			}

		}

		// set the payment type image url, if any, for convenience
		foreach ( $this->tokens[ $environment_id ][ $user_id ] as $key => $token ) {
			$this->tokens[ $environment_id ][ $user_id ][ $key ]->set_image_url( $this->get_payment_method_image_url( $token->is_credit_card() ? $token->get_card_type() : 'echeck' ) );
		}

		if ( $transient_key ) {
			set_transient( $transient_key, $this->tokens[ $environment_id ][ $user_id ], 60 );
		}

		/**
		 * Direct Payment Gateway Payment Tokens Loaded Action.
		 *
		 * Fired when payment tokens have been completely loaded.
		 *
		 * @since 4.0.0
		 * @param array $tokens array of SV_WC_Payment_Gateway_Payment_Tokens
		 * @param \SV_WC_Payment_Gateway_Direct direct gateway class instance
		 */
		do_action( 'wc_payment_gateway_' . $this->get_id() . '_payment_tokens_loaded', $this->tokens[ $environment_id ][ $user_id ], $this );

		return $this->tokens[ $environment_id ][ $user_id ];
	}


	/**
	 * Merge remote token data with local tokens, sometimes local tokens can provide
	 * additional detail that's not provided remotely
	 *
	 * @since 4.0.0
	 * @param array $local_tokens local tokens
	 * @param array $remote_tokens remote tokens
	 * @return array associative array of string token to SV_WC_Payment_Gateway_Payment_Token objects
	 */
	protected function merge_payment_token_data( $local_tokens, $remote_tokens ) {

		foreach ( $remote_tokens as &$remote_token ) {

			$remote_token_id = $remote_token->get_token();

			// bail if the remote token doesn't exist locally
			if ( ! isset( $local_tokens[ $remote_token_id ] ) ) {
				continue;
			}

			foreach ( $this->get_payment_token_merge_attributes() as $attribute ) {

				$get_method = "get_{$attribute}";
				$set_method = "set_{$attribute}";

				// if the remote token is missing an attribute and the local token has it...
				if ( ! $remote_token->$get_method() && $local_tokens[ $remote_token_id ]->$get_method() ) {

					// set the attribute on the remote token
					$remote_token->$set_method( $local_tokens[ $remote_token_id ]->$get_method() );
				}
			}
		}

		return $remote_tokens;
	}


	/**
	 * Return the attributes that should be used to merge local token data into
	 * a remote token.
	 *
	 * Gateways can override this method to add their own attributes, but must
	 * also include the associated get_*() & set_*() methods in the token class.
	 *
	 * See Authorize.net CIM for an example implementation.
	 *
	 * @since 4.0.0
	 * @return array associative array of string token to SV_WC_Payment_Gateway_Payment_Token objects
	 */
	protected function get_payment_token_merge_attributes() {

		return array( 'last_four', 'card_type', 'account_type', 'exp_month', 'exp_year' );
	}


	/**
	 * Return the payment token transient key for the given user, gateway,
	 * and environment
	 *
	 * Payment token transients can be disabled by using the filter below.
	 *
	 * @since 4.0.0
	 * @param string|int $user_id
	 * @return string transient key
	 */
	protected function get_payment_tokens_transient_key( $user_id = null ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// ex: wc_sv_tokens_<md5 hash of gateway_id, user ID, and environment ID>
		$key = sprintf( 'wc_sv_tokens_%s', md5( $this->get_id() . '_' . $user_id . '_' . $this->get_environment() ) );

		/**
		 * Filter payment tokens transient key
		 *
		 * Warning: this filter should generally only be used to disable token
		 * transients by returning false or an empty string. Setting an incorrect or invalid
		 * transient key (e.g. not keyed to the current user or environment) can
		 * result in unexpected and difficult to debug situations involving tokens.
		 *
		 * filter responsibly!
		 *
		 * @since 4.0.0
		 * @param string $key transient key (must be 45 chars or less)
		 * @param \SV_WC_Payment_Gateway_Direct $this direct gateway class instance
		 */
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_payment_tokens_transient_key', $key, $user_id, $this );
	}


	/**
	 * Helper method to clear the tokens transient
	 *
	 * TODO: ideally the transient would make use of actions to clear itself
	 * as needed (e.g. when customer IDs are updated/removed), but for now it's
	 * only cleared when the tokens are updated. @MR July 2015
	 *
	 * @since 4.0.0
	 * @param int|string $user_id
	 */
	public function clear_payment_tokens_transient( $user_id ) {

		delete_transient( $this->get_payment_tokens_transient_key( $user_id ) );
	}


	/**
	 * Updates the given payment tokens for the identified user, in the database.
	 *
	 * @since 1.0.0
	 * @param int $user_id WP user ID
	 * @param array $tokens array of tokens
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return string updated user meta id
	 */
	protected function update_payment_tokens( $user_id, $tokens, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		// update the local cache
		$this->tokens[ $environment_id ][ $user_id ] = $tokens;

		// clear the transient
		$this->clear_payment_tokens_transient( $user_id );

		// persist the updated tokens to the user meta
		return update_user_meta( $user_id, $this->get_payment_token_user_meta_name( $environment_id ), $this->payment_tokens_to_database_format( $tokens ) );
	}


	/**
	 * Returns the payment token object identified by $token from the user
	 * identified by $user_id
	 *
	 * @since 1.0.0
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param string $token payment token
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return SV_WC_Payment_Gateway_Payment_Token payment token object or null
	 */
	public function get_payment_token( $user_id, $token, $environment_id = null ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		$tokens = $this->get_payment_tokens( $user_id, array( 'environment_id' => $environment_id ) );

		if ( isset( $tokens[ $token ] ) ) return $tokens[ $token ];

		return null;
	}


	/**
	 * Update a single token by persisting it to user meta
	 *
	 * @since since 4.0.0
	 * @param int $user_id WP user ID
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token to update
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return string|int updated user meta ID
	 */
	public function update_payment_token( $user_id, $token, $environment_id = null ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		$tokens = $this->get_payment_tokens( $user_id, array( 'environment_id' => $environment_id ) );

		if ( isset( $tokens[ $token->get_id() ] ) ) {
			$tokens[ $token->get_id() ] = $token;
		}

		return $this->update_payment_tokens( $user_id, $tokens, $environment_id );
	}


	/**
	 * Returns true if the identified user has the given payment token
	 *
	 * @since 1.0.0
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param string|SV_WC_Payment_Gateway_Payment_Token $token payment token
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return boolean true if the user has the payment token, false otherwise
	 */
	public function has_payment_token( $user_id, $token, $environment_id = null ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		if ( is_object( $token ) ) {
			$token = $token->get_token();
		}

		// this is sort of a weird edge case: verifying a token exists for a guest customer
		//  using an API that doesn't support a tokenized payment method query operation.
		//  We will neither have a user record in the db, nor can we query the API endpoint,
		//  so just return true
		// Sample case: Guest pre-order transaction using FirstData
		if ( ! $this->get_api()->supports_get_tokenized_payment_methods() && ! $user_id ) {
			return true;
		}

		// token exists?
		return ! is_null( $this->get_payment_token( $user_id, $token, $environment_id ) );
	}


	/**
	 * Add a payment method and token as user meta.
	 *
	 * @since 1.0.0
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token $token the token
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return bool|int false if token not added, user meta ID if added
	 */
	public function add_payment_token( $user_id, $token, $environment_id = null ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		// get existing tokens
		$tokens = $this->get_payment_tokens( $user_id, array( 'environment_id' => $environment_id ) );

		// if this token is set as active, mark all others as false
		if ( $token->is_default() ) {
			foreach ( array_keys( $tokens ) as $key ) {
				$tokens[ $key ]->set_default( false );
			}
		}

		// add the new token
		$tokens[ $token->get_token() ] = $token;

		// persist the updated tokens
		return $this->update_payment_tokens( $user_id, $tokens, $environment_id );
	}


	/**
	 * Delete a credit card token from user meta
	 *
	 * @since 1.0.0
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token|string $token the payment token to delete
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return bool|int false if not deleted, updated user meta ID if deleted
	 */
	public function remove_payment_token( $user_id, $token, $environment_id = null ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		// unknown token?
		if ( ! $this->has_payment_token( $user_id, $token, $environment_id ) ) {
			return false;
		}

		// get the payment token object as needed
		if ( ! is_object( $token ) ) {
			$token = $this->get_payment_token( $user_id, $token, $environment_id );
		}

		// for direct gateways that allow it, attempt to delete the token from the endpoint
		if ( $this->get_api()->supports_remove_tokenized_payment_method() ) {

			try {

				$response = $this->get_api()->remove_tokenized_payment_method( $token->get_token(), $this->get_customer_id( $user_id, array( 'environment_id' => $environment_id ) ) );

				if ( ! $response->transaction_approved() ) {
					return false;
				}

			} catch( SV_WC_Plugin_Exception $e ) {
				if ( $this->debug_log() ) {
					$this->get_plugin()->log( $e->getMessage() . "\n" . $e->getTraceAsString(), $this->get_id() );
				}
				return false;
			}
		}

		// get existing tokens
		$tokens = $this->get_payment_tokens( $user_id, array( 'environment_id' => $environment_id ) );

		unset( $tokens[ $token->get_token() ] );

		// if the deleted card was the default one, make another one the new default
		if ( $token->is_default() ) {
			foreach ( array_keys( $tokens ) as $key ) {
				$tokens[ $key ]->set_default( true );
				break;
			}
		}

		// persist the updated tokens
		return $this->update_payment_tokens( $user_id, $tokens );
	}


	/**
	 * Set the default token for a user. This is shown as "Default Card" in the
	 * frontend and will be auto-selected during checkout
	 *
	 * @since 1.0.0
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token|string $token the token to make default
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return string|bool false if not set, updated user meta ID if set
	 */
	public function set_default_payment_token( $user_id, $token, $environment_id = null ) {

		assert( $this->supports_tokenization() );

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		// unknown token?
		if ( ! $this->has_payment_token( $user_id, $token ) )
			return false;

		// get the payment token object as needed
		if ( ! is_object( $token ) ) {
			$token = $this->get_payment_token( $user_id, $token, $environment_id );
		}

		// get existing tokens
		$tokens = $this->get_payment_tokens( $user_id, array( 'environment_id' => $environment_id ) );

		// mark $token as the only active
		foreach ( $tokens as $key => $_token ) {

			if ( $token->get_token() == $_token->get_token() ) {
				$tokens[ $key ]->set_default( true );
			} else {
				$tokens[ $key ]->set_default( false );
			}

		}

		// persist the updated tokens
		return $this->update_payment_tokens( $user_id, $tokens, $environment_id );

	}


	/**
	 * Returns $tokens in a format suitable for data storage
	 *
	 * @since 1.0.0
	 * @param array $tokens array of SV_WC_Payment_Gateway_Payment_Token tokens
	 * @return array data storage version of $tokens
	 */
	protected function payment_tokens_to_database_format( $tokens ) {

		assert( $this->supports_tokenization() );

		$_tokens = array();

		// to database format
		foreach ( $tokens as $key => $token ) {
			$_tokens[ $key ] = $token->to_datastore_format();
		}

		return $_tokens;
	}


	/** Add Payment Method feature ********************************************/


	/**
	 * Returns true if the gateway supports the add payment method feature
	 *
	 * @since 4.0.0
	 * @return boolean true if the gateway supports add payment method feature
	 */
	public function supports_add_payment_method() {
		return $this->supports( self::FEATURE_ADD_PAYMENT_METHOD );
	}


	/**
	 * Entry method for the Add Payment Method feature flow. Note this is *not*
	 * stubbed in the WC_Payment_Gateway abstract class, but is called if the
	 * gateway declares support for it.
	 *
	 * @since 4.0.0
	 */
	public function add_payment_method() {

		assert( $this->supports_add_payment_method() );

		$order = $this->get_order_for_add_payment_method();

		try {

			$result = $this->do_add_payment_method_transaction( $order );

		} catch ( SV_WC_Plugin_Exception $e ) {

			$result = array(
				'message' => sprintf( __( 'Oops, adding your new payment method failed: %s', $this->text_domain ), $e->getMessage() ),
				'success' => false,
			);
		}

		SV_WC_Helper::wc_add_notice( $result['message'], $result['success'] ? 'success' : 'error' );

		// redirect to my account on success, or back to Add Payment Method screen on failure so user can try again
		wp_safe_redirect( $result['success'] ? SV_WC_Plugin_Compatibility::wc_get_page_permalink( 'myaccount' ) : wc_get_endpoint_url( 'add-payment-method' ) );

		exit();
	}


	/**
	 * Perform the transaction to add the customer's payment method to their
	 * account
	 *
	 * @since 4.0.0
	 * @return array result with success/error message and request status (success/failure)
	 */
	protected function do_add_payment_method_transaction( WC_Order $order ) {

		$response = $this->get_api()->tokenize_payment_method( $order );

		if ( $response->transaction_approved() ) {

			$token = $response->get_payment_token();

			// set the token to the user account
			$this->add_payment_token( $order->customer_user, $token );

			// order note based on gateway type
			if ( $this->is_credit_card_gateway() ) {

				$message = sprintf( _x( 'Nice! New payment method added: %s ending in %s (expires %s)', 'Supports add payment method feature', $this->text_domain ),
					$token->get_type_full(),
					$token->get_last_four(),
					$token->get_exp_date()
				);

			} elseif ( $this->is_echeck_gateway() ) {

				// account type (checking/savings) may or may not be available, which is fine
				$message = sprintf( _x( 'Nice! New payment method added: %s account ending in %s', 'Supports add payment method feature', $this->text_domain ),
					$token->get_account_type(),
					$token->get_last_four()
				);

			} else {
				$message = _x( 'Nice! New payment method added.', 'Supports direct', $this->text_domain );
			}

			// add transaction data to user meta
			$this->add_add_payment_method_transaction_data( $response );

			// add customer data, primarily customer ID to user meta
			$this->add_add_payment_method_customer_data( $order, $response );

			$result = array( 'message' => $message, 'success' => true );

		} else {

			if ( $response->get_status_code() && $response->get_status_message() ) {
				$message = sprintf( 'Status codes %s: %s', $response->get_status_code(), $response->get_status_message() );
			} elseif ( $response->get_status_code() ) {
				$message = sprintf( 'Status code: %s', $response->get_status_code() );
			} elseif ( $response->get_status_message() ) {
				$message = sprintf( 'Status message: %s', $response->get_status_message() );
			} else {
				$message = 'Unknown Error';
			}

			$result = array( 'message' => $message, 'success' => false );
		}

		/**
		 * Add Payment Method Transaction Result Filter.
		 *
		 * Filter the result data from an add payment method transaction attempt -- this
		 * can be used to control the notice message displayed and whether the
		 * user is redirected back to the My Account page or remains on the add
		 * new payment method screen
		 *
		 * @since 4.0.0
		 * @param array $result {
		 *   @type string $message notice message to render
		 *   @type bool $success true to redirect to my account, false to stay on page
		 * }
		 * @param \SV_WC_Payment_Gateway_API_Create_Payment_Token_Response $response instance
		 * @param \WC_Order $order order instance
		 * @param \SV_WC_Payment_Gateway_Direct $this direct gateway instance
		 */
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_add_payment_method_transaction_result', $result, $response, $order, $this );
	}


	/**
	 * Creates the order required for adding a new payment method. Note that
	 * a mock order is generated as there is no actual order associated with the
	 * request.
	 *
	 * @since 4.0.0
	 * @return WC_Order generated order object
	 */
	protected function get_order_for_add_payment_method() {

		$order = new WC_Order( 0 );

		// mock order, as all gateway API implementations require an order object for tokenization
		$order = $this->get_order( $order );

		$user = get_userdata( get_current_user_id() );

		$order->customer_user = $user->ID;

		// billing & shipping
		$fields = array(
			'billing_first_name', 'billing_last_name', 'billing_address_1', 'billing_company',
			'billing_address_2', 'billing_city', 'billing_postcode', 'billing_state',
			'billing_country', 'billing_phone', 'billing_email', 'shipping_first_name',
			'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2',
			'shipping_city', 'shipping_postcode', 'shipping_state', 'shipping_country',
		);

		foreach ( $fields as $field ) {
			$order->$field = $user->$field;
		}

		// other default info
		$order->customer_id = $this->get_customer_id( $order->customer_user );
		$order->description = sprintf( _x( '%s - Add Payment Method for %s', 'Add payment method request description', $this->text_domain ), sanitize_text_field( get_bloginfo( 'name' ) ), $order->billing_email );

		// force zero amount
		$order->payment_total = '0.00';

		// allow other actors to modify the order object
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_get_order_for_add_payment_method', $order, $this );
	}


	/**
	 * Add customer data as part of the add payment method transaction, primarily
	 * customer ID
	 *
	 * @since 4.0.0
	 * @param WC_Order $order mock order
	 * @param SV_WC_Payment_Gateway_API_Create_Payment_Token_Response $response
	 */
	protected function add_add_payment_method_customer_data( $order, $response ) {

		$user_id = $order->get_user_id();

		// set customer ID from response if available
		if ( $this->supports_customer_id() && method_exists( $response, 'get_customer_id' ) && $response->get_customer_id() ) {

			$order->customer_id = $customer_id = $response->get_customer_id();

		} else {

			// default to the customer ID on "order"
			$customer_id = $order->customer_id;
		}

		// update the user
		if ( 0 != $user_id ) {
			$this->update_customer_id( $user_id, $customer_id );
		}
	}


	/**
	 * Adds data from the add payment method transaction, primarily:
	 *
	 * + transaction ID
	 * + transaction date
	 * + transaction environment
	 *
	 * @since 4.0.0
	 * @param \SV_WC_Payment_Gateway_API_Create_Payment_Token_Response $response
	 */
	protected function add_add_payment_method_transaction_data( $response ) {

		$user_meta_key = '_wc_' . $this->get_id() . '_add_payment_method_transaction_data';

		$data = (array) get_user_meta( get_current_user_id(), $user_meta_key, true );

		$new_data = array(
			'trans_id'    => $response->get_transaction_id() ? $response->get_transaction_id() : null,
			'trans_date'  => current_time( 'mysql' ),
			'environment' => $this->get_environment(),
		);

		$data[] = array_merge( $new_data, $this->get_add_payment_method_payment_gateway_transaction_data( $response ) );

		// only keep the 5 most recent transactions
		if ( count( $data ) > 5 ) {
			array_shift( $data );
		}

		update_user_meta( get_current_user_id(), $user_meta_key, array_filter( $data ) );
	}


	/**
	 * Allow gateway implementations to add additional data to the data saved
	 * during the add payment method transaction
	 *
	 * @since 4.0.0
	 * @param SV_WC_Payment_Gateway_API_Create_Payment_Token_Response $response create payment token response
	 * @return array
	 */
	protected function get_add_payment_method_payment_gateway_transaction_data( $response ) {

		// stub method
		return array();
	}


	/** Getters ******************************************************/


	/**
	 * Returns true if this is a direct type gateway
	 *
	 * @since 1.0.0
	 * @return boolean if this is a direct payment gateway
	 */
	public function is_direct_gateway() {
		return true;
	}


	/**
	 * Returns true if a transaction should be forced (meaning payment
	 * processed even if the order amount is 0).  This is useful mostly for
	 * testing situations
	 *
	 * @since 2.2.0
	 * @return boolean true if the transaction request should be forced
	 */
	public function transaction_forced() {
		return false;
	}

}

endif;  // class exists check
