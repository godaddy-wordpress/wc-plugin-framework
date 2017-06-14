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

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		add_action( 'wp_ajax_sv_wc_apple_pay_get_payment_request',        array( $this, 'get_payment_request' ) );
		add_action( 'wp_ajax_nopriv_sv_wc_apple_pay_get_payment_request', array( $this, 'get_payment_request' ) );
	}


	/**
	 * Initializes the scripts and hooks.
	 *
	 * @since 4.6.0-dev
	 */
	public function init() {

		$locations = $this->get_display_locations();

		if ( is_product() && in_array( 'product', $locations, true ) ) {
			$this->init_product();
		} else if ( is_cart() && in_array( 'cart', $locations, true ) ) {
			$this->init_cart();
		} else if ( is_checkout() && in_array( 'checkout', $locations, true ) ) {
			$this->init_checkout();
		}
	}


	/**
	 * Gets the configured display locations.
	 *
	 * @since 4.6.0-dev
	 * @return array
	 */
	protected function get_display_locations() {

		return get_option( 'sv_wc_apple_pay_display_locations', array() );
	}


	/**
	 * Enqueues the scripts.
	 *
	 * @since 4.6.0-dev
	 */
	public function enqueue_scripts() {

		wp_enqueue_style( 'sv-wc-apple-pay', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/css/frontend/sv-wc-payment-gateway-apple-pay.css', array(), $this->get_plugin()->get_version() ); // TODO: min

		wp_enqueue_script( 'sv-wc-apple-pay', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/js/frontend/sv-wc-payment-gateway-apple-pay.min.js', array( 'jquery' ), $this->get_plugin()->get_version(), true );

		/**
		 * Filters the Apple Pay JS handler params.
		 *
		 * @since 4.6.0-dev
		 * @param array $params the JS params
		 */
		$params = apply_filters( 'sv_wc_apple_pay_js_handler_params', array(
			'gateway_id'                       => $this->get_gateway()->get_id(),
			'gateway_id_dasherized'            => $this->get_gateway()->get_id_dasherized(),
			'merchant_id'                      => $this->get_handler()->get_merchant_id(),
			'ajax_url'                         => admin_url( 'admin-ajax.php' ),
			'validate_nonce'                   => wp_create_nonce( 'sv_wc_apple_pay_validate_merchant' ),
			'recalculate_product_totals_nonce' => wp_create_nonce( 'sv_wc_apple_pay_recalculate_product_totals' ),
			'process_nonce'                    => wp_create_nonce( 'sv_wc_apple_pay_process_payment' ),
			'generic_error'                    => __( 'An error occurred, please try again or try an alternate form of payment', 'woocommerce-plugin-framework' ),
		) );

		wp_localize_script( 'sv-wc-apple-pay', 'sv_wc_apple_pay_params', $params );
	}


	/**
	 * Renders an Apple Pay button.
	 *
	 * @since 4.6.0-dev
	 */
	public function render_button() {

		$button_text = '';
		$classes     = array(
			'sv-wc-apple-pay-button',
		);

		switch ( $this->get_handler()->get_button_style() ) {

			case 'black':
				$classes[] = 'apple-pay-button-black';
			break;

			case 'white':
				$classes[] = 'apple-pay-button-white';
			break;

			case 'white-with-line':
				$classes[] = 'apple-pay-button-white-with-line';
			break;
		}

		// if on the single product page, add some text
		if ( is_product() ) {
			$classes[]   = 'apple-pay-button-buy-now';
			$button_text = __( 'Buy with', 'woocommerce-plugin-framework' );
		}

		if ( $button_text ) {
			$classes[] = 'apple-pay-button-with-text';
		}

		echo '<button class="' . implode( ' ', array_map( 'sanitize_html_class', $classes ) ) . '" lang="' . esc_attr( substr( get_locale(), 0, 2 ) ) . '">';

			if ( $button_text ) {
				echo '<span class="text">' . esc_html( $button_text ) . '</span><span class="logo"></span>';
			}

		echo '</button>';
	}


	/**
	 * Initializes Apple Pay on the single product page.
	 *
	 * @since 4.6.0-dev
	 */
	public function init_product() {

		$args = array();

		try {

			$product = wc_get_product( get_the_ID() );

			if ( ! $product ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Product does not exist.' );
			}

			$payment_request = $this->build_product_payment_request( $product );

			$args['payment_request'] = $payment_request;

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			return;
		}

		/**
		 * Filters the Apple Pay product handler args.
		 *
		 * @since 4.6.0-dev
		 * @param array $args
		 */
		$args = apply_filters( 'sv_wc_apple_pay_product_handler_args', $args );

		wc_enqueue_js( sprintf( 'window.sv_wc_apple_pay_handler = new SV_WC_Apple_Pay_Product_Handler(%s);', json_encode( $args ) ) );

		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_button' ) );
	}


	/**
	 * Gets a single product payment request.
	 *
	 * @since 4.6.0-dev
	 * @param \WC_Product $product the product object
	 * @return array
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function build_product_payment_request( WC_Product $product ) {

		if ( ! is_user_logged_in() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		// no subscription products
		if ( $this->get_plugin()->is_subscriptions_active() && WC_Subscriptions_Product::is_subscription( $product ) ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Not available for subscription products.' );
		}

		// no pre-order "charge upon release" products
		if ( $this->get_plugin()->is_pre_orders_active() && WC_Pre_Orders_Product::product_is_charged_upon_release( $product ) ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Not available for pre-order products that are set to charge upon release.' );
		}

		// only simple products
		if ( ! $product->is_type( 'simple' ) ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Buy Now is only available for simple products' );
		}

		// if this product can't be purchased, bail
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() || ! $product->has_enough_stock( 1 ) ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Product is not available for purchase.' );
		}

		$args = array(
			'subtotal'       => $product->get_price(),
			'needs_shipping' => $product->needs_shipping(),
		);

		$request       = $this->get_handler()->build_payment_request( $product->get_price(), $args );
		$request_store = $this->get_handler()->get_stored_payment_request();

		// add the product ID to the stored request for later use
		$request_store['product_id'] = $product->get_id();

		$this->get_handler()->store_payment_request( $request_store );

		/**
		 * Filters the Apple Pay Buy Now JS payment request.
		 *
		 * @since 4.6.0-dev
		 * @param array $request request data
		 * @param \WC_Product $product product object
		 */
		return apply_filters( 'sv_wc_apple_pay_buy_now_payment_request', $request, $product );
	}


	/** Cart functionality ****************************************************/


	/**
	 * Initializes Apple Pay on the cart page.
	 *
	 * @since 4.6.0-dev
	 */
	public function init_cart() {

		$args = array();

		try {

			$payment_request = $this->build_cart_payment_request();

			$args['payment_request'] = $payment_request;

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			$args['payment_request'] = false;
		}

		/**
		 * Filters the Apple Pay cart handler args.
		 *
		 * @since 4.6.0-dev
		 * @param array $args
		 */
		$args = apply_filters( 'sv_wc_apple_pay_cart_handler_args', $args );

		wc_enqueue_js( sprintf( 'window.sv_wc_apple_pay_handler = new SV_WC_Apple_Pay_Cart_Handler(%s);', json_encode( $args ) ) );

		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'render_button' ) );
	}


	/** Checkout functionality ************************************************/


	/**
	 * Initializes Apple Pay on the checkout page.
	 *
	 * @since 4.6.0-dev
	 */
	public function init_checkout() {

		/**
		 * Filters the Apple Pay checkout handler args.
		 *
		 * @since 4.6.0-dev
		 * @param array $args
		 */
		$args = apply_filters( 'sv_wc_apple_pay_checkout_handler_args', array() );

		wc_enqueue_js( sprintf( 'window.sv_wc_apple_pay_handler = new SV_WC_Apple_Pay_Checkout_Handler(%s);', json_encode( $args ) ) );

		add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_button' ) );
	}


	/**
	 * Builds a payment request based on WC cart data.
	 *
	 * @since 4.6.0-dev
	 * @return array
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	protected function build_cart_payment_request() {

		$cart = WC()->cart;

		// ensure totals are fully calculated
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		if ( $this->get_plugin()->is_subscriptions_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Cart contains subscriptions.' );
		}

		if ( $this->get_plugin()->is_pre_orders_active() && WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Cart contains pre-orders.' );
		}

		$cart->calculate_totals();

		$args = array(
			'subtotal'       => $cart->subtotal_ex_tax,
			'discount_total' => $cart->get_cart_discount_total(),
			'shipping_total' => $cart->shipping_total,
			'fee_total'      => $cart->fee_total,
			'tax_total'      => $cart->tax_total + $cart->shipping_tax_total,
		);

		if ( $cart->needs_shipping() ) {

			$args['needs_shipping'] = true;

			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );

			if ( empty( $chosen_shipping_methods ) ) {
				throw new SV_WC_Payment_Gateway_Exception( __( 'No shipping method chosen.', 'woocommerce-plugin-framework' ) );
			}
		}

		// build it!
		$request = $this->get_handler()->build_payment_request( $cart->total, $args );

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
	 * Gets a payment request for the specified type.
	 *
	 * @since 4.6.0-dev
	 */
	public function get_payment_request() {

		$type = SV_WC_Helper::get_post( 'type' );

		try {

			switch ( $type ) {

				case 'product':
					$request = $this->build_product_payment_request( SV_WC_Helper::get_post( 'product_id' ) );
				break;

				case 'cart':
				case 'checkout':
					$request = $this->build_cart_payment_request();
				break;

				default:
					throw new SV_WC_Payment_Gateway_Exception( 'Invalid payment request type.' );
			}

			wp_send_json( array(
				'result'  => 'success',
				'request' => json_encode( $request ),
			) );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			$this->get_handler()->log( 'Could not build payment request. ' . $e->getMessage() );

			wp_send_json( array(
				'result'  => 'error',
				'error'   => $e->getMessage(),
				'message' => __( 'Apple Pay is currently unavailable.', 'woocommerce-plugin-framework' ),
			) );
		}
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
