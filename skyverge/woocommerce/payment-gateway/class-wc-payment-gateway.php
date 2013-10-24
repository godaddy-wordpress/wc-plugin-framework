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
 * @copyright Copyright (c) 2013, SkyVerge, Inc.
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
 * ## Gateway Types (one and only one):
 *
 * + `direct` - supports direct (XML, REST, SOAP, custom, etc) communication
 * + `redirect-hosted-ipn` - supports redirecting to a gateway server for payment collection, with an IPN notification of the transaction result TODO
 * + `redirect` - supports collecting payment info and posting directly to gateway server with the transaction result included in the response TODO
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
 * TODO: "Pay Now" button feature.  Not sure whether you'd do this as a "supports" or as a "gateway type".  I'd lean towards "supports", but maxrice is the expert here
 *
 * @version 0.1
 */
abstract class SV_WC_Payment_Gateway extends WC_Payment_Gateway {


	/** Sends through sale and request for funds to be charged to cardholder's credit card. */
	const TRANSACTION_TYPE_CHARGE = 'charge';

	/** Sends through a request for funds to be "reserved" on the cardholder's credit card. A standard authorization is reserved for 2-5 days. Reservation times are determined by cardholder's bank. */
	const TRANSACTION_TYPE_AUTHORIZATION = 'authorization';

	/** The production environment identifier */
	const ENVIRONMENT_PRODUCTION = 'production';

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

	/** Gateway which supports redirecting to a gateway server for payment collection, with an IPN notification of the transaction result */
	const GATEWAY_TYPE_REDIRECT_HOSTED_IPN = 'redirect-hosted-ipn';

	/** Gateway which supports collecting payment info and posting directly to gateway server with the transaction result included in the response */
	const GATEWAY_TYPE_REDIRECT = 'redirect';

	/** Credit card payment type */
	const PAYMENT_TYPE_CREDIT_CARD = 'credit-card';

	/** eCheck payment type */
	const PAYMENT_TYPE_ECHECK = 'echeck';

	/** Credit card types feature */
	const FEATURE_CARD_TYPES = 'card_types';

	/** Tokenization feature */
	const FEATURE_TOKENIZATION = 'tokenization';

	/** Credit Card charge transaction feature */
	const FEATURE_CREDIT_CARD_CHARGE = 'charge';

	/** Credit Card authorization transaction feature */
	const FEATURE_CREDIT_CARD_AUTHORIZATION = 'authorization';

	/** Subscriptions feature */
	const FEATURE_SUBSCRIPTIONS = 'subscriptions';

	/** Subscription payment method change feature */
	const FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE = 'subscription_payment_method_change';

	/** Pre-orders feature */
	const FEATURE_PRE_ORDERS = 'pre-orders';


	/** @var SV_WC_Payment_Gateway_Plugin the parent plugin class */
	private $plugin;

	/** @var string plugin text domain */
	private $text_domain;

	/** @var string payment type, one of 'credit-card' or 'echeck' */
	private $payment_type;

	/** @var string gateway type, one of 'direct', 'redirect-hosted-ipn', 'redirect' */
	private $gateway_type;

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

