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

if ( ! class_exists( 'SV_WC_Payment_Gateway' ) ) :

/**
 * # WooCommerce Payment Gateway Framework
 *
 * Full featured payment gateway framework
 *
 * ## Supports (zero or more):
 *
 * + `tokenization`  - supports tokenization methods
 * + `card_types`    - allows the user to configure a set of card types to display on the checkout page
 * + `charge`        - transaction type charge
 * + `authorization` - transaction type authorization
 * + `customer_decline_messages` - detailed customer decline messages on checkout
 *
 * ## Payment Types (one and only one):
 *
 * + `credit-card` - supports credit card transactions
 * + `echeck` = supports echeck transactions
 *
 * ## Usage
 *
 * Extend this class and implement the following methods:
 *
 * + `get_method_form_fields()` - return an array of admin settings form fields specific for this method (will probably include at least authentication fields).
 * + `payment_fields()` - probably very simple implementation, ie woocommerce_intuit_qbms_payment_fields( $this );
 *
 * Override any of the following optional method stubs:
 *
 * + `add_payment_gateway_transaction_data( $order, $response )` - add any gateway-specific transaction data to the order
 *
 * Following the instructions in templates/readme.txt copy and complete the
 * following templates as needed based on gateway type:
 *
 * + `wc-gateway-plugin-id-template.php` - template functions
 * + `wc-plugin-id.js - frontend javascript
 * + `credit-card/checkout/gateway-id-payment-fields.php` - renders the checkout payment fields for credit card gateways
 * + `credit-card/myaccount/gateway-id-my-cards.php` - renders the checkout payment fields for credit card gateways
 * + `check/checkout/gateway-id-payment-fields.php` - renders the checkout payment fields for echeck gateways
 * + `check/myaccount/gateway-id-my-accounts.php` - renders the checkout payment fields for echeck gateways
 *
 * ### Tokenization Support
 *
 * If the gateway supports payment method tokenization implement the following method stub:
 *
 * + `show_my_payment_methods_load_template()` - render the "My Payment Methods" template
 *
 * Copy and complete the following template:
 *
 * + `credit-card/myaccount/gateway-id-my-cards.php` - renders the "My Cards" section for credit card gateways
 *
 * #### Types of Tokenization Requests
 *
 * There are two different models used by payment gateways to tokenize payment
 * methods: tokenize with sale/zero dollar pre-auth, or tokenize first.
 * Sample gateways of the former include First Data and NETbilling, which
 * automatically tokenize a payment method as part of a regular authorization/
 * charge transaction.  While an example of the latter is Intuit QBMS, which
 * has a dedicated tokenize request that is always used.  This framework
 * assumes the "tokenize first" protocol.  To implement a gateway that
 * combines tokenization with sale, simply do the following:
 *
 * + Override SV_WC_Payment_Gateway_Direct::tokenize_with_sale() to return true
 * + Make sure that the API authorization response class also implements the
 *   SV_WC_Payment_Gateway_API_Create_Payment_Token_Response interface
 *
 * The framework assumes that for tokenize with sale gateways there will also be
 * a separate zero-dollar tokenization request, this should be implemented by
 * SV_WC_Payment_Gateway_API::tokenize_payment_method()
 *
 * ### Subscriptions support
 *
 * If the gateway conditionally adds subscriptions support (for instance
 * requiring tokenization) add support for all subscriptions features from the
 * child class constructor, after calling the parent constructor and performing
 * any required validations (ie tokenization enabled, CSC not required, etc).
 *
 * Override the get_remove_subscription_renewal_order_meta_fragment() method to remove any
 * order meta added by the add_payment_gateway_transaction_data( $order, $response )
 * method
 *
 * ### Gateway Type
 *
 * Implement the following method stubs based on the gateway type:
 *
 * + `get_api()` - for direct payment methods this returns the API instance
 *
 * ### Logging
 *
 * You are responsible for firing an action from your API/response class to provide
 * logging of the request/response.
 *
 * From within the API class, immediately after the remote request, for instance like:
 *
 * do_action( 'wc_intuit_qbms_api_request_performed', $request_data, $response_data );
 *
 * Where $request_data and $response_data are associative arrays.  Don't
 * forget to fire the action even when errors occur and when handling exceptions
 * even if there isn't any response data to pass (that parameter is optional)
 *
 * This will have the effect of logging every communication request with the
 * remote endpoint, without you having to litter your code with logging calls,
 * and is about the closest to an Aspect Oriented solution as we can get with WP/PHP
 *
 * ### Customer ID
 *
 * Most gateways use a form of customer identification.  If your gateway does
 * not, or you don't require it, override the following methods to return
 * false:
 *
 * + `get_customer_id_user_meta_name()`
 * + `get_guest_customer_id()`
 * + `get_customer_id()`
 *
 * ### Transaction URL
 *
 * Some, not all, gateways support linking directly to a transaction within
 * the merchant account.  If your gateway support this, you can override the
 * following method to return the direct transaction URL for the given order.
 * Don't forget to declare support for this within the gateway plugin class!:
 *
 * + `get_transaction_url( $order )`
 *
 */
abstract class SV_WC_Payment_Gateway extends WC_Payment_Gateway {


	/** Sends through sale and request for funds to be charged to cardholder's credit card. */
	const TRANSACTION_TYPE_CHARGE = 'charge';

	/** Sends through a request for funds to be "reserved" on the cardholder's credit card. A standard authorization is reserved for 2-5 days. Reservation times are determined by cardholder's bank. */
	const TRANSACTION_TYPE_AUTHORIZATION = 'authorization';

	/** The production environment identifier */
	const ENVIRONMENT_PRODUCTION = 'production';

	/** The test environment identifier */
	const ENVIRONMENT_TEST = 'test';

	/** Debug mode log to file */
	const DEBUG_MODE_LOG = 'log';

	/** Debug mode display on checkout */
	const DEBUG_MODE_CHECKOUT = 'checkout';

	/** Debug mode log to file and display on checkout */
	const DEBUG_MODE_BOTH = 'both';

	/** Debug mode disabled */
	const DEBUG_MODE_OFF = 'off';

	/** Gateway which supports direct (XML, REST, SOAP, custom, etc) communication */
	const GATEWAY_TYPE_DIRECT = 'direct';

	/** Gateway which supports redirecting to a gateway server for payment collection, or embedding an iframe on checkout */
	const GATEWAY_TYPE_HOSTED = 'hosted';

	/** Credit card payment type */
	const PAYMENT_TYPE_CREDIT_CARD = 'credit-card';

	/** eCheck payment type */
	const PAYMENT_TYPE_ECHECK = 'echeck';

	/** Gateway with multiple payment options */
	const PAYMENT_TYPE_MULTIPLE = 'multiple';

	/** Bank transfer gateway */
	const PAYMENT_TYPE_BANK_TRANSFER = 'bank_transfer';

	/** Products feature */
	const FEATURE_PRODUCTS = 'products';

	/** Credit card types feature */
	const FEATURE_CARD_TYPES = 'card_types';

	/** Tokenization feature */
	const FEATURE_TOKENIZATION = 'tokenization';

	/** Credit Card charge transaction feature */
	const FEATURE_CREDIT_CARD_CHARGE = 'charge';

	/** Credit Card authorization transaction feature */
	const FEATURE_CREDIT_CARD_AUTHORIZATION = 'authorization';

	/** Credit Card capture charge transaction feature */
	const FEATURE_CREDIT_CARD_CAPTURE = 'capture_charge';

	/** Display detailed customer decline messages on checkout */
	const FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES = 'customer_decline_messages';

	/** Refunds feature */
	const FEATURE_REFUNDS = 'refunds';

	/** Voids feature */
	const FEATURE_VOIDS = 'voids';

	/** Payment Form feature */
	const FEATURE_PAYMENT_FORM = 'payment_form';

	/** Customer ID feature */
	const FEATURE_CUSTOMER_ID = 'customer_id';

	/** @var SV_WC_Payment_Gateway_Plugin the parent plugin class */
	private $plugin;

	/** @var string payment type, one of 'credit-card' or 'echeck' */
	private $payment_type;

	/** @var array associative array of environment id to display name, defaults to 'production' => 'Production' */
	private $environments;

	/** @var array associative array of card type to display name */
	private $available_card_types;

	/** @var array optional array of currency codes this gateway is allowed for */
	private $currencies;

	/** @var string configuration option: the transaction environment, one of $this->environments keys */
	private $environment;

	/** @var string configuration option: the type of transaction, whether purchase or authorization, defaults to 'charge' */
	private $transaction_type;

	/** @var array configuration option: card types to show images for */
	private $card_types;

	/** @var string configuration option: indicates whether a Card Security Code field will be presented on checkout, either 'yes' or 'no' */
	private $enable_csc;

	/** @var array configuration option: supported echeck fields, one of 'check_number', 'account_type' */
	private $supported_check_fields;

	/** @var string configuration option: indicates whether tokenization is enabled, either 'yes' or 'no' */
	private $tokenization;

	/** @var string configuration option: indicates whether detailed customer decline messages should be displayed at checkout, either 'yes' or 'no' */
	private $enable_customer_decline_messages;

	/** @var string configuration option: 4 options for debug mode - off, checkout, log, both */
	private $debug_mode;

	/** @var string configuration option: whether to use a sibling gateway's connection/authentication settings */
	private $inherit_settings;

	/** @var array of shared setting names, if any.  This can be used for instance when a single plugin supports both credit card and echeck payments, and the same credentials can be used for both gateways */
	private $shared_settings = array();


