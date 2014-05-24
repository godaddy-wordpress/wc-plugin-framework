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
 * @copyright Copyright (c) 2013-2014, SkyVerge, Inc.
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
 * You are responsible for handling your own logging, though some helper
 * methods and best practices are defined.
 *
 * A recommended implementation strategy for direct payment gateways is to
 * do an action from within the API class, immediately after the remote request,
 * for instance like:
 *
 * do_action( 'wc_intuit_qbms_api_request_performed', $request_data, $response_data );
 *
 * Hook into this action from your child class constructor, ie:
 *
 * add_action( 'wc_intuit_qbms_api_request_performed', array( $this, 'log_api_communication' ), 10, 2 );
 *
 * Then define your own log_api_communication method, making use of
 * add_debug_message() method if so desired, ie:
 *
 * $this->add_debug_message( sprintf( __( "Request Method: %s\nRequest URI: %s\nRequest Body: %s", My_Gateway::TEXT_DOMAIN ), $request['method'], $request['uri'], $request['body'] ), 'message', true );
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

	/** Credit card types feature */
	const FEATURE_CARD_TYPES = 'card_types';

	/** Tokenization feature */
	const FEATURE_TOKENIZATION = 'tokenization';

	/** Credit Card charge transaction feature */
	const FEATURE_CREDIT_CARD_CHARGE = 'charge';

	/** Credit Card authorization transaction feature */
	const FEATURE_CREDIT_CARD_AUTHORIZATION = 'authorization';


	/** @var SV_WC_Payment_Gateway_Plugin the parent plugin class */
	private $plugin;

	/** @var string plugin text domain */
	protected $text_domain;

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
	 *   'subscription_amount_changes', 'subscription_date_changes', 'subscription_payment_method_change'.
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
	 * @since 1.0
	 * @param string $id the gateway id
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the parent plugin class
	 * @param string $text_domain the plugin text domain
	 * @param array $args gateway arguments
	 */
	public function __construct( $id, $plugin, $text_domain, $args ) {

		// first setup the gateway and payment type for this gateway
		$this->payment_type = isset( $args['payment_type'] ) ? $args['payment_type'] : self::PAYMENT_TYPE_CREDIT_CARD;

		// default credit card gateways to supporting 'charge' transaction type, this could be overridden by the 'supports' constructor parameter to include (or only support) authorization
		if ( $this->is_credit_card_gateway() ) {
			$this->add_support( self::FEATURE_CREDIT_CARD_CHARGE );
		}

		// required fields
		$this->id          = $id;  // @see WC_Payment_Gateway::$id

		$this->plugin      = $plugin;
		$this->text_domain = $text_domain;

		// optional parameters
		if ( isset( $args['method_title'] ) )       $this->method_title                 = $args['method_title'];        // @see WC_Settings_API::$method_title
		if ( isset( $args['method_description'] ) ) $this->method_description           = $args['method_description'];  // @see WC_Settings_API::$method_description
		if ( isset( $args['supports'] ) )           $this->set_supports( $args['supports'] );
		if ( isset( $args['card_types'] ) )         $this->available_card_types         = $args['card_types'];
		if ( isset( $args['echeck_fields'] ) )      $this->supported_check_fields       = $args['echeck_fields'];
		if ( isset( $args['environments'] ) )       $this->environments                 = array_merge( $this->get_environments(), $args['environments'] );
		if ( isset( $args['countries'] ) )          $this->countries                    = $args['countries'];  // @see WC_Payment_Gateway::$countries
		if ( isset( $args['shared_settings'] ) )    $this->shared_settings              = $args['shared_settings'];
		if ( isset( $args['currencies'] ) ) {
			$this->currencies = $args['currencies'];
		} else {
			$this->currencies = $this->get_plugin()->get_accepted_currencies();
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

		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->get_id(), array( $this, 'process_admin_options' ) );
		}

		// add gateway.js checkout javascript
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}


	/**
	 * Loads the plugin configuration settings
	 *
	 * @since 1.0
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
	 * @since 1.0
	 * @return boolean true if the scripts were enqueued, false otherwise
	 */
	public function enqueue_scripts() {

		// only load javascript once, if the gateway is available
		if ( ! $this->is_available() || wp_script_is( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-js', 'enqueued' ) ) {
			return false;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// load gateway.js checkout script
		$script_src = apply_filters( 'wc_payment_gateway_' . $this->get_plugin()->get_id() . '_javascript_url', $this->get_plugin()->get_plugin_url() . '/assets/js/frontend/wc-' . $this->get_plugin()->get_id_dasherized() . $suffix . '.js', $suffix );

		// some gateways don't use frontend scripts so don't enqueue if one doesn't exist
		if ( ! is_readable( $this->get_plugin()->get_plugin_path() . '/assets/js/frontend/wc-' . $this->get_plugin()->get_id_dasherized() . $suffix . '.js' ) ) {
			return false;
		}

		wp_enqueue_script( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-js', $script_src, array(), $this->get_plugin()->get_version(), true );

		// localize error messages
		$params = apply_filters( 'wc_gateway_' . $this->get_plugin()->get_id() . '_js_localize_script_params', $this->get_js_localize_script_params() );

		wp_localize_script( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-js', $this->get_plugin()->get_id() . '_params', $params );

		return true;
	}


	/**
	 * Returns true if on the pay page and this is the currently selected gateway
	 *
	 * @since 1.0
	 * @return mixed true if on pay page and is currently selected gateways, false if on pay page and not the selected gateway, null otherwise
	 */
	public function is_pay_page_gateway() {

		if ( SV_WC_Plugin_Compatibility::is_checkout_pay_page() ) {

			$order_id  = SV_WC_Plugin_Compatibility::get_checkout_pay_page_order_id();

			if ( $order_id ) {
				$order = new WC_Order( $order_id );

				return $order->payment_method == $this->get_id();
			}

		}

		return null;
	}


	/**
	 * Returns an array of javascript script params to localize for the
	 * checkout/pay page javascript.  Mostly used for i18n purposes
	 *
	 * @since 1.0
	 * @return array associative array of param name to value
	 */
	protected function get_js_localize_script_params() {

		return array(
			'card_number_missing'            => _x( 'Card number is missing', 'Supports direct credit card', $this->text_domain ),
			'card_number_invalid'            => _x( 'Card number is invalid', 'Supports direct credit card', $this->text_domain ),
			'card_number_digits_invalid'     => _x( 'Card number is invalid (only digits allowed)', 'Supports direct credit card', $this->text_domain ),
			'card_number_length_invalid'     => _x( 'Card number is invalid (wrong length)', 'Supports direct credit card', $this->text_domain ),
			'cvv_missing'                    => _x( 'Card security code is missing', 'Supports direct credit card', $this->text_domain ),
			'cvv_digits_invalid'             => _x( 'Card security code is invalid (only digits are allowed)', 'Supports direct credit card', $this->text_domain ),
			'cvv_length_invalid'             => _x( 'Card security code is invalid (must be 3 or 4 digits)', 'Supports direct credit card', $this->text_domain ),
			'card_exp_date_invalid'          => _x( 'Card expiration date is invalid', 'Supports direct credit card', $this->text_domain ),
			'check_number_digits_invalid'    => _x( 'Check Number is invalid (only digits are allowed)', 'Supports direct cheque', $this->text_domain ),
			'drivers_license_state_missing'  => _x( 'Drivers license state is missing', 'Supports direct cheque', $this->text_domain ),
			'drivers_license_number_missing' => _x( 'Drivers license number is missing', 'Supports direct cheque', $this->text_domain ),
			'drivers_license_number_invalid' => _x( 'Drivers license number is invalid', 'Supports direct cheque', $this->text_domain ),
			'account_number_missing'         => _x( 'Account Number is missing', 'Supports direct cheque', $this->text_domain ),
			'account_number_invalid'         => _x( 'Account Number is invalid (only digits are allowed)', 'Supports direct cheque', $this->text_domain ),
			'account_number_length_invalid'  => _x( 'Account number is invalid (must be between 5 and 17 digits)', 'Supports direct cheque', $this->text_domain ),
			'routing_number_missing'         => _x( 'Routing Number is missing', 'Supports direct cheque', $this->text_domain ),
			'routing_number_digits_invalid'  => _x( 'Routing Number is invalid (only digits are allowed)', 'Supports direct cheque', $this->text_domain ),
			'routing_number_length_invalid'  => _x( 'Routing number is invalid (must be 9 digits)', 'Supports direct cheque', $this->text_domain ),
		);

	}


	/**
	 * Adds a default simple pay page handler
	 *
	 * @since 1.0
	 */
	protected function add_pay_page_handler() {
		add_action( 'woocommerce_receipt_' . $this->get_id(), array( $this, 'payment_page' ) );
	}


	/**
	 * Render a simple payment page
	 *
	 * @since 2.1
	 * @param int $order_id identifies the order
	 */
	public function payment_page( $order_id ) {
		echo '<p>' . __( 'Thank you for your order.', $this->text_domain ) . '</p>';
	}


	/**
	 * Get the default payment method title, which is configurable within the
	 * admin and displayed on checkout
	 *
	 * @since 2.1
	 * @return string payment method title to show on checkout
	 */
	protected function get_default_title() {

		// defaults for credit card and echeck, override for others
		if ( $this->is_credit_card_gateway() ) {
			return _x( 'Credit Card', 'Supports credit card', $this->text_domain );
		} elseif ( $this->is_echeck_gateway() ) {
			return _x( 'eCheck', 'Supports cheque', $this->text_domain );
		}
	}


	/**
	 * Get the default payment method description, which is configurable
	 * within the admin and displayed on checkout
	 *
	 * @since 2.1
	 * @return string payment method description to show on checkout
	 */
	protected function get_default_description() {

		// defaults for credit card and echeck, override for others
		if ( $this->is_credit_card_gateway() ) {
			return _x( 'Pay securely using your credit card.', 'Supports credit card', $this->text_domain );
		} elseif ( $this->is_echeck_gateway() ) {
			return _x( 'Pay securely using your checking account.', 'Supports cheque', $this->text_domain );
		}
	}


	/**
	 * Initialize payment gateway settings fields
	 *
	 * @since 1.0
	 * @see WC_Settings_API::init_form_fields()
	 */
	public function init_form_fields() {

		// common top form fields
		$this->form_fields = array(

			'enabled' => array(
				'title'   => __( 'Enable / Disable', $this->text_domain ),
				'label'   => __( 'Enable this gateway', $this->text_domain ),
				'type'    => 'checkbox',
				'default' => 'no',
			),

			'title' => array(
				'title'    => __( 'Title', $this->text_domain ),
				'type'     => 'text',
				'desc_tip' => __( 'Payment method title that the customer will see during checkout.', $this->text_domain ),
				'default'  => $this->get_default_title(),
			),

			'description' => array(
				'title'    => __( 'Description', $this->text_domain ),
				'type'     => 'textarea',
				'desc_tip' => __( 'Payment method description that the customer will see during checkout.', $this->text_domain ),
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

		// add any common bottom fields
		$this->form_fields['debug_mode'] = array(
			'title'       => __( 'Debug Mode', $this->text_domain ),
			'type'        => 'select',
			'description' => sprintf( __( 'Show Detailed Error Messages and API requests/responses on the checkout page and/or save them to the debug log: %s', $this->text_domain ), '<strong class="nobr">wp-content/plugins/woocommerce/logs/' . $this->log_file_name() . '</strong>' ),
			'default'     => self::DEBUG_MODE_OFF,
			'options'     => array(
				self::DEBUG_MODE_OFF      => _x( 'Off', 'Debug mode off', $this->text_domain ),
				self::DEBUG_MODE_CHECKOUT => __( 'Show on Checkout Page', $this->text_domain ),
				self::DEBUG_MODE_LOG      => __( 'Save to Log', $this->text_domain ),
				self::DEBUG_MODE_BOTH     => _x( 'Both', 'Debug mode both show on checkout and log', $this->text_domain )
			),
		);

		// add the special 'shared-settings-field' class name to any shared settings fields
		foreach ( $this->shared_settings as $field_name ) {
			$this->form_fields[ $field_name ]['class'] = trim( isset( $this->form_fields[ $field_name ]['class'] ) ? $this->form_fields[ $field_name ]['class'] : '' ) . ' shared-settings-field';
		}
	}


	/**
	 * Returns an array of form fields specific for this method.
	 *
	 * To add environment-dependent fields, include the 'class' form field argument
	 * with 'environment-field production-field' where "production" matches a
	 * key from the environments member
	 *
	 * @since 1.0
	 * @return array of form fields
	 */
	abstract protected function get_method_form_fields();


	/**
	 * Adds the gateway environment form fields
	 *
	 * @since 1.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_environment_form_fields( $form_fields ) {

		$form_fields['environment'] = array(
			'title'    => __( 'Environment', $this->text_domain ),
			'type'     => 'select',
			'default'  => key( $this->get_environments() ),  // default to first defined environment
			'desc_tip' => __( 'Select the gateway environment to use for transactions.', $this->text_domain ),
			'options'  => $this->get_environments(),
		);

		return $form_fields;
	}


	/**
	 * Adds the optional shared settings toggle element.  The 'shared_settings'
	 * optional constructor parameter must have been used in order for shared
	 * settings to be supported.
	 *
	 * @since 1.0
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
			'title'       => _x( 'Share connection settings', 'Supports sibling gateways', $this->text_domain ),
			'type'        => 'checkbox',
			'label'       => _x( 'Use connection/authentication settings from other gateway', $this->text_domain ),
			'default'     => count( $configured_other_gateway_ids ) > 0 ? 'yes' : 'no',
			'disabled'    => count( $inherit_settings_other_gateway_ids ) > 0 ? true : false,
			'description' => count( $inherit_settings_other_gateway_ids ) > 0 ? __( 'Disabled because the other gateway is using these settings', $this->text_domain ) : '',
		);

		return $form_fields;
	}


	/**
	 * Adds the enable Card Security Code form fields
	 *
	 * @since 1.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_csc_form_fields( $form_fields ) {

		$form_fields['enable_csc'] = array(
			'title'   => _x( 'Card Verification (CSC)', 'Supports direct credit card', $this->text_domain ),
			'label'   => _x( 'Display the Card Security Code (CV2) field on checkout', 'Supports direct credit card', $this->text_domain ),
			'type'    => 'checkbox',
			'default' => 'yes',
		);

		return $form_fields;
	}


	/**
	 * Display settings page with some additional javascript for hiding conditional fields
	 *
	 * @since 1.0
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

			SV_WC_Plugin_Compatibility::wc_enqueue_js( ob_get_clean() );

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

			SV_WC_Plugin_Compatibility::wc_enqueue_js( ob_get_clean() );

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
	 * @since 1.0
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
		if ( $this->countries && SV_WC_Plugin_Compatibility::WC()->customer && SV_WC_Plugin_Compatibility::WC()->customer->get_country() && ! in_array( SV_WC_Plugin_Compatibility::WC()->customer->get_country(), $this->countries ) ) {
			$is_available = false;
		}

		return apply_filters( 'wc_gateway_' . $this->get_id() + '_is_available', $is_available );
	}


	/**
	 * Returns true if the gateway is properly configured to perform transactions
	 *
	 * @since 1.0
	 * @see SV_WC_Payment_Gateway::is_configured()
	 * @return boolean true if the gateway is properly configured
	 */
	protected function is_configured() {
		return true;
	}


	/**
	 * Returns the gateway icon markup
	 *
	 * @since 1.0
	 * @see WC_Payment_Gateway::get_icon()
	 * @return string icon markup
	 */
	public function get_icon() {

		$icon = '';

		// specific icon
		if ( $this->icon ) {

			// use icon provided by filter
			$icon = '<img src="' . esc_url( SV_WC_Plugin_Compatibility::force_https_url( $this->icon ) ) . '" alt="' . esc_attr( $this->title ) . '" />';
		}

		// credit card images
		if ( ! $icon && $this->supports_card_types() && $this->get_card_types() ) {

			// display icons for the selected card types
			foreach ( $this->get_card_types() as $card_type ) {

				if ( $url = $this->get_payment_method_image_url( $card_type ) ) {
					$icon .= '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( strtolower( $card_type ) ) . '" />';
				}
			}
		}

		// echeck image
		if ( ! $icon && $this->is_echeck_gateway() ) {

			if ( $url = $this->get_payment_method_image_url( 'echeck' ) ) {
				$icon .= '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( 'echeck' ) . '" />';
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
	 * @since 1.0
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

			// default: accept $type as is
		}

		// use plain card image if type is not known
		if ( ! $image_type ) {
			if ( $this->is_credit_card_gateway() ) {
				$image_type = 'cc-plain';
			}
		}

		// first, is the card image available within the plugin?
		if ( is_readable( $this->get_plugin()->get_plugin_path() . '/assets/images/card-' . $image_type . '.png' ) ) {
			return SV_WC_Plugin_Compatibility::force_https_url( $this->get_plugin()->get_plugin_url() ) . '/assets/images/card-' . $image_type . '.png';
		}

		// default: is the card image available within the framework?
		// NOTE: I don't particularly like hardcoding this path, but I don't see any real way around it
		if ( is_readable( $this->get_plugin()->get_plugin_path() . '/' . $this->get_plugin()->get_framework_image_path() . 'card-' . $image_type . '.png' ) ) {
			return SV_WC_Plugin_Compatibility::force_https_url( $this->get_plugin()->get_plugin_url() ) . '/' . $this->get_plugin()->get_framework_image_path() . 'card-' . $image_type . '.png';
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
	 *
	 * Note that not all gateways will necessarily pass or require all of the
	 * above.  These represent the most common attributes used among a variety
	 * of gateways, it's up to the specific gateway implementation to make use
	 * of, or ignore them, or add custom ones by overridding this method.
	 *
	 * The returned order is expected to be used in a transaction request.
	 *
	 * @since 1.0
	 * @param int|WC_Order $order the order or order ID being processed
	 * @return WC_Order object with payment and transaction information attached
	 */
	protected function get_order( $order ) {

		if ( is_int( $order ) ) {
			$order = new WC_Order( $order );
		}

		// set payment total here so it can be modified for later by add-ons like subscriptions which may need to charge an amount different than the get_total()
		$order->payment_total = number_format( $order->get_total(), 2, '.', '' );

		// logged in customer?
		if ( 0 != $order->user_id && false !== ( $customer_id = $this->get_customer_id( $order->user_id, array( 'order' => $order ) ) ) ) {
			$order->customer_id = $customer_id;
		}

		// add payment info
		$order->payment = new stdClass();

		// payment type (credit card/check)
		if ( $this->is_credit_card_gateway() ) {
			$order->payment->type = 'credit_card';
		} elseif ( $this->is_echeck_gateway() ) {
			$order->payment->type = 'check';
		} else {
			$order->payment->type = $this->get_payment_type();
		}

		$order->description = sprintf( _x( '%s - Order %s', 'Order description', $this->text_domain ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() );

		return $order;
	}


	/**
	 * Called after an unsuccessful transaction attempt
	 *
	 * @since 1.0
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Response $response the transaction response
	 * @return boolean false
	 */
	protected function do_transaction_failed_result( WC_Order $order, SV_WC_Payment_Gateway_API_Response $response ) {

		$order_note = '';

		// build the order note with what data we have
		if ( $response->get_status_code() && $response->get_status_message() ) {
			$order_note = sprintf( '%s: "%s"', $response->get_status_code(), $response->get_status_message() );
		} elseif ( $response->get_status_code() ) {
			$order_note = sprintf( 'Status code: "%s"', $response->get_status_code() );
		} elseif ( $response->get_status_message() ) {
			$order_note = sprintf( 'Status message: "%s"', $response->get_status_message() );
		}

		// add transaction id if there is one
		if ( $response->get_transaction_id() ) {
			$order_note .= ' ' . sprintf( __( 'Transaction id %s', $this->text_domain ), $response->get_transaction_id() );
		}

		$this->mark_order_as_failed( $order, $order_note );

		return false;
	}


	/**
	 * Adds the standard transaction data to the order
	 *
	 * @since 1.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response|null $response optional transaction response
	 */
	protected function add_transaction_data( $order, $response = null ) {

		// transaction id if available
		if ( $response && $response->get_transaction_id() ) {
			update_post_meta( $order->id, '_wc_' . $this->get_id() . '_trans_id', $response->get_transaction_id() );
		}

		// transaction date
		update_post_meta( $order->id, '_wc_' . $this->get_id() . '_trans_date', current_time( 'mysql' ) );

		// if there's more than one environment
		if ( count( $this->get_environments() ) > 1 ) {
			update_post_meta( $order->id, '_wc_' . $this->get_id() . '_environment', $this->get_environment() );
		}

		// if there is a payment gateway customer id, set it to the order (we don't append the environment here like we do for the user meta, because it's available from the 'environment' order meta already)
		if ( isset( $order->customer_id ) && $order->customer_id ) {
			update_post_meta( $order->id, '_wc_' . $this->get_id() . '_customer_id', $order->customer_id );
		}
	}


	/**
	 * Adds any gateway-specific transaction data to the order
	 *
	 * @since 1.0
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response $response the transaction response
	 */
	protected function add_payment_gateway_transaction_data( $order, $response ) {
		// Optional method
	}


	/**
	 * Mark the given order as 'on-hold', set an order note and display a message
	 * to the customer
	 *
	 * @since 1.0
	 * @param WC_Order $order the order
	 * @param string $message a message to display within the order note
	 */
	protected function mark_order_as_held( $order, $message ) {

		$order_note = sprintf( __( '%s Transaction Held for Review (%s)', $this->text_domain ), $this->get_method_title(), $message );

		// mark order as held
		if ( 'on-hold' != $order->status ) {
			$order->update_status( 'on-hold', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}

		$this->add_debug_message( $message, 'message', true );

		// we don't have control over the "Thank you. Your order has been received." message shown on the "Thank You" page.  Yet
		SV_WC_Plugin_Compatibility::wc_add_notice( __( 'Your order has been received and is being reviewed.  Thank you for your business.', $this->text_domain ) );
		SV_WC_Plugin_Compatibility::set_messages();  // TODO: do we need this?

	}


	/**
	 * Mark the given order as failed and set the order note
	 *
	 * @since 1.0
	 * @param WC_Order $order the order
	 * @param string $error_message a message to display inside the "Payment Failed" order note
	 */
	protected function mark_order_as_failed( $order, $error_message ) {

		$order_note = sprintf( _x( '%s Payment Failed (%s)', 'Order Note: (Payment method) Payment failed (error)', $this->text_domain ), $this->get_method_title(), $error_message );

		// Mark order as failed if not already set, otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
		if ( 'failed' != $order->status ) {
			$order->update_status( 'failed', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}

		$this->add_debug_message( $error_message, 'error' );

		SV_WC_Plugin_Compatibility::wc_add_notice( __( 'An error occurred, please try again or try an alternate form of payment.', $this->text_domain ), 'error' );
	}


	/**
	 * Mark the given order as cancelled and set the order note
	 *
	 * @since 2.1
	 * @param WC_Order $order the order
	 * @param string $error_message a message to display inside the "Payment Cancelled" order note
	 */
	protected function mark_order_as_cancelled( $order, $message ) {

		$order_note = sprintf( _x( '%s Transaction Cancelled (%s)', 'Cancelled order note', $this->text_domain ), $this->get_method_title(), $message );

		// Mark order as failed if not already set, otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
		if ( 'cancelled' != $order->status ) {
			$order->update_status( 'cancelled', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}

		$this->add_debug_message( $message, 'error' );
	}


	/**
	 * Gets/sets the payment gateway customer id, this defaults to wc-{user id}
	 * and retrieves/stores to the user meta named by get_customer_id_user_meta_name()
	 * This can be overridden for gateways that use some other value, or made to
	 * return false for gateways that don't support a customer id.
	 *
	 * @since 1.0
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
	 * @since 1.0
	 * @see SV_WC_Payment_Gateway::get_customer_id()
	 * @param int $user_id wordpress user identifier
	 * @param string payment gateway customer id
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
	 * Returns a payment gateway customer id for a guest customer.  This
	 * defaults to wc-guest-{order id} but can be overridden for gateways that
	 * use some other value, or made to return false for gateways that don't
	 * support a customer id
	 *
	 * @since 1.0
	 * @param WC_Order $order order object
	 * @return string payment gateway guest customer id
	 */
	public function get_guest_customer_id( WC_Order $order ) {

		// is there a customer id already tied to this order?
		$customer_id = get_post_meta( $order->id, '_wc_' . $this->get_id() . '_customer_id', true );

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
	 * @since 1.0
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return string payment gateway customer id user meta name
	 */
	public function get_customer_id_user_meta_name( $environment_id = null ) {

		if ( is_null( $environment_id ) )
			$environment_id = $this->get_environment();

		// no leading underscore since this is meant to be visible to the admin
		return 'wc_' . $this->get_plugin()->get_id() . '_customer_id' . ( ! $this->is_production_environment( $environment_id ) ? '_' . $environment_id : '' );

	}


	/**
	 * Add a button to the order actions meta box to view the order in the
	 * merchant account, if supported
	 *
	 * @since 1.0
	 * @see SV_WC_Payment_Gateway_Plugin::order_meta_box_transaction_link()
	 * @see SV_WC_Payment_Gateway::get_transaction_url()
	 * @param WC_Order $order the order object
	 */
	public function order_meta_box_transaction_link( $order ) {

		if ( $url = $this->get_transaction_url( $order ) ) {

			?>
			<li class="wide" style="text-align: center;">
				<a class="button tips" href="<?php echo esc_url( $url ); ?>" target="_blank" data-tip="<?php esc_attr_x( 'View this transaction in your merchant account', 'Supports transaction link', $this->text_domain ); ?>" style="cursor: pointer !important;"><?php printf( _x( 'View in %s', 'Supports transaction link', $this->text_domain ), $this->get_method_title() ); ?></a>
			</li>
			<?php

		}
	}


	/**
	 * Returns the merchant account transaction URL for the given order, if the
	 * gateway supports transaction direct-links, which not all gateways do.
	 *
	 * Override this method to return the transaction URL, if supported
	 *
	 * @since 1.0
	 * @see SV_WC_Payment_Gateway_Plugin::order_meta_box_transaction_link()
	 * @see SV_WC_Payment_Gateway::order_meta_box_transaction_link()
	 * @param WC_Order $order the order object
	 * @return string transaction url or null if not supported
	 */
	public function get_transaction_url( $order ) {

		// method stub
		return null;
	}


	/** Authorization/Charge feature ******************************************************/


	/**
	 * Returns true if this is a credit card gateway which supports
	 * authorization transactions
	 *
	 * @since 1.0
	 * @return boolean true if the gateway supports authorization
	 */
	public function supports_credit_card_authorization() {
		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CREDIT_CARD_AUTHORIZATION );
	}


	/**
	 * Returns true if this is a credit card gateway which supports
	 * charge transactions
	 *
	 * @since 1.0
	 * @return boolean true if the gateway supports charges
	 */
	public function supports_credit_card_charge() {
		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CREDIT_CARD_CHARGE );
	}


	/**
	 * Adds any credit card authorization/charge admin fields, allowing the
	 * administrator to choose between performing authorizations or charges
	 *
	 * @since 1.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if authorization & charge are not supported
	 */
	protected function add_authorization_charge_form_fields( $form_fields ) {

		if ( ! ( $this->supports_credit_card_authorization() && $this->supports_credit_card_charge() ) ) {
			throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( 'Authorization/Charge transactions not supported by gateway' );
		}

		$form_fields['transaction_type'] = array(
			'title'    => _x( 'Transaction Type', 'Supports credit card authorization/charge', $this->text_domain ),
			'type'     => 'select',
			'desc_tip' => _x( 'Select how transactions should be processed. Charge submits all transactions for settlement, Authorization simply authorizes the order total for capture later.', 'Supports credit card authorization/charge', $this->text_domain ),
			'default'  => self::TRANSACTION_TYPE_CHARGE,
			'options'  => array(
				self::TRANSACTION_TYPE_CHARGE        => _x( 'Charge', 'Supports credit card authorization/charge', $this->text_domain ),
				self::TRANSACTION_TYPE_AUTHORIZATION => _x( 'Authorization', 'Supports credit card authorization/charge', $this->text_domain ),
			),
		);

		return $form_fields;
	}


	/**
	 * Returns true if a credit card charge should be performed, false if an
	 * authorization should be
	 *
	 * @since 1.0
	 * @throws Exception
	 * @return boolean true if a charge should be performed
	 */
	public function perform_credit_card_charge() {

		if ( ! $this->supports_credit_card_charge() ) {
			throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( 'Credit Card charge transactions not supported by this gateway' );
		}

		return  self::TRANSACTION_TYPE_CHARGE == $this->transaction_type;
	}


	/**
	 * Returns true if a credit card authorization should be performed, false if aa
	 * charge should be
	 *
	 * @since 1.0
	 * @throws Exception
	 * @return boolean true if an authorization should be performed
	 */
	public function perform_credit_card_authorization() {

		if ( ! $this->supports_credit_card_authorization() ) {
			throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( 'Credit Card authorization transactions not supported by this gateway' );
		}

		return self::TRANSACTION_TYPE_AUTHORIZATION == $this->transaction_type;
	}


	/** Card Types feature ******************************************************/


	/**
	 * Returns true if the gateway supports card_types: allows the admin to
	 * configure card type icons to display at checkout
	 *
	 * @since 1.0
	 * @return boolean true if the gateway supports card_types
	 */
	public function supports_card_types() {
		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CARD_TYPES );
	}


	/**
	 * Returns the array of accepted card types if this is a credit card gateway
	 * that supports card types.  Return format is 'VISA', 'MC', 'AMEX', etc
	 *
	 * @since 1.0
	 * @see get_available_card_types()
	 * @return array of accepted card types, ie 'VISA', 'MC', 'AMEX', etc
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if card types are not supported
	 */
	public function get_card_types() {

		if ( ! $this->supports_card_types() ) {
			throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( 'Card Types not supported by gateway' );
		}

		return $this->card_types;
	}


	/**
	 * Adds any card types form fields, allowing the admin to configure the card
	 * types icons displayed during checkout
	 *
	 * @since 1.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if card types are not supported
	 */
	protected function add_card_types_form_fields( $form_fields ) {

		if ( ! $this->supports_card_types() ) {
			throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( 'Card Types not supported by gateway' );
		}

		$form_fields['card_types'] = array(
			'title'    => _x( 'Accepted Card Logos', 'Supports card types', $this->text_domain ),
			'type'     => 'multiselect',
			'desc_tip' => _x( 'Select which card types you accept to display the logos for on your checkout page.', 'Supports card types', $this->text_domain ),
			'default'  => array_keys( $this->get_available_card_types() ),
			'class'    => 'chosen_select',
			'css'      => 'width: 350px;',
			'options'  => $this->get_available_card_types(),
		);

		return $form_fields;
	}


	/**
	 * Returns available card types, ie 'VISA' => 'Visa', 'MC' => 'MasterCard', etc
	 *
	 * @since 1.0
	 * @return array associative array of card type to display name
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if credit card types is not supported
	 */
	public function get_available_card_types() {

		if ( ! $this->supports_card_types() ) {
			throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( 'Card Types not supported by gateway' );
		}

		// default available card types
		if ( ! isset( $this->available_card_types ) ) {

			$this->available_card_types = array(
				'VISA'   => 'Visa',
				'MC'     => 'MasterCard',
				'AMEX'   => 'American Express',
				'DISC'   => 'Discover',
				'DINERS' => 'Diners',
				'JCB'    => 'JCB',
			);

		}

		// return the default card types
		return apply_filters( 'wc_' . $this->get_id() . '_available_card_types', $this->available_card_types );
	}


	/** Tokenization feature ******************************************************/


	/**
	 * Returns true if the gateway supports tokenization
	 *
	 * @since 1.0
	 * @return boolean true if the gateway supports tokenization
	 */
	public function supports_tokenization() {
		return $this->supports( self::FEATURE_TOKENIZATION );
	}


	/**
	 * Returns true if tokenization is enabled
	 *
	 * @since 1.0
	 * @return boolean true if tokenization is enabled
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function tokenization_enabled() {

		if ( ! $this->supports_tokenization() ) {
			throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( 'Payment tokenization not supported by gateway' );
		}

		return 'yes' == $this->tokenization;
	}


	/**
	 * Adds any tokenization form fields for the settings page
	 *
	 * @since 1.0
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	protected function add_tokenization_form_fields( $form_fields ) {

		if ( ! $this->supports_tokenization() ) {
			throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( 'Payment tokenization not supported by gateway' );
		}

		$form_fields['tokenization'] = array(
			'title'   => _x( 'Tokenization', 'Supports tokenization', $this->text_domain ),
			'label'   => _x( 'Allow customers to securely save their payment details for future checkout.', 'Supports tokenization', $this->text_domain ),
			'type'    => 'checkbox',
			'default' => 'no',
		);

		return $form_fields;
	}


	/** Helper methods ******************************************************/


	/**
	 * Safely get and trim data from $_POST
	 *
	 * @since 1.0
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
	 * @since 1.0
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
	 * Perform standard luhn check.  Algorithm:
	 *
	 * 1. Double the value of every second digit beginning with the second-last right-hand digit.
	 * 2. Add the individual digits comprising the products obtained in step 1 to each of the other digits in the original number.
	 * 3. Subtract the total obtained in step 2 from the next higher number ending in 0.
	 * 4. This number should be the same as the last digit (the check digit). If the total obtained in step 2 is a number ending in zero (30, 40 etc.), the check digit is 0.
	 *
	 * @since 1.0
	 * @param string $account_number the credit card number to check
	 * @return bool true if $account_number passes the check, false otherwise
	 */
	protected function luhn_check( $account_number ) {

		for ( $sum = 0, $i = 0, $ix = strlen( $account_number ); $i < $ix - 1; $i++) {

			$weight = substr( $account_number, $ix - ( $i + 2 ), 1 ) * ( 2 - ( $i % 2 ) );
			$sum += $weight < 10 ? $weight : $weight - 9;

		}

		return substr( $account_number, $ix - 1 ) == ( ( 10 - $sum % 10 ) % 10 );
	}


	/**
	 * Adds debug messages to the page as a WC message/error, and/or to the WC Error log
	 *
	 * @since 1.0
	 * @param string $message message to add
	 * @param string $type how to add the message, options are:
	 *     'message' (styled as WC message), 'error' (styled as WC Error)
	 * @param bool $set_message sets any WC messages/errors provided so they appear on the next page load, useful for displaying messages on the thank you page
	 */
	protected function add_debug_message( $message, $type = 'message', $set_message = false ) {

		// do nothing when debug mode is off or no message
		if ( 'off' == $this->debug_off() || ! $message ) {
			return;
		}

		// add debug message to woocommerce->errors/messages if checkout or both is enabled
		if ( $this->debug_checkout() && ! is_admin() ) {

			if ( 'message' === $type ) {

				SV_WC_Plugin_Compatibility::wc_add_notice( str_replace( "\n", "<br/>", htmlspecialchars( $message ) ), 'notice' );

			} else {

				// defaults to error message
				SV_WC_Plugin_Compatibility::wc_add_notice( str_replace( "\n", "<br/>", htmlspecialchars( $message ) ), 'error' );
			}
		}

		// set messages for next page load
		if ( $set_message && ( ! is_admin() || defined( 'DOING_AJAX' ) ) ) {
			SV_WC_Plugin_Compatibility::set_messages();
		}

		// add log message to WC logger if log/both is enabled
		if ( $this->debug_log() ) {
			$this->get_plugin()->log( $message, $this->get_id() );
		}
	}


	/**
	 * Returns true if $currency is accepted by this gateway
	 *
	 * @since 2.1
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


	/** Getters ******************************************************/


	/**
	 * Returns the payment gateway id
	 *
	 * @since 1.0
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
	 * @since 1.0
	 * @return string payment gateway id with dashes in place of underscores
	 */
	public function get_id_dasherized() {
		return str_replace( '_', '-', $this->get_id() );
	}


	/**
	 * Returns the parent plugin object
	 *
	 * @since 1.0
	 * @return SV_WC_Payment_Gateway the parent plugin object
	 */
	public function get_plugin() {
		return $this->plugin;
	}


	/**
	 * Returns the admin method title.  This should be the gateway name, ie
	 * 'Intuit QBMS'
	 *
	 * @since 1.0
	 * @see WC_Settings_API::$method_title
	 * @return string method title
	 */
	public function get_method_title() {
		return $this->method_title;
	}


	/**
	 * Returns true if the Card Security Code (CVV) field should be used on checkout
	 *
	 * @since 1.0
	 * @return boolean true if the Card Security Code field should be used on checkout
	 */
	public function csc_enabled() {
		return 'yes' == $this->enable_csc;
	}


	/**
	 * Returns true if settings should be inherited for this gateway
	 *
	 * @since 1.0
	 * @return boolean true if settings should be inherited for this gateway
	 */
	public function inherit_settings() {
		return 'yes' == $this->inherit_settings;
	}


	/**
	 * Add support for the named feature or features
	 *
	 * @since 1.0
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
	 * @since 1.0
	 * @param array $features array of supported feature names
	 */
	public function set_supports( $features ) {
		$this->supports = $features;
	}


	/**
	 * Returns true if this echeck gateway supports
	 *
	 * @since 1.0
	 * @param string $field_name check gateway field name, includes 'check_number', 'account_type'
	 * @return boolean true if this check gateway supports the named field
	 * @throws Exception if this is called on a non-check gateway
	 */
	public function supports_check_field( $field_name ) {

		if ( ! $this->is_echeck_gateway() ) {
			throw new Exception( 'Check method called on non-check gateway' );
		}

		return is_array( $this->supported_check_fields ) && in_array( $field_name, $this->supported_check_fields );

	}


	/**
	 * Gets the set of environments supported by this gateway.  All gateways
	 * support at least the production environment
	 *
	 * @since 1.0
	 * @return array associative array of environment id to name supported by this gateway
	 */
	public function get_environments() {

		// default set of environments consists of 'production'
		if ( ! isset( $this->environments ) ) {
			$this->environments = array( self::ENVIRONMENT_PRODUCTION => _x( 'Production', 'Supports environments', $this->text_domain ) );
		}

		return $this->environments;
	}


	/**
	 * Returns the environment setting, one of the $environments keys, ie
	 * 'production'
	 *
	 * @since 1.0
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
	 * @since 1.0
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
	 * @since 2.1
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
	 * @since 2.1
	 * @see WC_Payment_Gateway::$enabled
	 * @return boolean true if the gateway is enabled
	 */
	public function is_enabled() {
		return $this->enabled;
	}


	/**
	 * Returns the set of accepted currencies, or empty array if all currencies
	 * are accepted by this gateway
	 *
	 * @since 2.1
	 * @return array of currencies accepted by this gateway
	 */
	public function get_accepted_currencies() {
		return $this->currencies;
	}


	/**
	 * Returns true if all debugging is disabled
	 *
	 * @since 1.0
	 * @return boolean if all debuging is disabled
	 */
	public function debug_off() {
		return self::DEBUG_MODE_OFF === $this->debug_mode;
	}


	/**
	 * Returns true if debug logging is enabled
	 *
	 * @since 1.0
	 * @return boolean if debug logging is enabled
	 */
	public function debug_log() {
		return self::DEBUG_MODE_LOG === $this->debug_mode || self::DEBUG_MODE_BOTH === $this->debug_mode;
	}


	/**
	 * Returns true if checkout debugging is enabled.  This will cause debugging
	 * statements to be displayed on the checkout/pay pages
	 *
	 * @since 1.0
	 * @return boolean if checkout debugging is enabled
	 */
	public function debug_checkout() {
		return self::DEBUG_MODE_CHECKOUT === $this->debug_mode || self::DEBUG_MODE_BOTH === $this->debug_mode;
	}


	/**
	 * Returns the log file name
	 *
	 * @param string $handle optional log handle, defaults to plugin id
	 * @return string the log file name
	 */
	protected function log_file_name( $handle = null ) {
		if ( ! $handle ) {
			$handle = $this->get_id();
		}
		return $handle . '-' . sanitize_file_name( wp_hash( $handle ) ) . '.txt';
	}


	/**
	 * Returns true if this is a direct type gateway
	 *
	 * @since 1.0
	 * @return boolean if this is a direct payment gateway
	 */
	public function is_direct_gateway() {
		return false;
	}


	/**
	 * Returns true if this is a hosted type gateway
	 *
	 * @since 1.0
	 * @return boolean if this is a hosted IPN payment gateway
	 */
	public function is_hosted_gateway() {
		return false;
	}


	/**
	 * Returns the payment type for this gateway
	 *
	 * @since 2.1
	 * @return string the payment type, ie 'credit-card', 'echeck', etc
	 */
	public function get_payment_type() {
		return $this->payment_type;
	}


	/**
	 * Returns true if this is a credit card gateway
	 *
	 * @since 1.0
	 * @return boolean true if this is a credit card gateway
	 */
	public function is_credit_card_gateway() {
		return self::PAYMENT_TYPE_CREDIT_CARD == $this->get_payment_type();
	}


	/**
	 * Returns true if this is an echeck gateway
	 *
	 * @since 1.0
	 * @return boolean true if this is an echeck gateway
	 */
	public function is_echeck_gateway() {
		return self::PAYMENT_TYPE_ECHECK == $this->get_payment_type();
	}

}

endif;  // class exists check
