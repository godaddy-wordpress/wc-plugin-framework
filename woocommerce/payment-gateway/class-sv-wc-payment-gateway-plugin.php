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
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0;

use Automattic\WooCommerce\Admin\Notes\WC_Admin_Note;
use Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\External_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Google_Pay\Google_Pay;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_Plugin' ) ) :


/**
 * # WooCommerce Payment Gateway Plugin Framework
 *
 * A payment gateway refinement of the WooCommerce Plugin Framework
 *
 * This framework class provides a base level of configurable and overrideable
 * functionality and features suitable for the implementation of a WooCommerce
 * payment gateway.  This class handles all the non-gateway support tasks such
 * as verifying dependencies are met, loading the text domain, etc.  It also
 * loads the payment gateway when needed now that the gateway is only created
 * on the checkout & settings pages / api hook.  The gateway can also be loaded
 * in the following instances:
 *
 * + On the My Account page to display / change saved payment methods (if supports tokenization)
 * + On the Admin User/Your Profile page to render/persist the customer ID field(s) (if supports customer_id)
 * + On the Admin Order Edit page to render a merchant account transaction direct link (if supports transaction_link)
 *
 * ## Supports (zero or more):
 *
 * + `customer_id`             - adds actions to show/persist the "Customer ID" area of the admin User edit page
 * + `transaction_link`        - adds actions to render the merchant account transaction direct link on the Admin Order Edit page.  (Don't forget to override the SV_WC_Payment_Gateway::get_transaction_url() method!)
 * + `capture_charge`          - adds actions to capture charge for authorization-only transactions
 * + `my_payment_methods`      - adds actions to show/handle a "My Payment Methods" area on the customer's My Account page. This will show saved payment methods for all plugin gateways that support tokenization.
 *
 * @version 2.0.0
 */
abstract class SV_WC_Payment_Gateway_Plugin extends SV_WC_Plugin {


	/** Customer ID feature */
	const FEATURE_CUSTOMER_ID = 'customer_id';

	/** Charge capture feature */
	const FEATURE_CAPTURE_CHARGE = 'capture_charge';

	/** My Payment Methods feature */
	const FEATURE_MY_PAYMENT_METHODS = 'my_payment_methods';

	/** @var array optional associative array of gateway id to array( 'gateway_class_name' => string, 'gateway' => SV_WC_Payment_Gateway ) */
	private $gateways;

	/** @var array optional array of currency codes this gateway is allowed for */
	private $currencies = array();

	/** @var array named features that this gateway supports which require action from the parent plugin, including 'tokenization' */
	private $supports = array();

	/** @var bool helper for lazy subscriptions active check */
	private $subscriptions_active;

	/** @var bool helper for lazy pre-orders active check */
	private $pre_orders_active;

	/** @var boolean true if this gateway requires SSL for processing transactions, false otherwise */
	private $require_ssl;

	/** @var SV_WC_Payment_Gateway_Privacy payment gateway privacy handler instance */
	protected $privacy_handler;

	/** @var SV_WC_Payment_Gateway_Admin_Order order handler instance */
	protected $admin_order_handler;

	/** @var SV_WC_Payment_Gateway_Admin_User_Handler user handler instance */
	protected $admin_user_handler;

	/** @var SV_WC_Payment_Gateway_My_Payment_Methods adds My Payment Method functionality */
	private $my_payment_methods;

	/** @var SV_WC_Payment_Gateway_Apple_Pay the Apple Pay handler instance */
	private $apple_pay;

	/** @var Google_Pay the Google Pay handler instance */
	private $google_pay;


	/**
	 * Initializes the plugin.
	 *
	 * Optional args:
	 *
	 * + `require_ssl` - boolean true if this plugin requires SSL for proper functioning, false otherwise. Defaults to false
	 * + `gateways` - array associative array of gateway id to gateway class name.  A single plugin might support more than one gateway, ie credit card, echeck.  Note that the credit card gateway must always be the first one listed.
	 * + `currencies` -  array of currency codes this gateway is allowed for, defaults to all
	 * + `supports` - array named features that this gateway supports, including 'tokenization', 'transaction_link', 'customer_id', 'capture_charge'
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::__construct()
	 * @param string $id plugin id
	 * @param string $version plugin version number
	 * @param array $args plugin arguments
	 */
	public function __construct( $id, $version, $args ) {

		parent::__construct( $id, $version, $args );

		$args = wp_parse_args( $args, array(
			'gateways'    => array(),
			'currencies'  => array(),
			'supports'    => array(),
			'require_ssl' => false,
		) );

		// add each gateway
		foreach ( $args['gateways'] as $gateway_id => $gateway_class_name ) {
			$this->add_gateway( $gateway_id, $gateway_class_name );
		}

		$this->currencies  = (array) $args['currencies'];
		$this->supports    = (array) $args['supports'];
		$this->require_ssl = (array) $args['require_ssl'];

		// require the files
		$this->includes();

		// add the action & filter hooks
		$this->add_hooks();
	}


	/**
	 * Builds the REST API handler instance.
	 *
	 * Gateway plugins can override this to add their own data and/or routes.
	 *
	 * @see SV_WC_Plugin::init_rest_api_handler()
	 *
	 * @since 5.2.0
	 */
	protected function init_rest_api_handler() {

		require_once( $this->get_payment_gateway_framework_path() . '/rest-api/class-sv-wc-payment-gateway-plugin-rest-api.php' );

		$this->rest_api_handler = new Payment_Gateway\REST_API( $this );
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.2.0
	 */
	private function add_hooks() {

		// add classes to WC Payment Methods
		add_filter( 'woocommerce_payment_gateways', array( $this, 'load_gateways' ) );

		// adjust the available gateways in certain cases
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'adjust_available_gateways' ) );

