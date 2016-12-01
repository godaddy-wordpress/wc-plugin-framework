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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Apple-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Sets up Apple Pay support.
 *
 * @since 4.6.0-dev
 */
class SV_WC_Payment_Gateway_Apple_Pay {


	/** @var \SV_WC_Payment_Gateway_Apple_Pay_Admin the admin instance */
	protected $admin;

	/** @var \SV_WC_Payment_Gateway_Apple_Pay_Frontend the frontend instance */
	protected $frontend;

	/** @var \SV_WC_Payment_Gateway_Plugin the plugin instance */
	protected $plugin;

	/** @var \SV_WC_Payment_Gateway_Apple_Pay_API the Apple Pay API */
	protected $api;


	/**
	 * Constructs the class.
	 *
	 * @since 4.6.0-dev
	 * @param \SV_WC_Payment_Gateway_Plugin $plugin the plugin instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->init();

		if ( $this->is_available() ) {

			// validate a merchant via AJAX
			add_action( 'wp_ajax_sv_wc_apple_pay_validate_merchant',        array( $this, 'validate_merchant' ) );
			add_action( 'wp_ajax_nopriv_sv_wc_apple_pay_validate_merchant', array( $this, 'validate_merchant' ) );

			// process the payment via AJAX
			add_action( 'wp_ajax_sv_wc_apple_pay_process_payment',        array( $this, 'process_payment' ) );
			add_action( 'wp_ajax_nopriv_sv_wc_apple_pay_process_payment', array( $this, 'process_payment' ) );

			add_filter( 'wc_payment_gateway_' . $this->get_processing_gateway()->get_id() . '_get_order', array( $this, 'add_order_data' ) );
		}
	}


	/**
	 * Initializes the Apple Pay handlers.
	 *
	 * @since 4.6.0-dev
	 */
	protected function init() {

		require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/class-sv-wc-payment-gateway-apple-pay-admin.php');
		require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/class-sv-wc-payment-gateway-apple-pay-frontend.php');

		require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/api/class-sv-wc-payment-gateway-apple-pay-payment-response.php');

		if ( is_admin() && ! is_ajax() ) {
			$this->admin = new SV_WC_Payment_Gateway_Apple_Pay_Admin( $this );
		} else {
			$this->frontend = new SV_WC_Payment_Gateway_Apple_Pay_Frontend( $this->get_plugin(), $this );
		}
	}


	/**
	 * Validates a merchant via AJAX.
	 *
	 * @since 4.6.0-dev
	 */
	public function validate_merchant() {

		check_ajax_referer( 'sv_wc_apple_pay_validate_merchant', 'nonce' );

		$merchant_id = SV_WC_Helper::get_post( 'merchant_id' );
		$url         = SV_WC_Helper::get_post( 'url' );

		try {

			$response = $this->get_api()->validate_merchant( $url, $merchant_id, home_url(), get_bloginfo( 'name' ) );

			wp_send_json( array(
				'result'           => 'success',
				'merchant_session' => $response->get_merchant_session(),
			) );

		} catch ( SV_WC_API_Exception $e ) {

			$this->log( 'Could not validate merchant. ' . $e->getMessage() );

			wp_send_json( array(
				'result'  => 'error',
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			) );
		}
	}


	/**
	 * Processes the payment after the Apple Pay authorization.
	 *
	 * @since 4.6.0-dev
	 */
	public function process_payment() {

		$type     = SV_WC_Helper::get_post( 'type' );
		$response = stripslashes( SV_WC_Helper::get_post( 'payment' ) );

		try {

			// store the the payment response JSON for later use
			WC()->session->set( 'apple_pay_payment_response', $response );

			$response = new SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $response );

			// log the payment response
			$this->log( "Payment Response:\n" . $response->to_string_safe() );

			// pretend this is at checkout so totals are fully calculated
			if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
				define( 'WOOCOMMERCE_CHECKOUT', true );
			}

			$order = null;

			// create a new order
			if ( 'cart' === $type || 'checkout' === $type ) {
				$order = $this->create_cart_order();
			} else if ( 'product' === $type ) {
				$order = $this->create_product_order();
			} else {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid payment type recieved' );
			}

			// if we got to this point, the payment was authorized by Apple Pay
			// from here on out, it's up to the gateway to not screw things up.
			$order->add_order_note( __( 'Apple Pay payment authorized.', 'woocommerce-plugin-framework' ) );