	/**
	 * Initialize the gateway
	 *
	 * Args:
	 *
	 * + `method_title` - string admin method title, ie 'Intuit QBMS', defaults to 'Settings'
	 * + `method_description` - string admin method description, defaults to ''
	 * + `supports` - array  list of supported gateway features, possible values include:
	 *   'products', 'card_types', 'tokenziation', 'charge', 'authorization', 'subscriptions',
	 *   'subscription_suspension', 'subscription_cancellation', 'subscription_reactivation',
	 *   'subscription_amount_changes', 'subscription_date_changes', 'subscription_payment_method_change',
	 *   'customer_decline_messages'
	 *   Defaults to 'products', 'charge' (credit-card gateways only)
	 * + `payment_type` - string one of 'credit-card' or 'echeck', defaults to 'credit-card'
	 * + `card_types` - array  associative array of card type to display name, used if the payment_type is 'credit-card' and the 'card_types' feature is supported.  Defaults to:
	 *   'VISA' => 'Visa', 'MC' => 'MasterCard', 'AMEX' => 'American Express', 'DISC' => 'Discover', 'DINERS' => 'Diners', 'JCB' => 'JCB'
	 * + `echeck_fields` - array of supported echeck fields, including 'check_number', 'account_type'
	 * + `environments` - associative array of environment id to display name, merged with default of 'production' => 'Production'
	 * + `currencies` -  array of currency codes this gateway is allowed for, defaults to plugin accepted currencies
	 * + `countries` -  array of two-letter country codes this gateway is allowed for, defaults to all
	 * + `shared_settings` - array of shared setting names, if any.  This can be used for instance when a single plugin supports both credit card and echeck payments, and the same credentials can be used for both gateways
	 *
	 * @since 1.0.0
	 * @param string $id the gateway id
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the parent plugin class
	 * @param array $args gateway arguments
	 */
	public function __construct( $id, $plugin, $args ) {

		// first setup the gateway and payment type for this gateway
		$this->payment_type = isset( $args['payment_type'] ) ? $args['payment_type'] : self::PAYMENT_TYPE_CREDIT_CARD;

		// default credit card gateways to supporting 'charge' transaction type, this could be overridden by the 'supports' constructor parameter to include (or only support) authorization
		if ( $this->is_credit_card_gateway() ) {
			$this->add_support( self::FEATURE_CREDIT_CARD_CHARGE );
		}

		// required fields
		$this->id          = $id;  // @see WC_Payment_Gateway::$id

		$this->plugin      = $plugin;
		// kind of sucks, but we need to register back to the plugin because
		//  there's no other way of grabbing existing gateways so as to avoid
		//  double-instantiation errors (esp for shared settings)
		$this->get_plugin()->set_gateway( $id, $this );

		// optional parameters
		if ( isset( $args['method_title'] ) ) {
			$this->method_title = $args['method_title'];        // @see WC_Settings_API::$method_title
		}
		if ( isset( $args['method_description'] ) ) {
			$this->method_description = $args['method_description'];  // @see WC_Settings_API::$method_description
		}
		if ( isset( $args['supports'] ) ) {
			$this->set_supports( $args['supports'] );
		}
		if ( isset( $args['card_types'] ) ) {
			$this->available_card_types = $args['card_types'];
		}
		if ( isset( $args['echeck_fields'] ) ) {
			$this->supported_check_fields = $args['echeck_fields'];
		}
		if ( isset( $args['environments'] ) ) {
			$this->environments = array_merge( $this->get_environments(), $args['environments'] );
		}
		if ( isset( $args['countries'] ) ) {
			$this->countries = $args['countries'];  // @see WC_Payment_Gateway::$countries
		}
		if ( isset( $args['shared_settings'] ) ) {
			$this->shared_settings = $args['shared_settings'];
		}
		if ( isset( $args['currencies'] ) ) {
			$this->currencies = $args['currencies'];
		} else {
			$this->currencies = $this->get_plugin()->get_accepted_currencies();
		}
		if ( isset( $args['order_button_text'] ) ) {
			$this->order_button_text = $args['order_button_text'];
		} else {
			$this->order_button_text = $this->get_order_button_text();
		}

		// always want to render the field area, even for gateways with no fields, so we can display messages  @see WC_Payment_Gateway::$has_fields
		$this->has_fields = true;

		// default icon filter  @see WC_Payment_Gateway::$icon
		$this->icon = apply_filters( 'wc_' + $this->get_id() + '_icon', '' );

		// Load the form fields
		$this->init_form_fields();

		// initialize and load the settings
		$this->init_settings();

		$this->load_settings();

		// pay page fallback
		$this->add_pay_page_handler();

		// filter order received text for held orders
		add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'maybe_render_held_order_received_text' ), 10, 2 );

		// admin only
		if ( is_admin() ) {

			// save settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->get_id(), array( $this, 'process_admin_options' ) );
		}

		// add gateway.js checkout javascript
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// add API request logging
		$this->add_api_request_logging();
	}


	/**
	 * Loads the plugin configuration settings
	 *
	 * @since 1.0.0
	 */
	protected function load_settings() {

		// Define user set variables
		foreach ( $this->settings as $setting_key => $setting ) {
			$this->$setting_key = $setting;
		}

		// inherit settings from sibling gateway(s)
		if ( $this->inherit_settings() ) {

			// get any other sibling gateways
			$other_gateway_ids = array_diff( $this->get_plugin()->get_gateway_ids(), array( $this->get_id() ) );

			// determine if any sibling gateways have any configured shared settings
			foreach ( $other_gateway_ids as $other_gateway_id ) {

				$other_gateway_settings = $this->get_plugin()->get_gateway_settings( $other_gateway_id );

				// if the other gateway isn't also trying to inherit settings...
				if ( ! isset( $other_gateway_settings['inherit_settings'] ) || 'no' == $other_gateway_settings['inherit_settings'] ) {

					// load the other gateway so we can access the shared settings properly
					$other_gateway = $this->get_plugin()->get_gateway( $other_gateway_id );

					foreach ( $this->shared_settings as $setting_key ) {
						$this->$setting_key = $other_gateway->$setting_key;
					}
				}
			}
		}
	}


	/**
	 * Enqueues the required gateway.js library and custom checkout javascript.
	 * Also localizes payment method validation errors
	 *
	 * @since 1.0.0
	 * @return boolean true if the scripts were enqueued, false otherwise
	 */
	public function enqueue_scripts() {

		// only load javascript once, if the gateway is available
		if ( ! $this->is_available() || wp_script_is( 'sv-wc-payment-gateway-frontend', 'enqueued' ) || wp_script_is( 'wc-' . $this->get_plugin()->get_id_dasherized(), 'enqueued' ) ) {
			return false;
		}

		$localized_script_handle = '';

		// payment form JS/CSS
		if ( $this->supports_payment_form() ) {

			// jQuery.payment - for credit card validation/formatting
			wp_enqueue_script( 'jquery-payment' );

			// frontend JS
			wp_enqueue_script( 'sv-wc-payment-gateway-frontend', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/js/frontend/sv-wc-payment-gateway-frontend.min.js', array(), SV_WC_Plugin::VERSION, true );

			// frontend CSS
			wp_enqueue_style( 'sv-wc-payment-gateway-frontend', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/css/frontend/sv-wc-payment-gateway-frontend.min.css', array(), SV_WC_Plugin::VERSION );

			$localized_script_handle = 'sv-wc-payment-gateway-frontend';
		}

		// some gateways (particularly those that don't support the payment form feature) have their own frontend JS
		if ( is_readable( $this->get_plugin()->get_plugin_path() . '/assets/js/frontend/wc-' . $this->get_plugin()->get_id_dasherized() . '.min.js' ) ) {

			$script_src = apply_filters( 'wc_payment_gateway_' . $this->get_plugin()->get_id() . '_javascript_url', $this->get_plugin()->get_plugin_url() . '/assets/js/frontend/wc-' . $this->get_plugin()->get_id_dasherized() . '.min.js' );

			wp_enqueue_script( 'wc-' . $this->get_plugin()->get_id_dasherized(), $script_src, array(), $this->get_plugin()->get_version(), true );

			$localized_script_handle = 'wc-' . $this->get_plugin()->get_id_dasherized();
		}

		// maybe localize error messages
		if ( $localized_script_handle ) {

			$params = apply_filters( 'wc_gateway_' . $this->get_plugin()->get_id() . '_js_localize_script_params', $this->get_js_localize_script_params() );

			wp_localize_script( $localized_script_handle, $this->get_plugin()->get_id() . '_params', $params );
		}

		return true;
	}


	/**
	 * Returns true if on the pay page and this is the currently selected gateway
	 *
	 * @since 1.0.0
	 * @return mixed true if on pay page and is currently selected gateways, false if on pay page and not the selected gateway, null otherwise
	 */
	public function is_pay_page_gateway() {

		if ( is_checkout_pay_page() ) {

			$order_id  = $this->get_checkout_pay_page_order_id();

			if ( $order_id ) {
				$order = wc_get_order( $order_id );

				return $order->payment_method == $this->get_id();
			}

		}

		return null;
	}


	/**
	 * Returns an array of javascript script params to localize for the
	 * checkout/pay page javascript.  Mostly used for i18n purposes
	 *
	 * @since 1.0.0
	 * @return array associative array of param name to value
	 */
	protected function get_js_localize_script_params() {

		return array(
			'card_number_missing'            => __( 'Card number is missing', 'sv-wc-plugin-framework' ),
			'card_number_invalid'            => __( 'Card number is invalid', 'sv-wc-plugin-framework' ),
			'card_number_digits_invalid'     => __( 'Card number is invalid (only digits allowed)', 'sv-wc-plugin-framework' ),
			'card_number_length_invalid'     => __( 'Card number is invalid (wrong length)', 'sv-wc-plugin-framework' ),
			'cvv_missing'                    => __( 'Card security code is missing', 'sv-wc-plugin-framework' ),
			'cvv_digits_invalid'             => __( 'Card security code is invalid (only digits are allowed)', 'sv-wc-plugin-framework' ),
			'cvv_length_invalid'             => __( 'Card security code is invalid (must be 3 or 4 digits)', 'sv-wc-plugin-framework' ),
			'card_exp_date_invalid'          => __( 'Card expiration date is invalid', 'sv-wc-plugin-framework' ),
			'check_number_digits_invalid'    => __( 'Check Number is invalid (only digits are allowed)', 'sv-wc-plugin-framework' ),
			'check_number_missing'           => __( 'Check Number is missing', 'sv-wc-plugin-framework' ),
			'drivers_license_state_missing'  => __( 'Drivers license state is missing', 'sv-wc-plugin-framework' ),
			'drivers_license_number_missing' => __( 'Drivers license number is missing', 'sv-wc-plugin-framework' ),
			'drivers_license_number_invalid' => __( 'Drivers license number is invalid', 'sv-wc-plugin-framework' ),
			'account_number_missing'         => __( 'Account Number is missing', 'sv-wc-plugin-framework' ),
			'account_number_invalid'         => __( 'Account Number is invalid (only digits are allowed)', 'sv-wc-plugin-framework' ),
			'account_number_length_invalid'  => __( 'Account number is invalid (must be between 5 and 17 digits)', 'sv-wc-plugin-framework' ),
			'routing_number_missing'         => __( 'Routing Number is missing', 'sv-wc-plugin-framework' ),
			'routing_number_digits_invalid'  => __( 'Routing Number is invalid (only digits are allowed)', 'sv-wc-plugin-framework' ),
			'routing_number_length_invalid'  => __( 'Routing number is invalid (must be 9 digits)', 'sv-wc-plugin-framework' ),
		);

	}


	/**
	 * Gets the order button text:
	 *
	 * Direct gateway: "Place order"
	 * Redirect/Hosted gateway: "Continue"
	 *
	 * @since 4.0.0-beta
	 */
	protected function get_order_button_text() {

		$text = $this->is_hosted_gateway() ? __( 'Continue', 'sv-wc-plugin-framework' ) : __( 'Place order', 'sv-wc-plugin-framework' );

		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_order_button_text', $text, $this );
	}


	/**
	 * Adds a default simple pay page handler
	 *
	 * @since 1.0.0
	 */
	protected function add_pay_page_handler() {
		add_action( 'woocommerce_receipt_' . $this->get_id(), array( $this, 'payment_page' ) );
	}


	/**
	 * Render a simple payment page
	 *
	 * @since 2.1.0
	 * @param int $order_id identifies the order
	 */
	public function payment_page( $order_id ) {
		echo '<p>' . __( 'Thank you for your order.', 'sv-wc-plugin-framework' ) . '</p>';
	}


	/** Payment Form Feature **************************************************/


	/**
	 * Returns true if the gateway supports the payment form feature
	 *
	 * @since 4.0.0-beta
	 * @return bool
	 */
	public function supports_payment_form() {

		return $this->supports( self::FEATURE_PAYMENT_FORM );
	}


	/**
	 * Render the payment fields
	 *
	 * @since 4.0.0-beta
	 * @see WC_Payment_Gateway::payment_fields()
	 * @see SV_WC_Payment_Gateway_Payment_Form class
	 */
	public function payment_fields() {

		if ( $this->supports_payment_form() ) {

			$form = new SV_WC_Payment_Gateway_Payment_Form( $this );

			$form->render();

		} else {

			parent::payment_fields();
		}
	}


	/**
	 * Get the payment form field defaults, primarily for gateways to override
	 * and set dummy credit card/eCheck info when in the test environment
	 *
	 * @since 4.0.0-beta
	 * @return array
	 */
	public function get_payment_method_defaults() {

		assert( $this->supports_payment_form() );

		$defaults = array(
			'account-number' => '',
			'routing-number' => '',
			'expiry'         => '',
			'csc'            => '',
		);

		if ( $this->is_test_environment() ) {
			$defaults['expiry'] = '01/' . ( date( 'Y' ) + 1 );
			$defaults['csc'] = '123';
		}

		return $defaults;
	}


	/**
	 * Get the default payment method title, which is configurable within the
	 * admin and displayed on checkout
	 *
	 * @since 2.1.0
	 * @return string payment method title to show on checkout
	 */
	protected function get_default_title() {

		// defaults for credit card and echeck, override for others
		if ( $this->is_credit_card_gateway() ) {
			return __( 'Credit Card', 'sv-wc-plugin-framework' );
		} elseif ( $this->is_echeck_gateway() ) {
			return __( 'eCheck', 'sv-wc-plugin-framework' );
		}
	}


	/**
	 * Get the default payment method description, which is configurable
	 * within the admin and displayed on checkout
	 *
	 * @since 2.1.0
	 * @return string payment method description to show on checkout
	 */
	protected function get_default_description() {

		// defaults for credit card and echeck, override for others
		if ( $this->is_credit_card_gateway() ) {
			return __( 'Pay securely using your credit card.', 'sv-wc-plugin-framework' );
		} elseif ( $this->is_echeck_gateway() ) {
			return __( 'Pay securely using your checking account.', 'sv-wc-plugin-framework' );
		}
	}


	/**
	 * Initialize payment gateway settings fields
	 *
	 * @since 1.0.0
	 * @see WC_Settings_API::init_form_fields()
	 */
	public function init_form_fields() {

		// common top form fields
		$this->form_fields = array(

			'enabled' => array(
				'title'   => __( 'Enable / Disable', 'sv-wc-plugin-framework' ),
				'label'   => __( 'Enable this gateway', 'sv-wc-plugin-framework' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),

			'title' => array(
				'title'    => __( 'Title', 'sv-wc-plugin-framework' ),
				'type'     => 'text',
				'desc_tip' => __( 'Payment method title that the customer will see during checkout.', 'sv-wc-plugin-framework' ),
				'default'  => $this->get_default_title(),
			),

			'description' => array(
				'title'    => __( 'Description', 'sv-wc-plugin-framework' ),
				'type'     => 'textarea',
				'desc_tip' => __( 'Payment method description that the customer will see during checkout.', 'sv-wc-plugin-framework' ),
				'default'  => $this->get_default_description(),
			),

		);

		// Card Security Code (CVV) field
		if ( $this->is_credit_card_gateway() ) {
			$this->form_fields = $this->add_csc_form_fields( $this->form_fields );
		}

		// both credit card authorization & charge supported
		if ( $this->supports_credit_card_authorization() && $this->supports_credit_card_charge() ) {
			$this->form_fields = $this->add_authorization_charge_form_fields( $this->form_fields );
		}

		// card types support
		if ( $this->supports_card_types() ) {
			$this->form_fields = $this->add_card_types_form_fields( $this->form_fields );
		}

		// tokenization support
		if ( $this->supports_tokenization() ) {
			$this->form_fields = $this->add_tokenization_form_fields( $this->form_fields );
		}

		// if there is more than just the production environment available
		if ( count( $this->get_environments() ) > 1 ) {
			$this->form_fields = $this->add_environment_form_fields( $this->form_fields );
		}

		// add the "inherit settings" toggle if there are settings shared with a sibling gateway
		if ( count( $this->shared_settings ) ) {
			$this->form_fields = $this->add_shared_settings_form_fields( $this->form_fields );
		}

		// add unique method fields added by concrete gateway class
		$gateway_form_fields = $this->get_method_form_fields();
		$this->form_fields = array_merge( $this->form_fields, $gateway_form_fields );

		// add "detailed customer decline messages" option if the feature is supported
		if ( $this->supports( self::FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES ) ) {
			$this->form_fields['enable_customer_decline_messages'] = array(
				'title'   => __( 'Detailed Decline Messages', 'sv-wc-plugin-framework' ),
				'type'    => 'checkbox',
				'label'   => __( 'Check to enable detailed decline messages to the customer during checkout when possible, rather than a generic decline message.', 'sv-wc-plugin-framework' ),
				'default' => 'no',
			);
		}

		// add any common bottom fields
		$this->form_fields['debug_mode'] = array(
			'title'   => __( 'Debug Mode', 'sv-wc-plugin-framework' ),
			'type'    => 'select',
			// translators: %1$s - <a> tag, %2$s - </a> tag
			'desc'    => sprintf( __( 'Show Detailed Error Messages and API requests/responses on the checkout page and/or save them to the %1$sdebug log%2$s', 'sv-wc-plugin-framework' ), '<a href="' . SV_WC_Helper::get_wc_log_file_url( $this->get_id() ) . '">', '</a>' ),
			'default' => self::DEBUG_MODE_OFF,
			'options' => array(
				self::DEBUG_MODE_OFF      => __( 'Off', 'Debug mode off', 'sv-wc-plugin-framework' ),
				self::DEBUG_MODE_CHECKOUT => __( 'Show on Checkout Page', 'sv-wc-plugin-framework' ),
				self::DEBUG_MODE_LOG      => __( 'Save to Log', 'sv-wc-plugin-framework' ),
				self::DEBUG_MODE_BOTH     => __( 'Both', 'Debug mode both show on checkout and log', 'sv-wc-plugin-framework' )
			),
		);

		// add the special 'shared-settings-field' class name to any shared settings fields
		foreach ( $this->shared_settings as $field_name ) {
			$this->form_fields[ $field_name ]['class'] = trim( isset( $this->form_fields[ $field_name ]['class'] ) ? $this->form_fields[ $field_name ]['class'] : '' ) . ' shared-settings-field';
		}

		/**
		 * Payment Gateway Form Fields Filter.
		 *
		 * Actors can use this to add, remove, or tweak gateway form fields
		 *
		 * @since 4.0.0-beta
		 * @param array $form_fields array of form fields in format required by WC_Settings_API
		 * @param \SV_WC_Payment_Gateway $this gateway instance
		 */
		$this->form_fields = apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_form_fields', $this->form_fields, $this );
	}


	/**
	 * Returns an array of form fields specific for this method.
	 *
	 * To add environment-dependent fields, include the 'class' form field argument
	 * with 'environment-field production-field' where "production" matches a
	 * key from the environments member
	 *
	 * @since 1.0.0
	 * @return array of form fields
	 */
	abstract protected function get_method_form_fields();


	/**
	 * Adds the gateway environment form fields
	 *
	 * @since 1.0.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_environment_form_fields( $form_fields ) {

		$form_fields['environment'] = array(
			// translators: environment as in a software environment (test/production)
			'title'    => __( 'Environment', 'sv-wc-plugin-framework' ),
			'type'     => 'select',
			'default'  => key( $this->get_environments() ),  // default to first defined environment
			'desc_tip' => __( 'Select the gateway environment to use for transactions.', 'sv-wc-plugin-framework' ),
			'options'  => $this->get_environments(),
		);

		return $form_fields;
	}


	/**
	 * Adds the optional shared settings toggle element.  The 'shared_settings'
	 * optional constructor parameter must have been used in order for shared
	 * settings to be supported.
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::$shared_settings
	 * @see SV_WC_Payment_Gateway::$inherit_settings
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_shared_settings_form_fields( $form_fields ) {

		// get any sibling gateways
		$other_gateway_ids                  = array_diff( $this->get_plugin()->get_gateway_ids(), array( $this->get_id() ) );
		$configured_other_gateway_ids       = array();
		$inherit_settings_other_gateway_ids = array();

		// determine if any sibling gateways have any configured shared settings
		foreach ( $other_gateway_ids as $other_gateway_id ) {

			$other_gateway_settings = $this->get_plugin()->get_gateway_settings( $other_gateway_id );

			// if the other gateway isn't also trying to inherit settings...
			if ( isset( $other_gateway_settings['inherit_settings'] ) && 'yes' == $other_gateway_settings['inherit_settings'] ) {
				$inherit_settings_other_gateway_ids[] = $other_gateway_id;
			}

			foreach ( $this->shared_settings as $setting_name ) {

				// if at least one shared setting is configured in the other gateway
				if ( isset( $other_gateway_settings[ $setting_name ] ) && $other_gateway_settings[ $setting_name ] ) {

					$configured_other_gateway_ids[] = $other_gateway_id;
					break;
				}
			}
		}

		// disable the field if the sibling gateway is already inheriting settings
		$form_fields['inherit_settings'] = array(
			'title'       => __( 'Share connection settings', 'sv-wc-plugin-framework' ),
			'type'        => 'checkbox',
			'label'       => __( 'Use connection/authentication settings from other gateway', 'sv-wc-plugin-framework' ),
			'default'     => count( $configured_other_gateway_ids ) > 0 ? 'yes' : 'no',
			'disabled'    => count( $inherit_settings_other_gateway_ids ) > 0 ? true : false,
			'description' => count( $inherit_settings_other_gateway_ids ) > 0 ? __( 'Disabled because the other gateway is using these settings', 'sv-wc-plugin-framework' ) : '',
		);

		return $form_fields;
	}


	/**
	 * Adds the enable Card Security Code form fields
	 *
	 * @since 1.0.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_csc_form_fields( $form_fields ) {

		$form_fields['enable_csc'] = array(
			'title'   => __( 'Card Verification (CSC)', 'sv-wc-plugin-framework' ),
			'label'   => __( 'Display the Card Security Code (CV2) field on checkout', 'sv-wc-plugin-framework' ),
			'type'    => 'checkbox',
			'default' => 'yes',
		);

		return $form_fields;
	}


	/**
	 * Display settings page with some additional javascript for hiding conditional fields
	 *
	 * @since 1.0.0
	 * @see WC_Settings_API::admin_options()
	 */
	public function admin_options() {

		parent::admin_options();

		?>
		<style type="text/css">.nowrap { white-space: nowrap; }</style>
		<?php

		// if there's more than one environment include the environment settings switcher code
		if ( count( $this->get_environments() ) > 1 ) {

			// add inline javascript
			ob_start();
			?>
				$( '#woocommerce_<?php echo $this->get_id(); ?>_environment' ).change( function() {

					// inherit settings from other gateway?
					var inheritSettings = $( '#woocommerce_<?php echo $this->get_id(); ?>_inherit_settings' ).is( ':checked' );

					var environment = $( this ).val();

					// hide all environment-dependant fields
					$( '.environment-field' ).closest( 'tr' ).hide();

					// show the currently configured environment fields that are not also being hidden as any shared settings
					var $environmentFields = $( '.' + environment + '-field' );
					if ( inheritSettings ) {
						$environmentFields = $environmentFields.not( '.shared-settings-field' );
					}

					$environmentFields.not( '.hidden' ).closest( 'tr' ).show();

				} ).change();
			<?php

			wc_enqueue_js( ob_get_clean() );

		}

		if ( ! empty( $this->shared_settings ) ) {

			// add inline javascript to show/hide any shared settings fields as needed
			ob_start();
			?>
				$( '#woocommerce_<?php echo $this->get_id(); ?>_inherit_settings' ).change( function() {

					var enabled = $( this ).is( ':checked' );

					if ( enabled ) {
						$( '.shared-settings-field' ).closest( 'tr' ).hide();
					} else {
						// show the fields
						$( '.shared-settings-field' ).closest( 'tr' ).show();

						// hide any that may not be available for the currently selected environment
						$( '#woocommerce_<?php echo $this->get_id(); ?>_environment' ).change();
					}

				} ).change();
			<?php

			wc_enqueue_js( ob_get_clean() );

		}

	}


	/**
	 * Checks for proper gateway configuration including:
	 *
	 * + gateway enabled
	 * + correct configuration (gateway specific)
	 * + any dependencies met
	 * + required currency
	 * + required country
	 *
	 * @since 1.0.0
	 * @see WC_Payment_Gateway::is_available()
	 * @return true if this gateway is available for checkout, false otherwise
	 */
	public function is_available() {

		// is enabled check
		$is_available = parent::is_available();

		// proper configuration
		if ( ! $this->is_configured() ) {
			$is_available = false;
		}

		// all plugin dependencies met
		if ( count( $this->get_plugin()->get_missing_dependencies() ) > 0 ) {
			$is_available = false;
		}

		// any required currencies?
		if ( ! $this->currency_is_accepted() ) {
			$is_available = false;
		}

		// any required countries?
		if ( $this->countries && WC()->customer && WC()->customer->get_country() && ! in_array( WC()->customer->get_country(), $this->countries ) ) {
			$is_available = false;
		}

		return apply_filters( 'wc_gateway_' . $this->get_id() . '_is_available', $is_available );
	}


	/**
	 * Returns true if the gateway is properly configured to perform transactions
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::is_configured()
	 * @return boolean true if the gateway is properly configured
	 */
	protected function is_configured() {
		// override this to check for gateway-specific required settings (user names, passwords, secret keys, etc)
		return true;
	}


	/**
	 * Returns the gateway icon markup
	 *
	 * @since 1.0.0
	 * @see WC_Payment_Gateway::get_icon()
	 * @return string icon markup
	 */
	public function get_icon() {

		$icon = '';

		// specific icon
		if ( $this->icon ) {

			// use icon provided by filter
			$icon = sprintf( '<img src="%s" alt="%s" class="sv-wc-payment-gateway-icon wc-%s-payment-gateway-icon" />', esc_url( WC_HTTPS::force_https_url( $this->icon ) ), esc_attr( $this->get_title() ), esc_attr( $this->get_id_dasherized() ) );
		}

		// credit card images
		if ( ! $icon && $this->supports_card_types() && $this->get_card_types() ) {

			// display icons for the selected card types
			foreach ( $this->get_card_types() as $card_type ) {

				if ( $url = $this->get_payment_method_image_url( $card_type ) ) {
					$icon .= sprintf( '<img src="%s" alt="%s" class="sv-wc-payment-gateway-icon wc-%s-payment-gateway-icon" width="40" height="25" />', esc_url( $url ), esc_attr( strtolower( $card_type ) ), esc_attr( $this->get_id_dasherized() ) );
				}
			}
		}

		// echeck image
		if ( ! $icon && $this->is_echeck_gateway() ) {

			if ( $url = $this->get_payment_method_image_url( 'echeck' ) ) {
				$icon .= sprintf( '<img src="%s" alt="%s" class="sv-wc-payment-gateway-icon wc-%s-payment-gateway-icon" width="40" height="25" />', esc_url( $url ), esc_attr( 'echeck' ), esc_attr( $this->get_id_dasherized() ) );
			}
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->get_id() );
	}


	/**
	 * Returns the payment method image URL (if any) for the given $type, ie
	 * if $type is 'amex' a URL to the american express card icon will be
	 * returned.  If $type is 'echeck', a URL to the echeck icon will be
	 * returned.
	 *
	 * @since 1.0.0
	 * @param string $type the payment method cc type or name
	 * @return string the image URL or null
	 */
	public function get_payment_method_image_url( $type ) {

		$image_type = strtolower( $type );

		// translate card name to type as needed
		switch( $image_type ) {

			case 'american express':
				$image_type = 'amex';
			break;

			case 'discover':
				$image_type = 'disc';
			break;

			case 'mastercard':
				$image_type = 'mc';
			break;

			case 'paypal':
				$image_type = 'paypal-1';
			break;

			case 'visa debit':
				$image_type = 'visa-debit';
			break;

			case 'visa electron':
				$image_type = 'visa-electron';
			break;

			case 'card':
				$image_type = 'cc-plain';
			break;

			// default: accept $type as is
		}

		// use plain card image if type is not known
		if ( ! $image_type ) {
			if ( $this->is_credit_card_gateway() ) {
				$image_type = 'cc-plain';
			}
		}

		// support fallback to PNG
		$image_extension = apply_filters( 'wc_payment_gateway_' . $this->get_plugin()->get_id() . '_use_svg', true ) ? '.svg' : '.png';

		// first, is the card image available within the plugin?
		if ( is_readable( $this->get_plugin()->get_payment_gateway_framework_assets_path() . '/images/card-' . $image_type . $image_extension ) ) {
			return WC_HTTPS::force_https_url( $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/images/card-' . $image_type . $image_extension );
		}

		// default: is the card image available within the framework?
		if ( is_readable( $this->get_plugin()->get_payment_gateway_framework_assets_path() . '/images/card-' . $image_type . $image_extension ) ) {
			return WC_HTTPS::force_https_url( $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/images/card-' . $image_type . $image_extension );
		}

		return null;
	}


	/**
	 * Add payment and transaction information as class members of WC_Order
	 * instance.  The standard information that can be added includes:
	 *
	 * $order->payment_total           - the payment total
	 * $order->customer_id             - optional payment gateway customer id (useful for tokenized payments, etc)
	 * $order->payment->type           - one of 'credit_card' or 'check'
	 * $order->description             - an order description based on the order
	 * $order->unique_transaction_ref  - a combination of order number + retry count, should provide a unique value for each transaction attempt
	 *
	 * Note that not all gateways will necessarily pass or require all of the
	 * above.  These represent the most common attributes used among a variety
	 * of gateways, it's up to the specific gateway implementation to make use
	 * of, or ignore them, or add custom ones by overridding this method.
	 *
	 * The returned order is expected to be used in a transaction request.
	 *
	 * @since 1.0.0
	 * @param int|WC_Order $order the order or order ID being processed
	 * @return WC_Order object with payment and transaction information attached
	 */
	protected function get_order( $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		// set payment total here so it can be modified for later by add-ons like subscriptions which may need to charge an amount different than the get_total()
		$order->payment_total = number_format( $order->get_total(), 2, '.', '' );

		// logged in customer?
		if ( 0 != $order->get_user_id() && false !== ( $customer_id = $this->get_customer_id( $order->get_user_id(), array( 'order' => $order ) ) ) ) {
			$order->customer_id = $customer_id;
		}

		// add payment info
		$order->payment = new stdClass();

		// payment type (credit_card/check/etc)
		$order->payment->type = str_replace( '-', '_', $this->get_payment_type() );

		// translators: %1$s - site title, %2$s - order number
		$order->description = sprintf( __( '%1$s - Order %2$s', 'sv-wc-plugin-framework' ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() );

		$order = $this->get_order_with_unique_transaction_ref( $order );

		/**
		 * Filter the base order for a payment transaction
		 *
		 * Actors can use this filter to adjust or add additional information to
		 * the order object that gateways use for processing transactions.
		 *
		 * @since 4.0.0-beta
		 * @param \WC_Order $order order object
		 * @param \SV_WC_Payment_Gateway $this payment gateway instance
		 */
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_get_order_base', $order, $this );
	}


	/** Refund feature ********************************************************/


	/**
	 * Returns true if this is gateway that supports refunds
	 *
	 * @since 3.1.0
	 * @return boolean true if the gateway supports refunds
	 */
	public function supports_refunds() {

		return $this->supports( self::FEATURE_REFUNDS );
	}


	/**
	 * Process refund
	 *
	 * @since 3.1.0
	 * @param int $order_id order being refunded
	 * @param float $amount refund amount
	 * @param string $reason user-entered reason text for refund
	 * @return bool|WP_Error true on success, or a WP_Error object on failure/error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		// add transaction-specific refund info (amount, reason, transaction IDs, etc)
		$order = $this->get_order_for_refund( $order_id, $amount, $reason );

		// let implementations/actors error out early (e.g. order is missing required data for refund, etc)
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		// if captures are supported and the order has an authorized, but not captured charge, void it instead
		if ( $this->supports_voids() && $this->authorization_valid_for_capture( $order ) ) {
			return $this->process_void( $order );
		}

		try {

			$response = $response = $this->get_api()->refund( $order );

			// allow gateways to void an order in response to a refund attempt
			if ( $this->supports_voids() && $this->maybe_void_instead_of_refund( $order, $response ) ) {
				return $this->process_void( $order );
			}

			if ( $response->transaction_approved() ) {

				// add standard refund-specific transaction data
				$this->add_refund_data( $order, $response );

				// let payment gateway implementations add their own data
				$this->add_payment_gateway_refund_data( $order, $response );

				// add order note
				$this->add_refund_order_note( $order, $response );

				// when full amount is refunded, update status to refunded
				if ( $order->get_total() == $order->get_total_refunded() ) {

					$this->mark_order_as_refunded( $order );
				}

				return true;

			} else {

				$error = $this->get_refund_failed_wp_error( $response->get_status_code(), $response->get_status_message() );

				$order->add_order_note( $error->get_error_message() );

				return $error;
			}

		} catch ( SV_WC_Plugin_Exception $e ) {

			$error = $this->get_refund_failed_wp_error( $e->getCode(), $e->getMessage() );

			$order->add_order_note( $error->get_error_message() );

			return $error;
		}
	}


	/**
	 * Add refund information as class members of WC_Order
	 * instance for use in refund transactions.  Standard information includes:
	 *
	 * $order->refund->amount = refund amount
	 * $order->refund->reason = user-entered reason text for the refund
	 * $order->refund->trans_id = the ID of the original payment transaction for the order
	 *
	 * Payment gateway implementations can override this to add their own
	 * refund-specific data
	 *
	 * @since 3.1.0
	 * @param WC_Order|int $order order being processed
	 * @param float $amount refund amount
	 * @param string $reason optional refund reason text
	 * @return WC_Order object with refund information attached
	 */
	protected function get_order_for_refund( $order, $amount, $reason ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		// add refund info
		$order->refund = new stdClass();
		$order->refund->amount = number_format( $amount, 2, '.', '' );

		// translators: %1$s - site title, %2$s - order number
		$order->refund->reason = $reason ? $reason : sprintf( __( '%1$s - Refund for Order %2$s', 'sv-wc-plugin-framework' ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() );

		// almost all gateways require the original transaction ID, so include it by default
		$order->refund->trans_id = $this->get_order_meta( $order->id, 'trans_id' );

		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_get_order_for_refund', $order, $this );
	}


	/**
	 * Adds the standard refund transaction data to the order
	 *
	 * Note that refunds can be performed multiple times for a single order so
	 * transaction IDs keys are not unique
	 *
	 * @since 3.1.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response $response transaction response
	 */
	protected function add_refund_data( WC_Order $order, $response ) {

		// indicate the order was refunded along with the refund amount
		$this->add_order_meta( $order->id, 'refund_amount', $order->refund->amount );

		// add refund transaction ID
		if ( $response && $response->get_transaction_id() ) {
			$this->add_order_meta( $order->id, 'refund_trans_id', $response->get_transaction_id() );
		}
	}


	/**
	 * Adds any gateway-specific data to the order after a refund is performed
	 *
	 * @since 3.1.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response $response the transaction response
	 */
	protected function add_payment_gateway_refund_data( WC_Order $order, $response ) {
		// Optional method
	}


	/**
	 * Adds an order note with the amount and (optional) refund transaction ID
	 *
	 * @since 3.1.0
	 * @param WC_Order $order order object
	 * @param SV_WC_Payment_Gateway_API_Response $response transaction response
	 */
	protected function add_refund_order_note( WC_Order $order, $response ) {

		$message = sprintf(
			// translators: %1$s - payment gateway title (such as Authorize.net, Braintree, etc), %2$s - a monetary amount
			__( '%1$s Refund in the amount of %2$s approved.', 'sv-wc-plugin-framework' ),
			$this->get_method_title(),
			wc_price( $order->refund->amount, array( 'currency' => $order->get_order_currency() ) )
		);

		// adds the transaction id (if any) to the order note
		if ( $response->get_transaction_id() ) {
			$message .= ' ' . sprintf( __( '(Transaction ID %s)', 'sv-wc-plugin-framework' ), $response->get_transaction_id() );
		}

		$order->add_order_note( $message );
	}


	/**
	 * Build the WP_Error object for a failed refund
	 *
	 * @since 3.1.0
	 * @param int|string $error_code error code
	 * @param string $error_message error message
	 * @return WP_Error suitable for returning from the process_refund() method
	 */
	protected function get_refund_failed_wp_error( $error_code, $error_message ) {

		if ( $error_code ) {
			$message = sprintf(
				// translators: %1$s - payment gateway title (such as Authorize.net, Braintree, etc), %2$s - error code, %3$s - error message
				__( '%1$s Refund Failed: %2$s - %3$s', 'sv-wc-plugin-framework' ),
				$this->get_method_title(),
				$error_code,
				$error_message
			);
		} else {
			$message = sprintf(
				// translators: %1$s - payment gateway title (such as Authorize.net, Braintree, etc), %2$s - error message
				__( '%1$s Refund Failed: %2$s', 'sv-wc-plugin-framework' ),
				$this->get_method_title(),
				$error_message
			);
		}

		return new WP_Error( 'wc_' . $this->get_id() . '_refund_failed', $message );
	}


	/**
	 * Mark an order as refunded. This should only be used when the full order
	 * amount has been refunded.
	 *
	 * @since 3.1.0
	 * @param WC_Order $order order object
	 */
	protected function mark_order_as_refunded( $order ) {

		// translators: %s - payment gateway title (such as Authorize.net, Braintree, etc)
		$order_note = sprintf( __( '%s Order completely refunded.', 'sv-wc-plugin-framework' ), $this->get_method_title() );

		// Mark order as refunded if not already set
		if ( ! $order->has_status( 'refunded' ) ) {
			$order->update_status( 'refunded', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}
	}


	/** Void feature ********************************************************/


	/**
	 * Returns true if this is gateway that supports voids
	 *
	 * @since 3.1.0
	 * @return boolean true if the gateway supports voids
	 */
	public function supports_voids() {

		return $this->supports( self::FEATURE_VOIDS ) && $this->supports_credit_card_capture();
	}


	/**
	 * Allow gateways to void an order that was attempted to be refunded. This is
	 * particularly useful for gateways that can void an authorized & captured
	 * charge that has not yet settled (e.g. Authorize.net AIM/CIM)
	 *
	 * @since 4.0.0-beta
	 * @param \WC_Order $order order
	 * @param \SV_WC_Payment_Gateway_API_Response $response refund response
	 * @return boolean true if a void should be performed for the given order/response
	 */
	protected function maybe_void_instead_of_refund( $order, $response ) {

		return false;
	}


	/**
	 * Process a void
	 *
	 * @since 3.1.0
	 * @param WC_Order $order order object (with refund class member already added)
	 * @return bool|WP_Error true on success, or a WP_Error object on failure/error
	 */
	protected function process_void( WC_Order $order ) {

		// partial voids are not supported
		if ( $order->refund->amount != $order->get_total() ) {
			return new WP_Error( 'wc_' . $this->get_id() . '_void_error', __( 'Oops, you cannot partially void this order. Please use the full order amount.', 'sv-wc-plugin-framework' ) );
		}

		try {

			$response = $this->get_api()->void( $order );

			if ( $response->transaction_approved() ) {

				// add standard void-specific transaction data
				$this->add_void_data( $order, $response );

				// let payment gateway implementations add their own data
				$this->add_payment_gateway_void_data( $order, $response );

				// update order status to "refunded" and add an order note
				$this->mark_order_as_voided( $order, $response );

				return true;

			} else {

				$error = $this->get_void_failed_wp_error( $response->get_status_code(), $response->get_status_message() );

				$order->add_order_note( $error->get_error_message() );

				return $error;
			}

		} catch ( SV_WC_Plugin_Exception $e ) {

			$error = $this->get_void_failed_wp_error( $e->getCode(), $e->getMessage() );

			$order->add_order_note( $error->get_error_message() );

			return $error;
		}
	}


	/**
	 * Adds the standard void transaction data to the order
	 *
	 * @since 3.1.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response $response transaction response
	 */
	protected function add_void_data( WC_Order $order, $response ) {

		// indicate the order was voided along with the amount
		$this->update_order_meta( $order->id, 'void_amount', $order->refund->amount );

		// add refund transaction ID
		if ( $response && $response->get_transaction_id() ) {
			$this->add_order_meta( $order->id, 'void_trans_id', $response->get_transaction_id() );
		}
	}


	/**
	 * Adds any gateway-specific data to the order after a void is performed
	 *
	 * @since 3.1.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response $response the transaction response
	 */
	protected function add_payment_gateway_void_data( WC_Order $order, $response ) {
		// Optional method
	}


	/**
	 * Build the WP_Error object for a failed void
	 *
	 * @since 3.1.0
	 * @param int|string $error_code error code
	 * @param string $error_message error message
	 * @return WP_Error suitable for returning from the process_refund() method
	 */
	protected function get_void_failed_wp_error( $error_code, $error_message ) {

		if ( $error_code ) {
			$message = sprintf(
				// translators: %1$s - payment gateway title, %2$s - error code, %3$s - error message. Void as in to void an order.
				__( '%1$s Void Failed: %2$s - %3$s', 'sv-wc-plugin-framework' ),
				$this->get_method_title(),
				$error_code,
				$error_message
			);
		} else {
			$message = sprintf(
				// translators: %1$s - payment gateway title, %2$s - error message. Void as in to void an order.
				__( '%1$s Void Failed: %2$s', 'sv-wc-plugin-framework' ),
				$this->get_method_title(),
				$error_message
			);
		}

		return new WP_Error( 'wc_' . $this->get_id() . '_void_failed', $message );
	}


	/**
	 * Mark an order as voided. Because WC has no status for "void", we use
	 * refunded.
	 *
	 * @since 3.1.0
	 * @param WC_Order $order order object
	 */
	protected function mark_order_as_voided( $order, $response ) {

		$message = sprintf(
			// translators: %1$s - payment gateway title, %2$s - a monetary amount. Void as in to void an order.
			__( '%1$s Void in the amount of %2$s approved.', 'sv-wc-plugin-framework' ),
			$this->get_method_title(),
			wc_price( $order->refund->amount, array( 'currency' => $order->get_order_currency() ) )
		);

		// adds the transaction id (if any) to the order note
		if ( $response->get_transaction_id() ) {
			$message .= ' ' . sprintf( __( '(Transaction ID %s)', 'sv-wc-plugin-framework' ), $response->get_transaction_id() );
		}

		// mark order as cancelled, since no money was actually transferred
		if ( ! $order->has_status( 'cancelled' ) ) {

			$this->voided_order_message = $message;

			// voids are fully "refunded" so cancel the voided order instead of marking as refunded
			if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_4() ) {

				// filter in WC 2.4+ allows us to skip the "refunded" then "cancelled" transition
				add_filter( 'woocommerce_order_fully_refunded_status', array( $this, 'maybe_cancel_voided_order' ), 10, 2 );

			} else {

				// WC 2.3/2.2 requires changing the order status to cancelled after it's already been changed to refunded ಠ_ಠ
				add_action( 'woocommerce_order_refunded', array( $this, 'maybe_cancel_voided_order_2_3' ) );
			}

		} else {

			$order->add_order_note( $message );
		}
	}


	/**
	 * Maybe change the order status for a voided order to cancelled
	 *
	 * @hooked woocommerce_order_fully_refunded_status filter
	 *
	 * @see SV_WC_Payment_Gateway::mark_order_as_voided()
	 * @since 4.0.0-beta
	 * @param string $order_status default order status for fully refunded orders
	 * @param int $order_id order ID
	 * @return string 'cancelled'
	 */
	public function maybe_cancel_voided_order( $order_status, $order_id ) {

		if ( empty( $this->voided_order_message ) ) {
			return $order_status;
		}

		$order = wc_get_order( $order_id );

		// no way to set the order note with the status change
		$order->add_order_note( $this->voided_order_message );

		return 'cancelled';
	}


	/**
	 * Maybe change the order status for a voided order to cancelled for WC 2.3/2.2
	 *
	 * This must be deferred until the woocommerce_order_refunded, otherwise
	 * it's changed back to refunded
	 *
	 * @TODO: this can be removed once WC 2.4 is required @MR 2015-07-21
	 *
	 * @hooked woocommerce_order_refunded action
	 *
	 * @see SV_WC_Payment_Gateway::mark_order_as_voided()
	 * @since 4.0.0-beta
	 * @param int $order_id order ID
	 */
	public function maybe_cancel_voided_order_2_3( $order_id ) {

		if ( ! empty( $this->voided_order_message ) ) {

			$order = wc_get_order( $order_id );

			$order->update_status( 'cancelled', $this->voided_order_message );
		}
	}


	/**
	 * Returns the $order object with a unique transaction ref member added
	 *
	 * @since 2.2.0
	 * @param WC_Order $order the order object
	 * @return WC_Order order object with member named unique_transaction_ref
	 */
	protected function get_order_with_unique_transaction_ref( $order ) {

		// generate a unique retry count
		if ( is_numeric( $this->get_order_meta( $order->id, 'retry_count' ) ) ) {
			$retry_count = $this->get_order_meta( $order->id, 'retry_count' );

			$retry_count++;
		} else {
			$retry_count = 0;
		}

		// keep track of the retry count
		$this->update_order_meta( $order->id, 'retry_count', $retry_count );

		// generate a unique transaction ref based on the order number and retry count, for gateways that require a unique identifier for every transaction request
		$order->unique_transaction_ref = ltrim( $order->get_order_number(),  __( '#', 'hash before order number', 'sv-wc-plugin-framework' ) ) . ( $retry_count > 0 ? '-' . $retry_count : '' );

		return $order;
	}


	/**
	 * Called after an unsuccessful transaction attempt
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Response $response the transaction response
	 * @return boolean false
	 */
	protected function do_transaction_failed_result( WC_Order $order, SV_WC_Payment_Gateway_API_Response $response ) {

		$order_note = '';

		// build the order note with what data we have
		if ( $response->get_status_code() && $response->get_status_message() ) {
			// translators: %1$s - status code, %2$s - status message
			$order_note = sprintf( __( 'Status code %1$s: %2$s', 'sv-wc-plugin-framework' ), $response->get_status_code(), $response->get_status_message() );
		} elseif ( $response->get_status_code() ) {
			// translators: %s - status code
			$order_note = sprintf( __( 'Status code: %s', 'sv-wc-plugin-framework' ), $response->get_status_code() );
		} elseif ( $response->get_status_message() ) {
			// translators: %s - status message
			$order_note = sprintf( __( 'Status message: %s', 'sv-wc-plugin-framework' ), $response->get_status_message() );
		}

		// add transaction id if there is one
		if ( $response->get_transaction_id() ) {
			$order_note .= ' ' . sprintf( __( 'Transaction ID %s', 'sv-wc-plugin-framework' ), $response->get_transaction_id() );
		}

		$this->mark_order_as_failed( $order, $order_note, $response );

		return false;
	}


	/**
	 * Adds the standard transaction data to the order
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response|null $response optional transaction response
	 */
	protected function add_transaction_data( $order, $response = null ) {

		// transaction id if available
		if ( $response && $response->get_transaction_id() ) {
			$this->update_order_meta( $order->id, 'trans_id', $response->get_transaction_id() );

			// set transaction ID for WC core - remove this and use WC_Order::payment_complete() to add transaction ID after 2.2+ can be required
			update_post_meta( $order->id, '_transaction_id', $response->get_transaction_id() );
		}

		// transaction date
		$this->update_order_meta( $order->id, 'trans_date', current_time( 'mysql' ) );

		// if there's more than one environment
		if ( count( $this->get_environments() ) > 1 ) {
			$this->update_order_meta( $order->id, 'environment', $this->get_environment() );
		}

		// customer data
		if ( $this->supports_customer_id() ) {
			$this->add_customer_data( $order, $response );
		}
	}


	/**
	 * Adds any gateway-specific transaction data to the order
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Customer_Response $response the transaction response
	 */
	protected function add_payment_gateway_transaction_data( $order, $response ) {
		// Optional method
	}


	/**
	 * Add customer data to an order/user if the gateway supports the customer ID
	 * response
	 *
	 * @since 4.0.0-beta
	 * @param \WC_Order $order order
	 * @param \SV_WC_Payment_Gateway_API_Customer_Response $response
	 */
	protected function add_customer_data( $order, $response = null ) {

		$user_id = $order->get_user_id();

		if ( $response && method_exists( $response, 'get_customer_id' ) && $response->get_customer_id() ) {

			$order->customer_id = $customer_id = $response->get_customer_id();

		} else {

			// default to the customer ID set on the order
			$customer_id = $order->customer_id;
		}

		// update the order with the customer ID, note environment is not appended here because it's already available
		// on the `environment` order meta
		$this->update_order_meta( $order->id, 'customer_id', $customer_id );

		// update the user
		if ( 0 != $user_id ) {
			$this->update_customer_id( $user_id, $customer_id );
		}
	}


	/**
	 * Mark the given order as 'on-hold', set an order note and display a message
	 * to the customer
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order
	 * @param string $message a message to display within the order note
	 * @param SV_WC_Payment_Gateway_API_Response optional $response the transaction response object
	 */
	protected function mark_order_as_held( $order, $message, $response = null ) {

		// translators: %1$s - payment gateway title, %2$s - message (probably reason for the transaction being held for review)
		$order_note = sprintf( __( '%1$s Transaction Held for Review (%2$s)', 'sv-wc-plugin-framework' ), $this->get_method_title(), $message );

		// mark order as held
		if ( ! $order->has_status( 'on-hold' ) ) {
			$order->update_status( 'on-hold', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}

		$this->add_debug_message( $message, 'message', true );

		// user message
		$user_message = '';
		if ( $response && $this->is_detailed_customer_decline_messages_enabled() ) {
			$user_message = $response->get_user_message();
		}
		if ( ! $user_message ) {
			$user_message = __( 'Your order has been received and is being reviewed. Thank you for your business.', 'sv-wc-plugin-framework' );
		}

		WC()->session->held_order_received_text = $user_message;
	}


	/**
	 * Maybe render custom order received text on the thank you page when
	 * an order is held
	 *
	 * If detailed customer decline messages are enabled, this message may
	 * additionally include more detailed information.
	 *
	 * @since 4.0.0-beta
	 * @param string $text order received text
	 * @param WC_Order|null $order order object
	 * @return string
	 */
	public function maybe_render_held_order_received_text( $text, $order ) {

		if ( $order && $order->has_status( 'on-hold') && isset( WC()->session->held_order_received_text ) ) {

			$text = WC()->session->held_order_received_text;

			unset( WC()->session->held_order_received_text );
		}

		return $text;
	}


	/**
	 * Mark the given order as failed and set the order note
	 *
	 * @since 1.0.0
	 * @param WC_Order $order the order
	 * @param string $error_message a message to display inside the "Payment Failed" order note
	 * @param SV_WC_Payment_Gateway_API_Response optional $response the transaction response object
	 */
	protected function mark_order_as_failed( $order, $error_message, $response = null ) {

		// translators: Order Note: [Payment method] Payment failed [error]
		// translators: %1$s - payment gateway title, %2$s - error message
		$order_note = sprintf( __( '%1$s Payment Failed (%2$s)', 'sv-wc-plugin-framework' ), $this->get_method_title(), $error_message );

		// Mark order as failed if not already set, otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
		if ( ! $order->has_status( 'failed' ) ) {
			$order->update_status( 'failed', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}

		$this->add_debug_message( $error_message, 'error' );

		// user message
		$user_message = '';
		if ( $response && $this->is_detailed_customer_decline_messages_enabled() ) {
			$user_message = $response->get_user_message();
		}
		if ( ! $user_message ) {
			$user_message = __( 'An error occurred, please try again or try an alternate form of payment.', 'sv-wc-plugin-framework' );
		}
		SV_WC_Helper::wc_add_notice( $user_message, 'error' );
	}


	/**
	 * Mark the given order as cancelled and set the order note
	 *
	 * @since 2.1.0
	 * @param WC_Order $order the order
	 * @param string $error_message a message to display inside the "Payment Cancelled" order note
	 * @param SV_WC_Payment_Gateway_API_Response optional $response the transaction response object
	 */
	protected function mark_order_as_cancelled( $order, $message, $response = null ) {

		// translators: %1$s - payment gateway title, %2$s - message/error
		$order_note = sprintf( __( '%1$s Transaction Cancelled (%2$s)', 'sv-wc-plugin-framework' ), $this->get_method_title(), $message );

		// Mark order as cancelled if not already set
		if ( ! $order->has_status( 'cancelled' ) ) {
			$order->update_status( 'cancelled', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}

		$this->add_debug_message( $message, 'error' );
	}


	/** Customer ID Feature  **************************************************/


	/**
	 * Returns true if this is gateway that supports gateway customer IDs
	 *
	 * @since 4.0.0-beta
	 * @return boolean true if the gateway supports gateway customer IDs
	 */
	public function supports_customer_id() {

		return $this->supports( self::FEATURE_CUSTOMER_ID );
	}


	/**
	 * Gets/sets the payment gateway customer id, this defaults to wc-{user id}
	 * and retrieves/stores to the user meta named by get_customer_id_user_meta_name()
	 * This can be overridden for gateways that use some other value, or made to
	 * return false for gateways that don't support a customer id.
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::get_customer_id_user_meta_name()
	 * @param int $user_id wordpress user identifier
	 * @param array $args optional additional arguments which can include: environment_id, autocreate (true/false), and order
	 * @return string payment gateway customer id
	 */
	public function get_customer_id( $user_id, $args = array() ) {

		$defaults = array(
			'environment_id' => $this->get_environment(),
			'autocreate'     => true,
			'order'          => null,
		);

		$args = array_merge( $defaults, $args );

		// does an id already exist for this user?
		$customer_id = get_user_meta( $user_id, $this->get_customer_id_user_meta_name( $args['environment_id'] ), true );

		if ( ! $customer_id && $args['autocreate'] ) {

			// generate a new customer id.  We try to use 'wc-<hash of billing email>'
			//  if an order is available, on the theory that it will avoid clashing of
			//  accounts if a customer uses the same merchant account on multiple independent
			//  shops.  Otherwise, we use 'wc-<user_id>-<random>'
			if ( $args['order'] && isset( $args['order']->billing_email ) && $args['order']->billing_email ) {
				$customer_id = 'wc-' . md5( $args['order']->billing_email );
			} else {
				$customer_id = uniqid( 'wc-' . $user_id . '-' );
			}

			$this->update_customer_id( $user_id, $customer_id, $args['environment_id'] );
		}

		return $customer_id;
	}


	/**
	 * Updates the payment gateway customer id for the given $environment, or
	 * for the plugin current environment
	 *
	 * @since 1.0.0
	 * @see SV_WC_Payment_Gateway::get_customer_id()
	 * @param int $user_id WP user ID
	 * @param string $customer_id payment gateway customer id
	 * @param string $environment_id optional environment id, defaults to current environment
	 * @return boolean|int false if no change was made (if the new value was the same as previous value) or if the update failed, meta id if the value was different and the update a success
	 */
	public function update_customer_id( $user_id, $customer_id, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		return update_user_meta( $user_id, $this->get_customer_id_user_meta_name( $environment_id ), $customer_id );
	}


	/**
	 * Removes the payment gateway customer id for the given $environment, or
	 * for the plugin current environment
	 *
	 * @since 4.0.0-beta
	 * @param int $user_id WP user ID
	 * @param string $environment_id optional environment id, defaults to current environment
	 * @return boolean true on success, false on failure
	 */
	public function remove_customer_id( $user_id, $environment_id = null ){

		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		// remove the user meta entry so it can be re-created
		return delete_user_meta( $user_id, $this->get_customer_id_user_meta_name( $environment_id ) );
	}


	/**
	 * Returns a payment gateway customer id for a guest customer.  This
	 * defaults to wc-guest-{order id} but can be overridden for gateways that
	 * use some other value, or made to return false for gateways that don't
	 * support a customer id
	 *
	 * @since 1.0.0
	 * @param WC_Order $order order object
	 * @return string payment gateway guest customer id
	 */
	public function get_guest_customer_id( WC_Order $order ) {

		// is there a customer id already tied to this order?
		$customer_id = $this->get_order_meta( $order->id, 'customer_id' );

		if ( $customer_id ) {
			return $customer_id;
		}

		// default
		return 'wc-guest-' . $order->id;
	}


	/**
	 * Returns the payment gateway customer id user meta name for persisting the
	 * gateway customer id.  Defaults to wc_{plugin id}_customer_id for the
	 * production environment and wc_{plugin id}_customer_id_{environment}
	 * for other environments.  A particular environment can be passed,
	 * otherwise this will default to the plugin current environment.
	 *
	 * This can be overridden and made to return false for gateways that don't
	 * support a customer id.
	 *
	 * NOTE: the plugin id, rather than gateway id, is used by default to create
	 * the meta key for this setting, because it's assumed that in the case of a
	 * plugin having multiple gateways (ie credit card and eCheck) the customer
	 * id will be the same between them.
	 *
	 * @since 1.0.0
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return string payment gateway customer id user meta name
	 */
	public function get_customer_id_user_meta_name( $environment_id = null ) {

		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		// no leading underscore since this is meant to be visible to the admin
		return 'wc_' . $this->get_plugin()->get_id() . '_customer_id' . ( ! $this->is_production_environment( $environment_id ) ? '_' . $environment_id : '' );
	}


	/** Authorization/Charge feature ******************************************/


	/**
	 * Returns true if this is a credit card gateway which supports
	 * authorization transactions
	 *
	 * @since 1.0.0
	 * @return boolean true if the gateway supports authorization
	 */
	public function supports_credit_card_authorization() {
		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CREDIT_CARD_AUTHORIZATION );
	}


	/**
	 * Returns true if this is a credit card gateway which supports
	 * charge transactions
	 *
	 * @since 1.0.0
	 * @return boolean true if the gateway supports charges
	 */
	public function supports_credit_card_charge() {
		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CREDIT_CARD_CHARGE );
	}


	/**
	 * Returns true if the gateway supports capturing a charge
	 *
	 * @since 3.1.0
	 * @return boolean true if the gateway supports capturing a charge
	 */
	public function supports_credit_card_capture() {
		return $this->supports( self::FEATURE_CREDIT_CARD_CAPTURE );
	}


	/**
	 * Adds any credit card authorization/charge admin fields, allowing the
	 * administrator to choose between performing authorizations or charges
	 *
	 * @since 1.0.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_authorization_charge_form_fields( $form_fields ) {

		assert( $this->supports_credit_card_authorization() && $this->supports_credit_card_charge() );

		$form_fields['transaction_type'] = array(
			'title'    => __( 'Transaction Type', 'sv-wc-plugin-framework' ),
			'type'     => 'select',
			'desc_tip' => __( 'Select how transactions should be processed. Charge submits all transactions for settlement, Authorization simply authorizes the order total for capture later.', 'sv-wc-plugin-framework' ),
			'default'  => self::TRANSACTION_TYPE_CHARGE,
			'options'  => array(
				self::TRANSACTION_TYPE_CHARGE        => _x( 'Charge',  'noun, credit card transaction type', 'sv-wc-plugin-framework' ),
				self::TRANSACTION_TYPE_AUTHORIZATION => _x( 'Authorization', 'credit card transaction type', 'sv-wc-plugin-framework' ),
			),
		);

		return $form_fields;
	}


	/**
	 * Returns true if the authorization for $order is still valid for capture
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order
	 * @return boolean true if the authorization is valid for capture, false otherwise
	 */
	public function authorization_valid_for_capture( $order ) {

		// check whether the charge has already been captured by this gateway
		$charge_captured = $this->get_order_meta( $order->id, 'charge_captured' );

		if ( 'yes' == $charge_captured ) {
			return false;
		}

		// if for any reason the authorization can not be captured
		$auth_can_be_captured = $this->get_order_meta( $order->id, 'auth_can_be_captured' );

		if ( 'no' == $auth_can_be_captured ) {
			return false;
		}

		// authorization hasn't already been captured, but has it expired?
		return ! $this->has_authorization_expired( $order );
	}


	/**
	 * Returns true if the authorization for $order has expired
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order
	 * @return boolean true if the authorization has expired, false otherwise
	 */
	public function has_authorization_expired( $order ) {

		$transaction_time = strtotime( $this->get_order_meta( $order->id, 'trans_date' ) );

		return floor( ( time() - $transaction_time ) / 3600 ) > $this->get_authorization_time_window();
	}


	/**
	 * Return the authorization time window in hours. An authorization is considered
	 * expired if it is older than this.
	 *
	 * 30 days (720 hours) is the standard authorization window. Individual gateways
	 * can override this as necessary.
	 *
	 * @since 2.2.0
	 * @return int hours
	 */
	protected function get_authorization_time_window() {

		return 720;
	}


	/**
	 * Returns true if a credit card charge should be performed, false if an
	 * authorization should be
	 *
	 * @since 1.0.0
	 * @throws Exception
	 * @return boolean true if a charge should be performed
	 */
	public function perform_credit_card_charge() {

		assert( $this->supports_credit_card_charge() );

		return self::TRANSACTION_TYPE_CHARGE == $this->transaction_type;
	}


	/**
	 * Returns true if a credit card authorization should be performed, false if aa
	 * charge should be
	 *
	 * @since 1.0.0
	 * @throws Exception
	 * @return boolean true if an authorization should be performed
	 */
	public function perform_credit_card_authorization() {

		assert( $this->supports_credit_card_authorization() );

		return self::TRANSACTION_TYPE_AUTHORIZATION == $this->transaction_type;
	}


	/** Card Types feature ******************************************************/


	/**
	 * Returns true if the gateway supports card_types: allows the admin to
	 * configure card type icons to display at checkout
	 *
	 * @since 1.0.0
	 * @return boolean true if the gateway supports card_types
	 */
	public function supports_card_types() {
		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CARD_TYPES );
	}


	/**
	 * Returns the array of accepted card types if this is a credit card gateway
	 * that supports card types.  Return format is 'VISA', 'MC', 'AMEX', etc
	 *
	 * @since 1.0.0
	 * @see get_available_card_types()
	 * @return array of accepted card types, ie 'VISA', 'MC', 'AMEX', etc
	 */
	public function get_card_types() {

		assert( $this->supports_card_types() );

		return $this->card_types;
	}


	/**
	 * Adds any card types form fields, allowing the admin to configure the card
	 * types icons displayed during checkout
	 *
	 * @since 1.0.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_card_types_form_fields( $form_fields ) {

		assert( $this->supports_card_types() );

		$form_fields['card_types'] = array(
			'title'    => __( 'Accepted Card Types', 'sv-wc-plugin-framework' ),
			'type'     => 'multiselect',
			'desc_tip' => __( 'Select which card types you accept.', 'sv-wc-plugin-framework' ),
			'default'  => array_keys( $this->get_available_card_types() ),
			'class'    => 'wc-enhanced-select chosen_select',
			'css'      => 'width: 350px;',
			'options'  => $this->get_available_card_types(),
		);

		return $form_fields;
	}


	/**
	 * Returns available card types, ie 'VISA' => 'Visa', 'MC' => 'MasterCard', etc
	 *
	 * @since 1.0.0
	 * @return array associative array of card type to display name
	 */
	public function get_available_card_types() {

		assert( $this->supports_card_types() );

		// default available card types
		if ( ! isset( $this->available_card_types ) ) {

			$this->available_card_types = array(
				'VISA'   => _x( 'Visa', 'credit card type', 'sv-wc-plugin-framework' ),
				'MC'     => _x( 'MasterCard', 'credit card type', 'sv-wc-plugin-framework' )
				'AMEX'   => _x( 'American Express', 'credit card type', 'sv-wc-plugin-framework' ),
				'DISC'   => _x( 'Discover', 'credit card type', 'sv-wc-plugin-framework' ),
				'DINERS' => _x( 'Diners', 'credit card type', 'sv-wc-plugin-framework' ),
				'JCB'    => _x( 'JCB', 'credit card type', 'sv-wc-plugin-framework' ),
			);

		}

		// return the default card types
		return apply_filters( 'wc_' . $this->get_id() . '_available_card_types', $this->available_card_types );
	}


	/** Tokenization feature **************************************************/


	/**
	 * Returns true if the gateway supports tokenization
	 *
	 * @since 1.0.0
	 * @return boolean true if the gateway supports tokenization
	 */
	public function supports_tokenization() {
		return $this->supports( self::FEATURE_TOKENIZATION );
	}


	/**
	 * Returns true if tokenization is enabled
	 *
	 * @since 1.0.0
	 * @return boolean true if tokenization is enabled
	 */
	public function tokenization_enabled() {

		assert( $this->supports_tokenization() );

		return 'yes' == $this->tokenization;
	}


	/**
	 * Adds any tokenization form fields for the settings page
	 *
	 * @since 1.0.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_tokenization_form_fields( $form_fields ) {

		assert( $this->supports_tokenization() );

		$form_fields['tokenization'] = array(
			// translators: http://www.cybersource.com/products/payment_security/payment_tokenization/ and https://en.wikipedia.org/wiki/Tokenization_(data_security)
			'title'   => __( 'Tokenization', 'sv-wc-plugin-framework' ),
			'label'   => __( 'Allow customers to securely save their payment details for future checkout.', 'sv-wc-plugin-framework' ),
			'type'    => 'checkbox',
			'default' => 'no',
		);

		return $form_fields;
	}


	/** Helper methods ******************************************************/


	/**
	 * Safely get and trim data from $_POST
	 *
	 * @deprecated use SV_WC_Helper::get_post()
	 * @since 1.0.0
	 * @param string $key array key to get from $_POST array
	 * @return string value from $_POST or blank string if $_POST[ $key ] is not set
	 */
	protected function get_post( $key ) {

		if ( isset( $_POST[ $key ] ) ) {
			return trim( $_POST[ $key ] );
		}

		return '';
	}


	/**
	 * Safely get and trim data from $_REQUEST
	 *
	 * @since 1.0.0
	 * @param string $key array key to get from $_REQUEST array
	 * @return string value from $_REQUEST or blank string if $_REQUEST[ $key ] is not set
	 */
	protected function get_request( $key ) {

		if ( isset( $_REQUEST[ $key ] ) ) {
			return trim( $_REQUEST[ $key ] );
		}

		return '';
	}


	/**
	 * Add API request logging for the gateway. The main plugin class typically handles this, but the payment
	 * gateway plugin class no-ops the method so each gateway's requests can be logged individually (e.g. credit card &
	 * eCheck) and make use of the payment gateway-specific add_debug_message() method
	 *
	 * @since 2.2.0
	 * @see SV_WC_Plugin::add_api_request_logging()
	 */
	public function add_api_request_logging() {

		if ( ! has_action( 'wc_' . $this->get_id() . '_api_request_performed' ) ) {
			add_action( 'wc_' . $this->get_id() . '_api_request_performed', array( $this, 'log_api_request' ), 10, 2 );
		}
	}


	/**
	 * Log gateway API requests/responses
	 *
	 * @since 2.2.0
	 * @param array $request request data, see SV_WC_API_Base::broadcast_request() for format
	 * @param array $response response data
	 */
	public function log_api_request( $request, $response ) {

		// request
		$this->add_debug_message( $this->get_plugin()->get_api_log_message( $request ), 'message' );

		// response
		if ( ! empty( $response ) ) {
			$this->add_debug_message( $this->get_plugin()->get_api_log_message( $response ), 'message' );
		}
	}


	/**
	 * Adds debug messages to the page as a WC message/error, and/or to the WC Error log
	 *
	 * @since 1.0.0
	 * @param string $message message to add
	 * @param string $type how to add the message, options are:
	 *     'message' (styled as WC message), 'error' (styled as WC Error)
	 */
	protected function add_debug_message( $message, $type = 'message' ) {

		// do nothing when debug mode is off or no message
		if ( 'off' == $this->debug_off() || ! $message ) {
			return;
		}

		// add log message to WC logger if log/both is enabled
		if ( $this->debug_log() ) {
			$this->get_plugin()->log( $message, $this->get_id() );
		}

		// avoid adding notices when performing refunds, these occur in the admin as an Ajax call, so checking the current filter
		// is the only reliably way to do so
		if ( in_array( 'wp_ajax_woocommerce_refund_line_items', $GLOBALS['wp_current_filter'] ) ) {
			return;
		}

		// add debug message to woocommerce->errors/messages if checkout or both is enabled, the admin/Ajax check ensures capture charge transactions aren't logged as notices to the front end
		if ( ( $this->debug_checkout() || ( 'error' === $type && $this->is_test_environment() ) ) && ( ! is_admin() || defined( 'DOING_AJAX' ) ) ) {

			if ( 'message' === $type ) {

				SV_WC_Helper::wc_add_notice( str_replace( "\n", "<br/>", htmlspecialchars( $message ) ), 'notice' );

			} else {

				// defaults to error message
				SV_WC_Helper::wc_add_notice( str_replace( "\n", "<br/>", htmlspecialchars( $message ) ), 'error' );
			}
		}
	}


	/**
	 * Returns true if $currency is accepted by this gateway
	 *
	 * @since 2.1.0
	 * @param string $currency optional three-letter currency code, defaults to
	 *        currently configured WooCommerce currency
	 * @return boolean true if $currency is accepted, false otherwise
	 */
	public function currency_is_accepted( $currency = null ) {

		// accept all currencies
		if ( ! $this->currencies ) {
			return true;
		}

		// default to currently configured currency
		if ( is_null( $currency ) ) {
			$currency = get_woocommerce_currency();
		}

		return in_array( get_woocommerce_currency(), $this->currencies );
	}


	/**
	 * Returns true if the given order needs shipping, false otherwise.  This
	 * is based on the WooCommerce core Cart::needs_shipping()
	 *
	 * @since 2.2.0
	 * @param \WC_Order $order
	 * @return boolean true if $order needs shipping, false otherwise
	 */
	protected function order_needs_shipping( $order ) {

		if ( get_option( 'woocommerce_calc_shipping' ) == 'no' ) {
			return false;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $order->get_product_from_item( $item );

			if ( $product->needs_shipping() ) {
				return true;
			}
		}

		// no shipping required
		return false;
	}


	/** Order Meta helper methods *********************************************/


	/**
	 * Add order meta
	 *
	 * @since 2.2.0
	 * @param int|string $order_id ID for order to add meta to
	 * @param string $key meta key (already prefixed with gateway ID)
	 * @param mixed $value meta value
	 * @param bool $unique
	 * @return bool|int
	 */
	protected function add_order_meta( $order_id, $key, $value, $unique = false ) {

		return add_post_meta( $order_id, $this->get_order_meta_prefix() . $key, $value, $unique );
	}


	/**
	 * Get order meta
	 *
	 * Note this is hardcoded to return a single value for the get_post_meta() call
	 *
	 * @since 2.2.0
	 * @param int|string $order_id ID for order to get meta for
	 * @param string $key meta key
	 * @return mixed
	 */
	protected function get_order_meta( $order_id, $key ) {

		return get_post_meta( $order_id, $this->get_order_meta_prefix() . $key, true );
	}


	/**
	 * Update order meta
	 *
	 * @since 2.2.0
	 * @param int|string $order_id ID for order to update meta for
	 * @param string $key meta key
	 * @param mixed $value meta value
	 * @return bool|int
	 */
	protected function update_order_meta( $order_id, $key, $value ) {

		return update_post_meta( $order_id, $this->get_order_meta_prefix() . $key, $value );
	}


	/**
	 * Delete order meta
	 *
	 * @since 2.2.0
	 * @param int|string $order_id ID for order to delete meta for
	 * @param string $key meta key
	 * @return bool
	 */
	protected function delete_order_meta( $order_id, $key ) {
		return delete_post_meta( $order_id, $this->get_order_meta_prefix() . $key );
	}


	/**
	 * Gets the order meta prefixed used for the *_order_meta() methods
	 *
	 * Defaults to `_wc_{gateway_id}_`
	 *
	 * @since 2.2.0
	 * @return string
	 */
	protected function get_order_meta_prefix() {
		return '_wc_' . $this->get_id() . '_';
	}


	/** Getters ******************************************************/


	/**
	 * Returns the payment gateway id
	 *
	 * @since 1.0.0
	 * @see WC_Payment_Gateway::$id
	 * @return string payment gateway id
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the payment gateway id with dashes in place of underscores, and
	 * appropriate for use in frontend element names, classes and ids
	 *
	 * @since 1.0.0
	 * @return string payment gateway id with dashes in place of underscores
	 */
	public function get_id_dasherized() {
		return str_replace( '_', '-', $this->get_id() );
	}


	/**
	 * Returns the parent plugin object
	 *
	 * @since 1.0.0
	 * @return \SV_WC_Payment_Gateway_Plugin the parent plugin object
	 */
	public function get_plugin() {
		return $this->plugin;
	}


	/**
	 * Returns the admin method title.  This should be the gateway name, ie
	 * 'Intuit QBMS'
	 *
	 * @since 1.0.0
	 * @see WC_Settings_API::$method_title
	 * @return string method title
	 */
	public function get_method_title() {
		return $this->method_title;
	}


	/**
	 * Returns true if the Card Security Code (CVV) field should be used on checkout
	 *
	 * @since 1.0.0
	 * @return boolean true if the Card Security Code field should be used on checkout
	 */
	public function csc_enabled() {
		return 'yes' == $this->enable_csc;
	}


	/**
	 * Returns true if settings should be inherited for this gateway
	 *
	 * @since 1.0.0
	 * @return boolean true if settings should be inherited for this gateway
	 */
	public function inherit_settings() {
		return 'yes' == $this->inherit_settings;
	}


	/**
	 * Returns an array of two-letter country codes this gateway is allowed for, defaults to all
	 *
	 * @since 2.2.0
	 * @see WC_Payment_Gateway::$countries
	 * @return array of two-letter country codes this gateway is allowed for, defaults to all
	 */
	public function get_available_countries() {
		return $this->countries;
	}


	/**
	 * Add support for the named feature or features
	 *
	 * @since 1.0.0
	 * @param string|array $feature the feature name or names supported by this gateway
	 */
	public function add_support( $feature ) {

		if ( ! is_array( $feature ) ) {
			$feature = array( $feature );
		}

		foreach ( $feature as $name ) {

			// add support for feature if it's not already declared
			if ( ! in_array( $name, $this->supports ) ) {

				$this->supports[] = $name;

				// allow other actors (including ourselves) to take action when support is declared
				do_action( 'wc_payment_gateway_' . $this->get_id() . '_supports_' . str_replace( '-', '_', $name ), $this, $name );
			}

		}
	}


	/**
	 * Set all features supported
	 *
	 * @since 1.0.0
	 * @param array $features array of supported feature names
	 */
	public function set_supports( $features ) {
		$this->supports = $features;
	}


	/**
	 * Returns true if this echeck gateway supports
	 *
	 * @since 1.0.0
	 * @param string $field_name check gateway field name, includes 'check_number', 'account_type'
	 * @return boolean true if this check gateway supports the named field
	 */
	public function supports_check_field( $field_name ) {

		assert( $this->is_echeck_gateway() );

		return is_array( $this->supported_check_fields ) && in_array( $field_name, $this->supported_check_fields );

	}


	/**
	 * Gets the set of environments supported by this gateway.  All gateways
	 * support at least the production environment
	 *
	 * @since 1.0.0
	 * @return array associative array of environment id to name supported by this gateway
	 */
	public function get_environments() {

		// default set of environments consists of 'production'
		if ( ! isset( $this->environments ) ) {
			// translators: https://www.skyverge.com/for-translators-environments/
			$this->environments = array( self::ENVIRONMENT_PRODUCTION => _x( 'Production', 'software environment', 'sv-wc-plugin-framework' ) );
		}

		return $this->environments;
	}


	/**
	 * Returns the environment setting, one of the $environments keys, ie
	 * 'production'
	 *
	 * @since 1.0.0
	 * @return string the configured environment id
	 */
	public function get_environment() {
		return $this->environment;
	}


	/**
	 * Returns true if the current environment is $environment_id
	 */
	public function is_environment( $environment_id ) {
		return $environment_id == $this->get_environment();
	}


	/**
	 * Returns true if the current gateway environment is configured to
	 * 'production'.  All gateways have at least the production environment
	 *
	 * @since 1.0.0
	 * @param string $environment_id optional environment id to check, otherwise defaults to the gateway current environment
	 * @return boolean true if $environment_id (if non-null) or otherwise the current environment is production
	 */
	public function is_production_environment( $environment_id = null ) {

		// if an environment was passed in, see whether it's the production environment
		if ( ! is_null( $environment_id ) ) {
			return self::ENVIRONMENT_PRODUCTION == $environment_id;
		}

		// default: check the current environment
		return $this->is_environment( self::ENVIRONMENT_PRODUCTION );
	}


	/**
	 * Returns true if the current gateway environment is configured to 'test'
	 *
	 * @since 2.1.0
	 * @param string $environment_id optional environment id to check, otherwise defaults to the gateway current environment
	 * @return boolean true if $environment_id (if non-null) or otherwise the current environment is test
	 */
	public function is_test_environment( $environment_id = null ) {

		// if an environment was passed in, see whether it's the production environment
		if ( ! is_null( $environment_id ) ) {
			return self::ENVIRONMENT_TEST == $environment_id;
		}

		// default: check the current environment
		return $this->is_environment( self::ENVIRONMENT_TEST );
	}


	/**
	 * Returns true if the gateway is enabled.  This has nothing to do with
	 * whether the gateway is properly configured or functional.
	 *
	 * @since 2.1.0
	 * @see WC_Payment_Gateway::$enabled
	 * @return boolean true if the gateway is enabled
	 */
	public function is_enabled() {
		return 'yes' == $this->enabled;
	}


	/**
	 * Returns true if detailed decline messages should be displayed to
	 * customers on checkout when available, rather than a single generic
	 * decline message
	 *
	 * @since 2.2.0
	 * @see SV_WC_Payment_Gateway_API_Response_Message_Helper
	 * @see SV_WC_Payment_Gateway_API_Response::get_user_message()
	 * @return boolean true if detailed decline messages should be displayed
	 *         on checkout
	 */
	public function is_detailed_customer_decline_messages_enabled() {
		return 'yes' == $this->enable_customer_decline_messages;
	}


	/**
	 * Returns the set of accepted currencies, or empty array if all currencies
	 * are accepted by this gateway
	 *
	 * @since 2.1.0
	 * @return array of currencies accepted by this gateway
	 */
	public function get_accepted_currencies() {
		return $this->currencies;
	}


	/**
	 * Returns true if all debugging is disabled
	 *
	 * @since 1.0.0
	 * @return boolean if all debuging is disabled
	 */
	public function debug_off() {
		return self::DEBUG_MODE_OFF === $this->debug_mode;
	}


	/**
	 * Returns true if debug logging is enabled
	 *
	 * @since 1.0.0
	 * @return boolean if debug logging is enabled
	 */
	public function debug_log() {
		return self::DEBUG_MODE_LOG === $this->debug_mode || self::DEBUG_MODE_BOTH === $this->debug_mode;
	}


	/**
	 * Returns true if checkout debugging is enabled.  This will cause debugging
	 * statements to be displayed on the checkout/pay pages
	 *
	 * @since 1.0.0
	 * @return boolean if checkout debugging is enabled
	 */
	public function debug_checkout() {
		return self::DEBUG_MODE_CHECKOUT === $this->debug_mode || self::DEBUG_MODE_BOTH === $this->debug_mode;
	}


	/**
	 * Returns true if this is a direct type gateway
	 *
	 * @since 1.0.0
	 * @return boolean if this is a direct payment gateway
	 */
	public function is_direct_gateway() {
		return false;
	}


	/**
	 * Returns true if this is a hosted type gateway
	 *
	 * @since 1.0.0
	 * @return boolean if this is a hosted IPN payment gateway
	 */
	public function is_hosted_gateway() {
		return false;
	}


	/**
	 * Returns the payment type for this gateway
	 *
	 * @since 2.1.0
	 * @return string the payment type, ie 'credit-card', 'echeck', etc
	 */
	public function get_payment_type() {
		return $this->payment_type;
	}


	/**
	 * Returns true if this is a credit card gateway
	 *
	 * @since 1.0.0
	 * @return boolean true if this is a credit card gateway
	 */
	public function is_credit_card_gateway() {
		return self::PAYMENT_TYPE_CREDIT_CARD == $this->get_payment_type();
	}


	/**
	 * Returns true if this is an echeck gateway
	 *
	 * @since 1.0.0
	 * @return boolean true if this is an echeck gateway
	 */
	public function is_echeck_gateway() {
		return self::PAYMENT_TYPE_ECHECK == $this->get_payment_type();
	}


	/**
	 * Returns the API instance for this gateway if it uses direct communication
	 *
	 * This is a stub method which must be overridden if this gateway performs
	 * direct communication
	 *
	 * @since 1.0.0
	 * @return SV_WC_Payment_Gateway_API the payment gateway API instance
	 */
	public function get_api() {

		// concrete stub method
		assert( false );
	}


	/**
	 * Returns the order_id if on the checkout pay page
	 *
	 * @since 3.0.0
	 * @return int order identifier
	 */
	public function get_checkout_pay_page_order_id() {
		global $wp;

		return isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
	}


	/**
	 * Returns the order_id if on the checkout order received page
	 *
	 * Note this must be used in the `wp` or later action, as earlier
	 * actions do not yet have access to the query vars
	 *
	 * @since 3.0.0
	 * @return int order identifier
	 */
	public function get_checkout_order_received_order_id() {
		global $wp;

		return isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
	}


}

endif;  // class exists check
