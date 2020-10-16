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

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_Integration_Subscriptions' ) ) :


/**
 * Subscriptions Integration
 *
 * @since 4.1.0
 */
class SV_WC_Payment_Gateway_Integration_Subscriptions extends SV_WC_Payment_Gateway_Integration {


	/** @var string|float renewal payment total for Subs 2.0.x renewals */
	protected $renewal_payment_total;


	/**
	 * Bootstraps the class.
	 *
	 * @since 4.1.0
	 *
	 * @param SV_WC_Payment_Gateway|SV_WC_Payment_Gateway_Direct $gateway
	 */
	public function __construct( SV_WC_Payment_Gateway $gateway ) {

		parent::__construct( $gateway );

		// add hooks
		$this->add_support();
	}


	/**
	 * Adds support for subscriptions by hooking in some necessary actions
	 *
	 * @since 4.1.0
	 */
	public function add_support() {

		$this->get_gateway()->add_support( array(
			'subscriptions',
			'subscription_suspension',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
		) );

		// disable default payment token change notice if wc_add_notice() is not available
		add_action( 'admin_init', [ $this, 'disable_default_payment_token_change_notice' ] );

		// force tokenization when needed
		add_filter( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_tokenization_forced', array( $this, 'maybe_force_tokenization' ) );

		// save token/customer ID to subscription objects
		add_action( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_add_transaction_data', array( $this, 'save_payment_meta' ), 10, 2 );

		// add additional subscription details to orders along with payment data
		add_action( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_get_order_base', [ $this, 'add_subscriptions_details_to_order' ], 10, 2 );

		// process renewal payments
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->get_gateway()->get_id(), array( $this, 'process_renewal_payment' ), 10, 2 );

		// update the customer/token ID on the subscription when updating a previously failing payment method
		add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->get_gateway()->get_id(), array( $this, 'update_failing_payment_method' ), 10, 2 );

		// display the current payment method used for a subscription in the "My Subscriptions" table
		add_filter( 'woocommerce_my_subscriptions_payment_method', array( $this, 'maybe_render_payment_method' ), 10, 3 );

		// don't copy over order-specific meta to the WC_Subscription object during renewal processing
		add_filter( 'wcs_renewal_order_meta', array( $this, 'do_not_copy_order_meta' ) );

		// process the Change Payment "transaction"
		add_filter( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_process_payment', array( $this, 'process_change_payment' ), 10, 3 );

		// remove order-specific meta from the Subscription object after the change payment method action
		add_filter( 'woocommerce_subscriptions_process_payment_for_change_method_via_pay_shortcode', array( $this, 'remove_order_meta_from_change_payment' ), 10, 2 );

		// don't copy over order-specific meta to the new WC_Subscription object during upgrade to 2.0.x
		add_filter( 'wcs_upgrade_subscription_meta_to_copy', array( $this, 'do_not_copy_order_meta_during_upgrade' ) );

		// allow concrete gateways to define additional order-specific meta keys to exclude
		if ( is_callable( array( $this->get_gateway(), 'subscriptions_get_excluded_order_meta_keys' ) ) ) {
			add_filter( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_subscriptions_order_specific_meta_keys', array( $this->get_gateway(), 'subscriptions_get_excluded_order_meta_keys' ) );
		}

		/* My Payment Methods */

		add_filter( 'wc_' . $this->get_gateway()->get_plugin()->get_id() . '_my_payment_methods_table_headers', array( $this, 'add_my_payment_methods_table_header' ), 10, 2 );

		add_filter( 'woocommerce_account_payment_methods_column_subscriptions', [ $this, 'add_payment_method_subscriptions' ] );

		/* Admin Change Payment Method support */

		// framework defaults - payment_token and customer_id
		add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'admin_add_payment_meta' ), 9, 2 );
		add_action( 'woocommerce_subscription_validate_payment_meta_' . $this->get_gateway()->get_id(), array( $this, 'admin_validate_payment_meta' ), 9 );

		// allow concrete gateways to add/change defaults
		if ( is_callable( array( $this->get_gateway(), 'subscriptions_admin_add_payment_meta' ) ) ) {
			add_filter( 'woocommerce_subscription_payment_meta', array( $this->get_gateway(), 'subscriptions_admin_add_payment_meta' ), 10, 2 );
		}

		// allow concrete gateways to perform additional validation
		if ( is_callable( array( $this->get_gateway(), 'subscriptions_admin_validate_payment_meta' ) ) ) {
			add_action( 'woocommerce_subscription_validate_payment_meta_' . $this->get_gateway()->get_id(), array( $this->get_gateway(), 'subscriptions_admin_validate_payment_meta' ), 10 );
		}
	}


	/**
	 * Disables Subscription's default payment token change notice if wc_add_notice() is not defined
	 *
	 * This prevents an uncaught error from being triggered when tokens are retrieved and saved in the user profile page.
	 *
	 * @internal
	 *
	 * @see \WCS_My_Account_Payment_Methods::display_default_payment_token_change_notice()
	 *
	 * @since 5.8.0
	 */
	public function disable_default_payment_token_change_notice() {

		if ( ! function_exists( 'wc_add_notice' ) ) {
			remove_action( 'woocommerce_payment_token_set_default', [ 'WCS_My_Account_Payment_Methods', 'display_default_payment_token_change_notice' ], 10, 2 );
		}
	}


	/**
	 * Force tokenization for subscriptions, this can be forced either during checkout
	 * or when the payment method for a subscription is being changed
	 *
	 * @since 4.1.0
	 * @see SV_WC_Payment_Gateway::tokenization_forced()
	 * @param bool $force_tokenization whether tokenization should be forced
	 * @return bool true if tokenization should be forced, false otherwise
	 */
	public function maybe_force_tokenization( $force_tokenization ) {

		// pay page with subscription?
		$pay_page_subscription = false;
		if ( $this->get_gateway()->is_pay_page_gateway() ) {

			$order_id = $this->get_gateway()->get_checkout_pay_page_order_id();

			if ( $order_id ) {
				$pay_page_subscription = wcs_order_contains_subscription( $order_id );
			}
		}

		if ( \WC_Subscriptions_Cart::cart_contains_subscription() ||
			 wcs_cart_contains_renewal() ||
			 \WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ||
			 $pay_page_subscription ) {
			$force_tokenization = true;
		}

		return $force_tokenization;
	}


	/**
	 * Save payment meta to the Subscription object after a successful transaction,
	 * this is primarily used for the payment token and customer ID which are then
	 * copied over to a renewal order prior to payment processing.
	 *
	 * @since 4.1.0
	 * @param \WC_Order $order order
	 */
	public function save_payment_meta( $order ) {

		// a single order can contain multiple subscriptions
		$subscriptions = wcs_get_subscriptions_for_order( $order, [
			'order_type' => [ 'any' ],
		] );

		foreach ( $subscriptions as $subscription ) {

			// payment token
			if ( ! empty( $order->payment->token ) ) {
				update_post_meta( $subscription->get_id(), $this->get_gateway()->get_order_meta_prefix() . 'payment_token', $order->payment->token );
			}

			// customer ID
			if ( ! empty( $order->customer_id ) ) {
				update_post_meta( $subscription->get_id(), $this->get_gateway()->get_order_meta_prefix() . 'customer_id', $order->customer_id );
			}
		}
	}


	/**
	 * Adds subscription details to order base data.
	 *
	 * Details are added to a \WC_Order::$payment->subscriptions[] property.
	 * @see SV_WC_Payment_Gateway_Integration_Subscriptions::add_subscription_details_to_order()
	 * @internal
	 *
	 * @since 5.4.2
	 *
	 * @param \WC_Order $order order object
	 * @param SV_WC_Payment_Gateway $gateway payment gateway
	 * @return \WC_Order
	 */
	public function add_subscriptions_details_to_order( $order, $gateway ) {

		if ( isset( $order->payment ) ) {

			// defaults
			$order->payment->subscriptions = [];
			$order->payment->recurring     = ! empty( $order->payment->recurring ) ?: false;

			// if the order contains a subscription (but is not a renewal)
			if ( wcs_order_contains_subscription( $order ) ) {

				$order->payment->recurring = true;

				$subscriptions = wcs_get_subscriptions_for_order( $order );

				if ( ! empty( $subscriptions ) ) {

					foreach ( $subscriptions as $subscription ) {

						if ( $subscription instanceof \WC_Subscription ) {

							$order->payment->subscriptions[] = $this->add_subscription_details_to_order( $subscription, false );
						}
					}
				}

			// order is for a subscription renewal
			} elseif ( wcs_order_contains_renewal( $order ) ) {

				$order->payment->recurring = true;

				$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );

				if ( ! empty( $subscriptions ) ) {

					foreach ( $subscriptions as $subscription ) {

						if ( $subscription instanceof \WC_Subscription ) {

							$order->payment->subscriptions[] = $this->add_subscription_details_to_order( $subscription, true );
						}
					}
				}
			}
		}

		return $order;
	}