			$order->set_address( $response->get_billing_address(),  'billing' );
			$order->set_address( $response->get_shipping_address(), 'shipping' );

			// process the payment via the gateway
			$result = $this->get_processing_gateway()->process_payment( $order->id );

			// clear the payment request data
			unset( WC()->session->apple_pay_payment_request );
			unset( WC()->session->apple_pay_payment_response );
			unset( WC()->session->order_awaiting_payment );

			wp_send_json( $result );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			$this->log( 'Payment failed. ' . $e->getMessage() );

			if ( $order ) {

				$order->add_order_note( sprintf(
					/** translators: Placeholders: %s - the error message */
					__( 'Apple Pay payment failed. %s', 'woocommerce-plugin-framework' ),
					$e->getMessage()
				) );
			}

			wp_send_json( array(
				'result'  => 'error',
				'message' => $e->getMessage(),
			) );
		}
	}


	/**
	 * Allows the processing gateway to add Apple Pay details to the payment data.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Order $order the order object
	 * @return \WC_Order
	 */
	public function add_order_data( $order ) {

		$response_data = WC()->session->get( 'apple_pay_payment_response', '' );

		if ( ! empty( $response_data ) ) {

			$response = new SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $response_data );

			$order = $this->get_processing_gateway()->get_order_for_apple_pay( $order, $response );
		}

		return $order;
	}


	/**
	 * Creates an order from the current cart.
	 *
	 * @since 4.6.0-dev
	 * @throws \SV_WC_Plugin_Exception
	 */
	public function create_cart_order() {

		$items = array();

		WC()->cart->calculate_totals();

		foreach ( WC()->cart->get_cart() as $cart_item_key => $item ) {

			$items[ $cart_item_key ] = array(
				'product'  => $item['data'],
				'quantity' => $item['quantity'],
				'args'     => array(
					'variation' => $item['variation'],
					'totals'    => array(
						'subtotal'     => $item['line_subtotal'],
						'subtotal_tax' => $item['line_subtotal_tax'],
						'total'        => $item['line_total'],
						'tax'          => $item['line_tax'],
						'tax_data'     => $item['line_tax_data']
					),
				),
				'values' => $item,
			);
		}

		$args = array(
			'fees' => WC()->cart->get_fees(),
		);

		if ( $packages = WC()->shipping->get_packages() ) {
			$args['packages'] = $packages;
		}

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) {

			$args['coupons'][ $code ] = array(
				'amount'     => WC()->cart->get_coupon_discount_amount( $code ),
				'tax_amount' => WC()->cart->get_coupon_discount_tax_amount( $code ),
			);
		}

		// set the cart hash to this can be resumed on failure
		$args['cart_hash'] = md5( json_encode( wc_clean( WC()->cart->get_cart_for_session() ) ) . WC()->cart->total );

		$order = $this->create_order( $items, $args );

		return $order;
	}


	/**
	 * Creates an order from a single product request.
	 *
	 * @since 4.6.0-dev
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	protected function create_product_order() {

		$payment_request = $this->get_stored_payment_request();

		if ( empty( $payment_request ) ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Payment request data is missing.' );
		}

		$items = array();
		$args  = array();

		foreach ( $payment_request['lineItems'] as $product_id => $item ) {

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			if ( ! $product->is_in_stock() || ! $product->has_enough_stock( 1 ) ) {
				throw new SV_WC_Payment_Gateway_Exception( __( 'The product is out of stock.', 'woocommerce-plugin-framework' ) );
			}

			$items[] = array(
				'product'  => $product,
				'quantity' => 1,
				'args'     => array(),
				'values'   => $item,
			);
		}

		// set the cart hash to this can be resumed on failure
		$args['cart_hash'] = md5( json_encode( wc_clean( $payment_request ) ) . $payment_request['total']['amount'] );

		$order = $this->create_order( $items, $args );

		// set the totals
		if ( ! empty( $payment_request['lineItems']['taxes'] ) ) {
			$order->set_total( $payment_request['lineItems']['taxes']['amount'], 'tax' );
		}

		if ( ! empty( $payment_request['lineItems']['shipping'] ) ) {
			$order->set_total( $payment_request['lineItems']['shipping']['amount'], 'shipping' );
		}

		$order->set_total( $payment_request['total']['amount'] );

		return $order;
	}


	/**
	 * Creates a new order from provided data.
	 *
	 * This is adapted from WooCommerce's `WC_Checkout::create_order()`
	 *
	 * @since 4.6.0-dev
	 * @param array $items {
	 *     The items to add to the order.
	 *
	 *     @type \WC_Product $product  The product object.
	 *     @type int         $quantity The item quantity.
	 *     @type array       $args     The item args. See `WC_Abstract_Order::add_product()` for required keys.
	 *     @type array       $values   The original cart item values. Only included to maintain compatibility
	 *                                 with the `woocommerce_add_order_item_meta` filter.
	 * }
	 * @param array $args {
	 *     Optional. The order args.
	 *
	 *     @type int    $customer_id The user ID for this customer. If left blank, the current user ID will be
	 *                               used, or the user will be Guest if there is no current user.
	 *     @type array  $fees        Any fees to add to the order. See `WC_Abstract_Order::add_fee()` for
	 *                               required values.
	 *     @type array  $packages    Any shipping packages to add to the order. As formatted by
	 *                              `WC()->shipping->get_packages()`
	 *     @type array  $coupons     Any coupons to add to the order. Arrays as
	 *                               `$code => array( $amount => 0.00, $tax_amount => 0.00 )`
	 *     @type string $cart_hash   The hashed cart object to be used later in case the order is to be resumed.
	 *
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function create_order( $items, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'customer_id' => get_current_user_id(),
			'fees'        => array(),
			'packages'    => array(),
			'coupons'     => array(),
			'cart_hash'   => '',
		) );

		try {

			if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_5() ) {
				wc_transaction_query( 'start' );
			}


			$order_data = array(
				'status'      => apply_filters( 'woocommerce_default_order_status', 'pending' ),
				'customer_id' => $args['customer_id'],
				'cart_hash'   => $args['cart_hash'],
				'created_via' => 'apple_pay',
			);

			$order = $this->get_order_object( $order_data );

			$order->set_payment_method( $this->get_processing_gateway() );

			// add line items
			foreach ( $items as $key => $item ) {

				$item_id = $order->add_product( $item['product'], $item['quantity'], $item['args'] );

				if ( ! $item_id ) {
					throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 525 ) );
				}

				/** This action is a duplicate from \WC_Checkout::create_order() */
				do_action( 'woocommerce_add_order_item_meta', $item_id, $item['values'], $key );
			}

			// add fees
			foreach ( $args['fees'] as $key => $fee ) {

				$item_id = $order->add_fee( $fee );

				if ( ! $item_id ) {
					throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 526 ) );
				}

				/** This action is a duplicate from \WC_Checkout::create_order() */
				do_action( 'woocommerce_add_order_fee_meta', $order->id, $item_id, $fee, $key );
			}

			// add shipping packages
			foreach ( $args['packages'] as $key => $package ) {

				$shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

				if ( isset( $package['rates'][ $shipping_methods[ $key ] ] ) ) {

					$item_id = $order->add_shipping( $package['rates'][ $shipping_methods[ $key ] ] );

					if ( ! $item_id ) {
						throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 527 ) );
					}

					/** This action is a duplicate from \WC_Checkout::create_order() */
					do_action( 'woocommerce_add_shipping_order_item', $order->id, $item_id, $key );
				}
			}

			// add coupons
			foreach ( $args['coupons'] as $code => $coupon ) {

				if ( ! $order->add_coupon( $code, $coupon['amount'], $coupon['tax_amount'] ) ) {
					throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 529 ) );
				}
			}

			$order->calculate_totals();

			if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_5() ) {
				wc_transaction_query( 'commit' );
			}

			return $order;

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_5() ) {
				wc_transaction_query( 'rollback' );
			}

			throw $e;
		}
	}


	/**
	 * Gets an order object for add items.
	 *
	 * @since 4.6.0-dev
	 * @param array $order_data the order data
	 * @return \WC_Order
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	protected function get_order_object( $order_data ) {

		$order_id = (int) WC()->session->get( 'order_awaiting_payment', 0 );

		if ( $order_id && $order_data['cart_hash'] === get_post_meta( $order_id, '_cart_hash', true ) && ( $order = wc_get_order( $order_id ) ) && $order->has_status( array( 'pending', 'failed' ) ) ) {

			$order_data['order_id'] = $order_id;

			$order = wc_update_order( $order_data );

			if ( is_wp_error( $order ) ) {

				throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 522 ) );

			} else {

				$order->remove_order_items();

				/** This action is a duplicate from \WC_Checkout::create_order() */
				do_action( 'woocommerce_resume_order', $order_id );
			}

		} else {

			$order = wc_create_order( $order_data );

			if ( is_wp_error( $order ) ) {
				throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 520 ) );
			} elseif ( false === $order ) {
				throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 521 ) );
			}

			// set the new order ID so it can be resumed in case of failure
			WC()->session->set( 'order_awaiting_payment', $order->id );

			/** This action is a duplicate from \WC_Checkout::create_order() */
			do_action( 'woocommerce_new_order', $order->id );
		}

		return $order;
	}


	/**
	 * Gets the stored payment request data.
	 *
	 * @since 4.6.0-dev
	 * @return array
	 */
	public function get_stored_payment_request() {

		return WC()->session->get( 'apple_pay_payment_request', array() );
	}


	/**
	 * Stores payment request data for later use.
	 *
	 * @since 4.6.0-dev
	 */
	public function store_payment_request( $data ) {

		WC()->session->set( 'apple_pay_payment_request', $data );
	}


	/**
	 * Gets the Apple Pay API.
	 *
	 * @since 4.6.0-dev
	 * @return \SV_WC_Payment_Gateway_Apple_Pay_API
	 */
	protected function get_api() {

		if ( ! $this->api instanceof SV_WC_Payment_Gateway_Apple_Pay_API ) {

			require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/api/class-sv-wc-payment-gateway-apple-pay-api.php');
			require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/api/class-sv-wc-payment-gateway-apple-pay-api-request.php');
			require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/api/class-sv-wc-payment-gateway-apple-pay-api-response.php');

			$this->api = new SV_WC_Payment_Gateway_Apple_Pay_API( $this->get_processing_gateway() );
		}

		return $this->api;
	}


	/**
	 * Adds a log entry to the gateway's debug log.
	 *
	 * @since 4.6.0-dev
	 * @param string $message the log message to add
	 */
	public function log( $message ) {

		$gateway = $this->get_processing_gateway();

		if ( ! $gateway ) {
			return;
		}

		if ( $gateway->debug_log() ) {
			$gateway->get_plugin()->log( '[Apple Pay] ' . $message, $gateway->get_id() );
		}
	}


	/**
	 * Determines if Apple Pay is available.
	 *
	 * This does not indicate browser support or a user's ability, but rather
	 * that Apple Pay is properly configured and ready to be initiated by the
	 * Apple Pay JS.
	 *
	 * @since 4.6.0-dev
	 * @return bool
	 */
	public function is_available() {

		$is_available = wc_site_is_https() && $this->is_configured();

		$is_available = $is_available && in_array( get_woocommerce_currency(), $this->get_accepted_currencies(), true );

		/**
		 * Filters whether Apple Pay should be made available to users.
		 *
		 * @since 4.6.0-dev
		 * @param bool $is_available
		 */
		return apply_filters( 'sv_wc_apple_pay_is_available', $is_available );
	}


	/**
	 * Determines if Apple Pay settings are properly configured.
	 *
	 * @since 4.6.0-dev
	 * @return bool
	 */
	public function is_configured() {

		if ( ! $this->get_processing_gateway() ) {
			return false;
		}

		$is_configured = $this->is_enabled() && $this->get_merchant_id() && $this->get_processing_gateway()->is_enabled();

		$is_configured = $is_configured && $this->is_cert_configured();

		return $is_configured;
	}


	/**
	 * Determines if the certification path is set and valid.
	 *
	 * @since 4.6.0-dev
	 * @return bool
	 */
	public function is_cert_configured() {

		return is_readable( $this->get_cert_path() );
	}


	/**
	 * Determines if Apple Pay is enabled.
	 *
	 * @since 4.6.0-dev
	 * @return bool
	 */
	public function is_enabled() {

		return 'yes' === get_option( 'sv_wc_apple_pay_enabled' );
	}


	/**
	 * Gets the configured Apple merchant ID.
	 *
	 * @since 4.6.0-dev
	 * @return string
	 */
	public function get_merchant_id() {

		return get_option( 'sv_wc_apple_pay_merchant_id' );
	}


	/**
	 * Gets the certificate file path.
	 *
	 * @since 4.6.0-dev
	 * @return string
	 */
	public function get_cert_path() {

		return get_option( 'sv_wc_apple_pay_cert_path' );
	}


	/**
	 * Gets the currencies accepted by the gateway's Apple Pay integration.
	 *
	 * @since 4.6.0-dev
	 * @return array
	 */
	public function get_accepted_currencies() {

		$currencies = ( $this->get_processing_gateway() ) ? $this->get_processing_gateway()->get_apple_pay_currencies() : array();

		/**
		 * Filters the currencies accepted by the gateway's Apple Pay integration.
		 *
		 * @since 4.6.0-dev
		 * @return array
		 */
		return apply_filters( 'sv_wc_apple_pay_accepted_currencies', $currencies );
	}


	/**
	 * Gets the gateway's Apple Pay capabilities.
	 *
	 * @since 4.6.0-dev
	 * @return array
	 */
	public function get_capabilities() {

		$valid_capabilities = array(
			'supports3DS',
			'supportsEMV',
			'supportsCredit',
			'supportsDebit',
		);

		$gateway_capabilities = ( $this->get_processing_gateway() ) ? $this->get_processing_gateway()->get_apple_pay_capabilities() : array();

		$capabilities = array_intersect( $valid_capabilities, $gateway_capabilities );

		/**
		 * Filters the gateway's Apple Pay capabilities.
		 *
		 * @since 4.6.0-dev
		 * @param array $capabilities the gateway capabilities
		 * @param \SV_WC_Payment_Gateway_Apple_Pay $handler the Apple Pay handler
		 */
		return apply_filters( 'sv_wc_apple_pay_capabilities', array_values( $capabilities ), $this );
	}


	/**
	 * Gets the supported networks for Apple Pay.
	 *
	 * @since 4.6.0-dev
	 * @return array
	 */
	public function get_supported_networks() {

		$accepted_card_types = ( $this->get_processing_gateway() ) ? $this->get_processing_gateway()->get_card_types() : array();

		$accepted_card_types = array_map( 'SV_WC_Payment_Gateway_Helper::normalize_card_type', $accepted_card_types );

		$valid_networks = array(
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_AMEX       => 'amex',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_DISCOVER   => 'discover',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD => 'masterCard',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA       => 'visa',
			'privateLabel' => 'privateLabel', // ?
		);

		$networks = array_intersect_key( $valid_networks, array_flip( $accepted_card_types ) );

		/**
		 * Filters the supported Apple Pay networks (card types).
		 *
		 * @since 4.6.0-dev
		 * @param array $networks the supported networks
		 * @param \SV_WC_Payment_Gateway_Apple_Pay $handler the Apple Pay handler
		 */
		return apply_filters( 'sv_wc_apple_pay_supported_networks', array_values( $networks ), $this );
	}


	/**
	 * Gets the gateways that declare Apple Pay support.
	 *
	 * @since 4.6.0-dev
	 * @return array the supporting gateways as `$gateway_id => \SV_WC_Payment_Gateway`
	 */
	public function get_supporting_gateways() {

		$available_gateways  = $this->get_plugin()->get_gateways();
		$supporting_gateways = array();

		foreach ( $available_gateways as $key => $gateway ) {

			if ( $gateway->supports_apple_pay() ) {
				$supporting_gateways[ $gateway->get_id() ] = $gateway;
			}
		}

		return $supporting_gateways;
	}


	/**
	 * Gets the gateway set to process Apple Pay transactions.
	 *
	 * @since 4.6.0-dev
	 * @return \SV_WC_Payment_Gateway|null
	 */
	public function get_processing_gateway() {

		$gateways = $this->get_supporting_gateways();

		$gateway_id = get_option( 'sv_wc_apple_pay_payment_gateway' );

		return isset( $gateways[ $gateway_id ] ) ? $gateways[ $gateway_id ] : null;
	}


	/**
	 * Gets the gateway plugin instance.
	 *
	 * @since 4.6.0-dev
	 * @return \SV_WC_Payment_Gateway_Plugin
	 */
	public function get_plugin() {

		return $this->plugin;
	}


}