		// my payment methods feature
		add_action( 'init', array( $this, 'maybe_init_my_payment_methods' ) );

		// apple pay feature
		add_action( 'init', array( $this, 'maybe_init_apple_pay' ) );

		// Google Pay feature
		add_action( 'init', [ $this, 'maybe_init_google_pay' ] );

		// TODO: move these to Subscriptions integration
		if ( $this->is_subscriptions_active() ) {

			// filter the payment gateway table on the checkout settings screen to indicate if a gateway can support Subscriptions but requires tokenization to be enabled
			add_action( 'admin_print_styles', array( $this, 'subscriptions_add_renewal_support_status_inline_style' ) );
			add_filter( 'woocommerce_payment_gateways_renewal_support_status_html', array( $this, 'subscriptions_maybe_edit_renewal_support_status' ), 10, 2 );
		}

		// add gateway information to the system status report
		add_action( 'woocommerce_system_status_report', array( $this, 'add_system_status_information' ) );
	}


	/**
	 * Initializes the plugin admin.
	 *
	 * @see SV_WC_Plugin::init_admin()
	 *
	 * @since 5.2.0
	 */
	public function init_admin() {

		parent::init_admin();

		$this->admin_order_handler = new SV_WC_Payment_Gateway_Admin_Order( $this );
		$this->admin_user_handler  = new SV_WC_Payment_Gateway_Admin_User_Handler( $this );
	}


	/**
	 * Adds any gateways supported by this plugin to the list of available payment gateways.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $gateways
	 * @return array $gateways
	 */
	public function load_gateways( $gateways ) {

		return array_merge( $gateways, $this->get_gateways() );
	}


	/**
	 * Adjust the available gateways in certain cases.
	 *
	 * @since 4.4.0
	 *
	 * @param array $available_gateways the available payment gateways
	 * @return array
	 */
	public function adjust_available_gateways( $available_gateways ) {

		if ( ! is_add_payment_method_page() ) {
			return $available_gateways;
		}

		foreach ( $this->get_gateways() as $gateway ) {

			if ( ! $gateway->supports_tokenization() || ! $gateway->supports_add_payment_method() || ! $gateway->tokenization_enabled() ) {
				unset( $available_gateways[ $gateway->id ] );
			}
		}

		return $available_gateways;
	}


	/**
	 * Include required files.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		$payment_gateway_framework_path = $this->get_payment_gateway_framework_path();

		// interfaces
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-request.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-response.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-authorization-response.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-create-payment-token-response.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-get-tokenized-payment-methods-response.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-payment-notification-response.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-payment-notification-credit-card-response.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-payment-notification-echeck-response.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-payment-notification-tokenization-response.php' );
		require_once( $payment_gateway_framework_path . '/api/interface-sv-wc-payment-gateway-api-customer-response.php' );

		// exceptions
		require_once( $payment_gateway_framework_path . '/exceptions/class-sv-wc-payment-gateway-exception.php' );

		// gateway
		require_once( $payment_gateway_framework_path . '/class-sv-wc-payment-gateway.php' );
		require_once( $payment_gateway_framework_path . '/class-sv-wc-payment-gateway-direct.php' );
		require_once( $payment_gateway_framework_path . '/class-sv-wc-payment-gateway-hosted.php' );
		require_once( $payment_gateway_framework_path . '/class-sv-wc-payment-gateway-payment-form.php' );
		require_once( $payment_gateway_framework_path . '/class-sv-wc-payment-gateway-my-payment-methods.php' );

		// handlers
		require_once( $payment_gateway_framework_path . '/Handlers/Abstract_Payment_Handler.php' );
		require_once( $payment_gateway_framework_path . '/Handlers/Abstract_Hosted_Payment_Handler.php' );
		require_once( $payment_gateway_framework_path . '/Handlers/Capture.php' );

		// External Checkout
		require_once( "{$payment_gateway_framework_path}/External_Checkout/External_Checkout.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/Admin.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/Frontend.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/Orders.php" );

		// Apple Pay
		require_once( "{$payment_gateway_framework_path}/External_Checkout/apple-pay/class-sv-wc-payment-gateway-apple-pay.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/apple-pay/class-sv-wc-payment-gateway-apple-pay-admin.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/apple-pay/class-sv-wc-payment-gateway-apple-pay-frontend.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/apple-pay/class-sv-wc-payment-gateway-apple-pay-ajax.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/apple-pay/api/class-sv-wc-payment-gateway-apple-pay-payment-response.php" );

		// Google Pay
		require_once( "{$payment_gateway_framework_path}/External_Checkout/Google_Pay/Google_Pay.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/Google_Pay/Admin.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/Google_Pay/AJAX.php" );
		require_once( "{$payment_gateway_framework_path}/External_Checkout/Google_Pay/Frontend.php" );

		// payment tokens
		require_once( $payment_gateway_framework_path . '/payment-tokens/class-sv-wc-payment-gateway-payment-token.php' );
		require_once( $payment_gateway_framework_path . '/payment-tokens/class-sv-wc-payment-gateway-payment-tokens-handler.php' );

		// helpers
		require_once( $payment_gateway_framework_path . '/api/class-sv-wc-payment-gateway-api-response-message-helper.php' );
		require_once( $payment_gateway_framework_path . '/class-sv-wc-payment-gateway-helper.php' );

		// admin
		require_once( $payment_gateway_framework_path . '/admin/class-sv-wc-payment-gateway-admin-order.php' );
		require_once( $payment_gateway_framework_path . '/admin/class-sv-wc-payment-gateway-admin-user-handler.php' );
		require_once( $payment_gateway_framework_path . '/admin/class-sv-wc-payment-gateway-admin-payment-token-editor.php' );

		// integrations
		require_once( $payment_gateway_framework_path . '/integrations/abstract-sv-wc-payment-gateway-integration.php' );

		// subscriptions
		if ( $this->is_subscriptions_active() ) {
			require_once( $payment_gateway_framework_path . '/integrations/class-sv-wc-payment-gateway-integration-subscriptions.php' );
		}

		// pre-orders
		if ( $this->is_pre_orders_active() ) {
			require_once( $payment_gateway_framework_path . '/integrations/class-sv-wc-payment-gateway-integration-pre-orders.php' );
		}

		// privacy
		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.4' ) ) {
			require_once( "{$payment_gateway_framework_path}/class-sv-wc-payment-gateway-privacy.php" );
			$this->privacy_handler = new SV_WC_Payment_Gateway_Privacy( $this );
		}
	}


	/** My Payment Methods methods ***********************************/


	/**
	 * Instantiates the My Payment Methods table class instance when a user is
	 * logged in on an account page and tokenization is enabled for at least
	 * one of the active gateways.
	 *
	 * @internal
	 *
	 * @since 4.0.0
	 */
	public function maybe_init_my_payment_methods() {

		// bail if not frontend or an AJAX request
		if ( is_admin() && ! is_ajax() ) {
			return;
		}

		if ( $this->supports_my_payment_methods() && $this->tokenization_enabled() && is_user_logged_in() ) {
			$this->my_payment_methods = $this->get_my_payment_methods_instance();
		}
	}


	/**
	 * Returns true if tokenization is supported and enabled for at least one
	 * active gateway
	 *
	 * @since 4.2.0
	 *
	 * @return bool
	 */
	public function tokenization_enabled() {

		foreach ( $this->get_gateways() as $gateway ) {

			if ( $gateway->is_enabled() && $gateway->supports_tokenization() && $gateway->tokenization_enabled() ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Gets the My Payment Methods table instance.
	 *
	 * Overrideable by concrete gateway plugins to return a custom instance as needed
	 *
	 * @since 4.0.0
	 *
	 * @return SV_WC_Payment_Gateway_My_Payment_Methods
	 */
	protected function get_my_payment_methods_instance() {

		return new SV_WC_Payment_Gateway_My_Payment_Methods( $this );
	}


	/**
	 * Determines whether the My Payment Methods feature is supported.
	 *
	 * @since 5.1.0
	 *
	 * @return bool
	 */
	public function supports_my_payment_methods() {

		return $this->supports( self::FEATURE_MY_PAYMENT_METHODS );
	}


	/** Apple Pay *************************************************************/


	/**
	 * Initializes Apple Pay if it's supported.
	 *
	 * @since 4.7.0
	 */
	public function maybe_init_apple_pay() {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.2' ) && $this->is_apple_pay_activated() && $this->supports_apple_pay() ) {
			$this->apple_pay = $this->build_apple_pay_instance();
		}
	}


	/**
	 * Determines whether Apple Pay is activated.
	 *
	 * @since 5.5.1
	 */
	private function is_apple_pay_activated() {

		/**
		 * Filters whether Apple Pay is activated.
		 *
		 * @since 4.7.0
		 *
		 * @param bool $activated whether Apple Pay is activated
		 */
		return (bool) apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_activate_apple_pay', false );
	}


	/**
	 * Builds the Apple Pay handler instance.
	 *
	 * Gateways can override this to define their own Apple Pay class.
	 *
	 * @since 4.7.0
	 *
	 * @return SV_WC_Payment_Gateway_Apple_Pay
	 */
	protected function build_apple_pay_instance() {

		return new SV_WC_Payment_Gateway_Apple_Pay( $this );
	}


	/**
	 * Gets the Apple Pay handler instance.
	 *
	 * @since 4.7.0
	 *
	 * @return SV_WC_Payment_Gateway_Apple_Pay
	 */
	public function get_apple_pay_instance() {

		return $this->apple_pay;
	}


	/**
	 * Determines if this plugin has any gateways with Apple Pay support.
	 *
	 * @since 4.7.0
	 *
	 * @return bool
	 */
	public function supports_apple_pay() {

		$is_supported = false;

		foreach ( $this->get_gateways() as $gateway ) {

			if ( $gateway->supports_apple_pay() ) {
				$is_supported = true;
			}
		}

		return $is_supported;
	}


	/** Google Pay *************************************************************/


	/**
	 * Initializes Google Pay if it's supported.
	 *
	 * @since 5.10.0
	 */
	public function maybe_init_google_pay() {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.2' ) && $this->supports_google_pay() ) {
			$this->google_pay = $this->build_google_pay_instance();
		}
	}


	/**
	 * Builds the Google Pay handler instance.
	 *
	 * Gateways can override this to define their own Google Pay class.
	 *
	 * @since 5.10.0
	 *
	 * @return Google_Pay
	 */
	protected function build_google_pay_instance() {

		return new Google_Pay( $this );
	}


	/**
	 * Gets the Google Pay handler instance.
	 *
	 * @since 5.10.0
	 *
	 * @return Google_Pay
	 */
	public function get_google_pay_instance() {

		return $this->google_pay;
	}


	/**
	 * Determines if this plugin has any gateways with Google Pay support.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public function supports_google_pay() {

		$is_supported = false;

		foreach ( $this->get_gateways() as $gateway ) {

			if ( $gateway->supports_google_pay() ) {
				$is_supported = true;
			}
		}

		return $is_supported;
	}


	/** Admin methods ******************************************************/


	/**
	 * Adds the gateway plugin action links.
	 *
	 * @see SV_WC_Plugin::plugin_action_links()
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $actions associative array of action names to anchor tags
	 * @return string[]
	 */
	public function plugin_action_links( $actions ) {

		$actions = parent::plugin_action_links( $actions );

		// remove the configure plugin link if it exists, since we'll be adding a link per available gateway
		if ( isset( $actions['configure'] ) ) {
			unset( $actions['configure'] );
		}

		// a configure link per gateway
		$custom_actions = array();

		foreach ( $this->get_gateway_ids() as $gateway_id ) {
			$custom_actions[ 'configure_' . $gateway_id ] = $this->get_settings_link( $gateway_id );
		}

		// add the links to the front of the actions list
		return array_merge( $custom_actions, $actions );
	}


	/**
	 * Determines if on the admin gateway settings screen for this plugin.
	 *
	 * Multi-gateway plugins will return true if on either settings page
	 *
	 * @since 2.0.0
	 *
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @return bool
	 */
	public function is_plugin_settings() {

		foreach ( $this->get_gateways() as $gateway ) {
			if ( $this->is_payment_gateway_configuration_page( $gateway->get_id() ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Convenience method to add delayed admin notices, which may depend upon
	 * some setting being saved prior to determining whether to render.
	 *
	 * @since 3.0.0
	 *
	 * @see SV_WC_Plugin::add_delayed_admin_notices()
	 */
	public function add_delayed_admin_notices() {

		parent::add_delayed_admin_notices();

		// reload all gateway settings so notices are correct after saving the settings
		foreach ( $this->get_gateways() as $gateway ) {
			$gateway->init_settings();
			$gateway->load_settings();
		}

		// notices for ssl requirement
		$this->add_ssl_admin_notices();

		// notices for currency issues
		$this->add_currency_admin_notices();

		// notices for subscriptions/pre-orders
		$this->add_integration_requires_tokenization_notices();

		// add notices about enabled debug logging
		$this->add_debug_setting_notices();

		// add notices about gateways not being configured
		$this->add_gateway_not_configured_notices();

		$this->add_apple_pay_not_supported_notices();

		$this->add_google_pay_not_supported_notices();
	}


	/**
	 * Adds any SSL admin notices.
	 *
	 * Checks if SSL is required and not available and adds a dismissible admin
	 * notice if so.
	 *
	 * @since 3.0.0
	 *
	 * @see SV_WC_Payment_Gateway_Plugin::add_admin_notices()
	 */
	protected function add_ssl_admin_notices() {

		if ( ! $this->requires_ssl() ) {
			return;
		}

		foreach ( $this->get_gateways() as $gateway ) {

			// don't display any notices for disabled gateways
			if ( ! $gateway->is_enabled() ) {
				continue;
			}

			// SSL check if gateway enabled/production mode
			if ( ! wc_checkout_is_https() ) {

				if ( $gateway->is_production_environment() && $this->get_admin_notice_handler()->should_display_notice( 'ssl-required' ) ) {

					/* translators: Placeholders: %1$s - plugin name, %2$s - <a> tag, %3$s - </a> tag */
					$message = sprintf( esc_html__( '%1$s: WooCommerce is not being forced over SSL; your customers\' payment data may be at risk. %2$sVerify your site URLs here%3$s', 'woocommerce-plugin-framework' ),
						'<strong>' . $this->get_plugin_name() . '</strong>',
						'<a href="' . admin_url( 'options-general.php' ) . '">',
						' &raquo;</a>'
					);

					$this->get_admin_notice_handler()->add_admin_notice( $message, 'ssl-required', array(
						'notice_class' => 'error',
					) );

					// just show the message once for plugins with multiple gateway support
					break;
				}

			} elseif ( $this->require_tls_1_2() && ! $this->is_tls_1_2_available() ) {

				/* translators: Placeholders: %s - payment gateway name */
				$message = sprintf( esc_html__( "%s will soon require TLS 1.2 support to process transactions and your server environment may need to be updated. Please contact your hosting provider to confirm that your site can send and receive TLS 1.2 connections and request they make any necessary updates.", 'woocommerce-plugin-framework' ), '<strong>' . $gateway->get_method_title() . '</strong>' );

				$this->get_admin_notice_handler()->add_admin_notice( $message, 'tls-1-2-required', array(
					'notice_class'            => 'notice-warning',
					'always_show_on_settings' => false,
				) );

				// just show the message once for plugins with multiple gateway support
				break;
			}
		}
	}


	/**
	 * Adds any currency admin notices.
	 *
	 * Checks if a particular currency is required and not being used and adds a
	 * dismissible admin notice if so.
	 *
	 * @since 3.0.0
	 *
	 * @see SV_WC_Payment_Gateway_Plugin::render_admin_notices()
	 */
	protected function add_currency_admin_notices() {

		// report any currency issues
		if ( $this->get_accepted_currencies() ) {

			// we might have a currency issue, go through any gateways provided by this plugin and see which ones (or all) have any unmet currency requirements
			// (gateway classes will already be instantiated, so it's not like this is a huge deal)
			$gateways = array();
			foreach ( $this->get_gateways() as $gateway ) {
				if ( $gateway->is_enabled() && ! $gateway->currency_is_accepted() ) {
					$gateways[] = $gateway;
				}
			}

			if ( count( $gateways ) == 0 ) {
				// no active gateways with unmet currency requirements
				return;
			} elseif ( count( $gateways ) == 1 && count( $this->get_gateways() ) > 1 ) {
				// one gateway out of many has a currency issue
				$suffix              = '-' . $gateway->get_id();
				$name                = $gateway->get_method_title();
				$accepted_currencies = $gateway->get_accepted_currencies();
			} else {
				// multiple gateways have a currency issue
				$suffix              = '';
				$name                = $this->get_plugin_name();
				$accepted_currencies = $this->get_accepted_currencies();
			}

			/* translators: [Plugin name] accepts payments in [currency/list of currencies] only */
			$message = sprintf(
				/* translators: Placeholders: %1$s - plugin name, %2$s - a currency/comma-separated list of currencies, %3$s - <a> tag, %4$s - </a> tag */
				_n(
					'%1$s accepts payment in %2$s only. %3$sConfigure%4$s WooCommerce to accept %2$s to enable this gateway for checkout.',
					'%1$s accepts payment in one of %2$s only. %3$sConfigure%4$s WooCommerce to accept one of %2$s to enable this gateway for checkout.',
					count( $accepted_currencies ),
					'woocommerce-plugin-framework'
				),
				$name,
				'<strong>' . implode( ', ', $accepted_currencies ) . '</strong>',
				'<a href="' . $this->get_general_configuration_url() . '">',
				'</a>'
			);

			$this->get_admin_notice_handler()->add_admin_notice( $message, 'accepted-currency' . $suffix, array(
				'notice_class' => 'error',
			) );

		}
	}


	/**
	 * Adds notices about enabled debug logging.
	 *
	 * @since 5.3.0
	 */
	protected function add_debug_setting_notices() {

		foreach ( $this->get_gateways() as $gateway ) {

			if ( $gateway->is_enabled() && $gateway->is_production_environment() && ! $gateway->debug_off() ) {

				$is_gateway_settings = $this->is_payment_gateway_configuration_page( $gateway->get_id() );

				$message = sprintf(
					/* translators: Placeholders: %1$s - payment gateway name, %2$s - opening <a> tag, %3$s - closing </a> tag */
					__( 'Heads up! %1$s is currently configured to log transaction data for debugging purposes. If you are not experiencing any problems with payment processing, we recommend %2$sturning off Debug Mode%3$s', 'woocommerce-plugin-framework' ),
					$gateway->get_method_title(),
					! $is_gateway_settings ? '<a href="' . esc_url( $this->get_payment_gateway_configuration_url( $gateway->get_id() ) ) . '">' : '', ! $is_gateway_settings ? ' &raquo;</a>' : ''
				);

				$this->get_admin_notice_handler()->add_admin_notice( $message, 'debug-in-production', array(
					'notice_class' => 'notice-warning',
				) );

				break;
			}
		}
	}


	/**
	 * Adds notices about gateways not being configured.
	 *
	 * @since 5.4.2
	 */
	protected function add_gateway_not_configured_notices() {

		$is_enhanced_admin_available = SV_WC_Plugin_Compatibility::is_enhanced_admin_available();

		foreach ( $this->get_gateways() as $gateway ) {

			$note_name = $gateway->get_id_dasherized() . '-not-configured';

			if ( $gateway->is_enabled() && ! $gateway->is_configured() && ! $gateway->inherit_settings() ) {

				if ( $is_enhanced_admin_available ) {

					try {

						if ( $note = Admin\Notes_Helper::get_note_with_name( $note_name ) ) {

							// if on the problem gateway's configuration page, revive the existing note that may have been dismissed
							if ( WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED === $note->get_status() && $this->is_payment_gateway_configuration_page( $gateway->get_id() ) ) {
								$note->set_status( WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED );
							}

						} else {

							$note = new WC_Admin_Note();

							$note->set_name( $note_name );
							$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_ERROR );
							$note->set_source( $gateway->get_id_dasherized() );

							$note->set_title( sprintf(
								/* translators: Placeholders: %s - gateway name */
								__( '%s is not configured', 'woocommerce-plugin-framework' ),
								$gateway->get_method_title()
							) );

							$note->set_content( $gateway->get_not_configured_error_message() );
						}

						$note->set_actions( [] );

						// add the action buttons if not on the gateway's configuration page
						if ( ! $this->is_payment_gateway_configuration_page( $gateway->get_id() ) ) {
							$note->add_action( 'configure', __( 'Configure', 'woocommerce-plugin-framework' ), $this->get_settings_url( $gateway->get_id() ), WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED, true );
							$note->add_action( 'dismiss', __( 'Dismiss', 'woocommerce-plugin-framework' ) );
						}

						$note->save();

					} catch ( \Exception $exception ) {}
				}

				// if not an enhanced admin screen, output the legacy style notice
				if ( ! SV_WC_Helper::is_enhanced_admin_screen() ) {

					$this->get_admin_notice_handler()->add_admin_notice( $gateway->get_not_configured_error_message(), $gateway->get_id() . '-not-configured', [
						'notice_class' => 'error',
					] );
				}

			// if all's well with this gateway, make sure and delete any previously added notes
			} elseif ( $is_enhanced_admin_available && Admin\Notes_Helper::note_with_name_exists( $note_name ) ) {

				WC_Admin_Notes::delete_notes_with_name( $note_name );
			}
		}
	}


	/**
	 * Adds notices about Apple Pay not supported in the current WooCommerce version.
	 *
	 * @since 5.5.1
	 */
	protected function add_apple_pay_not_supported_notices() {

		if ( 'wc-settings' === SV_WC_Helper::get_requested_value( 'page' ) && SV_WC_Plugin_Compatibility::is_wc_version_lt( '3.2' ) && $this->is_apple_pay_activated() ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				sprintf(
					/* translators: Placeholders: %1$s - plugin name, %2$s - opening <a> HTML link tag, %3$s - closing </a> HTML link tag */
					__( 'Heads up! Apple Pay for %1$s requires WooCommerce version 3.2 or greater. Please %2$supdate WooCommerce%3$s.', 'woocommerce-plugin-framework' ),
					$this->get_plugin_name(),
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) .'">',
					'</a>'
				),
				$this->get_id_dasherized() . '-apple-pay-requires-wc-version-3-2',
				[ 'notice_class' => 'error' ]
			);
		}
	}


	/**
	 * Adds notices about Google Pay not supported in the current WooCommerce version.
	 *
	 * @since 5.10.0
	 */
	protected function add_google_pay_not_supported_notices() {

		if ( 'wc-settings' === SV_WC_Helper::get_requested_value( 'page' ) && SV_WC_Plugin_Compatibility::is_wc_version_lt( '3.2' ) && $this->supports_google_pay() ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				sprintf(
					/* translators: Placeholders: %1$s - plugin name, %2$s - opening <a> HTML link tag, %3$s - closing </a> HTML link tag */
					__( 'Heads up! Google Pay for %1$s requires WooCommerce version 3.2 or greater. Please %2$supdate WooCommerce%3$s.', 'woocommerce-plugin-framework' ),
					$this->get_plugin_name(),
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) .'">',
					'</a>'
				),
				$this->get_id_dasherized() . '-google-pay-requires-wc-version-3-2',
				[ 'notice_class' => 'error' ]
			);
		}
	}


	/** Integration methods ***************************************************/


	/**
	 * Checks if a supported integration is activated (Subscriptions or Pre-Orders)
	 * and adds a notice if a gateway supports the integration *and* tokenization,
	 * but tokenization is not enabled
	 *
	 * @since 4.0.0
	 */
	protected function add_integration_requires_tokenization_notices() {

		// either integration requires tokenization
		if ( $this->is_subscriptions_active() || $this->is_pre_orders_active() ) {

			foreach ( $this->get_gateways() as $gateway ) {

				$tokenization_supported_but_not_enabled = $gateway->supports_tokenization() && ! $gateway->tokenization_enabled();

				// subscriptions
				if ( $this->is_subscriptions_active() && $gateway->is_enabled() && $tokenization_supported_but_not_enabled ) {

					/* translators: Placeholders: %1$s - payment gateway title (such as Authorize.net, Braintree, etc), %2$s - <a> tag, %3$s - </a> tag */
					$message = sprintf(
						esc_html__( '%1$s is inactive for subscription transactions. Please %2$senable tokenization%3$s to activate %1$s for Subscriptions.', 'woocommerce-plugin-framework' ),
						$gateway->get_method_title(),
						'<a href="' . $this->get_payment_gateway_configuration_url( $gateway->get_id() ) . '">',
						'</a>'
					);

					// add notice -- allow it to be dismissed even on the settings page as the admin may not want to use subscriptions with a particular gateway
					$this->get_admin_notice_handler()->add_admin_notice( $message, 'subscriptions-tokenization-' . $gateway->get_id(), array(
						'always_show_on_settings' => false,
						'notice_class'            => 'error',
					) );
				}

				// pre-orders
				if ( $this->is_pre_orders_active() && $gateway->is_enabled() && $tokenization_supported_but_not_enabled ) {

					/* translators: Placeholders: %1$s - payment gateway title (such as Authorize.net, Braintree, etc), %2$s - <a> tag, %3$s - </a> tag */
					$message = sprintf(
						esc_html__( '%1$s is inactive for pre-order transactions. Please %2$senable tokenization%3$s to activate %1$s for Pre-Orders.', 'woocommerce-plugin-framework' ),
						$gateway->get_method_title(),
						'<a href="' . $this->get_payment_gateway_configuration_url( $gateway->get_id() ) . '">',
						'</a>'
					);

					// add notice -- allow it to be dismissed even on the settings page as the admin may not want to use pre-orders with a particular gateway
					$this->get_admin_notice_handler()->add_admin_notice( $message, 'pre-orders-tokenization-' . $gateway->get_id(), array(
						'always_show_on_settings' => false,
						'notice_class'            => 'error',
					) );
				}
			}
		}
	}


	/**
	 * Edit the Subscriptions automatic renewal payments support column content
	 * when a gateway supports subscriptions (via tokenization) but tokenization
	 * is not enabled
	 *
	 * @since 4.1.0
	 *
	 * @param string $html column content
	 * @param \WC_Payment_Gateway|SV_WC_Payment_Gateway $gateway payment gateway being checked for support
	 * @return string html
	 */
	public function subscriptions_maybe_edit_renewal_support_status( $html, $gateway ) {

		// only for our gateways
		if ( ! in_array( $gateway->id, $this->get_gateway_ids() ) ) {
			return $html;
		}

		if ( $gateway->is_enabled() && $gateway->supports_tokenization() && ! $gateway->tokenization_enabled() ) {

			$tool_tip = esc_attr__( 'You must enable tokenization for this gateway in order to support automatic renewal payments with the WooCommerce Subscriptions extension.', 'woocommerce-plugin-framework' );
			$status   = esc_html__( 'Inactive', 'woocommerce-plugin-framework' );

			$html = sprintf( '<a href="%1$s"><span class="sv-wc-payment-gateway-renewal-status-inactive tips" data-tip="%2$s">%3$s</span></a>',
						esc_url( $this->get_payment_gateway_configuration_url( $gateway->get_id() ) ),
						$tool_tip, $status );
		}

		return $html;
	}


	/**
	 * Add some inline CSS to render the failed order status icon for the
	 * automatic renewal payment support status column
	 *
	 * @since 4.1.0
	 */
	public function subscriptions_add_renewal_support_status_inline_style() {

		if ( SV_WC_Helper::is_current_screen( SV_WC_Plugin_Compatibility::normalize_wc_screen_id() ) ) {
			wp_add_inline_style( 'woocommerce_admin_styles', '.sv-wc-payment-gateway-renewal-status-inactive{font-size:1.4em;display:block;text-indent:-9999px;position:relative;height:1em;width:1em;cursor:pointer}.sv-wc-payment-gateway-renewal-status-inactive:before{line-height:1;margin:0;position:absolute;width:100%;height:100%;content:"\e016";color:#ffba00;font-family:WooCommerce;speak:none;font-weight:400;font-variant:normal;text-transform:none;-webkit-font-smoothing:antialiased;text-indent:0;top:0;left:0;text-align:center}' );
		}
	}


	/**
	 * Add gateway information to the system status report.
	 *
	 * @since 4.3.0
	 */
	public function add_system_status_information() {

		foreach ( $this->get_gateways() as $gateway ) {

			// Skip gateways that aren't enabled
			if ( ! $gateway->is_enabled() ) {
				continue;
			}

			$environment = $gateway->get_environment_name();

			include( $this->get_payment_gateway_framework_path() . '/admin/views/html-admin-gateway-status.php' );
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Determines if the plugin supports the capture charge feature.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function supports_capture_charge() {

		return $this->supports( self::FEATURE_CAPTURE_CHARGE );
	}


	/**
	 * Returns true if the gateway supports the named feature
	 *
	 * @since 1.0.0
	 * @param string $feature the feature
	 * @return boolean true if the named feature is supported
	 */
	public function supports( $feature ) {
		return in_array( $feature, $this->supports );
	}


	/** Getter methods ******************************************************/


	/**
	 * Gets the privacy handler instance.
	 *
	 * @since 5.1.4
	 *
	 * @return SV_WC_Payment_Gateway_Privacy
	 */
	public function get_privacy_instance() {

		return $this->privacy_handler;
	}


	/**
	 * Get the admin order handler instance.
	 *
	 * @since 5.0.0
	 *
	 * @return SV_WC_Payment_Gateway_Admin_Order
	 */
	public function get_admin_order_handler() {
		return $this->admin_order_handler;
	}


	/**
	 * Get the admin user handler instance.
	 *
	 * @since 4.3.0
	 *
	 * @return SV_WC_Payment_Gateway_Admin_User_Handler
	 */
	public function get_admin_user_handler() {
		return $this->admin_user_handler;
	}


	/**
	 * Returns the gateway settings option name for the identified gateway.
	 * Defaults to woocommerce_{gateway id}_settings
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway_id
	 * @return string the gateway settings option name
	 */
	protected function get_gateway_settings_name( $gateway_id ) {

		return 'woocommerce_' . $gateway_id . '_settings';

	}


	/**
	 * Returns the settings array for the identified gateway.  Note that this
	 * will not include any defaults if the gateway has yet to be saved
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway_id gateway identifier
	 * @return array settings array
	 */
	public function get_gateway_settings( $gateway_id ) {

		return get_option( $this->get_gateway_settings_name( $gateway_id ) );
	}


	/**
	 * Returns true if this plugin requires SSL to function properly
	 *
	 * @since 1.0.0
	 *
	 * @return boolean true if this plugin requires ssl
	 */
	protected function requires_ssl() {
		return $this->require_ssl;
	}


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::get_settings_url()
	 * @param string $gateway_id the gateway identifier
	 * @return string gateway settings URL
	 */
	public function get_settings_url( $gateway_id = null ) {

		// default to first gateway
		if ( is_null( $gateway_id ) || $gateway_id === $this->get_id() ) {
			reset( $this->gateways );
			$gateway_id = key( $this->gateways );
		}

		return $this->get_payment_gateway_configuration_url( $gateway_id );
	}


	/**
	 * Returns the admin configuration url for a gateway
	 *
	 * @since 3.0.0
	 *
	 * @param string $gateway_id the gateway ID
	 * @return string admin configuration url for the gateway
	 */
	public function get_payment_gateway_configuration_url( $gateway_id ) {

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $gateway_id );
	}


	/**
	 * Returns true if the current page is the admin configuration page for a gateway
	 *
	 * @since 3.0.0
	 *
	 * @param string $gateway_id the gateway ID
	 * @return boolean true if the current page is the admin configuration page for the gateway
	 */
	public function is_payment_gateway_configuration_page( $gateway_id ) {

		return isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] &&
		isset( $_GET['tab'] ) && 'checkout' == $_GET['tab'] &&
		isset( $_GET['section'] ) && $gateway_id === $_GET['section'];
	}


	/**
	 * Get a gateway's settings screen section ID.
	 *
	 * This was used as a helper method for WC 2.5 compatibility, but is no longer needed and now deprecated.
	 *
	 * @since 4.4.0
	 * @deprecated 5.0.1
	 *
	 * @param string $gateway_id the gateway ID
	 * @return string
	 */
	public function get_payment_gateway_configuration_section( $gateway_id ) {

		wc_deprecated_function( __METHOD__, '5.0.1', 'strtolower( $gateway_id )' );

		return strtolower( $gateway_id );
	}


	/**
	 * Adds the given gateway id and gateway class name as an available gateway
	 * supported by this plugin
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway_id the gateway identifier
	 * @param string $gateway_class_name the corresponding gateway class name
	 */
	public function add_gateway( $gateway_id, $gateway_class_name ) {

		$this->gateways[ $gateway_id ] = array( 'gateway_class_name' => $gateway_class_name, 'gateway' => null );
	}


	/**
	 * Gets all supported gateway class names; typically this will be just one,
	 * unless the plugin supports credit card and echeck variations
	 *
	 * @since 1.0.0
	 *
	 * @return array of string gateway class names
	 */
	public function get_gateway_class_names() {

		assert( ! empty( $this->gateways ) );

		$gateway_class_names = array();

		foreach ( $this->gateways as $gateway ) {
			$gateway_class_names[] = $gateway['gateway_class_name'];
		}

		return $gateway_class_names;
	}


	/**
	 * Gets the gateway class name for the given gateway id
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway_id the gateway identifier
	 * @return string gateway class name
	 */
	public function get_gateway_class_name( $gateway_id ) {

		assert( isset( $this->gateways[ $gateway_id ]['gateway_class_name'] ) );

		return $this->gateways[ $gateway_id ]['gateway_class_name'];
	}


	/**
	 * Gets all supported gateway objects; typically this will be just one,
	 * unless the plugin supports credit card and echeck variations
	 *
	 * @since 1.0.0
	 *
	 * @return SV_WC_Payment_Gateway[]
	 */
	public function get_gateways() {

		assert( ! empty( $this->gateways ) );

		$gateways = array();

		foreach ( $this->get_gateway_ids() as $gateway_id ) {
			$gateways[] = $this->get_gateway( $gateway_id );
		}

		return $gateways;
	}


	/**
	 * Adds the given $gateway to the internal gateways store
	 *
	 * @since 2.2.0
	 *
	 * @param string $gateway_id the gateway identifier
	 * @param SV_WC_Payment_Gateway $gateway the gateway object
	 */
	public function set_gateway( $gateway_id, $gateway ) {
		$this->gateways[ $gateway_id ]['gateway'] = $gateway;
	}


	/**
	 * Returns the identified gateway object
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway_id optional gateway identifier, defaults to first gateway, which will be the credit card gateway in plugins with support for both credit cards and echecks
	 * @return SV_WC_Payment_Gateway the gateway object
	 */
	public function get_gateway( $gateway_id = null ) {

		// default to first gateway
		if ( is_null( $gateway_id ) ) {
			reset( $this->gateways );
			$gateway_id = key( $this->gateways );
		}

		if ( ! isset( $this->gateways[ $gateway_id ]['gateway'] ) ) {

			// instantiate and cache
			$gateway_class_name = $this->get_gateway_class_name( $gateway_id );
			$this->set_gateway( $gateway_id, new $gateway_class_name() );
		}

		return $this->gateways[ $gateway_id ]['gateway'];
	}


	/**
	 * Returns true if the plugin supports this gateway
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway_id the gateway identifier
	 * @return boolean true if the plugin has this gateway available, false otherwise
	 */
	public function has_gateway( $gateway_id ) {
		return isset( $this->gateways[ $gateway_id ] );
	}


	/**
	 * Returns all available gateway ids for the plugin
	 *
	 * @since 1.0.0
	 *
	 * @return array of gateway id strings
	 */
	public function get_gateway_ids() {

		assert( ! empty( $this->gateways ) );

		return array_keys( $this->gateways );
	}


	/**
	 * Returns the gateway for a given token
	 *
	 * @since 4.0.0
	 *
	 * @param string|int $user_id the user ID associated with the token
	 * @param string $token the token string
	 * @return SV_WC_Payment_Gateway|null gateway if found, null otherwise
	 */
	public function get_gateway_from_token( $user_id, $token ) {

		foreach ( $this->get_gateways() as $gateway ) {

			if ( $gateway->get_payment_tokens_handler()->user_has_token( $user_id, $token ) ) {
				return $gateway;
			}
		}

		return null;
	}


	/**
	 * No-op the plugin class implementation so the payment gateway class can
	 * implement its own request logging. This is primarily done to keep the log
	 * files separated by gateway ID
	 *
	 * @see SV_WC_Plugin::add_api_request_logging()
	 *
	 * @since 2.2.0
	 */
	public function add_api_request_logging() { }


	/**
	 * Returns the set of accepted currencies, or empty array if all currencies
	 * are accepted.  This is the intersection of all currencies accepted by
	 * any gateways this plugin supports.
	 *
	 * @since 1.0.0
	 *
	 * @return array of accepted currencies
	 */
	public function get_accepted_currencies() {
		return $this->currencies;
	}


	/**
	 * Checks is WooCommerce Subscriptions is active
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if the WooCommerce Subscriptions plugin is active, false if not active
	 */
	public function is_subscriptions_active() {

		if ( is_bool( $this->subscriptions_active ) ) {
			return $this->subscriptions_active;
		}

		return $this->subscriptions_active = $this->is_plugin_active( 'woocommerce-subscriptions.php' );
	}


	/**
	 * Checks is WooCommerce Pre-Orders is active
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if WC Pre-Orders is active, false if not active
	 */
	public function is_pre_orders_active() {

		if ( is_bool( $this->pre_orders_active ) ) {
			return $this->pre_orders_active;
		}

		return $this->pre_orders_active = $this->is_plugin_active( 'woocommerce-pre-orders.php' );
	}


	/**
	 * Returns the loaded payment gateway framework __FILE__
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_payment_gateway_framework_file() {

		return __FILE__;
	}


	/**
	 * Returns the loaded payment gateway framework path, without trailing slash.
	 *
	 * This is the highest version payment gateway framework that was loaded by
	 * the bootstrap.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_payment_gateway_framework_path() {

		return untrailingslashit( plugin_dir_path( $this->get_payment_gateway_framework_file() ) );
	}


	/**
	 * Returns the absolute path to the loaded payment gateway framework image
	 * directory, without a trailing slash
	 *
	 * @since 4.0.0
	 *
	 * @return string relative path to framework image directory
	 */
	public function get_payment_gateway_framework_assets_path() {

		return $this->get_payment_gateway_framework_path() . '/assets';
	}


	/**
	 * Returns the loaded payment gateway framework assets URL, without a trailing slash
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_payment_gateway_framework_assets_url() {

		return untrailingslashit( plugins_url( '/assets', $this->get_payment_gateway_framework_file() ) );
	}


}


endif;