	/**
	 * Builds and returns a subscription's details data.
	 *
	 * @see SV_WC_Payment_Gateway_Integration_Subscriptions::add_subscriptions_details_to_order()
	 *
	 * \stdClass {
	 *  int $id the subscription's ID
	 *  bool $is_renewal whether the order is for a subscription renewal
	 *  bool $is_installment whether the subscription is for an installment
	 *  bool $is_first whether it is the first payment for an installment series
	 *  bool $is_last whether it is the last payment for an installment series
	 *}
	 *
	 * @since 5.4.2
	 *
	 * @param \WC_Subscription $subscription subscription object
	 * @param bool $renewal whether the subscription is a renewal
	 * @return \stdClass subscription details data
	 */
	protected function add_subscription_details_to_order( $subscription, $renewal ) {

		$details = new \stdClass;

		$details->id             = max( 0, (int) $subscription->get_id() );
		$details->is_renewal     = (bool) $renewal;
		$details->is_installment = (bool) $subscription->get_date( 'end' );

		$details->is_first = $details->is_last = false;

		if ( $details->is_installment ) {

			// if this is not a renewal, but the subscription has an end date, then this must be the first installment
			if ( ! $details->is_renewal ) {
				$details->is_first = true;
			}

			// if the subscription has an end date, but there is no next payment date set, this must be the last installment
			if ( ! (bool) $subscription->get_date( 'next_payment' ) ) {
				$details->is_last = true;
			}
		}

		return $details;
	}


