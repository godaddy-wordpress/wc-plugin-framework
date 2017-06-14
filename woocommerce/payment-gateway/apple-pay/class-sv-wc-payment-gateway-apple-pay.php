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

			// recalculate product totals via AJAX
			add_action( 'wp_ajax_sv_wc_apple_pay_recalculate_product_totals',        array( $this, 'recalculate_product_totals' ) );
			add_action( 'wp_ajax_nopriv_sv_wc_apple_pay_recalculate_product_totals', array( $this, 'recalculate_product_totals' ) );

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
	 * Calculates shipping & taxes for a product.
	 *
	 * This is called via AJAX to calculate product shipping & taxes for a
	 * product from its since product page using the Buy Now Apple Pay button.
	 *
	 * @since 4.7.0-dev
	 */
	public function recalculate_product_totals() {

		check_ajax_referer( 'sv_wc_apple_pay_recalculate_product_totals', 'nonce' );

		try {

			if ( $payment_request = $this->get_stored_payment_request() ) {

				if ( ! empty( $payment_request['product_id'] ) ) {

					$product = wc_get_product( $payment_request['product_id'] );

					if ( ! $product ) {
						throw new SV_WC_Payment_Gateway_Exception( 'Invalid product ID.' );
					}

				} else {

					throw new SV_WC_Payment_Gateway_Exception( 'Product ID is missing.' );
				}

			} else {

				throw new SV_WC_Payment_Gateway_Exception( 'Payment request data is missing.' );
			}

			// if a contact is passed, set the customer address data
			if ( isset( $_REQUEST['contact'] ) && is_array( $_REQUEST['contact'] ) ) {

				$contact = wp_parse_args( $_REQUEST['contact'], array(
					'administrativeArea' => null,
					'countryCode'        => null,
					'locality'           => null,
					'postalCode'         => null,
				) );

				$state    = $contact['administrativeArea'];
				$country  = strtoupper( $contact['countryCode'] );
				$city     = $contact['locality'];
				$postcode = $contact['postalCode'];

				WC()->customer->set_shipping_city( $city );
				WC()->customer->set_shipping_state( $state );
				WC()->customer->set_shipping_country( $country );
				WC()->customer->set_shipping_postcode( $postcode );

				if ( $country ) {

					if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
						WC()->customer->set_calculated_shipping( true );
					} else {
						WC()->customer->calculated_shipping( true );
					}
				}
			}

			// if a specific method ID was chosen, set it in the session
			if ( ! empty( $_REQUEST['method'] ) ) {
				WC()->session->set( 'chosen_shipping_methods', array( wc_clean( $_REQUEST['method'] ) ) );
			} else {
				WC()->session->set( 'chosen_shipping_methods', array() );
			}

			$shipping_methods = array();
			$shipping_total   = 0;

			// set shipping total & methods if needed
			if ( $product->needs_shipping() ) {

				$shipping_rates = $this->get_product_shipping_rates( $product );

				foreach ( $shipping_rates as $method ) {

					/**
					 * Filters a shipping method's description for the Apple Pay payment card.
					 *
					 * @since 4.7.0-dev
					 *
					 * @param string $detail shipping method detail, such as delivery estimation
					 * @param object $method shipping method object
					 */
					$method_detail = apply_filters( 'wc_payment_gateway_apple_pay_shipping_method_detail', '', $method );

					$shipping_methods[] = array(
						'label'      => $method->get_label(),
						'detail'     => $method_detail,
						'amount'     => $this->format_price( $method->cost ),
						'identifier' => $method->id,
					);
				}

				$shipping_total = WC()->shipping->shipping_total;
			}

			$tax_total = array_sum( WC_Tax::calc_tax( $product->get_price(), WC_Tax::get_rates( $product->get_tax_class() ) ) ) + array_sum( WC()->shipping->shipping_taxes );

			$payment_request['lineItems'] = $this->build_payment_request_lines( array(
				'subtotal' => $product->get_price(),
				'shipping' => $shipping_total,
				'taxes'    => $tax_total,
			) );

			// reset the order total based on the new line items
			$payment_request['total']['amount'] = $this->format_price( array_sum( wp_list_pluck( $payment_request['lineItems'], 'amount' ) ) );

			// update the stored payment request session with the new line items & totals
			$this->store_payment_request( $payment_request );

			wp_send_json_success( array(
				'shipping_methods' => $shipping_methods,
				'line_items'       => array_values( $payment_request['lineItems'] ),
				'total'            => $payment_request['total'],
			) );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			wp_send_json_error( array(
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			) );
		}
	}


	/**
	 * Gets the shipping method rates available for a product.
	 *
	 * This is used for Apple Pay on the product page.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param \WC_Product $product product object
	 * @return array $rates shipping method rates
	 */
	protected function get_product_shipping_rates( WC_Product $product ) {

		// build a "package" for WC_Shipping to use in calculations
		$package = array(
			'contents' => array(
				array(
					'quantity' => 1,
					'data'     => $product,
				),
			),
			'contents_cost' => $product->get_price(),
			'user'          => array(
				'ID' => get_current_user_id(),
			),
			'destination' => array(
				'country'   => WC()->customer->get_shipping_country(),
				'state'     => WC()->customer->get_shipping_state(),
				'postcode'  => WC()->customer->get_shipping_postcode(),
				'city'      => WC()->customer->get_shipping_city(),
				'address'   => WC()->customer->get_shipping_address(),
				'address_2' => WC()->customer->get_shipping_address_2(),
			),
		);

		WC()->shipping->calculate_shipping( array( $package ) );

		$packages = WC()->shipping->get_packages();

		return $packages[0]['rates'];
	}


	/**
	 * Builds a payment request for the Apple Pay JS.
	 *
	 * This contains all of the data necessary to complete a payment, including
	 * line items and shipping info.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param float|int $amount amount to be charged by Apple Pay
	 * @param array $args {
	 *     Optional. The payment request args.
	 *
	 *     @type string    $currency_code         Payment currency code. Defaults to the shop currency.
	 *     @type string    $country_code          Payment country code. Defaults to the shop base country.
	 *     @type string    $merchant_name         Merchant name. Defaults to the shop name.
	 *     @type array     $merchant_capabilities merchant capabilities
	 *     @type array     $supported_networks    supported networks or card types
	 *     @type float|int $subtotal              order subtotal
	 *     @type float|int $discount_total        discount total
	 *     @type float|int $shipping_total        shipping total
	 *     @type float|int $fee_total             fees total
	 *     @type float|int $tax_total             taxes total
	 *     @type bool      $needs_shipping        whether the payment needs shipping
	 * }
	 *
	 * @return array
	 */
	public function build_payment_request( $amount, $args = array() ) {

		$this->log( 'Building payment request.' );

		$args = wp_parse_args( $args, array(

			// transaction details
			'currency_code'         => get_woocommerce_currency(),
			'country_code'          => get_option( 'woocommerce_default_country' ),
			'merchant_name'         => get_bloginfo( 'name', 'display' ),
			'merchant_capabilities' => $this->get_capabilities(),
			'supported_networks'    => $this->get_supported_networks(),

			// totals
			'subtotal'       => 0.00,
			'discount_total' => 0.00,
			'shipping_total' => 0.00,
			'fee_total'      => 0.00,
			'tax_total'      => 0.00,

			'needs_shipping' => false,
		) );

		// set the base required defaults
		$request = array(
			'currencyCode'                  => $args['currency_code'],
			'countryCode'                   => substr( $args['country_code'], 0, 2 ),
			'merchantCapabilities'          => $args['merchant_capabilities'],
			'supportedNetworks'             => $args['supported_networks'],
			'requiredBillingContactFields'  => array( 'postalAddress' ),
			'requiredShippingContactFields' => array(
				'phone',
				'email',
				'name',
			),
		);

		$line_items = $this->build_payment_request_lines( array(
			'subtotal' => $args['subtotal'],
			'discount' => $args['discount_total'],
			'shipping' => $args['shipping_total'],
			'fees'     => $args['fee_total'],
			'taxes'    => $args['tax_total'],
		) );

		if ( ! empty( $line_items ) ) {
			$request['lineItems'] = $line_items;
		}

		// if a shipping line is present, require the full shipping address
		if ( $args['shipping_total'] > 0 || $args['needs_shipping'] ) {
			$request['requiredShippingContactFields'][] = 'postalAddress';
		}

		// order total
		$request['total'] = array(
			'type'   => 'final',
			'label'  => $args['merchant_name'],
			'amount' => $this->format_price( $amount ),
		);

		$this->store_payment_request( $request );

		// remove line item keys that are only useful for us later
		if ( ! empty( $request['lineItems'] ) ) {
			$request['lineItems'] = array_values( $request['lineItems'] );
		}

		// log the payment request
		$this->log( "Payment Request:\n" . print_r( $request, true ) );

		return $request;
	}


	/**
	 * Builds payment request lines for the Apple Pay JS.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param array $totals {
	 *     Payment line totals.
	 *
	 *     @type float $subtotal items subtotal
	 *     @type float $discount discounts total
	 *     @type float $shipping shipping total
	 *     @type float $fees     fees total
	 *     @type float $taxes    tax total
	 * }
	 */
	public function build_payment_request_lines( $totals ) {

		$totals = wp_parse_args( $totals, array(
			'subtotal' => 0.00,
			'discount' => 0.00,
			'shipping' => 0.00,
			'fees'     => 0.00,
			'taxes'    => 0.00,
		) );

		$lines = array();

		// subtotal
		if ( $totals['subtotal'] > 0 ) {

			$lines['subtotal'] = array(
				'type'   => 'final',
				'label'  => __( 'Subtotal', 'woocommerce-plugin-framework' ),
				'amount' => $this->format_price( $totals['subtotal'] ),
			);
		}

		// discounts
		if ( $totals['discount'] > 0 ) {

			$lines['discount'] = array(
				'type'   => 'final',
				'label'  => __( 'Discount', 'woocommerce-plugin-framework' ),
				'amount' => abs( $this->format_price( $totals['discount'] ) ) * -1,
			);
		}

		// shipping
		if ( $totals['shipping'] > 0 ) {

			$lines['shipping'] = array(
				'type'   => 'final',
				'label'  => __( 'Shipping', 'woocommerce-plugin-framework' ),
				'amount' => $this->format_price( $totals['shipping'] ),
			);
		}

		// fees
		if ( $totals['fees'] > 0 ) {

			$lines['fees'] = array(
				'type'   => 'final',
				'label'  => __( 'Fees', 'woocommerce-plugin-framework' ),
				'amount' => $this->format_price( $totals['fees'] ),
			);
		}

		// taxes
		if ( $totals['taxes'] > 0 ) {

			$lines['taxes'] = array(
				'type'   => 'final',
				'label'  => __( 'Taxes', 'woocommerce-plugin-framework' ),
				'amount' => $this->format_price( $totals['taxes'] ),
			);
		}

		return $lines;
	}


	/**
	 * Formats a total price for use with Apple Pay JS.
	 *
	 * @since 4.7.0-dev
	 * @param string|float $price the price to format
	 * @return string
	 */
	protected function format_price( $price ) {

		return wc_format_decimal( $price, 2 );
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

			// store the payment response JSON for later use
			WC()->session->set( 'apple_pay_payment_response', $response );

			$response = new SV_WC_Payment_Gateway_Apple_Pay_Payment_Response( $response );

			// log the payment response
			$this->log( "Payment Response:\n" . $response->to_string_safe() . "\n" );

			// pretend this is at checkout so totals are fully calculated
			if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
				define( 'WOOCOMMERCE_CHECKOUT', true );
			}

			$order = null;

			// create a new order
			switch ( $type ) {

				case 'product':
					$order = $this->create_product_order();
				break;

				case 'cart':
				case 'checkout':
					$order = $this->create_cart_order();
				break;

				default:
					throw new SV_WC_Payment_Gateway_Exception( 'Invalid payment type recieved' );
			}

			// if we got to this point, the payment was authorized by Apple Pay
			// from here on out, it's up to the gateway to not screw things up.
			$order->add_order_note( __( 'Apple Pay payment authorized.', 'woocommerce-plugin-framework' ) );

			$order->set_address( $response->get_billing_address(),  'billing' );
			$order->set_address( $response->get_shipping_address(), 'shipping' );

			// save the order data before payment for WC 3.0+
			if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
				$order->save();
			}

			// process the payment via the gateway
			$result = $this->get_processing_gateway()->process_payment( SV_WC_Order_Compatibility::get_prop( $order, 'id' ) );

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
			'coupons'          => array(),
			'shipping_methods' => array(),
			'fees'             => WC()->cart->get_fees(),
		);

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) {

			$args['coupons'][ $code ] = array(
				'amount'     => WC()->cart->get_coupon_discount_amount( $code ),
				'tax_amount' => WC()->cart->get_coupon_discount_tax_amount( $code ),
			);
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );

		foreach ( WC()->shipping->get_packages() as $key => $package ) {

			if ( isset( $package['rates'][ $chosen_methods[ $key ] ] ) ) {

				$method = $package['rates'][ $chosen_methods[ $key ] ];

				$args['shipping_methods'][ $method->id ] = $method;
			}
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

		if ( empty( $payment_request['product_id'] ) ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Product ID is missing.' );
		}

		$items = array();
		$args  = array();

		$product = wc_get_product( $payment_request['product_id'] );

		if ( ! $product ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Invalid product ID.' );
		}

		if ( ! $product->is_in_stock() || ! $product->has_enough_stock( 1 ) ) {
			throw new SV_WC_Payment_Gateway_Exception( __( 'The product is out of stock.', 'woocommerce-plugin-framework' ) );
		}

		$items[] = array(
			'product'  => $product,
			'quantity' => 1,
			'args'     => array(),
			'values'   => array(),
		);

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
	 *     @type array  $coupons     Any coupons to add to the order. Arrays as
	 *                               `$code => array( $amount => 0.00, $tax_amount => 0.00 )`
	 *     @type array  $shipping_methods Any shipping methods to add to the order. As formatted by
	 *                              `WC()->shipping->get_packages()`
	 *     @type array  $fees        Any fees to add to the order. See `WC_Abstract_Order::add_fee()` for
	 *                               required values.
	 *     @type string $cart_hash   The hashed cart object to be used later in case the order is to be resumed.
	 *
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function create_order( $items, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'customer_id'      => get_current_user_id(),
			'coupons'          => array(),
			'shipping_methods' => array(),
			'fees'             => array(),
			'cart_hash'        => '',
		) );

		try {

			wc_transaction_query( 'start' );

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

				if ( ! $order->add_product( $item['product'], $item['quantity'], $item['args'] ) ) {
					throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 525 ) );
				}
			}

			// add coupons
			foreach ( $args['coupons'] as $code => $coupon ) {

				if ( ! SV_WC_Order_Compatibility::add_coupon( $order, $code, $coupon['amount'], $coupon['tax_amount'] ) ) {
					throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 529 ) );
				}
			}

			// add shipping methods
			foreach ( $args['shipping_methods'] as $method_id => $method ) {

				if ( ! SV_WC_Order_Compatibility::add_shipping( $order, $method ) ) {
					throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 527 ) );
				}
			}

			// add fees
			foreach ( $args['fees'] as $key => $fee ) {

				if ( ! SV_WC_Order_Compatibility::add_fee( $order, $fee ) ) {
					throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 526 ) );
				}
			}

			$order->calculate_totals();

			wc_transaction_query( 'commit' );

			return $order;

		} catch ( Exception $e ) {

			wc_transaction_query( 'rollback' );

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
			}

		} else {

			$order = wc_create_order( $order_data );

			if ( is_wp_error( $order ) ) {
				throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 520 ) );
			} elseif ( false === $order ) {
				throw new SV_WC_Payment_Gateway_Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-plugin-framework' ), 521 ) );
			}

			// set the new order ID so it can be resumed in case of failure
			WC()->session->set( 'order_awaiting_payment', SV_WC_Order_Compatibility::get_prop( $order, 'id' ) );
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
	 * Gets the Apple Pay button style.
	 *
	 * @since 4.6.0-dev
	 * @return string
	 */
	public function get_button_style() {

		return get_option( 'sv_wc_apple_pay_button_style', 'black' );
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
