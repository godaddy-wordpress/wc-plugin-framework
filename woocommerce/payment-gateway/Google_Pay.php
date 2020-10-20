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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Google-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Google_Pay\Admin;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Google_Pay\AJAX;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Google_Pay\Frontend;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Google_Pay\Orders;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\Google_Pay' ) ) :


/**
 * Sets up Google Pay support.
 *
 * @since 5.10.0
 */
class Google_Pay {


	/** @var Admin the admin instance */
	protected $admin;

	/** @var Frontend the frontend instance */
	protected $frontend;

	/** @var AJAX the AJAX instance */
	protected $ajax;

	/** @var SV_WC_Payment_Gateway_Plugin the plugin instance */
	protected $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 5.10.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the plugin instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->init();
	}


	/**
	 * Initializes the Google Pay handlers.
	 *
	 * @since 5.10.0
	 */
	protected function init() {

		if ( is_admin() && ! is_ajax() ) {
			$this->init_admin();
		} else {
			$this->init_ajax();
			$this->init_frontend();
		}
	}


	/**
	 * Initializes the admin handler.
	 *
	 * @since 5.10.0
	 */
	protected function init_admin() {

		$this->admin = new Admin( $this );
	}


	/**
	 * Initializes the AJAX handler.
	 *
	 * @since 5.10.0
	 */
	protected function init_ajax() {

		$this->ajax = new AJAX( $this );
	}


	/**
	 * Initializes the frontend handler.
	 *
	 * @since 5.10.0
	 */
	protected function init_frontend() {

		$this->frontend = new Frontend( $this->get_plugin(), $this );
	}


	/**
	 * Gets Google transaction info based on WooCommerce cart data.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @param string $product_id product ID, if we are on a Product page
	 * @return array
	 * @throws SV_WC_Payment_Gateway_Exception
	 */
	public function get_transaction_info( \WC_Cart $cart, $product_id ) {

		if ( $this->get_plugin()->is_subscriptions_active() && \WC_Subscriptions_Cart::cart_contains_subscription() ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Cart contains subscriptions.' );
		}

		if ( $this->get_plugin()->is_pre_orders_active() && \WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Cart contains pre-orders.' );
		}

		$cart->calculate_totals();

		if ( count( WC()->shipping->get_packages() ) > 1 ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Google Pay cannot be used for multiple shipments.' );
		}

		if ( ! empty( $cart->get_cart_contents_count() ) ) {

			$transaction_info = [
				'displayItems'     => $this->build_display_items( $cart ),
				'countryCode'      => substr( get_option( 'woocommerce_default_country' ), 0, 2 ),
				'currencyCode'     => get_woocommerce_currency(),
				'totalPriceStatus' => "FINAL",
				'totalPrice'       => wc_format_decimal( $cart->total, 2 ),
				'totalPriceLabel'  => "Total",
			];

		} elseif ( ! empty( $product_id ) && $product = wc_get_product( $product_id ) ) {

			// buying from the product page
			$transaction_info = [
				'displayItems'     => [
					[
						'label' => __( 'Subtotal', 'woocommerce-plugin-framework' ),
						'type'  => 'SUBTOTAL',
						'price' => wc_format_decimal( $product->get_price(), 2 ),
					],
				],
				'countryCode'      => substr( get_option( 'woocommerce_default_country' ), 0, 2 ),
				'currencyCode'     => get_woocommerce_currency(),
				'totalPriceStatus' => "FINAL",
				'totalPrice'       => wc_format_decimal( $product->get_price(), 2 ),
				'totalPriceLabel'  => "Total",
			];
		}

		/**
		 * Filters the Google Pay cart JS transaction info.
		 *
		 * @since 5.10.0
		 *
		 * @param array $transaction_info the cart JS transaction info
		 * @param \WC_Cart $cart the cart object
		 */
		return apply_filters( 'sv_wc_google_pay_cart_transaction_info', $transaction_info, $cart );
	}


	/**
	 * Recalculates the lines and totals after selecting an address or shipping method.
	 *
	 * @since 5.10.0
	 *
	 * @param string $chosen_shipping_method chosen shipping method
	 * @param string $product_id product ID, if we are on a Product page
	 * @return array
	 * @throws \Exception
	 */
	public function recalculate_totals( $chosen_shipping_method, $product_id ) {

		if ( ! WC()->cart ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Cart data is missing.' );
		}

		// if this is a single product page, make sure the cart gets populated
		if ( ! empty( $product_id ) && $product = wc_get_product( $product_id ) ) {

			if ( ! is_user_logged_in() ) {
				WC()->session->set_customer_session_cookie( true );
			}

			// no subscription products
			if ( $this->get_plugin()->is_subscriptions_active() && \WC_Subscriptions_Product::is_subscription( $product ) ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Not available for subscription products.' );
			}

			// no pre-order "charge upon release" products
			if ( $this->get_plugin()->is_pre_orders_active() && \WC_Pre_Orders_Product::product_is_charged_upon_release( $product ) ) {
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

			WC()->cart->empty_cart();

			WC()->cart->add_to_cart( $product->get_id() );
		}

		$response_data = [
			'newTransactionInfo'          => $this->get_transaction_info( WC()->cart, $product_id ),
			'newShippingOptionParameters' => [],
		];

		$shipping_options = [];
		$packages         = WC()->shipping->get_packages();

		if ( ! empty( $packages ) ) {

			/** @var \WC_Shipping_Rate $method */
			foreach ( $packages[0]['rates'] as $method ) {

				/**
				 * Filters a shipping method's description for the Google Pay payment.
				 *
				 * @since 5.10.0
				 *
				 * @param string $description shipping method description, such as delivery estimation
				 * @param object $method shipping method object
				 */
				$method_description = apply_filters( 'wc_payment_gateway_google_pay_shipping_method_description', '', $method );

				$shipping_options[] = [
					'id'          => $method->get_id(),
					'label'       => $method->get_label(),
					'description' => $method_description,
				];
			}
		}

		$response_data['newShippingOptionParameters']['shippingOptions'] = $shipping_options;

		if ( ! empty( $chosen_shipping_method ) ) {
			$response_data['newShippingOptionParameters']['defaultSelectedOptionId'] = $chosen_shipping_method;
		} elseif ( ! empty( $shipping_options ) ) {
			// set the first method as the default
			$response_data['newShippingOptionParameters']['defaultSelectedOptionId'] = $shipping_options[0]['id'];
		}

		return $response_data;
	}


	/**
	 * Builds display items for the Google Pay JS.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Cart $cart
	 * @return array
	 */
	public function build_display_items( \WC_Cart $cart ) {

		$subtotal = $cart->subtotal_ex_tax;
		$discount = $cart->get_cart_discount_total();
		$shipping = $cart->shipping_total;
		$fees     = $cart->fee_total;
		$taxes    = $cart->tax_total + $cart->shipping_tax_total;

		$items = [];

		// subtotal
		if ( $subtotal > 0 ) {

			$items[] = [
				'label' => __( 'Subtotal', 'woocommerce-plugin-framework' ),
				'type'  => 'SUBTOTAL',
				'price' => wc_format_decimal( $subtotal, 2 ),
			];
		}

		// discounts
		if ( $discount > 0 ) {

			$items[] = [
				'label' => __( 'Discount', 'woocommerce-plugin-framework' ),
				'type'  => 'LINE_ITEM',
				'price' => abs( wc_format_decimal( $discount, 2 ) ) * - 1,
			];
		}

		// shipping
		if ( $shipping > 0 ) {

			$items[] = [
				'label' => __( 'Shipping', 'woocommerce-plugin-framework' ),
				'type'  => 'LINE_ITEM',
				'price' => wc_format_decimal( $shipping, 2 ),
			];
		}

		// fees
		if ( $fees > 0 ) {

			$items[] = [
				'label' => __( 'Fees', 'woocommerce-plugin-framework' ),
				'type'  => 'LINE_ITEM',
				'price' => wc_format_decimal( $fees, 2 ),
			];
		}

		// taxes
		if ( $taxes > 0 ) {

			$items[] = [
				'label' => __( 'Taxes', 'woocommerce-plugin-framework' ),
				'type'  => 'TAX',
				'price' => wc_format_decimal( $taxes, 2 ),
			];
		}

		return $items;
	}


	/**
	 * Processes the payment after a Google Pay authorization.
	 *
	 *
	 * This method creates a new order and calls the gateway for processing.
	 *
	 * @since 5.10.0
	 *
	 * @param mixed $payment_data payment data returned by Google Pay
	 * @return array
	 * @throws \Exception
	 */
	public function process_payment( $payment_data ) {

		$order = null;

		try {

			$this->log( "Payment Method Response:\n" . $payment_data . "\n" );

			$payment_data = json_decode( $payment_data );

			$this->store_payment_response( $payment_data );

			$order = Orders::create_order( WC()->cart );

			$order->set_payment_method( $this->get_processing_gateway() );

			// if we got to this point, the payment was authorized by Google Pay
			// from here on out, it's up to the gateway to not screw things up.
			$order->add_order_note( __( 'Google Pay payment authorized.', 'woocommerce-plugin-framework' ) );

			if ( ! empty( $address_data = $payment_data->shippingAddress ) ) {

				if ( ! empty( $address_data->name ) ) {
					$first_name = strstr( $address_data->name, ' ', true );
					$last_name  = strstr( $address_data->name, ' ' );
				}

				$address = [
					'first_name' => isset( $first_name ) ? $first_name : '',
					'last_name'  => isset( $last_name ) ? $last_name : '',
					'address_1'  => isset( $address_data->address1 ) ? $address_data->address1 : '',
					'address_2'  => isset( $address_data->address2 ) ? $address_data->address2 : '',
					'city'       => isset( $address_data->locality ) ? $address_data->locality : '',
					'state'      => isset( $address_data->administrativeArea ) ? $address_data->administrativeArea : '',
					'postcode'   => isset( $address_data->postalCode ) ? $address_data->postalCode : '',
					'country'    => isset( $address_data->countryCode ) ? $address_data->countryCode : '',
				];

				$order->set_address( $address, 'billing' );
				$order->set_address( $address, 'shipping' );

				$order->set_billing_phone( isset( $address_data->phoneNumber ) ? $address_data->phoneNumber : '' );
				$order->set_billing_email( isset( $payment_data->email ) ? $payment_data->email : '' );
			}

			$order->save();

			// add Google Pay response data to the order
			add_filter( 'wc_payment_gateway_' . $this->get_processing_gateway()->get_id() . '_get_order', [ $this, 'add_order_data' ] );

			if ( $this->is_test_mode() ) {
				$result = $this->process_test_payment( $order );
			} else {
				$result = $this->get_processing_gateway()->process_payment( $order->get_id() );
			}

			if ( ! isset( $result['result'] ) || 'success' !== $result['result'] ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Gateway processing error.' );
			}

			return $result;

		} catch ( \Exception $e ) {

			if ( $order ) {

				$order->add_order_note( sprintf(
					/** translators: Placeholders: %s - the error message */
					__( 'Google Pay payment failed. %s', 'woocommerce-plugin-framework' ),
					$e->getMessage()
				) );
			}

			throw $e;
		}
	}


	/**
	 * Gets the stored payment response data.
	 *
	 * @since 5.10.0
	 *
	 * @return mixed|array $data
	 */
	public function get_stored_payment_response() {

		return WC()->session->get( 'google_pay_payment_response', [] );
	}


	/**
	 * Stores payment response data for later use.
	 *
	 * @since 5.10.0
	 *
	 * @param mixed|array $data
	 */
	public function store_payment_response( $data ) {

		WC()->session->set( 'google_pay_payment_response', $data );
	}


	/**
	 * Allows the processing gateway to add Google Pay details to the payment data.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Order $order the order object
	 * @return \WC_Order
	 */
	public function add_order_data( $order ) {

		if ( $response = $this->get_stored_payment_response() ) {

			$order = $this->get_processing_gateway()->get_order_for_google_pay( $order, $response );
		}

		return $order;
	}


	/**
	 * Adds a log entry to the gateway's debug log.
	 *
	 * @since 5.10.0
	 *
	 * @param string $message the log message to add
	 */
	public function log( $message ) {

		$gateway = $this->get_processing_gateway();

		if ( ! $gateway ) {
			return;
		}

		if ( $gateway->debug_log() ) {
			$gateway->get_plugin()->log( '[Google Pay] ' . $message, $gateway->get_id() );
		}
	}


	/**
	 * Simulates a successful gateway payment response.
	 *
	 * This provides an easy way for merchants to test that their settings are correctly configured and communicating
	 * with Google without processing actual payments to test.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Order $order order object
	 * @return array
	 */
	protected function process_test_payment( \WC_Order $order ) {

		$order->payment_complete();

		WC()->cart->empty_cart();

		return [
			'result'   => 'success',
			'redirect' => $this->get_processing_gateway()->get_return_url( $order ),
		];
	}


	/**
	 * Gets a single product payment request.
	 *
	 * @since 5.10.0
	 * @see SV_WC_Payment_Gateway_Apple_Pay::build_payment_request()
	 *
	 * @param \WC_Product $product product object
	 * @param bool $in_cart whether to generate a cart for this request
	 * @return array
	 * @throws \Exception
	 */
	public function get_product_payment_request( \WC_Product $product, $in_cart = false ) {

		if ( ! is_user_logged_in() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		// no subscription products
		if ( $this->get_plugin()->is_subscriptions_active() && \WC_Subscriptions_Product::is_subscription( $product ) ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Not available for subscription products.' );
		}

		// no pre-order "charge upon release" products
		if ( $this->get_plugin()->is_pre_orders_active() && \WC_Pre_Orders_Product::product_is_charged_upon_release( $product ) ) {
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

		if ( $in_cart ) {

			WC()->cart->empty_cart();

			WC()->cart->add_to_cart( $product->get_id() );

			$request = $this->get_cart_payment_request( WC()->cart );

		} else {

			$request = $this->build_payment_request( $product->get_price(), [ 'needs_shipping' => $product->needs_shipping() ] );

			$stored_request = $this->get_stored_payment_request();

			$stored_request['product_id'] = $product->get_id();

			$this->store_payment_request( $stored_request );
		}

		/**
		 * Filters the Apple Pay Buy Now JS payment request.
		 *
		 * @since 4.7.0
		 * @param array $request request data
		 * @param \WC_Product $product product object
		 */
		return apply_filters( 'sv_wc_apple_pay_buy_now_payment_request', $request, $product );
	}


	/**
	 * Determines if Google Pay is available.
	 *
	 * This does not indicate browser support or a user's ability, but rather
	 * that Google Pay is properly configured and ready to be initiated by the
	 * Google Pay JS.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_available() {

		$is_available = $this->is_configured();

		$accepted_currencies = $this->get_accepted_currencies();

		if ( ! empty( $accepted_currencies ) ) {

			$is_available = $is_available && in_array( get_woocommerce_currency(), $accepted_currencies, true );
		}

		/**
		 * Filters whether Google Pay should be made available to users.
		 *
		 * @since 5.10.0
		 * @param bool $is_available
		 */
		return apply_filters( 'sv_wc_google_pay_is_available', $is_available );
	}


	/**
	 * Determines if Google Pay settings are properly configured.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_configured() {

		if ( ! $this->get_processing_gateway() ) {
			return false;
		}

		return $this->is_enabled() && $this->get_processing_gateway()->is_enabled();
	}


	/**
	 * Determines if Google Pay is enabled.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return 'yes' === get_option( 'sv_wc_google_pay_enabled' );
	}


	/**
	 * Determines if test mode is enabled.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_test_mode() {

		return 'yes' === get_option( 'sv_wc_google_pay_test_mode' );
	}


	/**
	 * Gets the currencies supported by the gateway and available for shipping.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	public function get_available_countries() {

		$gateway_available_countries = $this->get_processing_gateway()->get_available_countries();
		$shipping_countries          = array_keys( WC()->countries->get_shipping_countries() );

		return ! empty( $gateway_available_countries ) ? array_intersect( $gateway_available_countries, $shipping_countries ) : $shipping_countries;
	}


	/**
	 * Gets the currencies accepted by the gateway's Google Pay integration.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	public function get_accepted_currencies() {

		$currencies = ( $this->get_processing_gateway() ) ? $this->get_processing_gateway()->get_google_pay_currencies() : [];

		/**
		 * Filters the currencies accepted by the gateway's Google Pay integration.
		 *
		 * @since 5.10.0
		 * @return array
		 */
		return apply_filters( 'sv_wc_google_pay_accepted_currencies', $currencies );
	}


	/**
	 * Gets the supported networks for Google Pay.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	public function get_supported_networks() {

		$accepted_card_types = ( $this->get_processing_gateway() ) ? $this->get_processing_gateway()->get_card_types() : [];

		$accepted_card_types = array_map( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_Helper::normalize_card_type', $accepted_card_types );

		$valid_networks = [
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_AMEX       => 'AMEX',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_DISCOVER   => 'DISCOVER',
			'interac'                                          => 'INTERAC',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_JCB        => 'JCB',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD => 'MASTERCARD',
			SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA       => 'VISA',
		];

		$networks = array_intersect_key( $valid_networks, array_flip( $accepted_card_types ) );

		/**
		 * Filters the supported Google Pay networks (card types).
		 *
		 * @since 5.10.0
		 *
		 * @param array $networks the supported networks
		 * @param \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Google_Pay $handler the Google Pay handler
		 */
		return apply_filters( 'sv_wc_google_pay_supported_networks', array_values( $networks ), $this );
	}


	/**
	 * Gets the gateways that declare Google Pay support.
	 *
	 * @since 5.10.0
	 *
	 * @return array the supporting gateways as `$gateway_id => \SV_WC_Payment_Gateway`
	 */
	public function get_supporting_gateways() {

		$available_gateways  = $this->get_plugin()->get_gateways();
		$supporting_gateways = [];

		foreach ( $available_gateways as $key => $gateway ) {

			if ( $gateway->supports_google_pay() ) {
				$supporting_gateways[ $gateway->get_id() ] = $gateway;
			}
		}

		return $supporting_gateways;
	}


	/**
	 * Gets the gateway set to process Google Pay transactions.
	 *
	 * @since 5.10.0
	 *
	 * @return SV_WC_Payment_Gateway|null
	 */
	public function get_processing_gateway() {

		$gateways = $this->get_supporting_gateways();

		$gateway_id = get_option( 'sv_wc_google_pay_payment_gateway' );

		return isset( $gateways[ $gateway_id ] ) ? $gateways[ $gateway_id ] : null;
	}


	/**
	 * Gets the Google Pay button style.
	 *
	 * @since 5.10.0
	 *
	 * @return string
	 */
	public function get_button_style() {

		return get_option( 'sv_wc_google_pay_button_style', 'black' );
	}


	/**
	 * Gets the gateway plugin instance.
	 *
	 * @since 5.10.0
	 *
	 * @return SV_WC_Payment_Gateway_Plugin
	 */
	public function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Gets the gateway merchant ID.
	 *
	 * Each plugin can override this method to get the merchant ID from their own setting.
	 *
	 * @since 5.10.0
	 *
	 * @return string
	 */
	public function get_merchant_id() {

		return method_exists( $this->get_gateway(), 'get_merchant_id' ) ? $this->get_gateway()->get_merchant_id() : '';
	}


}


endif;
