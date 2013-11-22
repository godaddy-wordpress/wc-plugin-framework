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
 * # WooCommerce Payment Gateway Plugin Framework
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
 * ## Usage
 *
 * Extend this class and implement the following abstract methods:
 *
 * + `get_file()` - the implementation should be: <code>return __FILE__;</code>
 * + `get_plugin_name()` - returns the plugin name (implemented this way so it can be localized)
 * + `load_translation()` - load the plugin text domain
 *
 * ## Supports (zero or more):
 *
 * + `tokenization`     - adds actions to show/handle the "My Payment Methods" area of the customer's My Account page
 * + `customer_id`      - adds actions to show/persist the "Customer ID" area of the admin User edit page
 * + `transaction_link` - adds actions to render the merchant account transaction direct link on the Admin Order Edit page.  (Don't forget to override the SV_WC_Payment_Gateway::get_transaction_url() method!)
 *
 * @version 1.0
 */
abstract class SV_WC_Payment_Gateway_Plugin {

	/** Payment Gateway Framework Version */
	const VERSION = '1.0';

	/** Tokenization feature */
	const FEATURE_TOKENIZATION = 'tokenization';

	/** Customer ID feature */
	const FEATURE_CUSTOMER_ID = 'customer_id';

	/** Link to transaction feature */
	const FEATURE_TRANSACTION_LINK = 'transaction_link';

	/** Charge capture feature */
	const FEATURE_CAPTURE_CHARGE = 'capture_charge';


	/** @var string plugin id */
	private $id;

	/** @var string plugin text domain */
	private $text_domain;

	/** @var string version number */
	private $version;

	/** @var array optional associative array of gateway id to array( 'gateway_class_name' => string, 'gateway' => SV_WC_Payment_Gateway ) */
	private $gateways;

	/** @var string plugin path without trailing slash */
	private $plugin_path;

	/** @var string plugin uri */
	private $plugin_url;

	/** @var \WC_Logger instance */
	private $logger;

	/** @var array string names of required PHP extensions */
	private $dependencies = array();

	/** @var boolean true if this gateway requires SSL for processing transactions, false otherwise */
	private $require_ssl;

	/** @var array optional array of currency codes this gateway is allowed for */
	private $currencies = array();

	/** @var array named features that this gateway supports which require action from the parent plugin, including 'tokenization' */
	private $supports = array();

	/** @var bool helper for lazy subscriptions active check */
	private $subscriptions_active;

	/** @var bool helper for lazy pre-orders active check */
	private $pre_orders_active;


	/**
	 * Initialize the plugin
	 *
	 * Optional args:
	 *
	 * + `gateways` - array associative array of gateway id to gateway class name.  A single plugin might support more than one gateway, ie credit card, echeck.  Note that the credit card gateway must always be the first one listed.
	 * + `dependencies` - array string names of required PHP extensions
	 * + `require_ssl` - boolean true if this gateway requires SSL for processing transactions, false otherwise. Defaults to false
	 * + `currencies` -  array of currency codes this gateway is allowed for, defaults to all
	 * + `supports` - array named features that this gateway supports, including 'tokenization', 'transaction_link', 'customer_id'
	 *
	 * @since 1.0
	 * @param string $minimum_version the minimum Framework version required by the concrete gateway
	 * @param string $id plugin id
	 * @param string $version plugin version number
	 * @param string $text_domain the plugin text domain
	 * @param array $args plugin arguments
	 */
	public function __construct( $minimum_version, $id, $version, $text_domain, $args ) {

		// required params
		$this->id          = $id;
		$this->version     = $version;
		$this->text_domain = $text_domain;

		// check that the current version of the framework meets the minimum
		//  required by the concrete gateway.

		if ( ! $this->check_version( $minimum_version ) ) {

			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

				// render any admin notices
				add_action( 'admin_notices', array( $this, 'render_minimum_version_notice' ) );

				// AJAX handler to dismiss any warning/error notices
				add_action( 'wp_ajax_wc_payment_gateway_' . $this->get_id() . '_dismiss_message', array( $this, 'handle_dismiss_message' ) );

			}

			return;
		}

		// optional parameters: the supported gateways
		if ( isset( $args['gateways'] ) ) {

			foreach ( $args['gateways'] as $gateway_id => $gateway_class_name ) {
				$this->add_gateway( $gateway_id, $gateway_class_name );
			}

		}
		if ( isset( $args['dependencies'] ) )       $this->dependencies = $args['dependencies'];
		if ( isset( $args['require_ssl'] ) )        $this->require_ssl  = $args['require_ssl'];
		if ( isset( $args['currencies'] ) )         $this->currencies   = $args['currencies'];
		if ( isset( $args['supports'] ) )           $this->supports     = $args['supports'];

		// include library files after woocommerce is loaded
		add_action( 'woocommerce_loaded', array( $this, 'lib_includes' ) );

		if ( ! is_admin() && $this->supports( self::FEATURE_TOKENIZATION ) ) {

			// Handle any actions from the My Payment Methods section
			add_action( 'wp', array( $this, 'handle_my_payment_methods_actions' ) );

			// Add the 'Manage My Payment Methods' on the 'My Account' page for the gateway
			add_action( 'woocommerce_after_my_account', array( $this, 'add_my_payment_methods' ) );

		}

		// Admin
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			// render any admin notices
			add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );

			// show/persist customer id field on edit user pages, if supported
			if ( $this->supports( self::FEATURE_CUSTOMER_ID ) ) {

				// show the customer ID
				add_action( 'show_user_profile', array( $this, 'add_customer_id_meta_field' ) );
				add_action( 'edit_user_profile', array( $this, 'add_customer_id_meta_field' ) );

				// save the customer ID
				add_action( 'personal_options_update',  array( $this, 'save_customer_id_meta_field' ) );
				add_action( 'edit_user_profile_update', array( $this, 'save_customer_id_meta_field' ) );

			}

			// order admin link to transaction, if supported
			if ( $this->supports( self::FEATURE_TRANSACTION_LINK ) ) {
				add_action( 'woocommerce_order_actions_start', array( $this, 'order_meta_box_transaction_link' ) );
			}

			// add a 'Configure' link to the plugin action links
			add_filter( 'plugin_action_links_' . plugin_basename( $this->get_file() ), array( $this, 'plugin_action_links' ) );

			// run every time
			$this->do_install();
		}

		if ( $this->supports( self::FEATURE_CAPTURE_CHARGE ) ) {

			add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'maybe_capture_charge' ) );
			add_action( 'woocommerce_order_status_on-hold_to_completed',  array( $this, 'maybe_capture_charge' ) );

			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				add_filter( 'woocommerce_order_actions',                                       array( $this, 'maybe_add_order_action_charge_action' ) );
				add_action( 'woocommerce_order_action_' . $this->get_id() . '_capture_charge', array( $this, 'maybe_capture_charge' ) );
			}
		}

		// AJAX handler to dismiss any warning/error notices
		add_action( 'wp_ajax_wc_payment_gateway_' . $this->get_id() . '_dismiss_message', array( $this, 'handle_dismiss_message' ) );

		// Add classes to WC Payment Methods
		add_filter( 'woocommerce_payment_gateways', array( $this, 'load_gateways' ) );

		// Load translation files
		add_action( 'init', array( $this, 'load_translation' ) );
	}


	/**
	 * Adds any gateways supported by this plugin to the list of available payment gateways
	 *
	 * @since 1.0
	 * @param array $gateways
	 * @return array $gateways
	 */
	public function load_gateways( $gateways ) {

		$gateways = array_merge( $gateways, $this->get_gateway_class_names() );

		return $gateways;

	}


	/**
	 * Load plugin text domain.  This implementation should look simply like:
	 *
	 * load_plugin_textdomain( 'text-domain-string', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	 *
	 * Note that the actual text domain string should be used, and not a
	 * variable or constant, otherwise localization plugins (Codestyling) will
	 * not be able to detect the localization directory.
	 *
	 * @since 1.0
	 */
	abstract public function load_translation();


	/**
	 * Include required library files
	 *
	 * @since 1.0
	 */
	public function lib_includes() {

		// include framework files
		require_once( 'api/interface-wc-payment-gateway-api.php' );
		require_once( 'api/interface-wc-payment-gateway-api-request.php' );
		require_once( 'api/interface-wc-payment-gateway-api-response.php' );
		require_once( 'api/interface-wc-payment-gateway-api-authorization-response.php' );
		require_once( 'api/interface-wc-payment-gateway-api-create-payment-token-response.php' );
		require_once( 'api/interface-wc-payment-gateway-api-get-tokenized-payment-methods-response.php' );

		require_once( 'exceptions/class-wc-payment-gateway-feature-unsupported-exception.php' );
		require_once( 'exceptions/class-wc-payment-gateway-unimplemented-method-exception.php' );

		require_once( 'class-wc-payment-gateway.php' );
		require_once( 'class-wc-payment-gateway-direct.php' );
		require_once( 'class-wc-payment-gateway-hosted.php' );
		require_once( 'class-wc-payment-token.php' );

	}


	/** Frontend methods ******************************************************/


	/**
	 * Helper to add the 'My Cards' section to the 'My Account' page
	 *
	 * @since 1.0
	 */
	public function add_my_payment_methods() {

		foreach ( $this->get_gateways() as $gateway ) {

			if ( $gateway->supports_tokenization() && $gateway->is_available() ) {
				$gateway->show_my_payment_methods();
			}
		}

	}


	/**
	 * Helper to handle any actions from the 'My Cards' section on the 'My Account'
	 * page
	 *
	 * @since 1.0
	 */
	public function handle_my_payment_methods_actions() {

		if ( is_account_page() ) {

			foreach ( $this->get_gateways() as $gateway ) {

				if ( $gateway->supports_tokenization() )
					$gateway->handle_my_payment_methods_actions();

			}

		}

	}


	/** Admin methods ******************************************************/


	public function is_gateway_settings() {

		return isset( $_GET['page'] ) && 'woocommerce_settings' == $_GET['page'] &&
			isset( $_GET['tab'] ) && 'payment_gateways' == $_GET['tab'] &&
			isset( $_GET['section'] ) && in_array( $_GET['section'], $this->get_gateway_class_names() );

	}


	/**
	 * Checks if required PHP extensions are loaded and SSL is enabled. Adds an admin notice if either check fails.
	 * Also gateway settings can be checked as well.
	 *
	 * @since 1.0
	 */
	public function render_admin_notices() {

		$notice_rendered = false;

		// report any missing extensions
		$missing_extensions = $this->get_missing_dependencies();

		if ( count( $missing_extensions ) > 0 && ( ! $this->is_message_dismissed( 'missing-extensions' ) || $this->is_gateway_settings() ) ) {

			$message = sprintf(
				_n(
					'%s requires the %s PHP extension to function.  Contact your host or server administrator to configure and install the missing extension.',
					'%s requires the following PHP extensions to function: %s.  Contact your host or server administrator to configure and install the missing extensions.',
					count( $missing_extensions ),
					$this->text_domain
				),
				$this->get_plugin_name(),
				'<strong>' . implode( ', ', $missing_extensions ) . '</strong>'
			);

			// dismiss link unless we're on the payment gateway settings page, in which case we'll always display the notice
			$dismiss_link = '<a href="#" class="js-wc-payment-gateway-' . $this->get_id() . '-message-dismiss" data-message-id="missing-extensions">' . __( 'Dismiss', $this->text_domain ) . '</a>';
			if ( $this->is_gateway_settings() )
				$dismiss_link = '';

			echo '<div class="error"><p>' . $message . ' ' . $dismiss_link . '</p></div>';

			$notice_rendered = true;

		}

		// report any currency issues
		if ( $this->currencies && ! in_array( get_woocommerce_currency(), $this->currencies ) )

		if ( count( $this->get_accepted_currencies() ) > 0 && ! in_array( get_woocommerce_currency(), $this->get_accepted_currencies() ) && ( ! $this->is_message_dismissed( 'accepted-currency' ) || $this->is_gateway_settings() ) ) {

			$message = sprintf(
				_n(
					'%s accepts payment in %s only.  <a href="%s">Configure</a> WooCommerce to accept %s to enable this gateway for checkout.',
					'%s accepts payment in one of %s only.  <a href="%s">Configure</a> WooCommerce to accept one of %s to enable this gateway for checkout.',
					count( $this->get_accepted_currencies() ),
					$this->text_domain
				),
				$this->get_plugin_name(),
				'<strong>' . implode( ', ', $this->get_accepted_currencies() ) . '</strong>',
				admin_url( 'admin.php?page=woocommerce_settings&tab=general' ),
				'<strong>' . implode( ', ', $this->get_accepted_currencies() ) . '</strong>'
			);

			// dismiss link unless we're on the payment gateway settings page, in which case we'll always display the notice
			$dismiss_link = '<a href="#" class="js-wc-payment-gateway-' . $this->get_id() . '-message-dismiss" data-message-id="accepted-currency">' . __( 'Dismiss', $this->text_domain ) . '</a>';
			if ( $this->is_gateway_settings() )
				$dismiss_link = '';

			echo '<div class="error"><p>' . $message . ' ' . $dismiss_link . '</p></div>';

			$notice_rendered = true;

		}


		// check settings:  gateway active and SSL enabled
		// TODO: does this work when a plugin is first activated, before the settings page has been saved?
		if ( $this->requires_ssl() && ( ! $this->is_message_dismissed( 'ssl-required' ) || $this->is_gateway_settings() ) ) {

			foreach ( $this->get_gateway_ids() as $gateway_id ) {

				$settings = $this->get_gateway_settings( $gateway_id );

				if ( isset( $settings['enabled'] ) && 'yes' == $settings['enabled'] ) {

					if ( isset( $settings['environment'] ) && 'production' == $settings['environment'] ) {

						// SSL check if gateway enabled/production mode
						if ( 'no' === get_option( 'woocommerce_force_ssl_checkout' ) ) {

							// dismiss link unless we're on the payment gateway settings page, in which case we'll always display the notice
							$dismiss_link = '<a href="#" class="js-wc-payment-gateway-' . $this->get_id() . '-message-dismiss" data-message-id="ssl-required">' . __( 'Dismiss', $this->text_domain ) . '</a>';
							if ( $this->is_gateway_settings() )
								$dismiss_link = '';

							echo '<div class="error"><p>' . sprintf( __( "%s: WooCommerce is not being forced over SSL; your customer's payment data may be at risk.", $this->text_domain ), '<strong>' . $this->get_plugin_name() . '</strong>' ) . ' ' . $dismiss_link . '</p></div>';

							$notice_rendered = true;

							// just show the message once for plugins with multiple gateway support
							break;
						}

					}
				}
			}
		}

		// if a notice was rendered, add the javascript code to handle the notice dismiss action
		if ( $notice_rendered ) {
			$this->render_admin_dismissible_notice_js();
		}
	}


	/**
	 * Render the javascript to handle the notice "dismiss" functionality
	 *
	 * @since 1.0
	 */
	protected function render_admin_dismissible_notice_js() {

		global $woocommerce;

		ob_start();
		?>
		// hide notice
		$( 'a.js-wc-payment-gateway-<?php echo $this->get_id(); ?>-message-dismiss' ).click( function() {

			$.get(
				ajaxurl,
				{
					action: 'wc_payment_gateway_<?php echo $this->get_id(); ?>_dismiss_message',
					messageid: $( this ).data( 'message-id' )
				}
			);

			$( this ).closest( 'div.error' ).fadeOut();

			return false;
		} );
		<?php
		$javascript = ob_get_clean();

		$woocommerce->add_inline_js( $javascript );
	}


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @since 1.0
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
	 */
	public function plugin_action_links( $actions ) {

		$custom_actions = array();

		// settings url(s)
		foreach ( $this->get_gateway_ids() as $gateway_id )
			$custom_actions[ 'configure_' . $gateway_id ] = $this->get_settings_link( $gateway_id );

		// documentation url if any
		if ( $this->get_documentation_url() )
			$custom_actions['docs'] = sprintf( '<a href="%s">%s</a>', $this->get_documentation_url(), __( 'Docs', $this->text_domain ) );

		// support url
		$custom_actions['support'] = sprintf( '<a href="%s">%s</a>', 'http://support.woothemes.com/', __( 'Support', $this->text_domain ) );

		// optional review link
		if ( $this->get_product_page_url() )
			$custom_actions['review'] = sprintf( '<a href="%s">%s</a>', $this->get_product_page_url() . '#review_form', __( 'Write a Review', $this->text_domain ) );

		// add the links to the front of the actions list
		return array_merge( $custom_actions, $actions );

	}


	/**
	 * Add a button to the order actions meta box to view the order in the
	 * gateway merchant account, if supported
	 *
	 * @since 1.0
	 * @see SV_WC_Payment_Gateway::get_transaction_url()
	 * @see SV_WC_Payment_Gateway::order_meta_box_transaction_link()
	 * @param int $post_id the order identifier
	 */
	public function order_meta_box_transaction_link( $post_id ) {

		$order = new WC_Order( $post_id );

		if ( $this->has_gateway( $order->payment_method ) ) {

			$this->get_gateway( $order->payment_method )->order_meta_box_transaction_link( $order );

		}

	}


	/**
	 * Display fields for the Customer ID meta for each and every environment,
	 * on the view/edit user page, if this gateway uses Customer ID's
	 * (ie SV_WC_Payment_Gateway::get_customer_id_user_meta_name() does not
	 * return false).
	 *
	 * If only a single environment is defined, the field will be named
	 * "Customer ID".  If more than one environment is defined the field will
	 * be named like "Customer ID (Production)", etc to distinguish them.
	 *
	 * NOTE: the plugin id, rather than gateway id, is used here, because it's
	 * assumed that in the case of a plugin having multiple gateways (ie credit
	 * card and eCheck) the customer id will be the same between them.
	 *
	 * @since 1.0
	 * @see SV_WC_Payment_Gateway::get_customer_id_user_meta_name()
	 * @see SV_WC_Payment_Gateway_Plugin::save_customer_id_meta_field()
	 * @param WP_User $user user object for the current edit page
	 */
	public function add_customer_id_meta_field( $user ) {

		// bail if the current user is not allowed to manage woocommerce
		if ( ! current_user_can( 'manage_woocommerce' ) )
			return;

		// if this plugin has multiple gateways available, just get the first one
		$gateway      = $this->get_gateway();
		$environments = $gateway->get_environments();

		// customer id's not supported
		if ( false === $gateway->get_customer_id_user_meta_name() )
			return;

		?>
		<h3><?php printf( __( '%s Customer Details', $this->text_domain ), $this->get_plugin_name() ); ?></h3>
		<table class="form-table">
		<?php

		foreach ( $environments as $environment_id => $environment_name ) :

			?>
				<tr>
					<th><label for="<?php printf( '_wc_%s_customer_id_%s', $this->get_id(), $environment_id ); ?>"><?php echo count( $environments ) > 1 ? sprintf( __( 'Customer ID (%s)', $this->text_domain ), $environment_name ) : __( 'Customer ID', $this->text_domain ); ?></label></th>
					<td>
						<input type="text" name="<?php printf( '_wc_%s_customer_id_%s', $this->get_id(), $environment_id ); ?>" id="<?php printf( '_wc_%s_customer_id_%s', $this->get_id(), $environment_id ); ?>" value="<?php echo esc_attr( $gateway->get_customer_id( $user->ID, array( 'environment_id' => $environment_id, 'autocreate' => false ) ) ); ?>" class="regular-text" /><br/>
						<span class="description"><?php echo count( $environments ) > 1 ? sprintf( __( 'The customer ID for the user in the %s environment. Only edit this if necessary.', $this->text_domain ), $environment_name ) : __( 'The customer ID for the user in the environment. Only edit this if necessary.', $this->text_domain ); ?></span>
					</td>
				</tr>
			<?php

		endforeach;

		?>
		</table>
		<?php
	}


	/**
	 * Persist the user gateway Customer ID for each defined environment, if
	 * the gateway uses Customer ID's
	 *
	 * NOTE: the plugin id, rather than gateway id, is used here, because it's
	 * assumed that in the case of a plugin having multiple gateways (ie credit
	 * card and eCheck) the customer id will be the same between them.
	 *
	 * @since 1.0
	 * @see SV_WC_Payment_Gateway_Plugin::add_customer_id_meta_field()
	 * @param int $user_id identifies the user to save the settings for
	 */
	public function save_customer_id_meta_field( $user_id ) {

		// bail if the current user is not allowed to manage woocommerce
		if ( ! current_user_can( 'manage_woocommerce' ) )
			return;

		// if this plugin has multiple gateways available, just get the first one
		$gateway      = $this->get_gateway();
		$environments = $gateway->get_environments();

		// customer id's not supported
		if ( false === $gateway->get_customer_id_user_meta_name() )
			return;

		// valid environments only
		foreach ( array_keys( $environments ) as $environment_id ) {

			// update (or blank out) customer id for the given environment
			if ( isset( $_POST[ '_wc_' . $this->get_id() . '_customer_id_' . $environment_id ] ) ) {
				$gateway->update_customer_id( $user_id, trim( $_POST[ '_wc_' . $this->get_id() . '_customer_id_' . $environment_id ] ), $environment_id );
			}

		}
	}


	/** Capture Charge Feature ******************************************************/


	/**
	 * Capture a credit card charge for a prior authorization if this payment
	 * method was used for the given order, the charge hasn't already been
	 * captured, and the gateway supports issuing a capture request
	 *
	 * @since 1.0
	 * @param WC_Order|int $order the order identifier or order object
	 */
	public function maybe_capture_charge( $order ) {

		if ( ! is_object( $order ) ) {
			$order = new WC_Order( $order );
		}

		// bail if the order wasn't payed for with this gateway
		if ( ! $this->has_gateway( $order->payment_method ) ) {
			return;
		}

		// check whether the charge has already been captured by this gateway
		$charge_captured = get_post_meta( $order->id, '_wc_' . $order->payment_method . '_charge_captured', true );
		if ( 'yes' == $charge_captured ) {
			return;
		}

		// finally, ensure that it supports captures
		if ( ! $this->can_capture_charge() ) {
			return;
		}

		// remove order status change actions, otherwise we get a whole bunch of capture calls and errors
		remove_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'maybe_capture_charge' ) );
		remove_action( 'woocommerce_order_status_on-hold_to_completed',  array( $this, 'maybe_capture_charge' ) );
		remove_action( 'woocommerce_order_action_' . $this->get_id() . '_capture_charge', array( $this, 'maybe_capture_charge' ) );

		// perform the capture
		$this->get_gateway( $order->payment_method )->do_credit_card_capture( $order );
	}


	/**
	 * Add a "Capture Charge" action to the Admin Order Edit Order
	 * Actions dropdown
	 *
	 * @since 1.0
	 * @param array $actions available order actionss
	 */
	public function maybe_add_order_action_charge_action( $actions ) {

		$order = new WC_Order( $_REQUEST['post'] );

		// bail if the order wasn't payed for with this gateway
		if ( ! $this->has_gateway( $order->payment_method ) ) {
			return $actions;
		}

		// check whether the charge has already been captured by this gateway
		$charge_captured = get_post_meta( $order->id, '_wc_' . $order->payment_method . '_charge_captured', true );
		if ( 'yes' == $charge_captured ) {
			return $actions;
		}

		// finally, ensure that it supports captures
		if ( ! $this->can_capture_charge() ) {
			return $actions;
		}

		$actions[ $this->get_id() . '_capture_charge' ] = 'Capture Charge';

		return $actions;
	}


	/** AJAX methods ******************************************************/


	/**
	 * Dismiss the identified message
	 *
	 * @since 1.0
	 */
	public function handle_dismiss_message() {

		$this->dismiss_message( $_REQUEST['messageid'] );

	}


	/** Helper methods ******************************************************/


	/**
	 * Returns true if the gateway supports the named feature
	 *
	 * @since 1.0
	 * @param string $feature the feature
	 * @return boolean true if the named feature is supported
	 */
	public function supports( $feature ) {

		return in_array( $feature, $this->supports );
	}


	/**
	 * Returns true if the gateway supports the charge capture operation and it
	 * can be invoked
	 *
	 * @since 1.0
	 * @return boolean true if thes gateway supports the charge capture operation and it can be invoked
	 */
	public function can_capture_charge() {
		return $this->supports( self::FEATURE_CAPTURE_CHARGE ) && $this->get_gateway()->is_available();
	}


	/**
	 * Gets the string name of any required PHP extensions that are not loaded
	 *
	 * @since 1.0
	 * @return array of missing dependencies
	 */
	public function get_missing_dependencies() {

		$missing_extensions = array();

		foreach ( $this->get_dependencies() as $ext ) {

			if ( ! extension_loaded( $ext ) )
				$missing_extensions[] = $ext;
		}

		return $missing_extensions;
	}


	/**
	 * Saves errors or messages to WooCommerce Log (woocommerce/logs/gateway-id-xxx.txt)
	 *
	 * @since 1.0
	 * @param string $message error or message to save to log
	 * @param string $gateway_id optional gateway id to segment the files by, defaults to a combined log with plugin id
	 */
	public function log( $message, $gateway_id = null ) {

		global $woocommerce;

		if ( is_null( $gateway_id ) )
			$log_id = $this->get_id();
		else
			$log_id = $gateway_id;

		if ( ! is_object( $this->logger ) )
			$this->logger = $woocommerce->logger();

		$this->logger->add( $log_id, $message );

	}


	/**
	 * Marks the identified admin message as dismissed for the given user
	 *
	 * @since 1.0
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return boolean true if the message has been dismissed by the admin user
	 */
	protected function dismiss_message( $message_id, $user_id = null ) {

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		$dismissed_messages = get_user_meta( $user_id, '_wc_payment_gateway_' . $this->get_id() . '_dismissed_messages', true );

		$dismissed_messages[ $message_id ] = true;

		update_user_meta( $user_id, '_wc_payment_gateway_' . $this->get_id() . '_dismissed_messages', $dismissed_messages );

	}


	/**
	 * Returns true if the identified admin message has been dismissed for the
	 * given user
	 *
	 * @since 1.0
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return boolean true if the message has been dismissed by the admin user
	 */
	protected function is_message_dismissed( $message_id, $user_id = null ) {

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		$dismissed_messages = get_user_meta( $user_id, '_wc_payment_gateway_' . $this->get_id() . '_dismissed_messages', true );

		return isset( $dismissed_messages[ $message_id ] ) && $dismissed_messages[ $message_id ];

	}


	/** Getter methods ******************************************************/


	/**
	 * The implementation for this abstract method should simply be:
	 *
	 * return __FILE__;
	 *
	 * @since 1.0
	 * @return string the full path and filename of the plugin file
	 */
	abstract protected function get_file();


	/**
	 * Returns the plugin id
	 *
	 * @since 1.0
	 * @return string plugin id
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the plugin id with dashes in place of underscores, and
	 * appropriate for use in frontend element names, classes and ids
	 *
	 * @since 1.0
	 * @return string payment gateway id with dashes in place of underscores
	 */
	public function get_id_dasherized() {
		return str_replace( '_', '-', $this->get_id() );
	}


	/**
	 * Returns the plugin full name including "WooCommerce", ie
	 * "WooCommerce X Gateway".  This method is defined abstract for localization purposes
	 *
	 * @since 1.0
	 * @return string plugin name
	 */
	abstract public function get_plugin_name();


	/**
	 * Returns the plugin version name.  Defaults to wc_{plugin id}_version
	 *
	 * @since 1.0
	 * @return string the plugin version name
	 */
	protected function get_plugin_version_name() {
		return 'wc_' . $this->get_id() . '_version';
	}


	/**
	 * Returns the current version of the plugin
	 *
	 * @since 1.0
	 * @return string plugin version
	 */
	public function get_version() {

		return $this->version;

	}


	/**
	 * Returns the gateway settings option name for the identified gateway.
	 * Defaults to woocommerce_{gateway id}_settings
	 *
	 * @since 1.0
	 * @param string $gateway_id
	 * @return string the gateway settings option name
	 */
	protected function get_gateway_settings_name( $gateway_id ) {

		return 'woocommerce_' . $gateway_id . '_settings';

	}


	/**
	 * Returns the settings array for the identified gateway
	 *
	 * @since 1.0
	 * @param string $gateway_id gateway identifier
	 * @return array settings array
	 */
	public function get_gateway_settings( $gateway_id ) {

		return get_option( $this->get_gateway_settings_name( $gateway_id ) );

	}


	/**
	 * Get the PHP dependencies for extension depending on the gateway being used
	 *
	 * @since 1.0
	 * @return array of required PHP extension names, based on the gateway in use
	 */
	protected function get_dependencies() {
		return $this->dependencies;
	}


	/**
	 * Returns true if this plugin requires SSL to function properly
	 *
	 * @since 1.0
	 * @return boolean true if this plugin requires ssl
	 */
	protected function requires_ssl() {
		return $this->require_ssl;
	}


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 1.0
	 * @param string $gateway_id the gateway identifier
	 * @return string gateway settings URL
	 * @see SV_WC_Payment_Gateway_Plugin::get_settings_link()
	 */
	protected function get_settings_url( $gateway_id ) {

		$manage_url = admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways' );

		$manage_url = add_query_arg( array( 'section' => $this->get_gateway_class_name( $gateway_id ) ), $manage_url ); // WC 2.0+

		return $manage_url;
	}


	/**
	 * Returns the "Configure" plugin action link to go directly to the gateway
	 * settings page
	 *
	 * @since 1.0
	 * @param string $gateway_id the gateway identifier
	 * @return string plugin configure link
	 * @see SV_WC_Payment_Gateway_Plugin::get_settings_url()
	 */
	protected function get_settings_link( $gateway_id ) {

		return sprintf( '<a href="%s">%s</a>', $this->get_settings_url( $gateway_id ), __( 'Configure', $this->text_domain ) );

	}


	/**
	 * Gets the plugin documentation url, which defaults to:
	 * http://docs.woothemes.com/document/woocommerce-{dasherized plugin id}/
	 *
	 * @since 1.0
	 * @return string documentation URL
	 */
	protected function get_documentation_url() {

		return 'http://docs.woothemes.com/document/woocommerce-' . $this->get_id_dasherized() . '/';

	}


	/**
	 * Gets the skyverge.com product page URL, which defaults to:
	 * http://www.skyverge.com/product/{dasherized plugin id}/
	 *
	 * @since 1.0
	 * @return string skyverge.com product page url
	 */
	protected function get_product_page_url() {

		return 'http://www.skyverge.com/product/' . $this->get_id_dasherized() . '/';

	}


	/**
	 * Adds the given gateway id and gateway class name as an available gateway
	 * supported by this plugin
	 *
	 * @since 1.0
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
	 * @since 1.0
	 * @return array of string gateway class names
	 * @throws Exception if no gateways are available
	 */
	public function get_gateway_class_names() {

		if ( empty( $this->gateways ) ) throw new Exception( __( 'Gateways not available', $this->text_domain ) );

		$gateway_class_names = array();

		foreach ( $this->gateways as $gateway ) {
			$gateway_class_names[] = $gateway['gateway_class_name'];
		}

		return $gateway_class_names;
	}


	/**
	 * Gets the gateway class name for the given gateway id
	 *
	 * @since 1.0
	 * @param string $gateway_id the gateway identifier
	 * @return string gateway class name
	 * @throws Exception if gateway is not found
	 */
	public function get_gateway_class_name( $gateway_id ) {

		if ( ! isset( $this->gateways[ $gateway_id ]['gateway_class_name'] ) ) throw new Exception( sprintf( __( "Gateway '%s' not available", $this->text_domain ), $gateway_id ) );

		return $this->gateways[ $gateway_id ]['gateway_class_name'];
	}


	/**
	 * Gets all supported gateway objects; typically this will be just one,
	 * unless the plugin supports credit card and echeck variations
	 *
	 * @since 1.0
	 * @return array of SV_WC_Payment_Gateway gateway objects
	 * @throws Exception if no gateways are available
	 */
	public function get_gateways() {

		if ( empty( $this->gateways ) ) throw new Exception( __( 'Gateways not available', $this->text_domain ) );

		$gateways = array();

		foreach ( $this->get_gateway_ids() as $gateway_id ) {

			$gateways[] = $this->get_gateway( $gateway_id );

		}

		return $gateways;
	}


	/**
	 * Returns the identified gateway object
	 *
	 * @since 1.0
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
			$this->gateways[ $gateway_id ]['gateway'] = new $gateway_class_name();

		}

		return $this->gateways[ $gateway_id ]['gateway'];
	}


	/**
	 * Returns true if the plugin supports this gateway
	 *
	 * @since 1.0
	 * @param string $gateway_id the gateway identifier
	 * @return boolean true if the plugin has this gateway available, false otherwise
	 */
	public function has_gateway( $gateway_id ) {

		return isset( $this->gateways[ $gateway_id ] );

	}


	/**
	 * Returns all available gateway ids for the plugin
	 *
	 * @since 1.0
	 * @throws Exception
	 * @return array of gateway id strings
	 */
	public function get_gateway_ids() {

		if ( empty( $this->gateways ) ) throw new Exception( __( 'Gateways not available', $this->text_domain ) );

		return array_keys( $this->gateways );

	}


	/**
	 * Returns the set of accepted currencies, or empty array if all currencies
	 * are accepted
	 *
	 * @since 1.0
	 * @return array of accepted currencies
	 */
	public function get_accepted_currencies() {
		return $this->currencies;
	}


	/**
	 * Returns the plugin's path without a trailing slash, i.e.
	 * /path/to/wp-content/plugins/plugin-directory
	 *
	 * @since 1.0
	 * @return string the plugin path
	 */
	public function get_plugin_path() {

		if ( $this->plugin_path )
			return $this->plugin_path;

		return $this->plugin_path = untrailingslashit( plugin_dir_path( $this->get_file() ) );
	}


	/**
	 * Returns the plugin's url without a trailing slash, i.e.
	 * http://skyverge.com/wp-content/plugins/plugin-directory
	 *
	 * @since 1.0
	 * @return string the plugin URL
	 */
	public function get_plugin_url() {

		if ( $this->plugin_url )
			return $this->plugin_url;

		return $this->plugin_url = untrailingslashit( plugins_url( '/', $this->get_file() ) );
	}


	/**
	 * Checks is WooCommerce Subscriptions is active
	 *
	 * @since 1.0
	 * @return bool true if the WooCommerce Subscriptions plugin is active, false if not active
	 */
	public function is_subscriptions_active() {

		if ( is_bool( $this->subscriptions_active ) )
			return $this->subscriptions_active;

		return $this->subscriptions_active = $this->is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );

	}


	/**
	 * Checks is WooCommerce Pre-Orders is active
	 *
	 * @since 1.0
	 * @return bool true if WC Pre-Orders is active, false if not active
	 */
	public function is_pre_orders_active() {

		if ( is_bool( $this->pre_orders_active ) )
			return $this->pre_orders_active;

		return $this->pre_orders_active = $this->is_plugin_active( 'woocommerce-pre-orders/woocommerce-pre-orders.php' );

	}


	/**
	 * Helper function to determine whether a plugin is active
	 *
	 * @since 1.0
	 * @param string $plugin_name the plugin name, as the plugin-dir/plugin-class.php
	 * @return boolean true if the named plugin is installed and active
	 */
	public function is_plugin_active( $plugin_name ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() )
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

		return in_array( $plugin_name, $active_plugins ) || array_key_exists( $plugin_name, $active_plugins );

	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Check that the framework meets the required $minimum_version.
	 *
	 * This is done because there is a chance that a shop could have two
	 * framework plugins installed, with different versions of the framework,
	 * only one of which will be loaded (probably based on the plugin name,
	 * alphabetically).  If an older version of the framework happens to be
	 * loaded in an install with another plugin requiring a higher version, this
	 * situation could lead to fatal errors that shut the whole site down.  To
	 * guard against this, every client of the framework must verify that the
	 * currently loaded framework meets its required minimum version, and if
	 * not, an admin error message will be displayed and the plugin must not
	 * operate.
	 *
	 * @since 1.0
	 * @param string $minimum_version the minimum framework version required by the concrete gateway
	 * @return boolean true if the framework version is greater than or equal to $minimum version
	 */
	final protected function check_version( $minimum_version ) {

		// installed version lower than minimum required?
		if ( -1 === version_compare( self::VERSION, $minimum_version ) )
			return false;

		return true;
	}


	/**
	 * Render the minimum version notice
	 *
	 * @since 1.0
	 */
	final public function render_minimum_version_notice() {

		if ( ! $this->is_message_dismissed( 'minimum-version' ) || $this->is_gateway_settings() ) {

			// a bit hacky, but get the directory name of the plugin which happened to load the framework
			$framework_plugin = explode( '/', __FILE__ );
			$framework_plugin = $framework_plugin[ count( $framework_plugin ) - 6 ];

			$message = sprintf(
				__( '%s requires that you update %s to the latest version, in order to function.  Until then, %s will remain non-functional.', $this->text_domain ),
				'<strong>' . $this->get_plugin_name() . '</strong>',
				'<strong>' . $framework_plugin . '</strong>',
				'<strong>' . $this->get_plugin_name() . '</strong>'
			);

			// dismiss link unless we're on the payment gateway settings page, in which case we'll always display the notice
			$dismiss_link = '<a href="#" class="js-wc-payment-gateway-' . $this->get_id() . '-message-dismiss" data-message-id="missing-extensions">' . __( 'Dismiss', $this->text_domain ) . '</a>';
			if ( $this->is_gateway_settings() )
				$dismiss_link = '';

			echo '<div class="error"><p>' . $message . ' ' . $dismiss_link . '</p></div>';

			$notice_rendered = true;

			$this->render_admin_dismissible_notice_js();

		}

	}


	/**
	 * Handles version checking
	 *
	 * @since 1.0
	 */
	protected function do_install() {

		$installed_version = get_option( $this->get_plugin_version_name() );

		// installed version lower than plugin version?
		if ( -1 === version_compare( $installed_version, $this->get_version() ) ) {

			if ( ! $installed_version )
				$this->install();
			else
				$this->upgrade( $installed_version );

			// new version number
			update_option( $this->get_plugin_version_name(), $this->get_version() );
		}

	}


	/**
	 * Plugin install method.  Perform any installation tasks here
	 *
	 * @since 1.0
	 */
	protected function install() {

		// stub

	}


	/**
	 * Plugin upgrade method.  Perform any required upgrades here
	 *
	 * @since 1.0
	 * @param string $installed_version the currently installed version
	 */
	protected function upgrade( $installed_version ) {

		// stub

	}

}

endif;
