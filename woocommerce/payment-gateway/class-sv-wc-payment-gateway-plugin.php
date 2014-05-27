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

if ( ! class_exists( 'SV_WC_Payment_Gateway_Plugin' ) ) :

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
 * + `tokenization`     - adds actions to show/handle the "My Payment Methods" area of the customer's My Account page
 * + `customer_id`      - adds actions to show/persist the "Customer ID" area of the admin User edit page
 * + `transaction_link` - adds actions to render the merchant account transaction direct link on the Admin Order Edit page.  (Don't forget to override the SV_WC_Payment_Gateway::get_transaction_url() method!)
 *
 * @version 2.0
 */
abstract class SV_WC_Payment_Gateway_Plugin extends SV_WC_Plugin {

	/** Tokenization feature */
	const FEATURE_TOKENIZATION = 'tokenization';

	/** Customer ID feature */
	const FEATURE_CUSTOMER_ID = 'customer_id';

	/** Link to transaction feature */
	const FEATURE_TRANSACTION_LINK = 'transaction_link';

	/** Charge capture feature */
	const FEATURE_CAPTURE_CHARGE = 'capture_charge';

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


	/**
	 * Initialize the plugin
	 *
	 * Optional args:
	 *
	 * + `require_ssl` - boolean true if this plugin requires SSL for proper functioning, false otherwise. Defaults to false
	 * + `gateways` - array associative array of gateway id to gateway class name.  A single plugin might support more than one gateway, ie credit card, echeck.  Note that the credit card gateway must always be the first one listed.
	 * + `currencies` -  array of currency codes this gateway is allowed for, defaults to all
	 * + `supports` - array named features that this gateway supports, including 'tokenization', 'transaction_link', 'customer_id'
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::__construct()
	 * @param string $id plugin id
	 * @param string $version plugin version number
	 * @param string $text_domain the plugin text domain
	 * @param array $args plugin arguments
	 */
	public function __construct( $id, $version, $text_domain, $args ) {

		parent::__construct( $id, $version, $text_domain, $args );

		// optional parameters: the supported gateways
		if ( isset( $args['gateways'] ) ) {

			foreach ( $args['gateways'] as $gateway_id => $gateway_class_name ) {
				$this->add_gateway( $gateway_id, $gateway_class_name );
			}

		}

		if ( isset( $args['currencies'] ) )         $this->currencies   = $args['currencies'];
		if ( isset( $args['supports'] ) )           $this->supports     = $args['supports'];
		if ( isset( $args['require_ssl'] ) )        $this->require_ssl  = $args['require_ssl'];

		if ( ! is_admin() && $this->supports( self::FEATURE_TOKENIZATION ) ) {

			// Handle any actions from the My Payment Methods section
			add_action( 'wp', array( $this, 'handle_my_payment_methods_actions' ) );

			// Add the 'Manage My Payment Methods' on the 'My Account' page for the gateway
			add_action( 'woocommerce_after_my_account', array( $this, 'add_my_payment_methods' ) );

		}

		// Admin
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

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
		}

		if ( $this->supports( self::FEATURE_CAPTURE_CHARGE ) ) {

			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

				// capture charge order action
				add_filter( 'woocommerce_order_actions', array( $this, 'maybe_add_capture_charge_order_action' ) );
				add_action( 'woocommerce_order_action_wc_' . $this->get_id() . '_capture_charge', array( $this, 'maybe_capture_charge' ) );

				// bulk capture charge order action
				add_action( 'admin_footer-edit.php', array( $this, 'add_capture_charge_bulk_order_action' ) );
				add_action( 'load-edit.php',         array( $this, 'process_capture_charge_bulk_order_action' ) );
			}
		}

		// Add classes to WC Payment Methods
		add_filter( 'woocommerce_payment_gateways', array( $this, 'load_gateways' ) );
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
	 * Include required library files
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::lib_includes()
	 */
	public function lib_includes() {

		parent::lib_includes();

		// include framework files
		require_once( 'api/interface-sv-wc-payment-gateway-api.php' );
		require_once( 'api/interface-sv-wc-payment-gateway-api-request.php' );
		require_once( 'api/interface-sv-wc-payment-gateway-api-response.php' );
		require_once( 'api/interface-sv-wc-payment-gateway-api-payment-notification-response.php' );
		require_once( 'api/interface-sv-wc-payment-gateway-api-authorization-response.php' );
		require_once( 'api/interface-sv-wc-payment-gateway-api-create-payment-token-response.php' );
		require_once( 'api/interface-sv-wc-payment-gateway-api-get-tokenized-payment-methods-response.php' );

		require_once( 'exceptions/class-sv-wc-payment-gateway-feature-unsupported-exception.php' );
		require_once( 'exceptions/class-sv-wc-payment-gateway-unimplemented-method-exception.php' );

		require_once( 'class-sv-wc-payment-gateway.php' );
		require_once( 'class-sv-wc-payment-gateway-direct.php' );
		require_once( 'class-sv-wc-payment-gateway-hosted.php' );
		require_once( 'class-sv-wc-payment-token.php' );
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

				if ( $gateway->supports_tokenization() ) {
					$gateway->handle_my_payment_methods_actions();
				}
			}
		}
	}


	/** Admin methods ******************************************************/


	/**
	 * Returns true if on the admin gateway settings page for this gateway
	 *
	 * @since 2.0
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @return boolean true if on the admin gateway settings page
	 */
	public function is_plugin_settings() {

		foreach ( $this->get_gateway_class_names() as $gateway_class_name ) {
			if ( SV_WC_Plugin_Compatibility::is_payment_gateway_configuration_page( $gateway_class_name ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Checks if required PHP extensions are loaded and SSL is enabled. Adds an
	 * admin notice if either check fails.  Also plugin settings can be checked
	 * as well.
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::render_admin_notices()
	 */
	public function render_admin_notices() {

		// any plugin notices
		parent::render_admin_notices();

		// notices for ssl requirement
		$this->render_ssl_admin_notices();

		// notices for currency issues
		$this->render_currency_admin_notices();
	}


	/**
	 * Checks if SSL is required and not available and adds a dismissible admin
	 * notice if so.  Notice will not be rendered to the admin user once dismissed
	 * unless on the plugin settings page, if any
	 *
	 * @since 2.0
	 * @see SV_WC_Payment_Gateway_Plugin::render_admin_notices()
	 */
	protected function render_ssl_admin_notices() {

		// check settings:  gateway active and SSL enabled
		// TODO: does this work when a plugin is first activated, before the settings page has been saved?
		if ( $this->requires_ssl() && ( ! $this->is_message_dismissed( 'ssl-required' ) || $this->is_plugin_settings() ) ) {

			foreach ( $this->get_gateway_ids() as $gateway_id ) {

				$settings = $this->get_gateway_settings( $gateway_id );

				if ( isset( $settings['enabled'] ) && 'yes' == $settings['enabled'] ) {

					if ( isset( $settings['environment'] ) && 'production' == $settings['environment'] ) {

						// SSL check if gateway enabled/production mode
						if ( 'no' === get_option( 'woocommerce_force_ssl_checkout' ) ) {

							$message = sprintf( _x( "%s: WooCommerce is not being forced over SSL; your customer's payment data may be at risk.", 'Requires SSL', $this->text_domain ), '<strong>' . $this->get_plugin_name() . '</strong>' );

							$this->add_dismissible_notice( $message, 'ssl-required' );

							// just show the message once for plugins with multiple gateway support
							break;
						}

					}
				}
			}
		}
	}


	/**
	 * Checks if a particular currency is required and not being used and adds a
	 * dismissible admin notice if so.  Notice will not be rendered to the admin
	 * user once dismissed unless on the plugin settings page, if any
	 *
	 * @since 2.0
	 * @see SV_WC_Payment_Gateway_Plugin::render_admin_notices()
	 */
	protected function render_currency_admin_notices() {

		// report any currency issues
		if ( $this->get_accepted_currencies() && ! in_array( get_woocommerce_currency(), $this->get_accepted_currencies() ) ) {

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

			if ( ! $this->is_message_dismissed( 'accepted-currency' . $suffix ) || $this->is_plugin_settings() ) {

				$message = sprintf(
					_n(
						'%s accepts payment in %s only.  <a href="%s">Configure</a> WooCommerce to accept %s to enable this gateway for checkout.',
						'%s accepts payment in one of %s only.  <a href="%s">Configure</a> WooCommerce to accept one of %s to enable this gateway for checkout.',
						count( $accepted_currencies ),
						'(Plugin) accepts payments in (currency/currencies) only.',
						$this->text_domain
					),
					$name,
					'<strong>' . implode( ', ', $accepted_currencies ) . '</strong>',
					SV_WC_Plugin_Compatibility::get_general_configuration_url(),
					'<strong>' . implode( ', ', $accepted_currencies ) . '</strong>'
				);

				$this->add_dismissible_notice( $message, 'accepted-currency' . $suffix );

			}
		}
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
		<h3><?php printf( _x( '%s Customer Details', 'Supports customer details', $this->text_domain ), $this->get_plugin_name() ); ?></h3>
		<table class="form-table">
		<?php

		foreach ( $environments as $environment_id => $environment_name ) :

			?>
				<tr>
					<th><label for="<?php printf( '_wc_%s_customer_id_%s', $this->get_id(), $environment_id ); ?>"><?php echo count( $environments ) > 1 ? sprintf( _x( 'Customer ID (%s)', 'Supports customer details', $this->text_domain ), $environment_name ) : _x( 'Customer ID', 'Supports customer details', $this->text_domain ); ?></label></th>
					<td>
						<input type="text" name="<?php printf( '_wc_%s_customer_id_%s', $this->get_id(), $environment_id ); ?>" id="<?php printf( '_wc_%s_customer_id_%s', $this->get_id(), $environment_id ); ?>" value="<?php echo esc_attr( $gateway->get_customer_id( $user->ID, array( 'environment_id' => $environment_id, 'autocreate' => false ) ) ); ?>" class="regular-text" /><br/>
						<span class="description"><?php echo count( $environments ) > 1 ? sprintf( _x( 'The customer ID for the user in the %s environment. Only edit this if necessary.', 'Supports customer details', $this->text_domain ), $environment_name ) : _x( 'The customer ID for the user in the environment. Only edit this if necessary.', 'Supports customer details', $this->text_domain ); ?></span>
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


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::plugin_action_links()
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
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


	/** Capture Charge Feature ******************************************************/


	/**
	 * Capture a credit card charge for a prior authorization if this payment
	 * method was used for the given order, the charge hasn't already been
	 * captured, and the gateway supports issuing a capture request
	 *
	 * @since 1.0
	 * @param \WC_Order|int $order the order identifier or order object
	 */
	public function maybe_capture_charge( $order ) {

		if ( ! is_object( $order ) ) {
			$order = new WC_Order( $order );
		}

		// bail if the order wasn't paid for with this gateway
		if ( ! $this->has_gateway( $order->payment_method ) ) {
			return;
		}

		$gateway = $this->get_gateway( $order->payment_method );

		// ensure that it supports captures
		if ( ! $this->can_capture_charge( $gateway ) ) {
			return;
		}

		// ensure the authorization is still valid for capture
		if ( ! $gateway->authorization_valid_for_capture( $order ) ) {
			return;
		}

		// remove order status change actions, otherwise we get a whole bunch of capture calls and errors
		remove_action( 'woocommerce_order_action_wc_' . $this->get_id() . '_capture_charge', array( $this, 'maybe_capture_charge' ) );

		// since a capture results in an update to the post object (by updating
		// the paid date) we need to unhook the save_post action, otherwise we
		// can get boomeranged and change the status back to on-hold
		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_1() ) {
			// WC 2.1+
			remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2 );
		} else {
			// WC 2.0
			remove_action( 'woocommerce_process_shop_order_meta', 'woocommerce_process_shop_order_meta', 10, 2 );
		}

		// perform the capture
		$gateway->do_credit_card_capture( $order );
	}


	/**
	 * Add a "Capture Charge" action to the Admin Order Edit Order
	 * Actions select if there is an authorization awaiting capture
	 *
	 * @since 1.0
	 * @param array $actions available order actions
	 * @return array actions
	 */
	public function maybe_add_capture_charge_order_action( $actions ) {

		$order = new WC_Order( $_REQUEST['post'] );

		// bail if the order wasn't paid for with this gateway
		if ( ! $this->has_gateway( $order->payment_method ) ) {
			return $actions;
		}

		$gateway = $this->get_gateway( $order->payment_method );

		// ensure that it supports captures
		if ( ! $this->can_capture_charge( $gateway ) ) {
			return $actions;
		}

		// ensure that the authorization is still valid for capture
		if ( ! $gateway->authorization_valid_for_capture( $order ) ) {
			return $actions;
		}

		return $this->add_order_action_charge_action( $actions );
	}


	/**
	 * Adds 'Capture charge' to the Orders screen bulk action select
	 *
	 * @since 2.1
	 */
	public function add_capture_charge_bulk_order_action() {
		global $post_type, $post_status;

		if ( $post_type == 'shop_order' && $post_status != 'trash' ) {
			?>
				<script type="text/javascript">
					jQuery( document ).ready( function ( $ ) {
						if ( 0 == $( 'select[name^=action] option[value=wc_capture_charge]' ).size() ) {
							$( 'select[name^=action]' ).append(
								$( '<option>' ).val( '<?php echo esc_js( 'wc_capture_charge' ); ?>' ).text( '<?php _ex( 'Capture Charge', 'Supports capture charge', $this->text_domain ); ?>' )
							);
						}
					});
				</script>
			<?php
		}
	}


	/**
	 * Process the 'Capture Charge' custom bulk action on the Orders screen
	 * bulk action select
	 *
	 * @since 2.1
	 */
	public function process_capture_charge_bulk_order_action() {
		global $typenow;

		if ( 'shop_order' == $typenow ) {

			// get the action
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action        = $wp_list_table->current_action();

			// bail if not processing a capture
			if ( 'wc_capture_charge' !== $action ) {
				return;
			}

			// security check
			check_admin_referer( 'bulk-posts' );

			// make sure order IDs are submitted
			if ( isset( $_REQUEST['post'] ) ) {
				$order_ids = array_map( 'absint', $_REQUEST['post'] );
			}

			// return if there are no orders to export
			if ( empty( $order_ids ) ) {
				return;
			}

			// give ourselves an unlimited timeout if possible
			@set_time_limit( 0 );

			foreach ( $order_ids as $order_id ) {

				$order = new WC_Order( $order_id );

				$this->maybe_capture_charge( $order );
			}
		}
	}


	/**
	 * Add a "Capture Charge" action to the Admin Order Edit Order
	 * Actions dropdown
	 *
	 * @since 2.1
	 * @param array $actions available order actions
	 * @return array actions
	 */
	public function add_order_action_charge_action( $actions ) {

		$actions[ 'wc_' . $this->get_id() . '_capture_charge' ] = _x( 'Capture Charge', 'Supports capture charge', $this->text_domain );

		return $actions;
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
	 * @param \SV_WC_Payment_Gateway $gateway the payment gateway
	 * @return boolean true if the gateway supports the charge capture operation and it can be invoked
	 */
	public function can_capture_charge( $gateway ) {
		return $this->supports( self::FEATURE_CAPTURE_CHARGE ) && $this->get_gateway()->is_available() && $gateway->supports( self::FEATURE_CAPTURE_CHARGE );
	}


	/** Getter methods ******************************************************/


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
	 * Returns the settings array for the identified gateway.  Note that this
	 * will not include any defaults if the gateway has yet to be saved
	 *
	 * @since 1.0
	 * @param string $gateway_id gateway identifier
	 * @return array settings array
	 */
	public function get_gateway_settings( $gateway_id ) {

		return get_option( $this->get_gateway_settings_name( $gateway_id ) );

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
	 * @see SV_WC_Plugin::get_settings_url()
	 * @param string $gateway_id the gateway identifier
	 * @return string gateway settings URL
	 */
	public function get_settings_url( $gateway_id = null ) {

		// default to first gateway
		if ( is_null( $gateway_id ) ) {
			reset( $this->gateways );
			$gateway_id = key( $this->gateways );
		}

		return SV_WC_Plugin_Compatibility::get_payment_gateway_configuration_url( $this->get_gateway_class_name( $gateway_id ) );
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

		if ( empty( $this->gateways ) ) throw new Exception( 'Gateways not available' );

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

		if ( ! isset( $this->gateways[ $gateway_id ]['gateway_class_name'] ) ) throw new Exception( sprintf( "Gateway '%s' not available", $gateway_id ) );

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

		if ( empty( $this->gateways ) ) {
			throw new Exception( 'Gateways not available' );
		}

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

		if ( empty( $this->gateways ) ) {
			throw new Exception( 'Gateways not available' );
		}

		return array_keys( $this->gateways );
	}


	/**
	 * Returns the set of accepted currencies, or empty array if all currencies
	 * are accepted.  This is the intersection of all currencies accepted by
	 * any gateways this plugin supports.
	 *
	 * @since 1.0
	 * @return array of accepted currencies
	 */
	public function get_accepted_currencies() {
		return $this->currencies;
	}


	/**
	 * Checks is WooCommerce Subscriptions is active
	 *
	 * @since 1.0
	 * @return bool true if the WooCommerce Subscriptions plugin is active, false if not active
	 */
	public function is_subscriptions_active() {

		if ( is_bool( $this->subscriptions_active ) ) {
			return $this->subscriptions_active;
		}

		return $this->subscriptions_active = $this->is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
	}


	/**
	 * Checks is WooCommerce Pre-Orders is active
	 *
	 * @since 1.0
	 * @return bool true if WC Pre-Orders is active, false if not active
	 */
	public function is_pre_orders_active() {

		if ( is_bool( $this->pre_orders_active ) ) {
			return $this->pre_orders_active;
		}

		return $this->pre_orders_active = $this->is_plugin_active( 'woocommerce-pre-orders/woocommerce-pre-orders.php' );
	}
}

endif;
