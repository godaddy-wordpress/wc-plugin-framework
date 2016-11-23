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
 * Sets up the Apple Pay front-end functionality.
 *
 * @since 4.6.0-dev
 */
class SV_WC_Payment_Gateway_Apple_Pay_Frontend {


	/** @var \SV_WC_Payment_Gateway_Plugin $plugin the gateway plugin instance */
	protected $plugin;

	/** @var \SV_WC_Payment_Gateway_Apple_Pay $handler the Apple Pay handler instance */
	protected $handler;

	/** @var \SV_WC_Payment_Gateway $gateway the gateway instance */
	protected $gateway;


	/**
	 * Constructs the class.
	 *
	 * @since 4.6.0-dev
	 * @param \SV_WC_Payment_Gateway_Plugin $plugin the gateway plugin instance
	 * @param \SV_WC_Payment_Gateway_Apple_Pay $handler the Apple Pay handler instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin, SV_WC_Payment_Gateway_Apple_Pay $handler ) {

		$this->plugin = $plugin;

		$this->handler = $handler;

		$this->gateway = $this->get_handler()->get_processing_gateway();

		if ( $this->get_handler()->is_available() ) {
			add_action( 'wp', array( $this, 'init' ) );
		}

		add_action( 'wp_ajax_sv_wc_apple_pay_get_cart_payment_request',        array( $this, 'get_cart_payment_request' ) );
		add_action( 'wp_ajax_nopriv_sv_wc_apple_pay_get_cart_payment_request', array( $this, 'get_cart_payment_request' ) );

		add_action( 'wp_ajax_sv_wc_apple_pay_get_checkout_payment_request',        array( $this, 'get_checkout_payment_request' ) );
		add_action( 'wp_ajax_nopriv_sv_wc_apple_pay_get_checkout_payment_request', array( $this, 'get_checkout_payment_request' ) );
	}


	/**
	 * Initializes the scripts and hooks.
	 *
	 * @since 4.6.0-dev
	 */
	public function init() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( is_product() && 'yes' === get_option( 'sv_wc_apple_pay_single_product' ) ) {
			$this->init_product();
		} else if ( is_cart() && 'yes' === get_option( 'sv_wc_apple_pay_cart' ) ) {
			$this->init_cart();
		} else if ( is_checkout() && 'yes' === get_option( 'sv_wc_apple_pay_checkout' ) ) {
			$this->init_checkout();
		}
	}


	/**
	 * Enqueues the scripts.
	 *
	 * @since 4.6.0-dev
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'sv-wc-apple-pay', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/js/frontend/sv-wc-payment-gateway-apple-pay.min.js', array( 'jquery' ), $this->get_plugin()->get_version(), true );

		/**
		 * Filters the Apple Pay JS handler params.
		 *
		 * @since 4.6.0-dev
		 * @param array $params the JS params
		 */
		$params = apply_filters( 'sv_wc_apple_pay_js_handler_params', array(
			'gateway_id'            => $this->get_gateway()->get_id(),
			'gateway_id_dasherized' => $this->get_gateway()->get_id_dasherized(),
			'merchant_id'           => $this->get_handler()->get_merchant_id(),
			'ajax_url'              => admin_url( 'admin-ajax.php' ),
			'validate_nonce'        => wp_create_nonce( 'sv_wc_apple_pay_validate_merchant' ),
			'process_nonce'         => wp_create_nonce( 'sv_wc_apple_pay_process_payment' ),
			'generic_error'         => __( 'An error occurred, please try again or try an alternate form of payment', 'woocommerce-plugin-framework' ),
		) );

		wp_localize_script( 'sv-wc-apple-pay', 'sv_wc_apple_pay_params', $params );
	}


	/**
	 * Renders an Apple Pay button.
	 *
	 * @since 4.6.0-dev
	 */
	public function render_button() {

		?>

			<style>
				.sv-wc-apple-pay-button {
					display: none;
					background-color: black;
					background-image: -webkit-named-image(apple-pay-logo-white);
					background-size: 100% 100%;
					background-origin: content-box;
					background-repeat: no-repeat;
					width: 100%;
					height: 44px;
					margin: 0 0 1em 0;
					padding: 10px 0;
					border-radius: 5px;
				}
				.sv-wc-apple-pay-button::hover {
					background-color: black;
				}
			</style>

			<button class="sv-wc-apple-pay-button" disabled="disabled"></button>

		<?php
	}


	/**
	 * Initializes Apple Pay on the single product page.
	 *
	 * @since 4.6.0-dev
	 */
	public function init_product() {

		$product = wc_get_product( get_the_ID() );

		// simple products only, for now
		if ( ! $product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		$payment_request = $this->get_product_payment_request( $product );

		/**
		 * Filters the Apple Pay product handler args.
		 *
		 * @since 4.6.0-dev
		 * @param array $args
		 */
		$args = apply_filters( 'sv_wc_apple_pay_product_handler_args', array(
			'payment_request' => $payment_request,
		) );

		wc_enqueue_js( sprintf( 'window.sv_wc_apple_pay_handler = new SV_WC_Apple_Pay_Product_Handler(%s);', json_encode( $args ) ) );

		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_button' ) );
	}


	/**
	 * Gets a single product payment request.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Product $product the product object
	 * @return array
	 */
	public function get_product_payment_request( WC_Product $product ) {

		$line_items = array(
			$product->get_id() => array(
				'name'     => $product->get_title(),
				'quantity' => 1,
				'amount'   => $product->get_price(),
			),
		);

		$args = array(
			'shipping_required' => $product->needs_shipping(),
			'shipping_total'    => 0,
			'tax_total'         => 0,
		);

		$shipping_cost = (float) get_option( 'sv_wc_apple_pay_buy_now_shipping_cost', 0 );
		$tax_rate      = (float) get_option( 'sv_wc_apple_pay_buy_now_tax_rate', 0 );

		if ( $product->needs_shipping() && $shipping_cost ) {
			$args['shipping_total'] = $shipping_cost;
		}

		$total = array(
			'amount' => $product->get_price() + $args['shipping_total'],
		);

		if ( $tax_rate && wc_tax_enabled() ) {

			$args['tax_total'] = round( $total['amount'] * ( $tax_rate / 100 ), 2 );

			$total['amount'] += $args['tax_total'];
		}

		return $this->build_payment_request( $total, $line_items, $args );
	}


	/** Cart functionality ****************************************************/


	/**
	 * Initializes Apple Pay on the cart page.
	 *
	 * @since 4.6.0-dev
	 */
	public function init_cart() {

		if ( $this->get_plugin()->is_subscriptions_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			return;
		}

		/**
		 * Filters the Apple Pay cart handler args.
		 *
		 * @since 4.6.0-dev
		 * @param array $args
		 */
		$args = apply_filters( 'sv_wc_apple_pay_cart_handler_args', array(
			'request_action' => 'sv_wc_apple_pay_get_cart_payment_request',
			'request_nonce'  => wp_create_nonce( 'sv_wc_apple_pay_get_cart_payment_request' )
		) );

		wc_enqueue_js( sprintf( 'window.sv_wc_apple_pay_handler = new SV_WC_Apple_Pay_Cart_Handler(%s);', json_encode( $args ) ) );

		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'render_button' ) );
	}


	/**
	 * Gets a payment request for the current cart.
	 *
	 * @since 4.6.0-dev
	 */
	public function get_cart_payment_request() {

		check_ajax_referer( 'sv_wc_apple_pay_get_cart_payment_request', 'nonce' );

		try {

			$request = $this->build_cart_payment_request( WC()->cart );

			wp_send_json( array(
				'result'  => 'success',
				'request' => json_encode( $request ),
			) );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			wp_send_json( array(
				'result'  => 'error',
				'message' => $e->getMessage(),
			) );
		}
	}


	/** Checkout functionality ************************************************/


	/**
	 * Initializes Apple Pay on the checkout page.
	 *
	 * @since 4.6.0-dev
	 */
	public function init_checkout() {

		if ( $this->get_plugin()->is_subscriptions_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			return;
		}

		/**
		 * Filters the Apple Pay checkout handler args.
		 *
		 * @since 4.6.0-dev
		 * @param array $args
		 */
		$args = apply_filters( 'sv_wc_apple_pay_checkout_handler_args', array(
			'request_action' => 'sv_wc_apple_pay_get_checkout_payment_request',
			'request_nonce'  => wp_create_nonce( 'sv_wc_apple_pay_get_checkout_payment_request' )
		) );

		wc_enqueue_js( sprintf( 'window.sv_wc_apple_pay_handler = new SV_WC_Apple_Pay_Checkout_Handler(%s);', json_encode( $args ) ) );

		add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_button' ) );
	}


	/**
	 * Gets a payment request for the checkout.
	 *
	 * @since 4.6.0-dev
	 */
	public function get_checkout_payment_request() {

		check_ajax_referer( 'sv_wc_apple_pay_get_checkout_payment_request', 'nonce' );

		try {

			$request = $this->build_cart_payment_request( WC()->cart );

			wp_send_json( array(
				'result'  => 'success',
				'request' => json_encode( $request ),
			) );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			wp_send_json( array(
				'result'  => 'error',
				'message' => $e->getMessage(),
			) );
		}
	}


	/**
	 * Builds a payment request based on WC cart data.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Cart $cart the cart object
	 * @return array
	 */
	protected function build_cart_payment_request( WC_Cart $cart ) {

		// product line items
		$line_items = array();

		// set the line items
		foreach ( $cart->get_cart() as $cart_item_key => $item ) {

			$line_items[ $item['data']->get_id() ] = array(
				'name'     => $item['data']->get_title(),
				'quantity' => $item['quantity'],
				'amount'   => $item['line_subtotal'],
			);
		}

		// set any fees
		foreach ( $cart->get_fees() as $fee ) {

			$line_items[ $fee->id ] = array(
				'name'     => $fee->name,
				'quantity' => 1,
				'amount'   => $fee->amount,
			);
		}

		$args = array();

		// discount total
		if ( $cart->has_discount() ) {
			$args['discount_total'] = $cart->get_cart_discount_total();
		}

		// set shipping totals
		if ( $cart->needs_shipping() ) {

			$args['shipping_required'] = true;

			// shipping
			$shipping_packages       = WC()->shipping->get_packages();
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );

			// if shipping methods have already been chosen, simply add the total as a line item
			// otherwise, we will build the list of options to choose via the Apple Pay card.
			if ( ! empty( $chosen_shipping_methods ) ) {

				$args['shipping_total'] = $cart->shipping_total;

			} else if ( 1 === count( $shipping_packages ) ) {

				$package = current( $shipping_packages );

				foreach ( $package['rates'] as $rate ) {

					$args['shipping_methods'][] = array(
						'label'      => $rate->get_label(),
						'amount'     => $this->format_price( $rate->cost ),
						'identifier' => $rate->id,
					);
				}

			} else {

				throw new SV_WC_Payment_Gateway_Exception( __( 'No shipping totals available.', 'woocommerce-plugin-framework' ) );
			}
		}

		// tax total
		$args['tax_total'] = $cart->tax_total + $cart->shipping_tax_total;

		// order total
		$total = array(
			'amount' => $cart->total,
		);

		$this->get_gateway()->add_debug_message( 'Generating Apple Pay Payment Request' );

		// build it!
		$request = $this->build_payment_request( $total, $line_items, $args );

		/**
		 * Filters the Apple Pay cart JS payment request.
		 *
		 * @since 4.6.0-dev
		 * @param array $args the cart JS payment request
		 * @param \WC_Cart $cart the cart object
		 */
		return apply_filters( 'sv_wc_apple_pay_cart_payment_request', $request, $cart );
	}


	/**
	 * Builds an Apple Pay payment request.
	 *
	 * This contains all of the data necessary to complete a payment, including
	 * line items and shipping info.
	 *
	 * @since 4.6.0-dev
	 * @param array $total {
	 *     The payment total.
	 *
	 *     @type string $label  the total label. Defaults to the site name
	 *     @type string $amount the total payment amount
	 * }
	 * @param array $line_items {
	 *     Optional. The order line items.
	 *
	 *     @type string $name the line item name (usually a WC product title)
	 *     @type string $amount the line subtotal
	 * }
	 * @param array $args {
	 *     Optional. The payment request args.
	 *
	 *     @type string $currency_code         the payment currency code. Defaults to the shop currency.
	 *     @type string $country_code          the payment country code. Defaults to the shop base country.
	 *     @type array  $merchant_capabilities the merchant capabilities
	 *     @type array  $supported_networks    the supported networks or card types
	 * }
	 * @return array
	 */
	protected function build_payment_request( $total, $line_items = array(), $args = array() ) {

		$args = wp_parse_args( $args, array(
			'currency_code'         => get_woocommerce_currency(),
			'country_code'          => get_option( 'woocommerce_default_country' ),
			'merchant_capabilities' => $this->get_handler()->get_capabilities(),
			'supported_networks'    => $this->get_handler()->get_supported_networks(),
			'shipping_required'     => false,
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

		// if a shipping address is required
		if ( $args['shipping_required'] ) {
			$request['requiredShippingContactFields'][] = 'postalAddress';
		}

		// line items
		foreach ( $line_items as $key => $item ) {

			$label = $item['name'];

			// add the item quantity if more than one
			if ( ! empty( $item['quantity'] ) && 1 < $item['quantity'] ) {
				$label .= ' (x' . (int) $item['quantity'] . ')';
			}

			$line_items[ $key ] = array(
				'type'   => 'final',
				'label'  => $label,
				'amount' => $this->format_price( $item['amount'] ),
			);
		}

		// discounts
		if ( ! empty( $args['discount_total'] ) ) {

			$line_items[ 'discount' ] = array(
				'type'   => 'final',
				'label'  => __( 'Discount', 'woocommerce-plugin-framework' ),
				'amount' => $this->format_price( $args['discount_total'] ),
			);
		}

		// shipping
		if ( ! empty( $args['shipping_methods'] ) ) {

			$request['shippingMethods'] = $args['shipping_methods'];

		} else if ( ! empty( $args['shipping_total'] ) ) {

			$line_items['shipping'] = array(
				'type'   => 'final',
				'label'  => __( 'Shipping', 'woocommerce-plugin-framework' ),
				'amount' => $this->format_price( $args['shipping_total'] ),
			);
		}

		// taxes
		if ( ! empty( $args['tax_total'] ) ) {

			$line_items['taxes'] = array(
				'type'   => 'final',
				'label'  => __( 'Taxes', 'woocommerce-plugin-framework' ),
				'amount' => $this->format_price( $args['tax_total'] ),
			);
		}

		if ( ! empty( $line_items ) ) {
			$request['lineItems'] = $line_items;
		}

		// order total
		$request['total'] = wp_parse_args( $total, array(
			'label'  => get_bloginfo( 'name', 'display' ),
			'amount' => 0,
		) );

		$request['total']['amount'] = $this->format_price( $request['total']['amount'] );

		$this->get_handler()->store_payment_request( $request );

		if ( ! empty( $request['lineItems'] ) ) {
			$request['lineItems'] = array_values( $request['lineItems'] );
		}

		// log the payment request
		$this->get_gateway()->add_debug_message( "Apple Pay Payment Request:\n" . print_r( $request, true ) );

		return $request;
	}


	/**
	 * Formats a total price for use with Apple Pay JS.
	 *
	 * @since 4.6.0-dev
	 * @param string|float $price the price to format
	 * @return string
	 */
	protected function format_price( $price ) {

		return wc_format_decimal( $price, 2 );
	}


	/**
	 * Gets the JS handler args.
	 *
	 * @since 4.6.0-dev
	 * @return array {
	 *     The handler arguments.
	 *
	 *     @type string $gateway_id            the processing gateway's ID
	 *     @type string $gateway_id_dasherized the processing gateway's dasherized ID
	 *     @type string $merchant_id           the Apple merchant ID
	 * }
	 */
	protected function get_js_args() {

		/**
		 * Filters the Apple Pay JS handler args.
		 *
		 * @since 4.6.0-dev
		 * @param array $args the JS handler args
		 * @param \SV_WC_Payment_Gateway $gateway the processing gateway instance
		 */
		return apply_filters( 'sv_wc_apple_pay_js_args', array(), $this->get_gateway() );
	}


	/**
	 * Gets the gateway instance.
	 *
	 * @since 4.6.0-dev
	 * @return \SV_WC_Payment_Gateway
	 */
	protected function get_gateway() {

		return $this->gateway;
	}


	/**
	 * Gets the gateway plugin instance.
	 *
	 * @since 4.6.0-dev
	 * @return \SV_WC_Payment_Gateway_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}

	/**
	 * Gets the Apple Pay handler instance.
	 *
	 * @since 4.6.0-dev
	 * @return \SV_WC_Payment_Gateway_Apple_Pay
	 */
	protected function get_handler() {

		return $this->handler;
	}


}