	/**
	 * Process a subscription renewal payment
	 *
	 * @since 4.1.0
	 * @param float $amount_to_charge subscription amount to charge, could include multiple renewals if they've previously failed and the admin has enabled it
	 * @param \WC_Order $order original order containing the subscription
	 */
	public function process_renewal_payment( $amount_to_charge, $order ) {

		// set payment total so it can override the default in get_order()
		$this->renewal_payment_total = SV_WC_Helper::number_format( $amount_to_charge );

		$token = $this->get_gateway()->get_order_meta( $order, 'payment_token' );

		// payment token must be present and valid
		if ( empty( $token ) || ! $this->get_gateway()->get_payment_tokens_handler()->user_has_token( $order->get_user_id(), $token ) ) {

			$this->get_gateway()->mark_order_as_failed( $order, __( 'Subscription Renewal: payment token is missing/invalid.', 'woocommerce-plugin-framework' ) );

			return;
		}

		// add subscriptions data to the order object prior to processing the payment
		add_filter( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_get_order', array( $this, 'get_order' ) );

		$this->get_gateway()->process_payment( $order->get_id() );
	}


	/**
	 * Adds subscriptions data to the order object, currently:
	 *
	 * + renewal order specific description
	 * + renewal payment total
	 * + token and associated data (last four, type, etc)
	 *
	 * @since 4.1.0
	 * @see SV_WC_Payment_Gateway_Direct::get_order()
	 * @param \WC_Order $order renewal order
	 * @return \WC_Order renewal order with payment token data set
	 */
	public function get_order( $order ) {

		$order->description = sprintf( esc_html__( '%1$s - Subscription Renewal Order %2$s', 'woocommerce-plugin-framework' ), wp_specialchars_decode( SV_WC_Helper::get_site_name(), ENT_QUOTES ), $order->get_order_number() );

		// override the payment total with the amount to charge given by Subscriptions
		$order->payment_total = $this->renewal_payment_total;

		// set payment token
		$order->payment->token = $this->get_gateway()->get_order_meta( $order, 'payment_token' );

		// use customer ID from renewal order, not user meta so the admin can update the customer ID for a subscription if needed
		$customer_id = $this->get_gateway()->get_order_meta( $order, 'customer_id' );

		// only if a customer ID exists in order meta, otherwise this will default to the previously set value from user meta
		if ( ! empty( $customer_id ) ) {
			$order->customer_id = $customer_id;
		}

		// get the token object
		$token = $this->get_gateway()->get_payment_tokens_handler()->get_token( $order->get_user_id(), $order->payment->token );

		// set token data on the order
		$order->payment->account_number = $token->get_last_four();
		$order->payment->last_four = $token->get_last_four();

		if ( $token->is_credit_card() ) {

			$order->payment->card_type = $token->get_card_type();
			$order->payment->exp_month = $token->get_exp_month();
			$order->payment->exp_year  = SV_WC_Payment_Gateway_Helper::format_exp_year( $token->get_exp_year() );

		} elseif ( $token->is_echeck() ) {

			$order->payment->account_type = $token->get_account_type();
		}

		return $order;
	}


	/**
	 * Don't copy order-specific meta to renewal orders from the WC_Subscription
	 * object. Generally the subscription object should not have any order-specific
	 * meta (aside from `payment_token` and `customer_id`) as they are not
	 * copied during the upgrade (see do_not_copy_order_meta_during_upgrade()), so
	 * this method is more of a fallback in case meta accidentally is copied.
	 *
	 * @since 4.1.0
	 * @param array $order_meta order meta to copy
	 * @return array
	 */
	public function do_not_copy_order_meta( $order_meta ) {

		$meta_keys = $this->get_order_specific_meta_keys();

		foreach ( $order_meta as $index => $meta ) {

			if ( in_array( $meta['meta_key'], $meta_keys ) ) {
				unset( $order_meta[ $index ] );
			}
		}

		return $order_meta;
	}


	/**
	 * Don't copy order-specific meta to the new WC_Subscription object during
	 * upgrade to 2.0.x. This only allows the `payment_token` and `customer_id`
	 * meta to be copied.
	 *
	 * @since 4.1.0
	 * @param array $order_meta order meta to copy
	 * @return array
	 */
	public function do_not_copy_order_meta_during_upgrade( $order_meta ) {

		foreach ( $this->get_order_specific_meta_keys() as $meta_key ) {

			if ( isset( $order_meta[ $meta_key ] ) ) {
				unset( $order_meta[ $meta_key ] );
			}
		}

		return $order_meta;
	}


	/**
	 * Processes a Change Payment transaction.
	 *
	 * This hooks in before standard payment processing to simply add or create
	 * token data and avoid certain failure conditions affecting the subscription
	 * object.
	 *
	 * @internal
	 *
	 * @since 4.6.4
	 *
	 * @param bool|array $result result from any others filtering this
	 * @param int $order_id an order or subscription ID
	 * @param SV_WC_Payment_Gateway_Direct $gateway gateway object
	 * @return array $result change payment result
	 */
	public function process_change_payment( $result, $order_id, $gateway ) {

		// if this is not a subscription and not changing payment, bail for normal order processing
		if ( ! wcs_is_subscription( $order_id ) || ! did_action( 'woocommerce_subscription_change_payment_method_via_pay_shortcode' ) ) {
			return $result;
		}

		$subscription = $gateway->get_order( $order_id );

		try {

			// if using a saved method, just add the data
			if ( isset( $subscription->payment->token ) && $subscription->payment->token ) {

				$gateway->add_transaction_data( $subscription );

			// otherwise...tokenize
			} else {

				$subscription = $gateway->get_payment_tokens_handler()->create_token( $subscription );
			}

			$result = array(
				'result'   => 'success',
				'redirect' => $subscription->get_view_order_url(),
			);

		} catch ( SV_WC_Plugin_Exception $e ) {

			/* translators: Placeholders: %1$s - payment gateway title, %2$s - error message; e.g. Order Note: [Payment method] Payment Change failed [error] */
			$note = sprintf( __( '%1$s Payment Change Failed (%2$s)', 'woocommerce-plugin-framework' ), $gateway->get_method_title(), $e->getMessage() );

			// add a subscription note to keep track of failures
			$subscription->add_order_note( $note );

			SV_WC_Helper::wc_add_notice( __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-plugin-framework' ), 'error' );

			// this isn't used by Subscriptions, but return a failure result anyway
			$result = array(
				'result'  => 'failure',
				'message' => $e->getMessage(),
			);
		}

		return $result;
	}


	/**
	 * Remove order meta (like trans ID) that's added to a Subscription object
	 * during the change payment method flow, which uses WC_Payment_Gateway::process_payment(),
	 * thus some order-specific meta is added that is undesirable to have copied
	 * over to renewal orders.
	 *
	 * @since 4.1.0
	 *
	 * @param array $result process_payment() result, unused
	 * @param int|\WC_Subscription $subscription subscription object
	 * @return array
	 */
	public function remove_order_meta_from_change_payment( $result, $subscription ) {

		// remove order-specific meta
		foreach ( $this->get_order_specific_meta_keys() as $meta_key ) {
			delete_post_meta( $subscription->get_id(), $meta_key );
		}

		// get a fresh subscription object after previous metadata changes
		$subscription = is_numeric( $subscription ) ? wcs_get_subscription( $subscription ) : $subscription;

		$old_payment_method = $subscription->get_meta( '_old_payment_method', true, 'edit' );
		$new_payment_method = $subscription->get_payment_method( 'edit' );
		$gateway_id         = $this->get_gateway()->get_id();

		// if the payment method has been changed to another gateway, additionally remove the old payment token and customer ID meta
		if ( $new_payment_method !== $gateway_id && $old_payment_method === $gateway_id ) {

			$this->get_gateway()->delete_order_meta( $subscription, 'payment_token' );
			$this->get_gateway()->delete_order_meta( $subscription, 'customer_id' );
		}

		return $result;
	}


	/**
	 * Update the payment token and optional customer ID for a subscription after a customer
	 * uses this gateway to successfully complete the payment for an automatic
	 * renewal payment which had previously failed.
	 *
	 * @since 4.1.0
	 * @param \WC_Subscription $subscription subscription being updated
	 * @param \WC_Order $renewal_order order which recorded the successful payment (to make up for the failed automatic payment).
	 */
	public function update_failing_payment_method( $subscription, $renewal_order ) {

		// if the order doesn't have a transaction date stored, bail
		// this prevents updating the subscription with a failing token in case the merchant is switching the order status manually without new payment
		if ( ! $this->get_gateway()->get_order_meta( $renewal_order, 'trans_date' ) ) {
			return;
		}

		if ( $customer_id = $this->get_gateway()->get_order_meta( $renewal_order, 'customer_id' ) ) {
			$this->get_gateway()->update_order_meta( $subscription, 'customer_id', $customer_id );
		}

		$this->get_gateway()->update_order_meta( $subscription, 'payment_token', $this->get_gateway()->get_order_meta( $renewal_order, 'payment_token' ) );
	}


	/**
	 * Get the order-specific meta keys that should not be copied to the WC_Subscription
	 * object during upgrade to 2.0.x or during change payment method actions
	 *
	 * @since 4.1.0
	 * @return array
	 */
	protected function get_order_specific_meta_keys() {

		$keys = array(
			'trans_id',
			'trans_date',
			'account_four',
			'card_expiry_date',
			'card_type',
			'authorization_code',
			'auth_can_be_captured',
			'charge_captured',
			'capture_trans_id',
			'account_type',
			'check_number',
			'environment',
			'retry_count',
		);

		foreach ( $keys as $index => $key ) {

			$keys[ $index ] = $this->get_gateway()->get_order_meta_prefix() . $key;
		}

		/**
		 * Filter Subscriptions order-specific meta keys
		 *
		 * Use this to include additional meta keys that should not be copied over
		 * to the WC_Subscriptions object during renewal payments, the
		 * change payment method action, or the upgrade to 2.0.x.
		 *
		 * @since 4.1.0
		 * @param array $keys meta keys, with gateway order meta prefix included
		 * @param \SV_WC_Payment_Gateway_Integration_Subscriptions $this subscriptions integration class instance
		 */
		return apply_filters( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_subscriptions_order_specific_meta_keys', $keys, $this );
	}


	/**
	 * Render the payment method used for a subscription in the "My Subscriptions" table
	 *
	 * @since 4.1.0
	 * @param string $payment_method_to_display the default payment method text to display
	 * @param \WC_Subscription $subscription
	 * @return string the subscription payment method
	 */
	public function maybe_render_payment_method( $payment_method_to_display, $subscription ) {

		// bail for other payment methods
		if ( $this->get_gateway()->get_id() !== $subscription->get_payment_method( 'edit' ) ) {
			return $payment_method_to_display;
		}

		$token = $this->get_gateway()->get_payment_tokens_handler()->get_token( $subscription->get_user_id(), $this->get_gateway()->get_order_meta( $subscription, 'payment_token' ) );

		if ( $token instanceof SV_WC_Payment_Gateway_Payment_Token ) {
			$payment_method_to_display = sprintf( __( 'Via %s ending in %s', 'woocommerce-plugin-framework' ), $token->get_type_full(), $token->get_last_four() );
		}

		return $payment_method_to_display;
	}


	/**
	 * Add a subscriptions header to the My Payment Methods table.
	 *
	 * @since 4.3.0
	 * @param array $headers the table headers
	 * @param \SV_WC_Payment_Gateway_My_Payment_Methods the my payment methods instance
	 * @return array
	 */
	public function add_my_payment_methods_table_header( $headers, $handler ) {

		if ( isset( $headers['subscriptions'] ) ) {
			return $headers;
		}

		$new_headers = array();

		foreach ( $headers as $id => $label ) {

			// Add the header before the actions
			if ( 'actions' === $id ) {
				$new_headers['subscriptions'] = __( 'Subscriptions', 'woocommerce-plugin-framework' );
			}

			$new_headers[ $id ] = $label;
		}

		return $new_headers;
	}


	/**
	 * Displays a list of subscriptions orders associated with the current token.
	 *
	 * This method only generates output for tokens created by the framework.
	 *
	 * TODO: Ask Subscriptions developers whether they would be interested in start adding the column to the payment methods table themselves {WV 2020-02-19}
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param array $method payment method
	 */
	public function add_payment_method_subscriptions( $method ) {

		if ( isset( $method['token'] ) ) {

			$token = $this->get_gateway()->get_payment_tokens_handler()->get_token( get_current_user_id(), $method['token'] );

			if ( $token instanceof SV_WC_Payment_Gateway_Payment_Token ) {
				echo $this->get_payment_method_subscriptions_html( $token );
			}
		}
	}


	/**
	 * Gets the HTML code for the list of subscriptions orders associated with the given token.
	 *
	 * @since 5.8.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token the payment token
	 * @return string
	 */
	private function get_payment_method_subscriptions_html( $token ) {

		$html = '';

		// make sure the token belongs to this gateway
		if ( $token->get_gateway_id() === $this->get_gateway()->get_id() ) {

			$subscription_ids = array();

			// build a link for each subscription
			foreach ( $this->get_payment_token_subscriptions( get_current_user_id(), $token ) as $subscription ) {
				$subscription_ids[] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $subscription->get_view_order_url() ), esc_attr( sprintf( _x( '#%s', 'hash before order number', 'woocommerce-plugin-framework' ), $subscription->get_order_number() ) ) );
			}

			if ( ! empty( $subscription_ids ) ) {
				$html = implode( ', ', $subscription_ids );
			} else {
				$html = __( 'N/A', 'woocommerce-plugin-framework' );
			}
		}

		return $html;
	}


	/**
	 * Add a subscriptions header to the My Payment Methods table.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-20 {WV 2020-02-20}
	 *
	 * @internal
	 *
	 * @since 4.3.0
	 * @deprecated 5.8.0
	 *
	 * @param array $method the table row data
	 * @param \SV_WC_Payment_Gateway_Payment_Token $token the payment token
	 * @param \SV_WC_Payment_Gateway_My_Payment_Methods the my payment methods instance
	 * @return array
	 */
	public function add_my_payment_methods_table_body_row_data( $method, $token, $handler ) {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return $method;
	}


	/**
	 * Disables the "Delete" My Payment Methods method action button if there is an associated subscription.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-20 {WV 2020-02-20}
	 *
	 * @internal
	 *
	 * @since 4.3.0
	 * @deprecated 5.8.0
	 *
	 * @param array $actions the token actions
	 * @param SV_WC_Payment_Gateway_Payment_Token the token object
	 * @param SV_WC_Payment_Gateway_My_Payment_Methods the my payment methods instance
	 * @return array
	 */
	public function disable_my_payment_methods_table_method_delete( $actions, $token, $handler ) {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return $actions;
	}


	/**
	 * Gets the subscriptions tied to a user payment token.
	 *
	 * @since 4.3.0
	 *
	 * @param int $user_id the user
	 * @param SV_WC_Payment_Gateway_Payment_Token $token the token object
	 * @return array the subscriptions or an empty array
	 */
	protected function get_payment_token_subscriptions( $user_id, $token ) {

		$subscriptions = wcs_get_users_subscriptions( $user_id );

		foreach ( $subscriptions as $key => $subscription ) {

			$payment_method  = $subscription->get_payment_method( 'edit' );
			$stored_token_id = (string) $this->get_gateway()->get_order_meta( $subscription, 'payment_token' );

			if ( $stored_token_id !== (string) $token->get_id() || $payment_method !== $this->get_gateway()->get_id() ) {
				unset( $subscriptions[ $key ] );
			}
		}

		return $subscriptions;
	}


	/**
	 * Include the payment meta data required to process automatic recurring
	 * payments so that store managers can manually set up automatic recurring
	 * payments for a customer via the Edit Subscriptions screen in 2.0.x
	 *
	 * @since 4.1.0
	 * @param array $meta associative array of meta data required for automatic payments
	 * @param \WC_Subscription $subscription subscription object
	 * @return array
	 */
	public function admin_add_payment_meta( $meta, $subscription ) {

		$prefix = $this->get_gateway()->get_order_meta_prefix();

		$meta[ $this->get_gateway()->get_id() ] = array(
			'post_meta' => array(
				$prefix . 'payment_token' => array(
					'value' => $this->get_gateway()->get_order_meta( $subscription, 'payment_token' ),
					'label' => __( 'Payment Token', 'woocommerce-plugin-framework' ),
				),
				$prefix . 'customer_id'   => array(
					'value' => $this->get_gateway()->get_order_meta( $subscription, 'customer_id' ),
					'label' => __( 'Customer ID', 'woocommerce-plugin-framework' ),
				),
			)
		);

		return $meta;
	}


	/**
	 * Validate the payment meta data required to process automatic recurring
	 * payments so that store managers can manually set up automatic recurring
	 * payments for a customer via the Edit Subscriptions screen in 2.0.x
	 *
	 * @since 4.1.0
	 *
	 * @param array $meta associative array of meta data required for automatic payments
	 * @throws \Exception if payment token or customer ID is missing or blank
	 */
	public function admin_validate_payment_meta( $meta ) {

		$prefix = $this->get_gateway()->get_order_meta_prefix();

		// payment token
		if ( empty( $meta['post_meta'][ $prefix . 'payment_token' ]['value'] ) ) {
			throw new \Exception( sprintf( __( '%s is required.', 'woocommerce-plugin-framework' ), $meta['post_meta'][ $prefix . 'payment_token' ]['label'] ) );
		}

		// customer ID - optional for some gateways so check if it's set first
		if ( isset( $meta['post_meta'][ $prefix . 'customer_id'] ) && empty( $meta['post_meta'][ $prefix . 'customer_id' ]['value'] ) ) {
			throw new \Exception( sprintf( __( '%s is required.', 'woocommerce-plugin-framework' ), $meta['post_meta'][ $prefix . 'customer_id' ]['label'] ) );
		}
	}


}


endif;
