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

		// validate a merchant via AJAX
		add_action( 'wp_ajax_sv_wc_apple_pay_validate_merchant',        array( $this, 'validate_merchant' ) );
		add_action( 'wp_ajax_nopriv_sv_wc_apple_pay_validate_merchant', array( $this, 'validate_merchant' ) );

		// process the payment via AJAX
		add_action( 'wp_ajax_sv_wc_apple_pay_process_payment',        array( $this, 'process_payment' ) );
		add_action( 'wp_ajax_nopriv_sv_wc_apple_pay_process_payment', array( $this, 'process_payment' ) );

		add_filter( 'wc_payment_gateway_' . $this->get_processing_gateway()->get_id() . '_get_order', array( $this, 'add_order_data' ) );
	}


	/**
	 * Initializes the Apple Pay handlers.
	 *
	 * @since 4.6.0-dev
	 */
	protected function init() {

		require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/class-sv-wc-payment-gateway-apple-pay-admin.php');
		require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/class-sv-wc-payment-gateway-apple-pay-frontend.php');

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

			$this->get_processing_gateway()->add_debug_message( 'Apple Pay API error. ' . $e->getMessage() );

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
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function process_payment() {

		$type    = SV_WC_Helper::get_post( 'type' );
		$payment = json_decode( stripslashes( SV_WC_Helper::get_post( 'payment' ) ) );

		try {

			if ( ! $payment ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid payment data recieved' );
			}

			// store the the payment response for later use
			WC()->session->set( 'apple_pay_payment_response', $payment );

			// pretend this is at checkout so totals are fully calculated
			if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
				define( 'WOOCOMMERCE_CHECKOUT', true );
			}

			// log the payment response
			$this->get_processing_gateway()->add_debug_message( "Apple Pay Payment Response:\n" . print_r( $payment, true ) );

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

			$billing_address = $shipping_address = array();

			// set the billing address
			if ( isset( $payment->billingContact ) ) {

				if ( ! empty( $payment->billingContact->givenName ) ) {
					$billing_address['first_name'] = $payment->billingContact->givenName;
					$billing_address['last_name']  = $payment->billingContact->familyName;
				}

				if ( ! empty( $payment->billingContact->addressLines ) ) {

					$billing_address = array_merge( $billing_address, array(
						'address_1'  => $payment->billingContact->addressLines[0],
						'address_2'  => ! empty( $payment->billingContact->addressLines[1] ) ? $payment->billingContact->addressLines[1] : '',
						'city'       => $payment->billingContact->locality,
						'state'      => $payment->billingContact->administrativeArea,
						'postcode'   => $payment->billingContact->postalCode,
						'country'    => strtoupper( $payment->billingContact->countryCode ),
					) );
				}

				// default the shipping address to the billing address
				$shipping_address = $billing_address;
			}

			// set the shipping address
			if ( isset( $payment->shippingContact ) ) {

				if ( isset( $payment->shippingContact->givenName ) ) {
					$shipping_address['first_name'] = $payment->shippingContact->givenName;
					$shipping_address['last_name']  = $payment->shippingContact->familyName;
				}

				if ( ! empty( $payment->shippingContact->addressLines ) ) {
					$shipping_address = array_merge( $shipping_address, array(
						'address_1'  => $payment->shippingContact->addressLines[0],
						'address_2'  => ! empty( $payment->shippingContact->addressLines[1] ) ? $payment->shippingContact->addressLines[1] : '',
						'city'       => $payment->shippingContact->locality,
						'state'      => $payment->shippingContact->administrativeArea,
						'postcode'   => $payment->shippingContact->postalCode,
						'country'    => strtoupper( $payment->shippingContact->countryCode ),
					) );
				}

				// set the billing email
				if ( ! empty( $payment->shippingContact->emailAddress ) ) {
					$billing_address['email'] = $payment->shippingContact->emailAddress;
				}

				// set the billing phone number
				if ( ! empty( $payment->shippingContact->phoneNumber ) ) {
					$billing_address['phone'] = $payment->shippingContact->phoneNumber;
				}
			}

			$order->set_address( $billing_address, 'billing' );
			$order->set_address( $shipping_address, 'shipping' );

			// process the payment via the gateway
			$result = $this->get_processing_gateway()->process_payment( $order->id );

			// clear the payment request data
			unset( WC()->session->apple_pay_payment_request );

			wp_send_json( $result );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			$this->get_processing_gateway()->add_debug_message( 'Apple Pay payment failed. ' . $e->getMessage() );

			$order->add_order_note( sprintf(
				/** translators: Placeholders: %s - the error message */
				__( 'Apple Pay payment failed. %s', 'woocommerce-plugin-framework' ),
				$e->getMessage()
			) );

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

		$payment_data = WC()->session->set( 'apple_pay_payment_response', array() );

		$order = $this->get_processing_gateway()->add_apple_pay_order_data( $order, $payment_data );

		unset( WC()->session->apple_pay_payment_response );

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

		$order = $this->create_order( $items, $args );

		return $order;
	}


	/**
	 * Creates an order from a single product request.
	 *
	 * @since 4.6.0-dev
	 * @throws \SV_WC_Plugin_Exception
	 */
	protected function create_product_order() {

		$payment_request = $this->get_stored_payment_request();

		$items = array();

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

		$order = $this->create_order( $items );

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
	 * @param array $args the order args
	 * @throws \SV_WC_Plugin_Exception
	 */
	public function create_order( $items, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'customer_id'      => get_current_user_id(),
			'fees'             => array(),
			'packages'         => array(),
			'coupons'          => array(),
			'billing_address'  => array(),
			'shipping_address' => array(),
		) );

		try {

			wc_transaction_query( 'start' );

			$order_data = array(
				'status'      => apply_filters( 'woocommerce_default_order_status', 'pending' ),
				'customer_id' => $args['customer_id'],
				'created_via' => 'apple_pay',
			);

			$order = wc_create_order( $order_data );

			if ( is_wp_error( $order ) ) {
				throw new SV_WC_Plugin_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 520 ) );
			} elseif ( false === $order ) {
				throw new SV_WC_Plugin_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 521 ) );
			} else {

				$order_id = $order->id;

				do_action( 'woocommerce_new_order', $order_id );
			}

			$order->set_payment_method( $this->get_processing_gateway()->get_id() );

			// add line items
			foreach ( $items as $key => $item ) {

				$item_id = $order->add_product( $item['product'], $item['quantity'], $item['args'] );

				if ( ! $item_id ) {
					throw new SV_WC_Plugin_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 525 ) );
				}

				do_action( 'woocommerce_add_order_item_meta', $item_id, $item['values'], $key );
			}

			// add fees
			foreach ( $args['fees'] as $key => $fee ) {

				$item_id = $order->add_fee( $fee );

				if ( ! $item_id ) {
					throw new SV_WC_Plugin_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 526 ) );
				}

				do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $key );
			}

			// add shipping packages
			foreach ( $args['packages'] as $key => $package ) {

				$shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

				if ( isset( $package['rates'][ $shipping_methods[ $key ] ] ) ) {

					$item_id = $order->add_shipping( $package['rates'][ $shipping_methods[ $key ] ] );

					if ( ! $item_id ) {
						throw new SV_WC_Plugin_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 527 ) );
					}

					do_action( 'woocommerce_add_shipping_order_item', $order_id, $item_id, $key );
				}
			}

			// add coupons
			foreach ( $args['coupons'] as $code => $coupon ) {

				if ( ! $order->add_coupon( $code, $coupon['amount'], $coupon['tax_amount'] ) ) {
					throw new SV_WC_Plugin_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 529 ) );
				}
			}

			$order->set_address( $args['billing_address'], 'billing' );
			$order->set_address( $args['shipping_address'], 'shipping' );

			$order->calculate_totals();

			wc_transaction_query( 'commit' );

			return $order;

		} catch ( SV_WC_Plugin_Exception $e ) {

			wc_transaction_query( 'rollback' );

			throw $e;
		}
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

			require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/class-sv-wc-payment-gateway-apple-pay-api.php');
			require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/class-sv-wc-payment-gateway-apple-pay-api-request.php');
			require_once( $this->get_plugin()->get_payment_gateway_framework_path() . '/apple-pay/class-sv-wc-payment-gateway-apple-pay-api-response.php');

			$this->api = new SV_WC_Payment_Gateway_Apple_Pay_API( $this->get_processing_gateway() );
		}

		return $this->api;
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

		/**
		 * Filters whether Apple Pay should be made available to users.
		 *
		 * @since 4.6.0-dev
		 * @param bool $is_available
		 */
		return apply_filters( 'sv_wc_apple_pay_is_available', $this->is_configured() );
	}


	/**
	 * Determines if Apple Pay settings are properly configured.
	 *
	 * @since 4.6.0-dev
	 * @return bool
	 */
	protected function is_configured() {

		$is_configured = $this->is_enabled() && $this->get_merchant_id() && $this->get_processing_gateway() && $this->get_processing_gateway()->is_enabled();

		$is_configured = $is_configured && $this->get_cert_path() && is_readable( $this->get_cert_path() );

		return $is_configured;
	}


	/**
	 * Determines if Apple Pay is enabled.
	 *
	 * @since 4.6.0-dev
	 * @return bool
	 */
	protected function is_enabled() {

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

		$capabilities = array_intersect( $valid_capabilities, $this->get_processing_gateway()->get_apple_pay_capabilities() );

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

		$accepted_card_types = array_map( 'SV_WC_Payment_Gateway_Helper::normalize_card_type', $this->get_processing_gateway()->get_card_types() );

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