	/** @var array array of cached user id to array of SV_WC_Payment_Gateway_Payment_Token token objects */
	protected $tokens;


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
	 * + `gateway_type` - string one of 'direct', 'redirect-hosted-ipn', 'redirect', defaults to 'direct'
	 * + `payment_type` - string one of 'credit-card' or 'echeck', defaults to 'credit-card'
	 * + `card_types` - array  associative array of card type to display name, used if the payment_type is 'credit-card' and the 'card_types' feature is supported.  Defaults to:
	 *   'VISA' => 'Visa', 'MC' => 'MasterCard', 'AMEX' => 'American Express', 'DISC' => 'Discover', 'DINERS' => 'Diners', 'JCB' => 'JCB'
	 * + `echeck_fields` - array of supported echeck fields, including 'check_number', 'account_type'
	 * + `environments` - associative array of environment id to display name, merged with default of 'production' => 'Production'
	 * + `currencies` -  array of currency codes this gateway is allowed for, defaults to all
	 * + `countries` -  array of two-letter country codes this gateway is allowed for, defaults to all
	 * + `shared_settings` - array of shared setting names, if any.  This can be used for instance when a single plugin supports both credit card and echeck payments, and the same credentials can be used for both gateways
	 *
	 * @since 0.1
	 * @param string $id the gateway id
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the parent plugin class
	 * @param string $text_domain the plugin text domain
	 * @param array $args gateway arguments
	 */
	public function __construct( $id, $plugin, $text_domain, $args ) {

		// first setup the gateway and payment type for this gateway
		$this->gateway_type = isset( $args['gateway_type'] ) ? $args['gateway_type'] : self::GATEWAY_TYPE_DIRECT;
		$this->payment_type = isset( $args['payment_type'] ) ? $args['payment_type'] : self::PAYMENT_TYPE_CREDIT_CARD;

		// default credit card gateways to supporting 'charge' transaction type, this could be overridden by the 'supports' constructor parameter to include (or only support) authorization
		if ( $this->is_credit_card_gateway() )
			$this->add_support( self::FEATURE_CREDIT_CARD_CHARGE );

		// required fields
		$this->id          = $id;
		$this->plugin      = $plugin;
		$this->text_domain = $text_domain;

		// optional parameters
		if ( isset( $args['method_title'] ) )       $this->method_title                 = $args['method_title'];        // @see WC_Settings_API::$method_title
		if ( isset( $args['method_description'] ) ) $this->method_description           = $args['method_description'];  // @see WC_Settings_API::$method_description
		if ( isset( $args['supports'] ) )           $this->set_supports( $args['supports'] );
		if ( isset( $args['card_types'] ) )         $this->available_card_types         = $args['card_types'];
		if ( isset( $args['echeck_fields'] ) )      $this->supported_check_fields       = $args['echeck_fields'];
		if ( isset( $args['environments'] ) )       $this->environments                 = array_merge( $this->get_environments(), $args['environments'] );
		if ( isset( $args['currencies'] ) )         $this->currencies                   = $args['currencies'];
		if ( isset( $args['countries'] ) )          $this->countries                    = $args['countries'];  // @see WC_Payment_Gateway::$countries
		if ( isset( $args['shared_settings'] ) )    $this->shared_settings              = $args['shared_settings'];

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
			add_action( 'woocommerce_update_options_payment_gateways',                    array( $this, 'process_admin_options' ) ); // WC < 2.0
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->get_id(), array( $this, 'process_admin_options' ) ); // WC >= 2.0
		}

		// add gateway.js checkout javascript
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ) );

		// watch for subscriptions support
		if ( $this->get_plugin()->is_subscriptions_active() ) {

			add_action( 'wc_payment_gateway_' . $this->get_id() . '_supports_' . self::FEATURE_SUBSCRIPTIONS,                      array( $this, 'add_subscriptions_support' ) );
			add_action( 'wc_payment_gateway_' . $this->get_id() . '_supports_' . self::FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE, array( $this, 'add_subscription_payment_method_change_support' ) );

		}

		// watch for pre-orders support
		if ( $this->get_plugin()->is_pre_orders_active() ) {

			add_action( 'wc_payment_gateway_' . $this->get_id() . '_supports_' . str_replace( '-', '_', self::FEATURE_PRE_ORDERS ), array( $this, 'add_pre_orders_support' ) );

		}

	}


	/**
	 * Loads the plugin configuration settings
	 *
	 * @since 0.1
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
	 * @since 0.1
	 */
	public function enqueue_js() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// load gateway.js checkout script
		wp_enqueue_script( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-js', $this->get_plugin()->get_plugin_url() . '/assets/js/frontend/wc-' . $this->get_plugin()->get_id_dasherized() . $suffix . '.js', array(), $this->get_plugin()->get_version(), true );

		// localize error messages
		$params = apply_filters( 'wc_gateway_' . $this->get_plugin()->get_id() + '_js_localize_script_params', $this->get_js_localize_script_params() );

		wp_localize_script( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-js', $this->get_plugin()->get_id() . '_params', $params );

	}


	/**
	 * Returns an array of javascript script params to localize for the
	 * checkout/pay page javascript.  Mostly used for i18n purposes
	 *
	 * @since 0.1
	 * @return array associative array of param name to value
	 */
	protected function get_js_localize_script_params() {

		return array(
				'card_number_missing'            => __( 'Card number is missing', $this->text_domain ),
				'card_number_invalid'            => __( 'Card number is invalid', $this->text_domain ),
				'card_number_digits_invalid'     => __( 'Card number is invalid (only digits allowed)', $this->text_domain ),
				'card_number_length_invalid'     => __( 'Card number is invalid (wrong length)', $this->text_domain ),
				'cvv_missing'                    => __( 'Card security code is missing', $this->text_domain ),
				'cvv_digits_invalid'             => __( 'Card security code is invalid (only digits are allowed)', $this->text_domain ),
				'cvv_length_invalid'             => __( 'Card security code is invalid (must be 3 or 4 digits)', $this->text_domain ),
				'card_exp_date_invalid'          => __( 'Card expiration date is invalid', $this->text_domain ),
				'check_number_digits_invalid'    => __( 'Check Number is invalid (only digits are allowed)', $this->text_domain ),
				'drivers_license_state_missing'  => __( 'Drivers license state is missing', $this->text_domain ),
				'drivers_license_number_missing' => __( 'Drivers license number is missing', $this->text_domain ),
				'drivers_license_number_invalid' => __( 'Drivers license number is invalid', $this->text_domain ),
				'account_number_missing'         => __( 'Account Number is missing', $this->text_domain ),
				'account_number_invalid'         => __( 'Account Number is invalid (only digits are allowed)', $this->text_domain ),
				'account_number_length_invalid'  => __( 'Account number is invalid (must be between 5 and 17 digits)', $this->text_domain ),
				'routing_number_missing'         => __( 'Routing Number is missing', $this->text_domain ),
				'routing_number_digits_invalid'  => __( 'Routing Number is invalid (only digits are allowed)', $this->text_domain ),
				'routing_number_length_invalid'  => __( 'Routing number is invalid (must be 9 digits)', $this->text_domain ),
			);

	}


	/**
	 * Adds a default simple pay page handler
	 *
	 * @since 0.1
	 */
	protected function add_pay_page_handler() {

		add_action( 'woocommerce_receipt_' . $this->get_id(), create_function( '$order', 'echo "<p>' . __( "Thank you for your order.", $this->text_domain ) . '</p>";' ) );

	}


	/**
	 * Initialize payment gateway settings fields
	 *
	 * @since 0.1
	 * @see WC_Settings_API::init_form_fields()
	 */
	public function init_form_fields() {

		// default to credit cards
		if ( $this->is_credit_card_gateway() ) {
			$default_title       = __( 'Credit Card', $this->text_domain );
			$default_description = __( 'Pay securely using your credit card.', $this->text_domain );
		} else {
			// echeck
			$default_title       = __( 'eCheck', $this->text_domain );
			$default_description = __( 'Pay securely using your checking account.', $this->text_domain );
		}

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
				'default'  => $default_title,
			),

			'description' => array(
				'title'    => __( 'Description', $this->text_domain ),
				'type'     => 'textarea',
				'desc_tip' => __( 'Payment method description that the customer will see during checkout.', $this->text_domain ),
				'default'  => $default_description,
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
		$this->form_fields = array_merge( $this->form_fields, $this->get_method_form_fields() );

		// add any common bottom fields
		$this->form_fields['debug_mode'] = array(
			'title'    => __( 'Debug Mode', $this->text_domain ),
			'type'     => 'select',
			'desc_tip' => __( 'Show Detailed Error Messages and API requests/responses on the checkout page and/or save them to the log for debugging purposes.', $this->text_domain ),
			'default'  => self::DEBUG_MODE_OFF,
			'options'  => array(
				self::DEBUG_MODE_OFF      => __( 'Off', $this->text_domain ),
				self::DEBUG_MODE_CHECKOUT => __( 'Show on Checkout Page', $this->text_domain ),
				self::DEBUG_MODE_LOG      => __( 'Save to Log', $this->text_domain ),
				self::DEBUG_MODE_BOTH     => __( 'Both', $this->text_domain )
			),
		);

		// add the special 'shared-settings-field' class name to any shared settings fields
		foreach ( $this->shared_settings as $field_name ) {

			$this->form_fields[ $field_name ]['class'] = trim( $this->form_fields[ $field_name ]['class'] . ' shared-settings-field' );

		}

	}


	/**
	 * Returns an array of form fields specific for this method.
	 *
	 * To add environment-dependent fields, include the 'class' form field argument
	 * with 'environment-field production-field' where "production" matches a
	 * key from the environments member
	 *
	 * @since 0.1
	 * @return array of form fields
	 */
	abstract protected function get_method_form_fields();


	/**
	 * Adds the gateway environment form fields
	 *
	 * @since 0.1
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
	 * @since 0.1
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
			'title'   => __( 'Share connection settings', $this->text_domain ),
			'type'    => 'checkbox',
			'label'   => $this->is_credit_card_gateway() ? __( 'Use connection/authentication settings from echeck gateway', $this->text_domain ) :  __( 'Use connection/authentication settings from credit card gateway', $this->text_domain ),
			'default' => count( $configured_other_gateway_ids ) > 0 ? 'yes' : 'no',
			'disabled' => count( $inherit_settings_other_gateway_ids ) > 0 ? true : false,
			'description' => count( $inherit_settings_other_gateway_ids ) > 0 ? ( $this->is_credit_card_gateway() ? __( 'Disabled because the echeck gateway is using these settings', $this->text_domain ) : __( 'Disabled because the credit card gateway is using these settings', $this->text_domain ) ) : '',
		);

		return $form_fields;
	}


	/**
	 * Adds the enable Card Security Code form fields
	 *
	 * @since 0.1
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_csc_form_fields( $form_fields ) {

		$form_fields['enable_csc'] = array(
			'title'   => __( 'Card Verification (CSC)', $this->text_domain ),
			'label'   => __( 'Display the Card Security Code (CV2) field on checkout', $this->text_domain ),
			'type'    => 'checkbox',
			'default' => 'yes',
		);

		return $form_fields;
	}


	/**
	 * Display settings page with some additional javascript for hiding conditional fields
	 *
	 * @since 0.1
	 * @see WC_Settings_API::admin_options()
	 */
	public function admin_options() {

		global $woocommerce;

		parent::admin_options();

		// if there's more than one environment include the environment settings switcher code
		if ( count( $this->get_environments() ) > 1 ) {

			// add inline javascript
			ob_start();
			?>
				$( '#woocommerce_<?php echo $this->get_id(); ?>_environment' ).change( function() {

					// if the fields are all hidden because we're inheriting settings from the other gateway, then there's nothing to do
					if ( $( '#woocommerce_<?php echo $this->get_id(); ?>_inherit_settings' ).is( ':checked' ) )
						return;

					var environment = $( this ).val();

					// hide all environment-dependant fields
					$( '.environment-field' ).closest( 'tr' ).hide();

					// show the currently configured environment fields
					$( '.' + environment + '-field' ).closest( 'tr' ).show();

				} ).change();
			<?php

			$woocommerce->add_inline_js( ob_get_clean() );

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
						$( '#woocommerce_<?php echo $this->get_id(); ?>_environment' ).change();
					}

				} ).change();
			<?php

			$woocommerce->add_inline_js( ob_get_clean() );

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
	 * @since 0.1
	 * @see WC_Payment_Gateway::is_available()
	 * @return true if this gateway is available for checkout, false otherwise
	 */
	public function is_available() {

		global $woocommerce;

		// is enabled check
		$is_available = parent::is_available();

		// proper configuration
		if ( ! $this->is_configured() )
			$is_available = false;

		// all plugin dependencies met
		if ( count( $this->get_plugin()->get_missing_dependencies() ) > 0 )
			$is_available = false;

		// any required currencies?
		if ( $this->currencies && ! in_array( get_option( 'woocommerce_currency' ), $this->currencies ) )
			$is_available = false;

		// any required countries?
		if ( $this->countries && $woocommerce->customer && $woocommerce->customer->get_country() && ! in_array( $woocommerce->customer->get_country(), $this->countries ) )
			$is_available = false;

		return apply_filters( 'wc_gateway_' . $this->get_id() + '_is_available', $is_available );
	}


	/**
	 * Returns true if the gateway is properly configured to perform transactions
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::is_configured()
	 * @return boolean true if the gateway is properly configured
	 */
	protected function is_configured() {
		return true;
	}


	/**
	 * Returns the gateway icon markup
	 *
	 * @since 0.1
	 * @see WC_Payment_Gateway::get_icon()
	 * @return string icon markup
	 */
	public function get_icon() {

		global $woocommerce;

		$icon = '';

		// specific icon
		if ( $this->icon ) {

			// use icon provided by filter
			$icon = '<img src="' . esc_url( $woocommerce->force_ssl( $this->icon ) ) . '" alt="' . esc_attr( $this->title ) . '" />';

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
		if ( ! $icon && $this->is_check_gateway() ) {

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
	 * @since 0.1
	 * @param string $type the payment method cc type or name
	 * @return string the image URL or null
	 */
	public function get_payment_method_image_url( $type ) {

		global $woocommerce;

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

		// first, is the card image available within the plugin?
		if ( is_readable( $this->get_plugin()->get_plugin_path() . '/assets/images/card-' . $image_type . '.png' ) )
			return $woocommerce->force_ssl( $this->get_plugin()->get_plugin_url() ) . '/assets/images/card-' . $image_type . '.png';

		// default: is the card image available within the framework?
		// NOTE: I don't particularly like hardcoding this path, but I don't see any real way around it
		if ( is_readable( $this->get_plugin()->get_plugin_path() . '/' . $this->get_framework_image_path() . 'card-' . $image_type . '.png' ) )
			return $woocommerce->force_ssl( $this->get_plugin()->get_plugin_url() ) . '/' . $this->get_framework_image_path() . 'card-' . $image_type . '.png';

		return null;

	}


	/**
	 * Returns the relative path to the framework image directory, with a
	 * trailing slash
	 *
	 * @since 0.1
	 * @return string relative path to framework image directory
	 */
	public function get_framework_image_path() {
		return 'lib/skyverge/woocommerce/payment-gateway/assets/images/';
	}


	/**
	 * Validate the payment fields when processing the checkout
	 *
	 * NOTE: if we want to bring billing field validation (ie length) into the
	 * fold, see the Elavon VM Payment Gateway for a sample implementation
	 *
	 * @since 0.1
	 * @see WC_Payment_Gateway::validate_fields()
	 * @return bool true if fields are valid, false otherwise
	 */
	public function validate_fields() {

		global $woocommerce;

		$is_valid = parent::validate_fields();

		if ( $this->supports_tokenization() ) {

			// tokenized transaction?
			if ( $this->get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) {

				// unknown token?
				if ( ! $this->has_payment_token( get_current_user_id(), $this->get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) ) {
					$woocommerce->add_error( __( 'Payment error, please try another payment method or contact us to complete your transaction.', $this->text_domain ) );
					$is_valid = false;
				}

				// no more validation to perform
				return $is_valid;
			}
		}

		// validate remaining payment fields
		if ( $this->is_credit_card_gateway() )
			return $this->validate_credit_card_fields( $is_valid );
		else
			return $this->validate_check_fields( $is_valid );
	}


	/**
	 * Returns true if the posted credit card fields are valid, false otherwise
	 *
	 * @since 0.1
	 * @param boolean $is_valid true if the fields are valid, false otherwise
	 * @return boolean true if the fields are valid, false otherwise
	 */
	protected function validate_credit_card_fields( $is_valid ) {

		global $woocommerce;

		$card_number      = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-account-number' );
		$expiration_month = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-exp-month' );
		$expiration_year  = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-exp-year' );
		$csc              = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-csc' );

		// validate card number
		$card_number = str_replace( array( ' ', '-' ), '', $card_number );

		if ( empty( $card_number ) ) {

			$woocommerce->add_error( __( 'Card number is missing', $this->text_domain ) );
			$is_valid = false;

		} else {

			if ( strlen( $card_number ) < 12 || strlen( $card_number ) > 19 ) {
				$woocommerce->add_error( __( 'Card number is invalid (wrong length)', $this->text_domain ) );
				$is_valid = false;
			}

			if ( ! ctype_digit( $card_number ) ) {
				$woocommerce->add_error( __( 'Card number is invalid (only digits allowed)', $this->text_domain ) );
				$is_valid = false;
			}

			if ( ! $this->luhn_check( $card_number ) ) {
				$woocommerce->add_error( __( 'Card number is invalid', $this->text_domain ) );
				$is_valid = false;
			}

		}

		// validate expiration data
		$current_year  = date( 'Y' );
		$current_month = date( 'n' );

		if ( ! ctype_digit( $expiration_month ) || ! ctype_digit( $expiration_year ) ||
			$expiration_month > 12 ||
			$expiration_month < 1 ||
			$expiration_year < $current_year ||
			( $expiration_year == $current_year && $expiration_month < $current_month ) ||
			$expiration_year > $current_year + 20
		) {
			$woocommerce->add_error( __( 'Card expiration date is invalid', $this->text_domain ) );
			$is_valid = false;
		}

		// validate card security code
		if ( $this->csc_enabled() ) {
			$is_valid = $this->validate_csc( $csc ) && $is_valid;
		}

		return $is_valid;
	}


	/**
	 * Validates the provided Card Security Code, adding user error messages as
	 * needed
	 *
	 * @since 0.1
	 * @param string $csc the customer-provided card security code
	 * @return boolean true if the card security code is valid, false otherwise
	 */
	protected function validate_csc( $csc ) {

		global $woocommerce;

		$is_valid = true;

		// validate security code
		if ( empty( $csc ) ) {

			$woocommerce->add_error( __( 'Card security code is missing', $this->text_domain ) );
			$is_valid = false;

		} else {

			// digit validation
			if ( ! ctype_digit( $csc ) ) {
				$woocommerce->add_error( __( 'Card security code is invalid (only digits are allowed)', $this->text_domain ) );
				$is_valid = false;
			}

			// length validation
			if ( strlen( $csc ) < 3 || strlen( $csc ) > 4 ) {
				$woocommerce->add_error( __( 'Card security code is invalid (must be 3 or 4 digits)', $this->text_domain ) );
				$is_valid = false;
			}

		}

		return $is_valid;

	}


	/**
	 * Returns true if the posted echeck fields are valid, false otherwise
	 *
	 * @since 0.1
	 * @param boolean $is_valid true if the fields are valid, false otherwise
	 */
	protected function validate_check_fields( $is_valid ) {

		global $woocommerce;

		$account_number         = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-account-number' );
		$routing_number         = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-routing-number' );
		$drivers_license_number = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-drivers-license-number' );
		$drivers_license_state  = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-drivers-license-state' );

		// optional fields (excluding account type for now)
		$check_number = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-check-number' );

		// routing number exists?
		if ( empty( $routing_number ) ) {

			$woocommerce->add_error( __( 'Routing Number is missing', $this->text_domain ) );
			$is_valid = false;

		} else {

			// routing number digit validation
			if ( ! ctype_digit( $routing_number ) ) {
				$woocommerce->add_error( __( 'Routing Number is invalid (only digits are allowed)', $this->text_domain ) );
				$is_valid = false;
			}

			// routing number length validation
			if ( 9 != strlen( $routing_number ) ) {
				$woocommerce->add_error( __( 'Routing number is invalid (must be 9 digits)', $this->text_domain ) );
				$is_valid = false;
			}

		}

		// account number exists?
		if ( empty( $account_number ) ) {

			$woocommerce->add_error( __( 'Account Number is missing', $this->text_domain ) );
			$is_valid = false;

		} else {

			// account number digit validation
			if ( ! ctype_digit( $account_number ) ) {
				$woocommerce->add_error( __( 'Account Number is invalid (only digits are allowed)', $this->text_domain ) );
				$is_valid = false;
			}

			// account number length validation
			if ( strlen( $account_number ) < 5 || strlen( $account_number ) > 17 ) {
				$woocommerce->add_error( __( 'Account number is invalid (must be between 5 and 17 digits)', $this->text_domain ) );
				$is_valid = false;
			}
		}

		// drivers license number validation
		if ( empty( $drivers_license_number ) ) {
			$woocommerce->add_error( __( 'Drivers license number is missing', $this->text_domain ) );
			$is_valid = false;
		} else if ( ! preg_match( '/^[a-zA-Z0-9 -]+$/', $drivers_license_number ) ) {
			$woocommerce->add_error( __( 'Drivers license number is invalid', $this->text_domain ) );
			$is_valid = false;
		}

		// drivers license state validation
		if ( empty( $drivers_license_state ) ) {
			$woocommerce->add_error( __( 'Drivers license state is missing', $this->text_domain ) );
			$is_valid = false;
		}

		// optional check number validation
		if ( ! empty( $check_number ) && ! ctype_digit( $check_number ) ) {
			$woocommerce->add_error( __( 'Check Number is invalid (only digits are allowed)', $this->text_domain ) );
			$is_valid = false;
		}

		return $is_valid;

	}


	/**
	 * Handles payment processing
	 *
	 * @since 0.1
	 */
	public function process_payment( $order_id ) {

		global $woocommerce;

		// give other actors an opportunity to intercept and implement the process_payment() call for this transaction
		if ( true !== ( $result = apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_process_payment', true, $order_id, $this ) ) ) {
			return $result;
		}

		// add payment information to order
		$order = $this->get_order( $order_id );

		try {

			// registered customer checkout (already logged in or creating account at checkout)
			if ( $this->supports_tokenization() && 0 !== $order->user_id && $this->should_tokenize_payment_method() ) {
				$order = $this->create_payment_token( $order );
			}

			// payment failures are handled internally by do_transaction()
			// the order amount will be $0 if a WooCommerce Subscriptions free trial product is being processed
			// note that customer id & payment token are saved to order when create_payment_token() is called
			if ( 0 == $order->payment_total || $this->do_transaction( $order ) ) {

				if ( 'on-hold' == $order->status )
					$order->reduce_order_stock(); // reduce stock for held orders, but don't complete payment
				else
					$order->payment_complete(); // mark order as having received payment

				$woocommerce->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}

		} catch ( Exception $e ) {

			$this->mark_order_as_failed( $order, $e->getMessage() );

		}
	}


	/**
	 * Add payment and transaction information as class members of WC_Order
	 * instance.  The standard information that can be added includes:
	 *
	 * $order->payment_total           - the payment total
	 * $order->customer_id             - optional payment gateway customer id (useful for tokenized payments, etc)
	 * $order->payment->type           - one of 'credit_card' or 'check'
	 * $order->payment->account_number - the credit card or checking account number
	 * $order->payment->routing_number - account routing number (check transactions only)
	 * $order->payment->account_type   - optional type of account one of 'checking' or 'savings' if type is 'check'
	 * $order->payment->card_type      - optional card type, ie one of 'visa', etc
	 * $order->payment->exp_month      - the credit card expiration month (for credit card gateways)
	 * $order->payment->exp_year       - the credit card expiration year (for credit card gateways)
	 * $order->payment->csc            - the card security code (for credit card gateways)
	 * $order->payment->check_number   - optional check number (check transactions only)
	 * $order->payment->driver_license_number - optional driver license number (check transactions only)
	 * $order->payment->driver_license_state  - optional driver license state code (check transactions only)
	 * $order->payment->token          - payment token (for tokenized transactions)
	 *
	 * Note that not all gateways will necessarily pass or require all of the
	 * above.  These represent the most common attributes used among a variety
	 * of gateways, it's up to the specific gateway implementation to make use
	 * of, or ignore them, or add custom ones by overridding this method.
	 *
	 * Note: we could consider adding birthday to the list here, but do any gateways besides NETBilling use this one?
	 *
	 * @since 0.1
	 * @param int $order_id order ID being processed
	 * @return WC_Order object with payment and transaction information attached
	 */
	protected function get_order( $order_id ) {

		$order = new WC_Order( $order_id );

		// set payment total here so it can be modified for later by add-ons like subscriptions which may need to charge an amount different than the get_total()
		$order->payment_total = $order->get_total();

		// logged in customer?
		if ( 0 !== $order->user_id && false !== ( $customer_id = $this->get_customer_id( $order->user_id ) ) ) {
			$order->customer_id = $customer_id;
		}

		// add payment info
		$order->payment = new stdClass();

		// payment info
		if ( $this->get_post( 'wc-' . $this->get_id_dasherized() . '-account-number' ) && ! $this->get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) {

			// common attributes
			$order->payment->account_number = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-account-number' );

			if ( $this->is_credit_card_gateway() ) {

				// credit card specific attributes
				$order->payment->type           = 'credit_card';
				$order->payment->card_type      = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-card-type' );
				$order->payment->exp_month      = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-exp-month' );
				$order->payment->exp_year       = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-exp-year' );

				if ( $this->csc_enabled() )
					$order->payment->csc        = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-csc' );

			} else {

				// echeck specific attributes
				$order->payment->type                  = 'check';
				$order->payment->routing_number        = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-routing-number' );
				$order->payment->account_type          = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-account-type' );
				$order->payment->check_number          = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-check-number' );
				$order->payment->driver_license_number = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-driver-license-number' );
				$order->payment->driver_license_state  = $this->get_post( 'wc-' . $this->get_id_dasherized() . '-driver-license-state' );

			}

		} elseif ( $this->get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) {

			// paying with tokenized payment method (we've already verified that this token exists in the validate_fields method)
			$token = $this->get_payment_token( $order->user_id, $this->get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) );

			$order->payment->token          = $token->get_token();
			$order->payment->account_number = $token->get_last_four();

			if ( $this->is_credit_card_gateway() ) {

				// credit card specific attributes
				$order->payment->type         = 'credit_card';
				$order->payment->card_type    = $token->get_card_type();
				$order->payment->exp_month    = $token->get_exp_month();
				$order->payment->exp_year     = $token->get_exp_year();

			} else {

				// echeck specific attributes
				$order->payment->type         = 'check';
				$order->payment->account_type = $token->get_account_type();

			}

			// make this the new default payment token
			$this->set_default_payment_token( $order->user_id, $token );
		}

		// allow other actors to modify the order object
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_get_order', $order, $this );

	}


	/**
	 * Performs a check transaction for the given order and returns the
	 * result
	 *
	 * @since 0.1
	 * @param WC_Order $order the order object
	 * @return SV_WC_Payment_Gateway_API_Response the response
	 * @throws Exception network timeouts, etc
	 */
	protected function do_check_transaction( $order ) {

		$response = $this->get_api()->check_debit( $order );

		// success! update order record
		if ( $response->transaction_approved() ) {

			$last_four = substr( $order->payment->account_number, -4 );

			// check order note.  there may not be an account_type available, but that's fine
			$message = sprintf( __( '%s Check Transaction Approved: %s account ending in %s', $this->text_domain ), $this->get_method_title(), $order->payment->account_type, $last_four );

			// optional check number
			if ( $order->payment->check_number ) {
				$message .= '. ' . sprintf( __( 'Check number %s', $this->text_domain ), $order->payment->check_number );
			}

			// adds the transaction id (if any) to the order note
			if ( $response->get_transaction_id() ) {
				$message .= ' ' . sprintf( __( '(Transaction ID %s)', $this->text_domain ), $response->get_transaction_id() );
			}

			$order->add_order_note( $message );

		}

		return $response;

	}


	/**
	 * Performs a credit card transaction for the given order and returns the
	 * result
	 *
	 * @since 0.1
	 * @param WC_Order $order the order object
	 * @return SV_WC_Payment_Gateway_API_Response the response
	 * @throws Exception network timeouts, etc
	 */
	protected function do_credit_card_transaction( $order ) {

		if ( $this->perform_credit_card_charge() ) {
			$response = $this->get_api()->credit_card_charge( $order );
		} else {
			$response = $this->get_api()->credit_card_authorization( $order );
		}

		// success! update order record
		if ( $response->transaction_approved() ) {

			$last_four = substr( $order->payment->account_number, -4 );

			// credit card order note
			$message = sprintf(
				__( '%s %s Approved: %s ending in %s (expires %s)', $this->text_domain ),
				$this->get_method_title(),
				$this->perform_credit_card_authorization() ? 'Authorization' : 'Charge',
				isset( $order->payment->card_type ) && $order->payment->card_type ? SV_WC_Payment_Gateway_Payment_Token::type_to_name( $order->payment->card_type ) : 'card',
				$last_four,
				$order->payment->exp_month . '/' . substr( $order->payment->exp_year, -2 )
			);

			// adds the transaction id (if any) to the order note
			if ( $response->get_transaction_id() ) {
				$message .= ' ' . sprintf( __( '(Transaction ID %s)', $this->text_domain ), $response->get_transaction_id() );
			}

			$order->add_order_note( $message );

		}

		return $response;

	}


	/**
	 * Create a transaction
	 *
	 * @since 0.1
	 * @param WC_Order $order the order object
	 * @return bool true if transaction was successful, false otherwise
	 * @throws Exception network timeouts, etc
	 */
	protected function do_transaction( $order ) {

		// perform the credit card or check transaction
		$response = $this->is_credit_card_gateway() ? $this->do_credit_card_transaction( $order ) : $this->do_check_transaction( $order );

		// handle the response
		if ( $response->transaction_approved() || $response->transaction_held() ) {

			// add the standard transaction data
			$this->add_transaction_data( $order, $response );

			// allow the concrete class to add any gateway-specific transaction data to the order
			$this->add_payment_gateway_transaction_data( $order, $response );

			// if the transaction was held (ie fraud validation failure) mark it as such
			if ( $response->transaction_held() ) {
				$this->mark_order_as_held( $order, $response->get_status_message() );
			}

			return true;

		} else { // failure

			$this->mark_order_as_failed( $order, sprintf( '%s: %s', $response->get_status_code(), $response->get_status_message() ) );

			return false;
		}
	}


	/**
	 * Adds the standard transaction data to the order
	 *
	 * @since 0.1
	 * @param WC_Order $order the order object
	 * @param SV_WC_Payment_Gateway_API_Response|null $response optional transaction response
	 */
	protected function add_transaction_data( $order, $response = null ) {

		// payment info
		if ( isset( $order->payment->token ) && $order->payment->token )
			update_post_meta( $order->id, '_wc_' . $this->get_id() . '_payment_token', $order->payment->token );

		// account number
		if ( isset( $order->payment->account_number ) && $order->payment->account_number )
			update_post_meta( $order->id, '_wc_' . $this->get_id() . '_account_four', substr( $order->payment->account_number, -4 ) );

		if ( $this->is_credit_card_gateway() ) {

			// credit card gateway data

			if ( isset( $order->payment->exp_year ) && $order->payment->exp_year && isset( $order->payment->exp_month ) && $order->payment->exp_month )
				update_post_meta( $order->id, '_wc_' . $this->get_id() . '_card_expiry_date', $order->payment->exp_year . '-' . $order->payment->exp_month );

			if ( isset( $order->payment->card_type ) && $order->payment->card_type )
				update_post_meta( $order->id, '_wc_' . $this->get_id() . '_card_type', $order->payment->card_type );

		} else {

			// checking gateway data

			// optional account type (checking/savings)
			if ( isset( $order->payment->account_type ) && $order->payment->account_type )
				update_post_meta( $order->id, '_wc_' . $this->get_id() . '_account_type', $order->payment->account_type );

			// optional check number
			if ( isset( $order->payment->check_number ) && $order->payment->check_number )
				update_post_meta( $order->id, '_wc_' . $this->get_id() . '_check_number', $order->payment->account_type );

		}

		// if there's more than one environment
		if ( count( $this->get_environments() ) > 1 )
			update_post_meta( $order->id, '_wc_' . $this->get_id() . '_environment', $this->get_environment() );

		// if there is a payment gateway customer id, set it to the order (we don't append the environment here like we do for the user meta, because it's available from the 'environment' order meta already)
		if ( isset( $order->customer_id ) && $order->customer_id )
			update_post_meta( $order->id, '_wc_' . $this->get_id() . '_customer_id', $order->customer_id );

	}


	/**
	 * Adds any gateway-specific transaction data to the order
	 *
	 * @since 0.1
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
	 * @since 0.1
	 * @param WC_Order $order the order
	 * @param string $message a message to display within the order note
	 */
	protected function mark_order_as_held( $order, $message ) {

		global $woocommerce;

		$order_note = sprintf( __( '%s Transaction Held for Review (%s)', $this->text_domain ), $this->get_method_title(), $message );

		// mark order as held
		if ( 'on-hold' != $order->status )
			$order->update_status( 'on-hold', $order_note );
		else
			$order->add_order_note( $order_note );

		$this->add_debug_message( $message, 'message', true );

		// we don't have control over the "Thank you. Your order has been received." message shown on the "Thank You" page.  Yet
		$woocommerce->add_message( __( 'Your order has been received and is being reviewed.  Thank you for your business.', $this->text_domain ) );
		$woocommerce->set_messages();

	}


	/**
	 * Mark the given order as failed and set the order note
	 *
	 * @since 0.1
	 * @param WC_Order $order the order
	 * @param string $error_message a message to display inside the "Payment Failed" order note
	 */
	protected function mark_order_as_failed( $order, $error_message ) {

		global $woocommerce;

		$order_note = sprintf( __( '%s Payment Failed (%s)', $this->text_domain ), $this->get_method_title(), $error_message );

		// Mark order as failed if not already set, otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
		if ( 'failed' != $order->status )
			$order->update_status( 'failed', $order_note );
		else
			$order->add_order_note( $order_note );

		$this->add_debug_message( $error_message, 'error' );

		$woocommerce->add_error( __( 'An error occurred, please try again or try an alternate form of payment.', $this->text_domain ) );

	}


	/**
	 * Gets/sets the payment gateway customer id, this defaults to wc-{user id}
	 * and retrieves/stores to the user meta named by get_customer_id_user_meta_name()
	 * This can be overridden for gateways that use some other value, or made to
	 * return false for gateways that don't support a customer id.
	 *
	 * If you want to check for the existance of a customer id, pass false for
	 * the third parameter
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::get_customer_id_user_meta_name()
	 * @param int $user_id wordpress user identifier
	 * @param string $environment_id optional environment id, defaults to current environment
	 * @param boolean $autocreate optional, whether to automatically create the customer id if it doesn't already exist.  Defaults to true.
	 * @return string payment gateway customer id
	 */
	public function get_customer_id( $user_id, $environment_id = null, $autocreate = true ) {

		// default to current environment
		if ( is_null( $environment_id ) )
			$environment_id = $this->get_environment();

		// does an id already exist for this user?
		$customer_id = get_user_meta( $user_id, $this->get_customer_id_user_meta_name( $environment_id ), true );

		if ( ! $customer_id && $autocreate ) {
			// generate a new customer id.  We prepend the wordpress user
			//  id with 'wc-' on the theory that it will be less likely to
			//  clash with a user id generated by some other payment channel
			$customer_id = 'wc-' . $user_id;

			$this->update_customer_id( $user_id, $customer_id, $environment_id );
		}

		return $customer_id;

	}


	/**
	 * Updates the payment gateway customer id for the given $environment, or
	 * for the plugin current environment
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::get_customer_id()
	 * @param int $user_id wordpress user identifier
	 * @param string payment gateway customer id
	 * @param string $environment_id optional environment id, defaults to current environment
	 * @return boolean|int false if no change was made (if the new value was the same as previous value) or if the update failed, meta id if the value was different and the update a success
	 */
	public function update_customer_id( $user_id, $customer_id, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) )
			$environment_id = $this->get_environment();

		return update_user_meta( $user_id, $this->get_customer_id_user_meta_name( $environment_id ), $customer_id );

	}


	/**
	 * Returns a payment gateway customer id for a guest customer.  This
	 * defaults to wc-guest-{order id} but can be overridden for gateways that
	 * use some other value, or made to return false for gateways that don't
	 * support a customer id
	 *
	 * @since 0.1
	 * @param WC_Order $order order object
	 * @return string payment gateway guest customer id
	 */
	public function get_guest_customer_id( $order ) {

		// is there a customer id already tied to this order?
		$customer_id = get_post_meta( $order->id, '_wc_' . $this->get_id() . '_customer_id', true );

		if ( $customer_id )
			return $customer_id;

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
	 * @since 0.1
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
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway_Plugin::order_meta_box_transaction_link()
	 * @see SV_WC_Payment_Gateway::get_transaction_url()
	 * @param WC_Order $order the order object
	 */
	public function order_meta_box_transaction_link( $order ) {

		if ( $url = $this->get_transaction_url( $order ) ) {

			?>
			<li class="wide" style="text-align: center;">
				<a class="button tips" href="<?php echo esc_url( $url ); ?>" target="_blank" data-tip="<?php esc_attr_e( 'View this transaction in your merchant account', $this->text_domain ); ?>" style="cursor: pointer !important;"><?php printf( __( 'View in %s', $this->text_domain ), $this->get_method_title() ); ?></a>
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
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway_Plugin::order_meta_box_transaction_link()
	 * @see SV_WC_Payment_Gateway::order_meta_box_transaction_link()
	 * @param WC_Order $order the order object
	 * @return string transaction url or null if not supported
	 */
	public function get_transaction_url( $order ) {

		// method stub
		return null;

	}


	/** Subscriptions feature ******************************************************/


	/**
	 * Returns true if this gateway with its current configuration supports subscriptions
	 *
	 * @since 0.1
	 * @return boolean true if the gateway supports subscriptions
	 */
	public function supports_subscriptions() {

		return $this->supports( self::FEATURE_SUBSCRIPTIONS ) && $this->supports_tokenization() && $this->tokenization_enabled();

	}


	/**
	 * Adds support for subscriptions by hooking in some necessary actions
	 *
	 * @since 0.1
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if subscriptions are not supported by this gateway or its current configuration
	 */
	public function add_subscriptions_support() {

		if ( ! $this->supports_subscriptions() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Subscriptions not supported by gateway', $this->text_domain ) );

		// force tokenization when needed
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_tokenization_forced', array( $this, 'subscriptions_tokenization_forced' ) );

		// add subscriptions data to the order object
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_get_order', array( $this, 'subscriptions_get_order' ) );

		// process scheduled subscription payments
		add_action( 'scheduled_subscription_payment_' . $this->get_id(), array( $this, 'process_subscription_renewal_payment' ), 10, 3 );

		// prevent unnecessary order meta from polluting parent renewal orders
		add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'remove_subscription_renewal_order_meta' ), 10, 4 );

	}


	/**
	 * Force tokenization for subscriptions
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::tokenization_forced()
	 * @param boolean $force_tokenization whether tokenization should be forced
	 * @return boolean true if tokenization should be forced, false otherwise
	 */
	public function subscriptions_tokenization_forced( $force_tokenization ) {

		if ( class_exists( 'WC_Subscriptions_Cart' ) &&  WC_Subscriptions_Cart::cart_contains_subscription() ) {
			$force_tokenization = true;
		}

		return $force_tokenization;

	}


	/**
	 * Adds subscriptions data to the order object
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::get_order()
	 * @param WC_Order $order the order
	 * @return WC_Order the orders
	 */
	public function subscriptions_get_order( $order ) {

		// subscriptions total, ensuring that we have a decimal point, even if it's 1.00
		if ( $this->supports_subscriptions() && WC_Subscriptions_Order::order_contains_subscription( $order->id ) ) {
			$order->payment_total = number_format( (double) WC_Subscriptions_Order::get_total_initial_payment( $order ), 2, '.', '' );
		}

		// load any required members that we might not have
		if ( ! isset( $order->payment->token ) || ! $order->payment->token )
			$order->payment->token = get_post_meta( $order->id, '_wc_' . $this->get_id() . '_payment_token', true );

		if ( ! isset( $order->customer_id ) || ! $order->customer_id )
			$order->customer_id = get_post_meta( $order->id, '_wc_' . $this->get_id() . '_customer_id', true );

		// ensure the payment token is still valid
		if ( ! $this->has_payment_token( $order->user_id, $order->payment->token ) ) {
			$order->payment->token = null;
		} else {

			// get the token object
			$token = $this->get_payment_token( $order->user_id, $order->payment->token );

			if ( ! isset( $order->payment->account_number ) || ! $order->payment->account_number )
				$order->payment->account_number = $token->get_last_four();

			if ( $this->is_credit_card_gateway() ) {

				// credit card token

				if ( ! isset( $order->payment->card_type ) || ! $order->payment->card_type )
					$order->payment->card_type = $token->get_card_type();

				if ( ! isset( $order->payment->exp_month ) || ! $order->payment->exp_month )
					$order->payment->exp_month = $token->get_exp_month();

				if ( ! isset( $order->payment->exp_month ) || ! $order->payment->exp_month )
					$order->payment->exp_month = $token->get_exp_month();

			} else {

				// check token

				if ( ! isset( $order->payment->account_type ) || ! $order->payment->account_type )
					$order->payment->account_type = $token->get_account_type();

			}

		}

		return $order;

	}


	/**
	 * Process subscription renewal
	 *
	 * @since 0.1
	 * @param float $amount_to_charge subscription amount to charge, could include multiple renewals if they've previously failed and the admin has enabled it
	 * @param WC_Order $order original order containing the subscription
	 * @param int $product_id the subscription product id
	 */
	public function process_subscription_renewal_payment( $amount_to_charge, $order, $product_id ) {

		try {

			// set order defaults
			$order = $this->get_order( $order->id );

			// set the amount to charge, ensuring that we have a decimal point, even if it's 1.00
			$order->payment_total = number_format( (double) $amount_to_charge, 2, '.', '' );

			// required
			if ( ! $order->payment->token || ! $order->customer_id )
				throw new Exception( __( 'Subscription Renewal: Customer ID or Payment Token is missing/invalid.', $this->text_domain ) );

			// get the token, we've already verified it's good
			$token = $this->get_payment_token( $order->user_id, $order->payment->token );

			// perform the transaction
			if ( $this->is_credit_card_gateway() ) {

				if ( $this->perform_credit_card_charge() ) {
					$response = $this->get_api()->credit_card_charge( $order );
				} else {
					$response = $this->get_api()->credit_card_authorization( $order );
				}

			} else {

				$response = $this->get_api()->check_debit( $order );

			}

			// check for success  TODO: handle transaction held
			if ( $response->transaction_approved() ) {

				// order note based on gateway type
				if ( $this->is_credit_card_gateway() ) {
					$message = sprintf(
						__( '%s %s Subscription Renewal Payment Approved: %s ending in %s (expires %s)', $this->text_domain ),
						$this->get_method_title(),
						$this->perform_credit_card_authorization() ? 'Authorization' : 'Charge',
						$token->get_card_type() ? $token->get_type_full() : 'card',
						$token->get_last_four(),
						$token->get_exp_month() . '/' . $token->get_exp_year()
					);
				} elseif( $this->is_check_gateway() ) {

					// there may or may not be an account type (checking/savings) available, which is fine
					$message = sprintf( __( '%s Check Subscription Renewal Payment Approved: %s account ending in %s', $this->text_domain ), $this->get_method_title(), $token->get_account_type(), $token->get_last_four() );

				}

				// add order note
				$order->add_order_note( $message );

				// update subscription
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $order, $product_id );

			} else {

				// failure
				throw new Exception( sprintf( '%s: %s', $response->get_status_code(), $response->get_status_message() ) );
			}

		} catch ( Exception $e ) {

			$this->mark_order_as_failed( $order, $e->getMessage() );

			// update subscription
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		}
	}


	/**
	 * Don't copy over gateway-specific order meta when creating a parent renewal order.
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::get_remove_subscription_renewal_order_meta_fragment()
	 * @param array $order_meta_query MySQL query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return string MySQL meta query for pulling the metadata, excluding data added by this gateway
	 */
	public function remove_subscription_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

		// all required and optional
		if ( 'parent' == $new_order_role ) {
			$order_meta_query .= $this->get_remove_subscription_renewal_order_meta(
				array(
					'_wc_' . $this->get_id() . '_payment_token',
					'_wc_' . $this->get_id() . '_account_four',
					'_wc_' . $this->get_id() . '_card_expiry_date',
					'_wc_' . $this->get_id() . '_card_type',
					'_wc_' . $this->get_id() . '_account_type',
					'_wc_' . $this->get_id() . '_check_number',
					'_wc_' . $this->get_id() . '_environment',
					'_wc_' . $this->get_id() . '_customer_id',
				)
			);
		}

		return $order_meta_query;

	}


	/**
	 * Returns the query fragment to remove the given subscription renewal order meta
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::remove_subscription_renewal_order_meta()
	 * @param array $meta_names array of string meta names to remove
	 * @return string query fragment
	 */
	protected function get_remove_subscription_renewal_order_meta_fragment( $meta_names ) {

		return " AND `meta_key` NOT IN ( '" . join( "', '", $meta_names ) . "' )";

	}


	/**
	 * Adds support for subscriptions by hooking in some necessary actions
	 *
	 * @since 0.1
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if subscriptions or subscription payment method changes are not supported by this gateway or its current configuration
	 */
	public function add_subscription_payment_method_change_support() {

		if ( ! $this->supports_subscriptions() || ! $this->supports( self::FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE ) ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Subscription payment method change not supported by gateway', $this->text_domain ) );

		// update the customer/token ID on the original order when making payment for a failed automatic renewal order
		add_action( 'woocommerce_subscriptions_changed_failing_payment_method_' . $this->get_id(), array( $this, 'update_failing_payment_method' ), 10, 2 );

	}


	/**
	 * Update the customer id/payment token for a subscription after a customer
	 * uses this gateway to successfully complete the payment for an automatic
	 * renewal payment which had previously failed.
	 *
	 * @since 0.1
	 * @param WC_Order $original_order the original order in which the subscription was purchased.
	 * @param WC_Order $renewal_order the order which recorded the successful payment (to make up for the failed automatic payment).
	 */
	public function update_failing_payment_method( WC_Order $original_order, WC_Order $renewal_order ) {

		update_post_meta( $original_order->id, '_wc_' . $this->get_id() . '_customer_id',   get_post_meta( $renewal_order->id, '_wc_' . $this->get_id() . '_customer_id', true ) );
		update_post_meta( $original_order->id, '_wc_' . $this->get_id() . '_payment_token', get_post_meta( $renewal_order->id, '_wc_' . $this->get_id() . '_payment_token', true ) );

	}


	/** Pre-Orders feature ******************************************************/


	/**
	 * Returns true if this gateway with its current configuration supports pre-orders
	 *
	 * @since 0.1
	 * @return boolean true if the gateway supports pre-orders
	 */
	public function supports_pre_orders() {

		return $this->supports( self::FEATURE_PRE_ORDERS ) && $this->supports_tokenization() && $this->tokenization_enabled();

	}


	/**
	 * Adds support for pre-orders by hooking in some necessary actions
	 *
	 * @since 0.1
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if pre-orders are not supported by this gateway or its current configuration
	 */
	public function add_pre_orders_support() {

		if ( ! $this->supports_pre_orders() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Pre-Orders not supported by gateway', $this->text_domain ) );

		// force tokenization when needed
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_tokenization_forced', array( $this, 'pre_orders_tokenization_forced' ) );

		// add pre-orders data to the order object
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_get_order', array( $this, 'pre_orders_get_order' ) );

		// process pre-order initial payment as needed
		add_filter( 'wc_payment_gateway_' . $this->get_id() . '_process_payment', array( $this, 'process_pre_order_payment' ), 10, 2 );

		// process batch pre-order payments
		add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->get_id(), array( $this, 'process_pre_order_release_payment' ) );

	}

	/**
	 * Force tokenization for pre-orders
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::tokenization_forced()
	 * @param boolean $force_tokenization whether tokenization should be forced
	 * @return boolean true if tokenization should be forced, false otherwise
	 */
	public function pre_orders_tokenization_forced( $force_tokenization ) {

		if ( class_exists( 'WC_Pre_Orders_Cart' ) && WC_Pre_Orders_Cart::cart_contains_pre_order() &&
			class_exists( 'WC_Pre_Orders_Product' ) && WC_Pre_Orders_Product::product_is_charged_upon_release( WC_Pre_Orders_Cart::get_pre_order_product() ) ) {

			// always tokenize the card for pre-orders that are charged upon release
			$force_tokenization = true;

		}

		return $force_tokenization;

	}


	/**
	 * Adds pre-orders data to the order object
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::get_order()
	 * @param WC_Order $order the order
	 * @return WC_Order the orders
	 */
	public function pre_orders_get_order( $order ) {

		if ( WC_Pre_Orders_Order::order_contains_pre_order( $order ) &&
			WC_Pre_Orders_Order::order_requires_payment_tokenization( $order ) ) {

			// normally a guest user wouldn't be assigned a customer id, but for a pre-order requiring tokenization, it will be
			if ( 0 == $order->user_id && false !== ( $customer_id = $this->get_guest_customer_id( $order ) ) ) {
				$order->customer_id = $customer_id;
			}

		} elseif ( WC_Pre_Orders_Order::order_has_payment_token( $order ) ) {

			// if this is a pre-order release payment with a tokenized payment method, get the payment token to complete the order

			// retrieve the payment token
			$order->payment->token = get_post_meta( $order->id, '_wc_' . $this->get_id() . '_payment_token', true );

			// retrieve the customer id
			$order->customer_id = get_post_meta( $order->id, '_wc_' . $this->get_id() . '_customer_id', true );

			// verify that this customer still has the token tied to this order.  Pass in customer_id to support tokenized guest orders
			if ( ! $this->has_payment_token( $order->user_id, $order->payment->token, $order->customer_id ) ) {

				$order->payment->token = null;

			} else {
				// Push expected payment data into the order, from the payment token when possible,
				//  or from the order object otherwise.  The theory is that the token will have the
				//  most up-to-date data, while the meta attached to the order is a second best

				// for a guest transaction with a gateway that doesn't support "tokenization get" this will return null
				$token = $this->get_payment_token( $order->user_id, $order->payment->token, $order->customer_id );

				// account last four
				$order->payment->account_number = $token && $token->get_last_four() ? $token->get_last_four() : get_post_meta( $order->id, '_wc_' . $this->get_id() . '_account_four', true );

				if ( $this->is_credit_card_gateway() ) {

					$order->payment->card_type = $token && $token->get_card_type() ? $token->get_card_type() : get_post_meta( $order->id, '_wc_' . $this->get_id() . '_card_type', true );

					if ( $token && $token->get_exp_month() && $token->get_exp_year() ) {
						$order->payment->exp_month  = $token->get_exp_month();
						$order->payment->exp_year   = $token->get_exp_year();
					} else {
						list( $exp_year, $exp_month ) = explode( '-', get_post_meta( $order->id, '_wc_' . $this->get_id() . '_card_expiry_date', true ) );
						$order->payment->exp_month  = $exp_month;
						$order->payment->exp_year   = $exp_year;
					}

				} else {

					// set the account type if available (checking/savings)
					$order->payment->account_type = $token && $token->get_account_type ? $token->get_account_type() : get_post_meta( $order->id, '_wc_' . $this->get_id() . '_account_type', true );

				}

			}

		}

		return $order;

	}


	/**
	 * Handle the pre-order initial payment/tokenization, or defer back to the normal payment
	 * processing flow
	 *
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::process_payment()
	 * @param boolean $result the result of this pre-order payment process
	 * @param int $order_id the order identifier
	 * @return true|array true to process this payment as a regular transaction, otherwise
	 *         return an array containing keys 'result' and 'redirect'
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if pre-orders are not supported by this gateway or its current configuration
	 */
	public function process_pre_order_payment( $result, $order_id ) {

		global $woocommerce;

		if ( ! $this->supports_pre_orders() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Pre-Orders not supported by gateway', $this->text_domain ) );

		if ( $this->get_plugin()->is_pre_orders_active() && class_exists( 'WC_Pre_Orders_Order' ) &&
			WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) &&
			WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) ) {

			$order = $this->get_order( $order_id );

			try {

				// using an existing tokenized payment method
				if ( isset( $order->payment->token ) && $order->payment->token ) {

					// save the tokenized card info for completing the pre-order in the future
					$this->add_transaction_data( $order );

				} else {

					// otherwise tokenize the payment method
					$order = $this->create_payment_token( $order );

				}

				// mark order as pre-ordered / reduce order stock
				WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

				// empty cart
				$woocommerce->cart->empty_cart();

				// redirect to thank you page
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			} catch( Exception $e ) {

				$this->mark_order_as_failed( $order, sprintf( __( 'Pre-Order Tokenization attempt failed (%s)', $this->text_domain ), $this->get_method_title(), $e->getMessage() ) );

			}
		}

		// processing regular product
		return $result;
	}


	/**
	 * Process a pre-order payment when the pre-order is released
	 *
	 * @since 0.1
	 * @param WC_Order $order original order containing the pre-order
	 */
	public function process_pre_order_release_payment( $order ) {

		try {

			// set order defaults
			$order = $this->get_order( $order->id );

			// order description
			$order->description = sprintf( __( '%s - Pre-Order Release Payment for Order %s', $this->text_domain ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() );

			// token is required
			if ( ! $order->payment->token )
				throw new Exception( __( 'Payment token missing/invalid.', $this->text_domain ) );

			// perform the transaction
			if ( $this->is_credit_card_gateway() ) {

				if ( $this->perform_credit_card_charge() ) {
					$response = $this->get_api()->credit_card_charge( $order );
				} else {
					$response = $this->get_api()->credit_card_authorization( $order );
				}

			} else {

				$response = $this->get_api()->check_debit( $order );

			}

			// success! update order record
			if ( $response->transaction_approved() ) {

				$last_four = substr( $order->payment->account_number, -4 );

				// order note based on gateway type
				if ( $this->is_credit_card_gateway() ) {

					$message = sprintf(
						__( '%s %s Pre-Order Release Payment Approved: %s ending in %s (expires %s)', $this->text_domain ),
						$this->get_method_title(),
						$this->perform_credit_card_authorization() ? 'Authorization' : 'Charge',
						isset( $order->payment->card_type ) && $order->payment->card_type ? SV_WC_Payment_Gateway_Payment_Token::type_to_name( $order->payment->card_type ) : 'card',
						$last_four,
						$order->payment->exp_month . '/' . substr( $order->payment->exp_year, -2 )
					);

				} elseif ( $this->is_check_gateway() ) {

					// account type (checking/savings) may or may not be available, which is fine
					$message = sprintf( __( '%s eCheck Pre-Order Release Payment Approved: %s account ending in %s', $this->text_domain ), $this->get_method_title(), $order->payment->account_type, $last_four );

				}

				// adds the transaction id (if any) to the order note
				if ( $response->get_transaction_id() ) {
					$message .= ' ' . sprintf( __( '(Transaction ID %s)', $this->text_domain ), $response->get_transaction_id() );
				}

				$order->add_order_note( $message );
			}

			if ( $response->transaction_approved() || $response->transaction_held() ) {

				// add the standard transaction data
				$this->add_transaction_data( $order, $response );

				// allow the concrete class to add any gateway-specific transaction data to the order
				$this->add_payment_gateway_transaction_data( $order, $response );

				// if the transaction was held (ie fraud validation failure) mark it as such
				if ( $response->transaction_held() ) {

					$this->mark_order_as_held( $order, $response->get_status_message() );
					$order->reduce_order_stock(); // reduce stock for held orders, but don't complete payment

				} else {
					// otherwise complete the order
					$order->payment_complete();
				}

			} else {

				// failure
				throw new Exception( sprintf( '%s: %s', $response->get_status_code(), $response->get_status_message() ) );

			}

		} catch ( Exception $e ) {

			// Mark order as failed
			$this->mark_order_as_failed( $order, sprintf( __( 'Pre-Order Release Payment Failed: %s', $this->text_domain ), $e->getMessage() ) );

		}
	}


	/** Authorization/Charge feature ******************************************************/


	/**
	 * Returns true if this is a credit card gateway which supports
	 * authorization transactions
	 *
	 * @since 0.1
	 * @return boolean true if the gateway supports authorization
	 */
	public function supports_credit_card_authorization() {

		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CREDIT_CARD_AUTHORIZATION );

	}


	/**
	 * Returns true if this is a credit card gateway which supports
	 * charge transactions
	 *
	 * @since 0.1
	 * @return boolean true if the gateway supports charges
	 */
	public function supports_credit_card_charge() {

		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CREDIT_CARD_CHARGE );

	}


	/**
	 * Adds any credit card authorization/charge admin fields, allowing the
	 * administrator to choose between performing authorizations or charges
	 *
	 * @since 0.1
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if authorization & charge are not supported
	 */
	protected function add_authorization_charge_form_fields( $form_fields ) {

		if ( ! ( $this->supports_credit_card_authorization() && $this->supports_credit_card_charge() ) ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Authorization/Charge transactions not supported by gateway', $this->text_domain ) );

		$form_fields['transaction_type'] = array(
			'title'    => __( 'Transaction Type', $this->text_domain ),
			'type'     => 'select',
			'desc_tip' => __( 'Select how transactions should be processed. Charge submits all transactions for settlement, Authorization simply authorizes the order total for capture later.', $this->text_domain ),
			'default'  => self::TRANSACTION_TYPE_CHARGE,
			'options'  => array(
				self::TRANSACTION_TYPE_CHARGE        => __( 'Charge', $this->text_domain ),
				self::TRANSACTION_TYPE_AUTHORIZATION => __( 'Authorization', $this->text_domain ),
			),
		);

		return $form_fields;
	}


	/**
	 * Returns true if a credit card charge should be performed, false if an
	 * authorization should be
	 *
	 * @since 0.1
	 * @return boolean true if a charge should be performed
	 */
	public function perform_credit_card_charge() {

		if ( ! $this->supports_credit_card_charge() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Credit Card charge transactions not supported by this gateway', $this->text_domain ) );

		return  self::TRANSACTION_TYPE_CHARGE == $this->transaction_type;
	}


	/**
	 * Returns true if a credit card authorization should be performed, false if aa
	 * charge should be
	 *
	 * @since 0.1
	 * @return boolean true if an authorization should be performed
	 */
	public function perform_credit_card_authorization() {

		if ( ! $this->supports_credit_card_authorization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Credit Card authorization transactions not supported by this gateway', $this->text_domain ) );

		return self::TRANSACTION_TYPE_AUTHORIZATION == $this->transaction_type;
	}


	/** Card Types feature ******************************************************/


	/**
	 * Returns true if the gateway supports card_types: allows the admin to
	 * configure card type icons to display at checkout
	 *
	 * @since 0.1
	 * @return boolean true if the gateway supports card_types
	 */
	public function supports_card_types() {

		return $this->is_credit_card_gateway() && $this->supports( self::FEATURE_CARD_TYPES );

	}


	/**
	 * Returns the array of accepted card types if this is a credit card gateway
	 * that supports card types.  Return format is 'VISA', 'MC', 'AMEX', etc
	 *
	 * @since 0.1
	 * @see get_available_card_types()
	 * @return array of accepted card types, ie 'VISA', 'MC', 'AMEX', etc
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if card types are not supported
	 */
	public function get_card_types() {

		if ( ! $this->supports_card_types() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Card Types not supported by gateway', $this->text_domain ) );

		return $this->card_types;
	}


	/**
	 * Adds any card types form fields, allowing the admin to configure the card
	 * types icons displayed during checkout
	 *
	 * @since 0.1
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if card types are not supported
	 */
	protected function add_card_types_form_fields( $form_fields ) {

		if ( ! $this->supports_card_types() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Card Types not supported by gateway', $this->text_domain ) );

		$form_fields['card_types'] = array(
			'title'    => __( 'Accepted Card Logos', $this->text_domain ),
			'type'     => 'multiselect',
			'desc_tip' => __( 'Select which card types you accept to display the logos for on your checkout page.', $this->text_domain ),
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
	 * @since 0.1
	 * @return array associative array of card type to display name
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if credit card types is not supported
	 */
	public function get_available_card_types() {

		if ( ! $this->supports_card_types() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Card Types not supported by gateway', $this->text_domain ) );

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
	 * @since 0.1
	 * @return boolean true if the gateway supports tokenization
	 */
	public function supports_tokenization() {

		return $this->supports( self::FEATURE_TOKENIZATION );

	}


	/**
	 * Returns true if tokenization is enabled
	 *
	 * @since 0.1
	 * @return boolean true if tokenization is enabled
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function tokenization_enabled() {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		return 'yes' == $this->tokenization;
	}


	/**
	 * Adds any tokenization form fields for the settings page
	 *
	 * @since 0.1
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	protected function add_tokenization_form_fields( $form_fields ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		$form_fields['tokenization'] = array(
			'title'   => __( 'Tokenization', $this->text_domain ),
			'label'   => __( 'Allow customers to securely save their payment details for future checkout.', $this->text_domain ),
			'type'    => 'checkbox',
			'default' => 'no',
		);

		return $form_fields;

	}


	/**
	 * A factory method to build and return a payment token object for the
	 * gateway.  Concrete classes can override this method to return a custom
	 * payment token implementation.
	 *
	 * Payment token data can include:
	 *
	 * + `default`   - boolean optional indicates this is the default payment token
	 * + `type`      - string credit card type (visa, mc, amex, disc, diners, jcb) or echeck
	 * + `last_four` - string last four digits of account number
	 * + `exp_month` - string optional expiration month (credit card only)
	 * + `exp_year`  - string optional expiration year (credit card only)
	 *
	 * @since 0.1
	 * @param string $token payment token
	 * @param array $data payment token data
	 * @return SV_WC_Payment_Gateway_Payment_Token payment token
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	protected function build_payment_token( $token, $data ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		return new SV_WC_Payment_Gateway_Payment_Token( $token, $data );

	}


	/**
	 * Tokenizes the current payment method and adds the standard transaction
	 * data to the order post record.
	 *
	 * @since 0.1
	 * @param WC_Order $order the order object
	 * @return WC_Order the order object
	 * @throws Exception on network error or request error
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	protected function create_payment_token( $order ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		// perform the API request to tokenize the payment method
		$response = $this->get_api()->tokenize_payment_method( $order );

		if ( $response->transaction_approved() ) {

			// add the token to the order object for processing
			$token                 = $response->get_payment_token();
			$order->payment->token = $token->get_token();

			// for credit card transactions add the card type, if known (some gateways return the credit card type as part of the response, others may require it as part of the request, and still others it may never be known)
			if ( $this->is_credit_card_gateway() && $token->get_card_type() )
				$order->payment->card_type = $token->get_card_type();

			// checking/savings, if known
			if ( $this->is_check_gateway() && $token->get_account_type() )
				$order->payment->account_type = $token->get_account_type();

			// set the token to the user account
			if ( $order->user_id )
				$this->add_payment_token( $order->user_id, $token );

			// add the standard transaction data
			$this->add_transaction_data( $order, $response );

		} else {
			throw new Exception( sprintf( '%s: %s', $response->get_status_code(), $response->get_status_message() ) );
		}

		return $order;
	}


	/**
	 * Returns true if tokenization should be forced on the checkout page,
	 * false otherwise.  This is most useful to force tokenization for a
	 * subscription or pre-orders initial transaction.
	 *
	 * @since 0.1
	 * @return boolean true if tokenization should be forced on the checkout page, false otherwise
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function tokenization_forced() {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		// otherwise generally no need to force tokenization
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_tokenization_forced', false, $this );
	}


	/**
	 * Returns true if the current payment method should be tokenized: whether
	 * requested by customer or otherwise forced.  This parameter is passed from
	 * the checkout page/payment form.
	 *
	 * @since 0.1
	 * @return boolean true if the current payment method should be tokenized
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	protected function should_tokenize_payment_method() {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		return $this->get_post( 'wc-' . $this->get_id_dasherized() . '-tokenize-payment-method' ) && ! $this->get_post( 'wc-' . $this->get_id_dasherized() . '-payment-token' );

	}


	/**
	 * Returns the payment token user meta name for persisting the payment tokens.
	 * Defaults to _wc_{gateway id}_payment_tokens for the production environment,
	 * and _wc_{gateway id}_payment_tokens_{environment} for any other environment.
	 *
	 * NOTE: the gateway id, rather than plugin id, is used by default to create
	 * the meta key for this setting, because it's assumed that in the case of a
	 * plugin having multiple gateways (ie credit card and eCheck) the payment
	 * tokens will be distinct between them
	 *
	 * @since 0.1
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @return string payment token user meta name
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function get_payment_token_user_meta_name( $environment_id = null ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		// default to current environment
		if ( is_null( $environment_id ) )
			$environment_id = $this->get_environment();

		// leading underscore since this will never be displayed to an admin user in its raw form
		return '_wc_' . $this->get_id() . '_payment_tokens' . ( ! $this->is_production_environment( $environment_id ) ? '_' . $environment_id : '' );

	}


	/**
	 * Get the available payment tokens for a user as an associative array of
	 * payment token to SV_WC_Payment_Gateway_Payment_Token
	 *
	 * @since 0.1
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param string $customer_id optional unique customer identifier, if not provided this will be looked up based on $user_id which cannot be 0
	 * @return array associative array of string token to SV_WC_Payment_Gateway_Payment_Token object
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function get_payment_tokens( $user_id, $customer_id = null ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		if ( is_null( $customer_id ) ) {
			$customer_id = $this->get_customer_id( $user_id );
		}

		// return cached tokens, if any
		if ( isset( $this->tokens[ $customer_id ] ) ) {
			return $this->tokens[ $customer_id ];
		}

		$this->tokens[ $customer_id ] = array();
		$tokens = array();

		// retrieve the datastore persisted tokens first, so we have a fallback, as well as the default token
		if ( $user_id ) {

			$_tokens = get_user_meta( $user_id, $this->get_payment_token_user_meta_name(), true );

			// from database format
			if ( is_array( $_tokens ) ) {
				foreach ( $_tokens as $token => $data ) {
					$tokens[ $token ] = $this->build_payment_token( $token, $data );
				}
			}

			$this->tokens[ $customer_id ] = $tokens;

		}

		// if the payment gateway API supports retrieving tokens directly, do so as it's easier to stay synchronized
		if ( $this->get_api()->supports_get_tokenized_payment_methods() ) {

			try {

				// retrieve the payment method tokes from the remote API
				$response = $this->get_api()->get_tokenized_payment_methods( $customer_id );
				$this->tokens[ $customer_id ] = $response->get_payment_tokens();

				// check for a default from the persisted set, if any
				$default_token = null;
				foreach ( $tokens as $default_token ) {
					if ( $default_token->is_default() )
						break;
				}

				// mark the corresponding token from the API as the default one
				if ( $default_token && $default_token->is_default() && isset( $this->tokens[ $customer_id ][ $default_token->get_token() ] ) ) {
					$this->tokens[ $customer_id ][ $default_token->get_token() ]->set_default( true );
				}

			} catch( Exception $e ) {
				// communication or other error, fallback to the locally stored tokens
				$this->tokens[ $customer_id ] = $tokens;
			}

		}

		// set the payment type image url, if any, for convenience
		foreach ( $this->tokens[ $customer_id ] as $key => $token ) {
			$this->tokens[ $customer_id ][ $key ]->set_image_url( $this->get_payment_method_image_url( $token->is_credit_card() ? $token->get_card_type() : 'echeck' ) );
		}

		return $this->tokens[ $customer_id ];

	}


	/**
	 * Updates the given payment tokens for the identified user, in the database.
	 *
	 * @since 0.1
	 * @param int $user_id wordpress user identifier
	 * @param array $tokens array of tokens
	 * @return string updated user meta id
	 */
	protected function update_payment_tokens( $user_id, $tokens ) {

		// update the local cache
		$this->tokens[ $this->get_customer_id( $user_id ) ] = $tokens;

		// persist the updated tokens to the user meta
		return update_user_meta( $user_id, $this->get_payment_token_user_meta_name(), $this->payment_tokens_to_database_format( $tokens ) );

	}


	/**
	 * Returns the payment token object identified by $token from the user
	 * identified by $user_id
	 *
	 * @since 0.1
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param string $token payment token
	 * @param string $customer_id optional unique customer identifier, if not provided this will be looked up based on $user_id which cannot be 0
	 * @return SV_WC_Payment_Gateway_Payment_Token payment token object or null
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function get_payment_token( $user_id, $token, $customer_id = null ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		$tokens = $this->get_payment_tokens( $user_id, $customer_id );

		if ( isset( $tokens[ $token ] ) ) return $tokens[ $token ];

		return null;
	}


	/**
	 * Returns true if the identified user has the given payment token
	 *
	 * @since 0.1
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param string|SV_WC_Payment_Gateway_Payment_Token $token payment token
	 * @param string $customer_id optional unique customer identifier, if not provided this will be looked up based on $user_id which cannot be 0
	 * @return boolean true if the user has the payment token, false otherwise
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function has_payment_token( $user_id, $token, $customer_id = null ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		if ( ! is_string( $token ) )
			$token = $token->get_token();

		// this is sort of a weird edge case: verifying a token exists for a guest customer
		//  using an API that doesn't support a tokenized payment method query operation.
		//  We will neither have a user record in the db, nor can we query the API endpoint,
		//  so just return true
		// Sample case: Guest pre-order transaction using FirstData
		if ( ! $this->get_api()->supports_get_tokenized_payment_methods() && ! $user_id ) {
			return true;
		}

		// token exists?
		return ! is_null( $this->get_payment_token( $user_id, $token, $customer_id ) );
	}


	/**
	 * Add a payment method and token as user meta.
	 *
	 * @since 0.1
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token $token the token
	 * @return bool|int false if token not added, user meta ID if added
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function add_payment_token( $user_id, $token ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		// get existing tokens
		$tokens = $this->get_payment_tokens( $user_id );

		// if this token is set as active, mark all others as false
		if ( $token->is_default() ) {
			foreach ( array_keys( $tokens ) as $key ) {
				$tokens[ $key ]->set_default( false );
			}
		}

		// add the new token
		$tokens[ $token->get_token() ] = $token;

		// persist the updated tokens
		return $this->update_payment_tokens( $user_id, $tokens );

	}


	/**
	 * Delete a credit card token from user meta
	 *
	 * @since 0.1
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token|string $token the payment token to delete
	 * @return bool|int false if not deleted, updated user meta ID if deleted
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function remove_payment_token( $user_id, $token ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		// unknown token?
		if ( ! $this->has_payment_token( $user_id, $token ) )
			return false;

		// get the payment token object as needed
		if ( is_string( $token ) )
			$token = $this->get_payment_token( $user_id, $token );

		// for direct gateways that allow it, attempt to delete the token from the endpoint
		if ( $this->is_direct_gateway() && $this->get_api()->supports_remove_tokenized_payment_method() ) {

			try {

				$response = $this->get_api()->remove_tokenized_payment_method( $this->get_customer_id( $user_id ), $token->get_token() );

				if ( ! $response->transaction_approved() ) {
					return false;
				}

			} catch( Exception $e ) {
				return false;
			}
		}

		// get existing tokens
		$tokens = $this->get_payment_tokens( $user_id );

		unset( $tokens[ $token->get_token() ] );

		// if the deleted card was the default one, make another one the new default
		if ( $token->is_default() ) {
			foreach ( array_keys( $tokens ) as $key ) {
				$tokens[ $key ]->set_default( true );
				break;
			}
		}

		// persist the updated tokens
		return $this->update_payment_tokens( $user_id, $tokens );

	}


	/**
	 * Set the default token for a user. This is shown as "Default Card" in the
	 * frontend and will be auto-selected during checkout
	 *
	 * @since 0.1
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token|string $token the token to make default
	 * @return string|bool false if not set, updated user meta ID if set
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function set_default_payment_token( $user_id, $token ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		// unknown token?
		if ( ! $this->has_payment_token( $user_id, $token ) )
			return false;

		// get the payment token object as needed
		if ( is_string( $token ) )
			$token = $this->get_payment_token( $user_id, $token );

		// get existing tokens
		$tokens = $this->get_payment_tokens( $user_id );

		// mark $token as the only active
		foreach ( $tokens as $key => $_token ) {

			if ( $token->get_token() == $_token->get_token() )
				$tokens[ $key ]->set_default( true );
			else
				$tokens[ $key ]->set_default( false );

		}

		// persist the updated tokens
		return $this->update_payment_tokens( $user_id, $tokens );

	}


	/**
	 * Returns $tokens in a format suitable for data storage
	 *
	 * @since 0.1
	 * @param int $user_id user identifier
	 * @param array $tokens array of SV_WC_Payment_Gateway_Payment_Token tokens
	 * @return array data storage version of $tokens
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	protected function payment_tokens_to_database_format( $tokens ) {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		$_tokens = array();

		// to database format
		foreach ( $tokens as $key => $token ) {
			$_tokens[ $key ] = $token->to_datastore_format();
		}

		return $_tokens;
	}


	/**
	 * Handle any actions from the 'My Payment Methods' section on the
	 * 'My Account' page
	 */
	public function handle_my_payment_methods_actions() {

		global $woocommerce;

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		if ( ! $this->is_available() || ! $this->tokenization_enabled() )
			return;

		$token  = isset( $_GET[ 'wc-' . $this->get_id_dasherized() . '-token' ] )  ? trim( $_GET[ 'wc-' . $this->get_id_dasherized() . '-token' ] ) : '';
		$action = isset( $_GET[ 'wc-' . $this->get_id_dasherized() . '-action' ] ) ? $_GET[ 'wc-' . $this->get_id_dasherized() . '-action' ] : '';

		// process payment method actions
		if ( $token && $action && ! empty( $_GET['_wpnonce'] ) ) {

			// security check
			if ( false === wp_verify_nonce( $_GET['_wpnonce'], 'wc-' . $this->get_id_dasherized() . '-token-action' ) ) {

				$woocommerce->add_error( __( "There was an error with your request, please try again.", $this->text_domain ) );
				$woocommerce->set_messages();
				wp_redirect( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
				exit;

			}

			// current logged in user
			$user_id = get_current_user_id();

			// handle deletion
			if ( 'delete' === $action ) {

				if ( ! $this->remove_payment_token( $user_id, $token ) ) {

					$woocommerce->add_error( __( "Error removing payment method", $this->text_domain ) );
					$woocommerce->set_messages();

				}

			}

			// handle default change
			if ( 'make-default' === $action ) {
				$this->set_default_payment_token( $user_id, $token );
			}

			// remove the query params
			wp_redirect( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
			exit;
		}
	}


	/**
	 * Display the 'My Payment Methods' section on the 'My Account'
	 *
	 * @since 0.1
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 */
	public function show_my_payment_methods() {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		if ( ! $this->is_available() || ! $this->tokenization_enabled() )
			return;

		// render the template
		$this->show_my_payment_methods_load_template();

	}


	/**
	 * Render the "My Payment Methods" template
	 *
	 * This is a stub method which must be overridden if this gateway supports
	 * tokenization
	 *
	 * @since 0.1
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if payment method tokenization is not supported
	 * @throws SV_WC_Payment_Gateway_Unimplemented_Method_Exception if this gateway supports direct communication but has not provided an implementation for this method
	 */
	protected function show_my_payment_methods_load_template() {

		if ( ! $this->supports_tokenization() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Payment tokenization not supported by gateway', $this->text_domain ) );

		// concrete stub method
		throw new SV_WC_Payment_Gateway_Unimplemented_Method_Exception( get_class( $this ) . substr( __METHOD__, strpos( __METHOD__, '::' ) ) . "()" );

	}


	/** Direct Payment Gateway ******************************************************/


	/**
	 * Returns the API instance for this gateway if it is a direct communication
	 *
	 * This is a stub method which must be overridden if this gateway performs
	 * direct communication
	 *
	 * @since 0.1
	 * @return SV_WC_Payment_Gateway_API the payment gateway API instance
	 * @throws SV_WC_Payment_Gateway_Feature_Unsupported_Exception if this gateway does not support direct communication
	 * @throws SV_WC_Payment_Gateway_Unimplemented_Method_Exception if this gateway supports direct communication but has not provided an implementation for this method
	 */
	public function get_api() {

		if ( ! $this->is_direct_gateway() ) throw new SV_WC_Payment_Gateway_Feature_Unsupported_Exception( __( 'Direct communication not supported by gateway', $this->text_domain ) );

		// concrete stub method
		throw new SV_WC_Payment_Gateway_Unimplemented_Method_Exception( get_class( $this ) . substr( __METHOD__, strpos( __METHOD__, '::' ) ) . "()" );

	}


	/** Helper methods ******************************************************/


	/**
	 * Safely get and trim data from $_POST
	 *
	 * @since 0.1
	 * @param string $key array key to get from $_POST array
	 * @return string value from $_POST or blank string if $_POST[ $key ] is not set
	 */
	protected function get_post( $key ) {

		if ( isset( $_POST[ $key ] ) )
			return trim( $_POST[ $key ] );

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
	 * @since 0.1
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
	 * @since 0.1
	 * @param string $message message to add
	 * @param string $type how to add the message, options are:
	 *     'message' (styled as WC message), 'error' (styled as WC Error)
	 * @param bool $set_message sets any WC messages/errors provided so they appear on the next page load, useful for displaying messages on the thank you page
	 */
	protected function add_debug_message( $message, $type = 'message', $set_message = false ) {

		global $woocommerce;

		// do nothing when debug mode is off or no message
		if ( 'off' == $this->debug_off() || ! $message )
			return;

		// add debug message to woocommerce->errors/messages if checkout or both is enabled
		if ( $this->debug_checkout() ) {

			if ( 'message' === $type ) {

				$woocommerce->add_message( str_replace( "\n", "<br/>", htmlspecialchars( $message ) ) );

			} else {

				// defaults to error message
				$woocommerce->add_error( str_replace( "\n", "<br/>", htmlspecialchars( $message ) ) );
			}
		}

		// set messages for next page load
		if ( $set_message )
			$woocommerce->set_messages();

		// add log message to WC logger if log/both is enabled
		if ( $this->debug_log() ) {
			$this->get_plugin()->log( $message, $this->get_id() );
		}
	}


	/** Getters ******************************************************/


	/**
	 * Returns the payment gateway id
	 *
	 * @since 0.1
	 * @return string payment gateway id
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the payment gateway id with dashes in place of underscores, and
	 * appropriate for use in frontend element names, classes and ids
	 *
	 * @since 0.1
	 * @return string payment gateway id with dashes in place of underscores
	 */
	public function get_id_dasherized() {
		return str_replace( '_', '-', $this->get_id() );
	}


	/**
	 * Returns the parent plugin object
	 *
	 * @since 0.1
	 * @return SV_WC_Payment_Gateway the parent plugin object
	 */
	public function get_plugin() {
		return $this->plugin;
	}


	/**
	 * Returns the description setting.  This is the description configured by
	 * the admin and displayed to the customer during checkout.
	 *
	 * @since 0.1
	 * @return string the checkout page description
	 */
	public function get_description() {
		return $this->description;
	}


	/**
	 * Returns the title setting.  This is the title configured by the admin and
	 * displayed to the customer during checkout.
	 *
	 * @since 0.1
	 * @return string the checkout page title
	 */
	public function get_title() {
		return $this->title;
	}


	/**
	 * Returns the admin method title.  This should be the gateway name, ie
	 * 'Intuit QBMS'
	 *
	 * @since 0.1
	 * @see WC_Settings_API::$method_title
	 * @return string method title
	 */
	public function get_method_title() {
		return $this->method_title;
	}


	/**
	 * Returns true if the Card Security Code (CVV) field should be used on checkout
	 *
	 * @since 0.1
	 * @return boolean true if the Card Security Code field should be used on checkout
	 */
	public function csc_enabled() {

		return 'yes' == $this->enable_csc;

	}


	/**
	 * Returns true if settings should be inherited for this gateway
	 *
	 * @since 0.1
	 * @return boolean true if settings should be inherited for this gateway
	 */
	public function inherit_settings() {

		return 'yes' == $this->inherit_settings;

	}


	/**
	 * Add support for the named feature or features
	 *
	 * @since 0.1
	 * @param string|array $feature the feature name or names supported by this gateway
	 */
	public function add_support( $feature ) {

		if ( ! is_array( $feature ) )
			$feature = array( $feature );

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
	 * @since 0.1
	 * @param array $features array of supported feature names
	 */
	public function set_supports( $features ) {
		$this->supports = $features;
	}


	/**
	 * Returns true if this echeck gateway supports
	 *
	 * @since 0.1
	 * @param string $field_name check gateway field name, includes 'check_number', 'account_type'
	 * @return boolean true if this check gateway supports the named field
	 * @throws Exception if this is called on a non-check gateway
	 */
	public function supports_check_field( $field_name ) {

		if ( ! $this->is_check_gateway() ) throw new Exception( __( 'Check method called on non-check gateway', $this->text_domain ) );

		return is_array( $this->supported_check_fields ) && in_array( $field_name, $this->supported_check_fields );

	}


	/**
	 * Gets the set of environments supported by this gateway.  All gateways
	 * support at least the production environment
	 *
	 * @since 0.1
	 * @return array associative array of environment id to name supported by this gateway
	 */
	public function get_environments() {

		// default set of environments consists of 'production'
		if ( ! isset( $this->environments ) ) {
			$this->environments = array( self::ENVIRONMENT_PRODUCTION => __( 'Production', $this->text_domain ) );
		}

		return $this->environments;
	}


	/**
	 * Returns the environment setting, one of the $environments keys, ie
	 * 'production'
	 *
	 * @since 0.1
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
	 * @since 0.1
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
	 * Returns true if all debugging is disabled
	 *
	 * @since 0.1
	 * @return boolean if all debuging is disabled
	 */
	public function debug_off() {
		return self::DEBUG_MODE_OFF === $this->debug_mode;
	}


	/**
	 * Returns true if debug logging is enabled
	 *
	 * @since 0.1
	 * @return boolean if debug logging is enabled
	 */
	public function debug_log() {
		return self::DEBUG_MODE_LOG === $this->debug_mode || self::DEBUG_MODE_BOTH === $this->debug_mode;
	}


	/**
	 * Returns true if checkout debugging is enabled.  This will cause debugging
	 * statements to be displayed on the checkout/pay pages
	 *
	 * @since 0.1
	 * @return boolean if checkout debugging is enabled
	 */
	public function debug_checkout() {
		return self::DEBUG_MODE_CHECKOUT === $this->debug_mode || self::DEBUG_MODE_BOTH === $this->debug_mode;
	}


	/**
	 * Returns true if this is a direct type gateway
	 *
	 * @since 0.1
	 * @return boolean if this is a direct payment gateway
	 */
	public function is_direct_gateway() {
		return self::GATEWAY_TYPE_DIRECT == $this->gateway_type;
	}


	/**
	 * Returns true if this is a hosted IPN type gateway
	 *
	 * @since 0.1
	 * @return boolean if this is a hosted IPN payment gateway
	 */
	public function is_redirect_hosted_ipn_gateway() {
		return self::GATEWAY_TYPE_REDIRECT_HOSTED_IPN == $this->gateway_type;
	}


	/**
	 * Returns true if this is a redirect type gateway
	 *
	 * @since 0.1
	 * @return boolean if this is a redirect payment gateway
	 */
	public function is_redirect_gateway() {
		return self::GATEWAY_TYPE_REDIRECT == $this->gateway_type;
	}


	/**
	 * Returns true if this is a credit card gateway
	 *
	 * @since 0.1
	 * @return boolean true if this is a credit card gateway
	 */
	public function is_credit_card_gateway() {
		return self::PAYMENT_TYPE_CREDIT_CARD == $this->payment_type;
	}


	/**
	 * Returns true if this is an echeck gateway
	 *
	 * @since 0.1
	 * @return boolean true if this is an echeck gateway
	 */
	public function is_check_gateway() {
		return self::PAYMENT_TYPE_ECHECK == $this->payment_type;
	}

}

endif;  // class exists check
