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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/External_Checkout/Google-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Google_Pay;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\External_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Orders;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\External_Checkout\\Google_Pay\\Google_Pay' ) ) :


/**
 * Sets up Google Pay support.
 *
 * @since 5.10.0
 */
class Google_Pay extends External_Checkout {


	/** @var Admin the admin instance */
	protected $admin;

	/** @var Frontend the frontend instance */
	protected $frontend;

	/** @var AJAX the AJAX instance */
	protected $ajax;


	/**
	 * Constructs the class.
	 *
	 * @since 5.10.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the plugin instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->id    = 'google_pay';
		$this->label = __( 'Google Pay', 'woocommerce-plugin-framework' );

		parent::__construct( $plugin );

		if ( $this->is_available() ) {
			add_filter( 'woocommerce_customer_taxable_address', [ $this, 'set_customer_taxable_address' ] );
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
	 * Checks if the external checkout provides the customer billing address to WC before payment confirmation.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function is_billing_address_available_before_payment() {

		// Google Pay does not provide billing information until the payment is confirmed
		return false;
	}


	/**
	 * Gets Google transaction info based on WooCommerce cart or product data.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @param string $product_id product ID, if we are on a Product page
	 * @return array
	 * @throws SV_WC_Payment_Gateway_Exception
	 */
	public function get_transaction_info( \WC_Cart $cart, $product_id = '' ) {

		if ( ! empty( $product_id ) && $product = wc_get_product( $product_id ) ) {
			// buying from the product page
			$transaction_info = $this->get_product_transaction_info( $product );
		} else {
			$transaction_info = $this->get_cart_transaction_info( $cart );
		}

		/**
		 * Filters the Google Pay JS transaction info.
		 *
		 * @since 5.10.0
		 *
		 * @param array $transaction_info the JS transaction info
		 * @param \WC_Cart $cart the cart object
		 * @param \WC_Product|false $product the product object, if buying from the product page
		 */
		return apply_filters( 'sv_wc_google_pay_cart_transaction_info', $transaction_info, $cart, ! empty( $product ) ? $product : false );
	}


	/**
	 * Checks if all products in the cart can be purchased using Google Pay.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @throws SV_WC_Payment_Gateway_Exception
	 */
	public function validate_cart( \WC_Cart $cart ) {

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
	}


	/**
	 * Checks if a single product can be purchased using Google Pay.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Product $product product object
	 * @throws SV_WC_Payment_Gateway_Exception
	 */
	public function validate_product( \WC_Product $product ) {

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
	}


	/**
	 * Gets Google transaction info based on WooCommerce cart data.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @return array
	 * @throws SV_WC_Payment_Gateway_Exception
	 */
	public function get_cart_transaction_info( \WC_Cart $cart ) {

		$this->validate_cart( $cart );

		return [
			'displayItems'     => $this->build_display_items( $cart ),
			'countryCode'      => substr( get_option( 'woocommerce_default_country' ), 0, 2 ),
			'currencyCode'     => get_woocommerce_currency(),
			'totalPriceStatus' => "FINAL",
			'totalPrice'       => wc_format_decimal( $cart->total, 2 ),
			'totalPriceLabel'  => "Total",
		];
	}


	/**
	 * Gets Google transaction info based on product data.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Product $product product object
	 * @return array
	 * @throws SV_WC_Payment_Gateway_Exception
	 */
	public function get_product_transaction_info( \WC_Product $product ) {

		$this->validate_product( $product );

		$price = wc_format_decimal( wc_get_price_including_tax( $product ), 2 );

		return [
			'displayItems'     => [
				[
					'label' => __( 'Subtotal', 'woocommerce-plugin-framework' ),
					'type'  => 'SUBTOTAL',
					'price' => $price,
				],
			],
			'countryCode'      => substr( get_option( 'woocommerce_default_country' ), 0, 2 ),
			'currencyCode'     => get_woocommerce_currency(),
			'totalPriceStatus' => "FINAL",
			'totalPrice'       => $price,
			'totalPriceLabel'  => "Total",
		];
	}


