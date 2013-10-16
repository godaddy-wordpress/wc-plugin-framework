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
 *
 * ## Supports (zero or more):
 *
 * + `tokenization`     - adds actions to show/handle the "My Payment Methods" area of the customer's My Account page
 * + `customer_id`      - adds actions to show/persist the "Customer ID" area of the admin User edit page
 * + `transaction_link` - adds actions to render the merchant account transaction direct link on the Admin Order Edit page.  (Don't forget to override the SV_WC_Payment_Gateway::get_transaction_url() method!)
 *
 * @version 0.1
 */
abstract class SV_WC_Payment_Gateway_Plugin {


	/** @var string plugin id */
	private $id;

	/** @var string plugin text domain */
	private $text_domain;

	/** @var string version number */
	private $version;

	/** @var string optional gateway class name */
	private $gateway_class_name;

	/** @var string optional gateway id */
	private $gateway_id;

	/** @var SV_WC_Payment_Gateway the payment gateway */
	private $gateway;

	/** @var string plugin path without trailing slash */
	private $plugin_path;

	/** @var string plugin uri */
	private $plugin_url;

	/** @var \WC_Logger instance */
	private $logger;

	/** @var array string names of required PHP extensions */
	private $dependencies;

	/** @var boolean true if this gateway requires SSL for processing transactions, false otherwise */
	private $require_ssl;

	/** @var array named features that this gateway supports which require action from the parent plugin, including 'tokenization' */
	private $supports;

	/** @var bool helper for lazy subscriptions active check */
	private $subscriptions_active;

	/** @var bool helper for lazy pre-orders active check */
	private $pre_orders_active;

	/** Tokenization feature */
	const FEATURE_TOKENIZATION = 'tokenization';

	/** Customer ID feature */
	const FEATURE_CUSTOMER_ID = 'customer_id';

	/** Link to transaction feature */
	const FEATURE_TRANSACTION_LINK = 'transaction_link';


	/**
	 * Initialize the plugin
	 *
	 * Optional args:
	 *
	 * + `gateway_class_name` - string gateway class name
	 * + `gateway_id` - string gateway id
	 * + `dependencies` - array string names of required PHP extensions
	 * + `require_ssl` - boolean true if this gateway requires SSL for processing transactions, false otherwise. Defaults to false
	 * + `supports` - array named features that this gateway supports, including 'tokenization', 'transaction_link', 'customer_id'
	 *
	 * @since 0.1
	 * @param string $id plugin id
	 * @param string $version plugin version number
	 * @param string $text_domain the plugin text domain
	 * @param array $args plugin arguments
	 */
	public function __construct( $id, $version, $text_domain, $args ) {

		// required params
		$this->id          = $id;
		$this->version     = $version;
		$this->text_domain = $text_domain;

		// optional parameters
		if ( isset( $args['gateway_class_name'] ) ) $this->gateway_class_name = $args['gateway_class_name'];
		if ( isset( $args['gateway_id'] ) )         $this->gateway_id         = $args['gateway_id'];
		if ( isset( $args['dependencies'] ) )       $this->dependencies       = $args['dependencies'];
		if ( isset( $args['require_ssl'] ) )        $this->require_ssl        = $args['require_ssl'];
		if ( isset( $args['supports'] ) )           $this->supports           = $args['supports'];

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
			add_filter( 'plugin_action_links_' . plugin_basename( $this->get_file() ), array( $this, 'plugin_configure_link' ) );

			// run every time
			$this->do_install();
		}

		// Add classes to WC Payment Methods
		add_filter( 'woocommerce_payment_gateways', array( $this, 'load_gateway' ) );

		// Load translation files
		add_action( 'init', array( $this, 'load_translation' ) );
	}


	/**
	 * Adds the gateway to the list of available payment gateways
	 *
	 * @since 0.1
	 * @param array $gateways
	 * @return array $gateways
	 */
	public function load_gateway( $gateways ) {

		$gateways[] = $this->get_gateway_class_name();

		return $gateways;

	}


	/**
	 * Load plugin text domain
	 *
	 * @since 0.1
	 */
	public function load_translation() {

		load_plugin_textdomain( $this->text_domain, false, dirname( plugin_basename( $this->get_file() ) ) . '/languages' );

	}


	/**
	 * Include required library files
	 *
	 * @since 0.1
	 */
	public function lib_includes() {

		// include framework files
		require_once( 'api/interface-wc-payment-gateway-api.php' );
		require_once( 'api/interface-wc-payment-gateway-api-request.php' );
		require_once( 'api/interface-wc-payment-gateway-api-response.php' );
		require_once( 'api/interface-wc-payment-gateway-api-create-payment-token-response.php' );
		require_once( 'api/interface-wc-payment-gateway-api-get-tokenized-payment-methods-response.php' );

		require_once( 'exceptions/class-wc-payment-gateway-feature-unsupported-exception.php' );
		require_once( 'exceptions/class-wc-payment-gateway-unimplemented-method-exception.php' );

		require_once( 'class-wc-payment-gateway.php' );
		require_once( 'class-wc-payment-token.php' );

	}


	/** Frontend methods ******************************************************/


	/**
	 * Helper to add the 'My Cards' section to the 'My Account' page
	 *
	 * @since 0.1
	 */
	public function add_my_payment_methods() {

		$this->get_gateway()->show_my_payment_methods();

	}


	/**
	 * Helper to handle any actions from the 'My Cards' section on the 'My Account'
	 * page
	 *
	 * @since 0.1
	 */
	public function handle_my_payment_methods_actions() {

		if ( is_account_page() ) {

			$this->get_gateway()->handle_my_payment_methods_actions();

		}

	}


	/** Admin methods ******************************************************/


	/**
	 * Checks if required PHP extensions are loaded and SSL is enabled. Adds an admin notice if either check fails.
	 * Also gateway settings can be checked as well.
	 *
	 * @since 0.1
	 */
	public function render_admin_notices() {

		// report any missing extensions
		$missing_extensions = $this->get_missing_dependencies();

		if ( count( $missing_extensions ) > 0 ) {

			$message = sprintf(
				_n(
					'%s requires the %s PHP extension to function.  Contact your host or server administrator to configure and install the missing extension.',
					'%s requires the following PHP extensions to function: %s.  Contact your host or server administrator to configure and install the missing extensions.',
					count( $missing_extensions ),
					self::TEXT_DOMAIN
				),
				$this->get_plugin_name(),
				'<strong>' . implode( ', ', $missing_extensions ) . '</strong>'
			);

			echo '<div class="error"><p>' . $message . '</p></div>';
		}

		// check settings:  gateway active and SSL enabled
		// TODO: does this work when a plugin is first activated, before the settings page has been saved?
		if ( $this->requires_ssl() ) {

			$settings = get_option( $this->get_plugin_settings_name() );

			if ( isset( $settings['enabled'] ) && 'yes' == $settings['enabled'] ) {

				if ( isset( $settings['environment'] ) && 'production' == $settings['environment'] ) {

					// SSL check if gateway enabled/production mode
					if ( 'no' === get_option( 'woocommerce_force_ssl_checkout' ) )
						echo '<div class="error"><p>' . sprintf( __( "%s: WooCommerce is not being forced over SSL; your customer's credit card data is at risk.", $this->text_domain ), '<strong>' . $this->get_plugin_name() . '</strong>' ), '</p></div>';
				}
			}
		}
	}


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @since 0.1
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
	 */
	public function plugin_configure_link( $actions ) {

		$custom_actions = array();

		// settings url if any
		if ( $this->get_settings_url() )
			$custom_actions['configure'] = sprintf( '<a href="%s">%s</a>', $this->get_settings_url(), __( 'Configure', $this->text_domain ) );

		// documentation url if any
		if ( $this->get_documentation_url() )
			$custom_actions['docs'] = sprintf( '<a href="%s">%s</a>', $this->get_documentation_url(), __( 'Docs', $this->text_domain ) );

		// support url
		$custom_actions['support'] = sprintf( '<a href="%s">%s</a>', 'http://support.woothemes.com/', __( 'Support', $this->text_domain ) );

		// add the links to the front of the actions list
		return array_merge( $custom_actions, $actions );

	}


	/**
	 * Add a button to the order actions meta box to view the order in the
	 * gateway merchant account, if supported
	 *
	 * @since 0.1
	 * @param int $post_id the order identifier
	 */
	public function order_meta_box_transaction_link( $post_id ) {

		$order = new WC_Order( $post_id );

		// TODO: multiple gateway support
		if ( $this->get_gateway_id() == $order->payment_method ) {
			$this->get_gateway()->order_meta_box_transaction_link( $order );
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
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway::get_customer_id_user_meta_name()
	 * @see SV_WC_Payment_Gateway_Plugin::save_customer_id_meta_field()
	 * @param WP_User $user user object for the current edit page
	 */
	public function add_customer_id_meta_field( $user ) {

		// bail if the current user is not allowed to manage woocommerce
		if ( ! current_user_can( 'manage_woocommerce' ) )
			return;

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
					<th><label for="<?php printf( '_wc_%s_customer_id_%s', $this->get_plugin_id(), $environment_id ); ?>"><?php echo count( $environments ) > 1 ? sprintf( __( 'Customer ID (%s)', $this->text_domain ), $environment_name ) : __( 'Customer ID', $this->text_domain ); ?></label></th>
					<td>
						<input type="text" name="<?php printf( '_wc_%s_customer_id_%s', $this->get_plugin_id(), $environment_id ); ?>" id="<?php printf( '_wc_%s_customer_id_%s', $this->get_plugin_id(), $environment_id ); ?>" value="<?php echo esc_attr( $gateway->get_customer_id( $user->ID, $environment_id, false ) ); ?>" class="regular-text" /><br/>
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
	 * @since 0.1
	 * @see SV_WC_Payment_Gateway_Plugin::add_customer_id_meta_field()
	 * @param int $user_id identifies the user to save the settings for
	 */
	public function save_customer_id_meta_field( $user_id ) {

		// bail if the current user is not allowed to manage woocommerce
		if ( ! current_user_can( 'manage_woocommerce' ) )
			return;

		$gateway      = $this->get_gateway();
		$environments = $gateway->get_environments();

		// customer id's not supported
		if ( false === $gateway->get_customer_id_user_meta_name() )
			return;

		// valid environments only
		foreach ( array_keys( $environments ) as $environment_id ) {

			// update (or blank out) customer id for the given environment
			if ( isset( $_POST[ '_wc_' . $this->get_plugin_id() . '_customer_id_' . $environment_id ] ) ) {
				$gateway->update_customer_id( $user_id, trim( $_POST[ '_wc_' . $this->get_plugin_id() . '_customer_id_' . $environment_id ] ), $environment_id );
			}

		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Returns true if the gateway supports the named feature
	 *
	 * @since 0.1
	 * @param string $feature the feature
	 * @return bool true if the named feature is supported
	 */
	public function supports( $feature ) {

		return in_array( $feature, $this->supports );

	}


	/**
	 * Gets the string name of any required PHP extensions that are not loaded
	 *
	 * @since 0.1
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
	 * @since 0.1
	 * @param string $message error or message to save to log
	 */
	public function log( $message ) {

		global $woocommerce;

		if ( ! is_object( $this->logger ) )
			$this->logger = $woocommerce->logger();

		$this->logger->add( $this->get_gateway_id(), $message );

	}


	/** Getter methods ******************************************************/


	/**
	 * The implementation for this abstract method should simply be:
	 *
	 * return __FILE__;
	 *
	 * @since 0.1
	 * @return string the full path and filename of the plugin file
	 */
	abstract protected function get_file();


	/**
	 * Returns the plugin id
	 *
	 * @since 0.1
	 * @return string plugin id
	 */
	public function get_plugin_id() {
		return $this->id;
	}


	/**
	 * Returns the plugin id with dashes in place of underscores, and
	 * appropriate for use in frontend element names, classes and ids
	 *
	 * @since 0.1
	 * @return string payment gateway id with dashes in place of underscores
	 */
	public function get_plugin_id_dasherized() {
		return str_replace( '_', '-', $this->get_plugin_id() );
	}


	/**
	 * Returns the plugin full name including "WooCommerce", ie
	 * "WooCommerce X Gateway".  This method is defined abstract for localization purposes
	 *
	 * @since 0.1
	 * @return string plugin name
	 */
	abstract public function get_plugin_name();


	/**
	 * Returns the plugin version name.  Defaults to wc_{plugin id}_version
	 *
	 * @since 0.1
	 * @return string the plugin version name
	 */
	protected function get_plugin_version_name() {
		return 'wc_' . $this->get_plugin_id() . '_version';
	}


	/**
	 * Returns the gateway object
	 *
	 * @since 0.1
	 * @return SV_WC_Payment_Gateway the gateway object
	 */
	protected function get_gateway() {

		if ( ! isset( $this->gateway ) ) {

			$gateway_class_name = $this->get_gateway_class_name();
			$this->gateway = new $gateway_class_name();

		}

		return $this->gateway;

	}


	/**
	 * Returns the plugin settings option name.  Defaults to woocommerce_{gateway id}_settings
	 *
	 * @since 0.1
	 * @return string the plugin settings option name
	 */
	protected function get_plugin_settings_name() {
		return 'woocommerce_' . $this->get_gateway_id() . '_settings';
	}


	/**
	 * Get the PHP dependencies for extension depending on the gateway being used
	 *
	 * @since 0.1
	 * @return array of required PHP extension names, based on the gateway in use
	 */
	protected function get_dependencies() {
		return $this->dependencies;
	}


	/**
	 * Returns true if this plugin requires SSL to function properly
	 *
	 * @since 0.1
	 * @return boolean true if this plugin requires ssl
	 */
	protected function requires_ssl() {
		return $this->require_ssl;
	}


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 0.1
	 * @return string gateway settings URL
	 */
	protected function get_settings_url() {

		$manage_url = admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways' );

		if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 )
			$manage_url = add_query_arg( array( 'section' => $this->get_gateway_class_name() ), $manage_url ); // WC 2.0+
		else
			$manage_url = add_query_arg( array( 'subtab' => 'gateway-' . $this->get_gateway_id() ), $manage_url ); // WC 1.6.6-

		return $manage_url;
	}


	/**
	 * Gets the plugin documentation url, which defaults to:
	 * http://docs.woothemes.com/document/woocommerce-{dasherized plugin id}/
	 *
	 * @since 0.1
	 * @return string documentation URL
	 */
	protected function get_documentation_url() {

		return 'http://docs.woothemes.com/document/woocommerce-' . $this->get_plugin_id_dasherized() . '/';

	}


	/**
	 * Gets the gateway class name.
	 *
	 * @since 0.1
	 * @return string the gateway class name
	 * @throws Exception if a gateway class name is not set
	 */
	public function get_gateway_class_name() {

		if ( ! isset( $this->gateway_class_name ) ) throw new Exception( __( 'Gateway Class Name Not Set', $this->text_domain ) );

		return $this->gateway_class_name;
	}


	/**
	 * Gets the gateway id for the current gateway
	 *
	 * @since 0.1
	 * @return string returns the gateway id
	 * @throws Exception if a gateway id is not set
	 */
	public function get_gateway_id() {

		if ( ! isset( $this->gateway_id ) ) throw new Exception( __( 'Gateway ID Not Set', $this->text_domain ) );

		return $this->gateway_id;
	}


	/**
	 * Returns the plugin's path without a trailing slash, i.e.
	 * /path/to/wp-content/plugins/plugin-directory
	 *
	 * @since 0.1
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
	 * @since 0.1
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
	 * @since 0.1
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
	 * @since 0.1
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
	 * @since 0.1
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
	 * Handles version checking
	 *
	 * @since 0.1
	 */
	protected function do_install() {

		$installed_version = get_option( $this->get_plugin_version_name() );

		// installed version lower than plugin version?
		if ( -1 === version_compare( $installed_version, $this->version ) ) {

			if ( ! $installed_version )
				$this->install();
			else
				$this->upgrade( $installed_version );

			// new version number
			update_option( $this->get_plugin_version_name(), $this->version );
		}

	}


	/**
	 * Plugin install method.  Perform any installation tasks here
	 *
	 * @since 0.1
	 */
	protected function install() {

		// stub

	}


	/**
	 * Plugin upgrade method.  Perform any required upgrades here
	 *
	 * @since 0.1
	 * @param string $installed_version the currently installed version
	 */
	protected function upgrade( $installed_version ) {

		// stub

	}

}

endif;