	/**
	 * Populates cart with a single product.
	 *
	 * @since 5.10.0
	 *
	 * @param string $product_id product ID, if we are on a Product page
	 * @throws \Exception
	 */
	public function add_product_to_cart( $product_id ) {

		if ( ! empty( $product_id ) && $product = wc_get_product( $product_id ) ) {

			if ( ! is_user_logged_in() ) {
				WC()->session->set_customer_session_cookie( true );
			}

			$this->validate_product( $product );

			WC()->cart->empty_cart();

			WC()->cart->add_to_cart( $product->get_id() );
		}
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

		// if this is a single product page, make sure the cart gets populated
		$this->add_product_to_cart( $product_id );

		if ( ! WC()->cart ) {
			throw new SV_WC_Payment_Gateway_Exception( 'Cart data is missing.' );
		}

		$response_data = [
			// we do not pass the product ID here because we want to get the totals from the cart (including tax and shipping)
			'newTransactionInfo'          => $this->get_transaction_info( WC()->cart ),
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
	 * This method creates a new order and calls the gateway for processing.
	 *
	 * @since 5.10.0
	 *
	 * @param mixed $payment_data payment data returned by Google Pay
	 * @param string $product_id product ID, if we are on a Product page
	 * @return array
	 * @throws \Exception
	 */
	public function process_payment( $payment_data, $product_id ) {

		$order = null;

		try {

			$this->log( "Payment Method Response:\n" . $payment_data . "\n" );

			$payment_data = json_decode( $payment_data, true );

			$this->store_payment_response( $payment_data );

			// if this is a single product page, make sure the cart gets populated
			$this->add_product_to_cart( $product_id );

			$order = Orders::create_order( WC()->cart, [ 'created_via' => 'google_pay' ] );

			$order->set_payment_method( $this->get_processing_gateway() );

			// if we got to this point, the payment was authorized by Google Pay
			// from here on out, it's up to the gateway to not screw things up.
			$order->add_order_note( __( 'Google Pay payment authorized.', 'woocommerce-plugin-framework' ) );

			if ( ! empty( $payment_data['paymentMethodData']['info']['billingAddress'] ) ) {

				$billing_address_data = $payment_data['paymentMethodData']['info']['billingAddress'];

				if ( ! empty( $billing_address_data['name'] ) ) {
					$first_name = strstr( $billing_address_data['name'], ' ', true );
					$last_name  = strstr( $billing_address_data['name'], ' ' );
				}

				$billing_address = [
					'first_name' => isset( $first_name ) ? $first_name : '',
					'last_name'  => isset( $last_name ) ? $last_name : '',
					'address_1'  => isset( $billing_address_data['address1'] ) ? $billing_address_data['address1'] : '',
					'address_2'  => isset( $billing_address_data['address2'] ) ? $billing_address_data['address2'] : '',
					'city'       => isset( $billing_address_data['locality'] ) ? $billing_address_data['locality'] : '',
					'state'      => isset( $billing_address_data['administrativeArea'] ) ? $billing_address_data['administrativeArea'] : '',
					'postcode'   => isset( $billing_address_data['postalCode'] ) ? $billing_address_data['postalCode'] : '',
					'country'    => isset( $billing_address_data['countryCode'] ) ? $billing_address_data['countryCode'] : '',
				];

				$order->set_address( $billing_address, 'billing' );

				$order->set_billing_phone( isset( $billing_address_data['phoneNumber'] ) ? $billing_address_data['phoneNumber'] : '' );
			}

			$order->set_billing_email( isset( $payment_data['email'] ) ? $payment_data['email'] : '' );

			if ( ! empty( $payment_data['shippingAddress'] ) ) {

				$shipping_address_data = $payment_data['shippingAddress'];

				if ( ! empty( $shipping_address_data['name'] ) ) {
					$first_name = strstr( $shipping_address_data['name'], ' ', true );
					$last_name  = strstr( $shipping_address_data['name'], ' ' );
				}

				$shipping_address = [
					'first_name' => isset( $first_name ) ? $first_name : '',
					'last_name'  => isset( $last_name ) ? $last_name : '',
					'address_1'  => isset( $shipping_address_data['address1'] ) ? $shipping_address_data['address1'] : '',
					'address_2'  => isset( $shipping_address_data['address2'] ) ? $shipping_address_data['address2'] : '',
					'city'       => isset( $shipping_address_data['locality'] ) ? $shipping_address_data['locality'] : '',
					'state'      => isset( $shipping_address_data['administrativeArea'] ) ? $shipping_address_data['administrativeArea'] : '',
					'postcode'   => isset( $shipping_address_data['postalCode'] ) ? $shipping_address_data['postalCode'] : '',
					'country'    => isset( $shipping_address_data['countryCode'] ) ? $shipping_address_data['countryCode'] : '',
				];

				$order->set_address( $shipping_address, 'shipping' );
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
	 * Filters and sets the customer's taxable address.
	 *
	 * This is necessary because Google Pay doesn't ever provide a billing
	 * address until after payment is complete. If the shop is set to calculate
	 * tax based on the billing address, we need to use the shipping address
	 * to at least get some rates for new customers.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 *
	 * @param array $address taxable address
	 * @return array
	 */
	public function set_customer_taxable_address( $address ) {

		// set to the shipping address provided by Google Pay if:
		// 1. billing is not available
		// 2. shipping is available
		// 3. taxes aren't configured to use the shop base
		if ( empty( WC()->customer->get_billing_postcode() ) && WC()->customer->get_shipping_postcode() && 'base' !== get_option( 'woocommerce_tax_based_on' ) ) {

			$address = [
				WC()->customer->get_shipping_country(),
				WC()->customer->get_shipping_state(),
				WC()->customer->get_shipping_postcode(),
				WC()->customer->get_shipping_city(),
			];
		}

		return $address;
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
		 * @param Google_Pay $handler the Google Pay handler
		 */
		return apply_filters( 'sv_wc_google_pay_supported_networks', array_values( $networks ), $this );
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

		return method_exists( $this->get_processing_gateway(), 'get_merchant_id' ) ? $this->get_processing_gateway()->get_merchant_id() : '';
	}


}


endif;
