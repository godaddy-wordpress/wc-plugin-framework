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

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_My_Payment_Methods' ) ) :


/**
 * My Payment Methods Class
 *
 * Renders the My Payment Methods table on the My Account page and handles
 * any associated actions (deleting a payment method, etc)
 *
 * @since 4.0.0
 */
class SV_WC_Payment_Gateway_My_Payment_Methods extends Handlers\Script_Handler {


	/** @var SV_WC_Payment_Gateway_Plugin */
	protected $plugin;

	/** @var SV_WC_Payment_Gateway_Payment_Token[] array of token objects */
	protected $tokens;

	/** @var SV_WC_Payment_Gateway_Payment_Token[] array of token objects */
	protected $credit_card_tokens;

	/** @var SV_WC_Payment_Gateway_Payment_Token[] array of token objects */
	protected $echeck_tokens;

	/** @var bool true if there are tokens */
	protected $has_tokens;

	/** @var string JS handler base class name, without the FW version */
	protected $js_handler_base_class_name = 'SV_WC_Payment_Methods_Handler';


	/**
	 * Sets up the class.
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin gateway plugin
	 *
	 * @since 4.0.0
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		parent::__construct();
	}


	/**
	 * Adds the action and filter hooks.
	 *
	 * @since 5.7.0
	 */
	protected function add_hooks() {

		parent::add_hooks();

		add_action( 'wp', array( $this, 'init' ) );

		// save a payment method via AJAX
		add_action( 'wp_ajax_wc_' . $this->get_plugin()->get_id() . '_save_payment_method', array( $this, 'ajax_save_payment_method' ) );

		add_action( 'woocommerce_payment_token_set_default', [ $this, 'clear_payment_methods_transients' ], 10, 2 );

		add_action( 'woocommerce_payment_token_deleted', [ $this, 'payment_token_deleted' ], 10, 2 );
	}


	/**
	 * Gets the script ID.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->get_plugin()->get_id() . '_payment_methods';
	}


	/**
	 * Gets the script ID, dasherized.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_id_dasherized() {

		return $this->get_plugin()->get_id_dasherized() . '-payment-methods';
	}


	/**
	 * Initializes the My Payment Methods table
	 *
	 * @since 5.1.0
	 */
	public function init() {

		if ( ! $this->is_payment_methods_page() ) {
			return;
		}

		// initializes tokens as WooCommerce core tokens
		$this->load_tokens();

		// styles/scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_styles_scripts' ) );

		add_filter( 'woocommerce_payment_methods_list_item', [ $this, 'add_payment_methods_list_item_id' ], 10, 2 );
		add_filter( 'woocommerce_payment_methods_list_item', [ $this, 'add_payment_methods_list_item_edit_action' ], 10, 2 );

		add_filter( 'woocommerce_account_payment_methods_columns', [ $this, 'add_payment_methods_columns' ] );

		add_action( 'woocommerce_account_payment_methods_column_title',   [ $this, 'add_payment_method_title' ] );
		add_action( 'woocommerce_account_payment_methods_column_details', [ $this, 'add_payment_method_details' ] );
		add_action( 'woocommerce_account_payment_methods_column_default', [ $this, 'add_payment_method_default' ] );

		// map Framework payment methods actions to WooCommerce actions for backwards compatibility
		add_action( 'woocommerce_before_account_payment_methods', [ $this, 'before_payment_methods_table' ] );
		add_action( 'woocommerce_after_account_payment_methods',  [ $this, 'after_payment_methods_table'] );

		// handle custom payment method actions
		$this->handle_payment_method_actions();

		// render JavaScript used in the My Payment Methods section
		add_action( 'woocommerce_after_account_payment_methods', [ $this, 'render_js' ] );
	}


	/**
	 * Enqueue frontend CSS/JS
	 *
	 * @since 4.0.0
	 */
	public function maybe_enqueue_styles_scripts() {

		$handle = 'sv-wc-payment-gateway-my-payment-methods';

		wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );

		wp_enqueue_style( "$handle-v5_10_0", $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/css/frontend/' . $handle . '.min.css', array( 'dashicons' ), SV_WC_Plugin::VERSION );

		wp_enqueue_script( "$handle-v5_10_0", $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/dist/frontend/' . $handle . '.js', array( 'jquery-tiptip', 'jquery' ), SV_WC_Plugin::VERSION );
	}


	/**
	 * Gets the the available tokens for each plugin gateway and combine them.
	 *
	 * Tokens are also separated into Credit Card and eCheck-specific class members for convenience.
	 *
	 * @since 4.0.0
	 */
	protected function load_tokens() {

		if ( ! empty( $this->tokens ) ) {
			return $this->tokens;
		}

		$this->credit_card_tokens = $this->echeck_tokens = array();

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! $gateway->is_available() || ! ( $gateway->supports_tokenization() && $gateway->tokenization_enabled() ) ) {
				continue;
			}

			foreach ( $gateway->get_payment_tokens_handler()->get_tokens( get_current_user_id() ) as $token ) {

				// prevent duplicates, as some gateways will return all tokens in each each gateway
				if ( isset( $this->credit_card_tokens[ $token->get_id() ] ) ||  isset( $this->echeck_tokens[ $token->get_id() ] ) ) {
					continue;
				}

				if ( $token->is_credit_card() ) {

					$this->credit_card_tokens[ $token->get_id() ] = $token;

				} elseif ( $token->is_echeck() ) {

					$this->echeck_tokens[ $token->get_id() ] = $token;
				}
			}
		}

		// we don't use array_merge here since the indexes could be numeric
		// and cause the indexes to be reset
		$this->tokens = $this->credit_card_tokens + $this->echeck_tokens;

		$this->has_tokens = ! empty( $this->tokens );

		return $this->tokens;
	}


	/**
	 * Clear the tokens transients after making a method the default,
	 * so that the correct payment method shows as default.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param int $token_id token ID
	 * @param \WC_Payment_Token $token core token object
	 */
	public function clear_payment_methods_transients( $token_id, $token ) {

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! $gateway->is_available() || ! ( $gateway->supports_tokenization() && $gateway->tokenization_enabled() ) ) {
				continue;
			}

			$gateway->get_payment_tokens_handler()->clear_transient( get_current_user_id() );
		}
	}


	/**
	 * Adds the token ID to the token data array.
	 *
	 * @see wc_get_account_saved_payment_methods_list
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param array $item individual list item from woocommerce_saved_payment_methods_list
	 * @param \WC_Payment_Token $token payment token associated with this method entry
	 * @return array
	 */
	public function add_payment_methods_list_item_id( $item, $token ) {

		$item['token'] = $token->get_token();

		return $item;
	}


	/**
	 * Adds the Edit and Save buttons to the Actions column.
	 *
	 * @see wc_get_account_saved_payment_methods_list
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param array $item individual list item from woocommerce_saved_payment_methods_list
	 * @param \WC_Payment_Token $core_token payment token associated with this method entry
	 * @return array
	 */
	public function add_payment_methods_list_item_edit_action( $item, $core_token ) {

		// add new actions for FW tokens belonging to this gateway
		if ( $token = $this->get_token_by_id( $core_token->get_token() ) ) {

			$new_actions = [
				'edit' => [
					'url'  => '#',
					'name' => esc_html__( 'Edit', 'woocommerce-plugin-framework' ),
				],
				'save' => [
					'url'  => '#',
					'name' => esc_html__( 'Save', 'woocommerce-plugin-framework' ),
				]
			];

			/**
			 * My Payment Methods Table Method Actions Filter.
			 *
			 * Allows actors to modify the table method actions.
			 *
			 * @since 4.0.0
			 * @since 5.8.0 defining a class for the action button is no longer supported
			 *
			 * @param $actions array {
			 *     @type string $url action URL
			 *     @type string $name action button name
			 * }
			 * @param SV_WC_Payment_Gateway_Payment_Token $token
			 * @param SV_WC_Payment_Gateway_My_Payment_Methods $this instance
			 */
			$custom_actions = apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_method_actions', [], $token, $this );

			$item['actions'] = array_merge( $new_actions, $item['actions'], $custom_actions );
		}

		return $item;
	}


	/**
	 * Adds columns to the payment methods table.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param array of table columns in key => Title format
	 * @return array of table columns in key => Title format
	 */
	public function add_payment_methods_columns( $columns  = [] ) {

		$title_column   = [ 'title' => __( 'Title', 'woocommerce-plugin-framework' ) ];
		$columns        = SV_WC_Helper::array_insert_after( $columns, 'method', $title_column );

		$details_column = [ 'details' => __( 'Details', 'woocommerce-plugin-framework' ) ];
		$columns        = SV_WC_Helper::array_insert_after( $columns, 'title', $details_column );

		$default_column = [ 'default' => __( 'Default?', 'woocommerce-plugin-framework' ) ];
		$columns        = SV_WC_Helper::array_insert_after( $columns, 'expires', $default_column );

		/**
		 * My Payment Methods Table Headers Filter.
		 *
		 * Allow actors to modify the table headers.
		 *
		 * In 5.6.0, moved from SV_WC_Payment_Gateway_My_Payment_Methods::get_table_headers() and
		 * renamed the `expires` column (previously `expiry`) for consistency with core column keys.
		 *
		 * @since 4.0.0
		 * @param array $headers table headers {
		 *     @type string $method
		 *     @type string $title
		 *     @type string $details
		 *     @type string $expires
		 *     @type string $default
		 *     @type string $actions
		 * }
		 * @param SV_WC_Payment_Gateway_My_Payment_Methods $this instance
		 */
		$columns = apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_headers', $columns, $this );

		// backwards compatibility for 3rd parties using the filter with the old column keys
		if ( array_key_exists( 'expiry', $columns ) ) {

			$columns['expires'] = $columns['expiry'];
			unset( $columns['expiry'] );
		}

		return $columns;
	}


	/**
	 * Gets FW token object from payment method token ID.
	 *
	 * @since 5.8.0
	 *
	 * @param string $token_id token string
	 * @return SV_WC_Payment_Gateway_Payment_Token|null
	 */
	private function get_token_by_id( $token_id ) {

		$token = null;

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			$token = $gateway->get_payment_tokens_handler()->get_token( get_current_user_id(), $token_id );

			if ( ! empty( $token ) ) {
				break;
			}
		}

		return $token;
	}


	/**
	 * Gets FW token object from payment method data array.
	 *
	 * @since 5.8.0
	 *
	 * @param array $method payment method data array
	 * @return SV_WC_Payment_Gateway_Payment_Token|null
	 */
	private function get_token_from_method_data_array( $method ) {

		if ( ! empty( $method['token'] ) ) {
			return $this->get_token_by_id( $method['token'] );
		}

		return null;
	}

	/**
	 * Adds the Title column content.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param array $method payment method
	 */
	public function add_payment_method_title( $method ) {

		if ( $token = $this->get_token_from_method_data_array( $method ) ) {

			echo $this->get_payment_method_title_html( $token );
		}
	}


	/**
	 * Adds the Details column content.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param array $method payment method
	 */
	public function add_payment_method_details( $method ) {

		if ( $token = $this->get_token_from_method_data_array( $method ) ) {

			echo $this->get_payment_method_details_html( $token );
		}
	}


	/**
	 * Adds the Default column content.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param array $method payment method
	 */
	public function add_payment_method_default( $method ) {

		echo $this->get_payment_method_default_html( ! empty( $method['is_default'] ), $this->get_token_from_method_data_array( $method ) );
	}


	/**
	 * Triggers the wc_{id}_before_my_payment_method_table action.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param bool $has_methods whether there any saved payment methods in the table
	 */
	public function before_payment_methods_table( $has_methods ) {

		if ( $has_methods ) {

			/**
			 * Before My Payment Methods Table Action.
			 *
			 * Fired before WooCommerce's My Payment Methods table HTML is rendered.
			 *
			 * @param SV_WC_Payment_Gateway_My_Payment_Methods $this instance
			 *
			 * @since 5.8.0 triggered on woocommerce_before_account_payment_methods
			 *
			 * @since 4.0.0
			 */
			do_action( 'wc_' . $this->get_plugin()->get_id() . '_before_my_payment_method_table', $this );
		}
	}


	/**
	 * Triggers the wc_{id}_after_my_payment_method_table action.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param bool $has_methods whether there any saved payment methods in the table
	 */
	public function after_payment_methods_table( $has_methods ) {

		if ( $has_methods ) {

			/**
			 * After My Payment Methods Table Action.
			 *
			 * Fired after WooCommerce's My Payment Methods table HTML is rendered.
			 *
			 * @since 4.0.0
			 * @since 5.8.0 triggered on woocommerce_after_account_payment_methods
			 *
			 * @param SV_WC_Payment_Gateway_My_Payment_Methods $this instance
			 */
			do_action( 'wc_' . $this->get_plugin()->get_id() . '_after_my_payment_method_table', $this );
		}
	}


	/**
	 * Triggers action wc_payment_gateway_{id}_payment_method_deleted when a framework token is deleted.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param int $core_token_id the ID of a core token
	 * @param \WC_Payment_Token $core_token the core token object
	 */
	public function payment_token_deleted( $core_token_id, $core_token ) {

		$token_id = null;

		// find out if the core token belongs to one of the gateways from this plugin
		// we can't use get_token_by_id() here because the FW token and associated core token were already deleted
		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( $gateway->get_id() === $core_token->get_gateway_id() ) {

				$token_id = $core_token->get_token();
				break;
			}
		}

		// confirm this is one of the plugin's tokens and that the token was deleted from the Payment Methods screen
		if ( $token_id && (int) $core_token_id === (int) get_query_var( 'delete-payment-method' ) ) {

			$user_id = get_current_user_id();

			/**
			 * Fires after a new payment method is deleted by a customer.
			 *
			 * @param string $token_id ID of the deleted token
			 * @param int $user_id user ID
			 *
			 * @since 5.0.0
			 *
			 */
			do_action( 'wc_payment_gateway_' . $core_token->get_gateway_id() . '_payment_method_deleted', $token_id, $user_id );
		}
	}


	/**
	 * Renders the payment methods table.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-20 {WV 2020-02-20}
	 *
	 * @internal
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 */
	public function render() {

		wc_deprecated_function( __METHOD__, '5.8.0' );
	}


	/**
	 * Gets the JS args for the payment methods handler.
	 *
	 * Payment gateways can overwrite this method to define specific args.
	 * render_js() will apply filters to the returned array of args.
	 *
	 * @since 5.7.0
	 *
	 * @return array
	 */
	protected function get_js_handler_args() {

		$args = [
			'id'              => $this->get_plugin()->get_id(),
			'slug'            => $this->get_plugin()->get_id_dasherized(),
			'has_core_tokens' => (bool) wc_get_customer_saved_methods_list( get_current_user_id() ),
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'      => wp_create_nonce( 'wc_' . $this->get_plugin()->get_id() . '_save_payment_method' ),
			'i18n'            => [
				'edit_button'   => esc_html__( 'Edit', 'woocommerce-plugin-framework' ),
				'cancel_button' => esc_html__( 'Cancel', 'woocommerce-plugin-framework' ),
				'save_error'    => esc_html__( 'Oops, there was an error updating your payment method. Please try again.', 'woocommerce-plugin-framework' ),
				'delete_ays'    => esc_html__( 'Are you sure you want to delete this payment method?', 'woocommerce-plugin-framework' ),
			],
		];

		return $args;
	}


	/**
	 * Gets the JS handler class name.
	 *
	 * Plugins can override this for their own JS implementations.
	 *
	 * @since 5.1.0
	 * @deprecated 5.7.0
	 *
	 * @return string
	 */
	protected function get_js_handler_class() {

		wc_deprecated_function( __METHOD__, '5.7.0', __CLASS__ . '::get_js_handler_class_name()' );

		return parent::get_js_handler_class_name();
	}


	/**
	 * Adds a log entry.
	 *
	 * @since 5.7.0
	 *
	 * @param string $message message to log
	 */
	protected function log_event( $message ) {

		$this->get_plugin()->log( $message );
	}


	/**
	 * Determines whether logging is enabled.
	 *
	 * Considers logging enabled at the plugin level if at least one gateway has logging enabled.
	 *
	 * @since 5.7.0
	 *
	 * @return bool
	 */
	protected function is_logging_enabled() {

		$is_logging_enabled = parent::is_logging_enabled();

		if ( ! $is_logging_enabled ) {

			foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

				if ( $gateway->debug_log() ) {
					$is_logging_enabled = true;
					break;
				}
			}
		}

		return $is_logging_enabled;
	}


	/**
	 * Return the no payment methods section HTML
	 *
	 * @since 4.0.0
	 * @return string no payment methods HTML
	 */
	protected function get_no_payment_methods_html() {

		/**
		 * My Payment Methods Table No Methods Text Filter.
		 *
		 * Allow actors to modify the text shown when no saved payment methods are
		 * present.
		 *
		 * @since 4.0.0
		 *
		 * @param string $message no methods text
		 * @param SV_WC_Payment_Gateway_My_Payment_Methods $this instance
		 */
		/* translators: Payment method as in a specific credit card, eCheck or bank account */
		$html = '<p>' . apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_no_payment_methods_text', esc_html__( 'You do not have any saved payment methods.', 'woocommerce-plugin-framework' ), $this ) . '</p>';

		/**
		 * My Payment Methods Table No Methods HTML Filter.
		 *
		 * Allow actors to modify the HTML used when no saved payment methods are
		 * present.
		 *
		 * @since 4.0.0
		 *
		 * @param string $html no methods HTML
		 * @param SV_WC_Payment_Gateway_My_Payment_Methods $this instance
		 */
		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_no_payment_methods_html', $html, $this );
	}


	/** Table HTML methods ****************************************************/


	/**
	 * Return the table title HTML, text defaults to "My Payment Methods"
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-21 {WV 2020-02-21}
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 *
	 * @return string table title HTML
	 */
	protected function get_table_title_html() {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return '';
	}


	/**
	 * Returns the table HTML
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-21 {WV 2020-02-21}
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 *
	 * @return string table HTML
	 */
	public function get_table_html() {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return '';
	}


	/**
	 * Returns the table head HTML
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-21 {WV 2020-02-21}
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 *
	 * @return string table thead HTML
	 */
	protected function get_table_head_html() {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return '';
	}


	/**
	 * Returns the table headers.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-17 {DM 2020-02-17}
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 *
	 * @return array of table headers in key => Title format
	 */
	protected function get_table_headers() {

		wc_deprecated_function( __METHOD__, '5.8.0', 'SV_WC_Payment_Gateway_My_Payment_Methods::add_payment_methods_columns' );

		return $this->add_payment_methods_columns();
	}


	/**
	 * Returns the table body HTML
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-21 {WV 2020-02-21}
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 *
	 * @return string table tbody HTML
	 */
	protected function get_table_body_html() {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return '';
	}


	/**
	 * Returns the table body row HTML, each row represents a single payment method.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-21 {WV 2020-02-21}
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token[] $tokens token objects
	 * @return string table tbody > tr HTML
	 */
	protected function get_table_body_row_html( $tokens ) {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return '';
	}


	/**
	 * Gets the payment method data for a given token.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-24 {FN 2020-02-21}
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token the token object
	 * @return array payment method data suitable for HTML output
	 */
	protected function get_table_body_row_data( $token ) {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return [];
	}


	/**
	 * Get a token's payment method title HTML.
	 *
	 * @since 5.1.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
	 * @return string
	 */
	protected function get_payment_method_title_html( SV_WC_Payment_Gateway_Payment_Token $token ) {

		$nickname = $token->get_nickname();
		$title    = $nickname ?: $token->get_type_full();

		/**
		 * Filter a token's payment method title.
		 *
		 * @since 4.0.0
		 *
		 * @param string $title payment method title
		 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
		 */
		$title = apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_method_title', $title, $token, $this );

		$html = '<div class="view">' . esc_html( $title ) . '</div>';

		// add the edit context input
		$html .= '<div class="edit" style="display:none;">';
			$html .= '<input type="text" class="nickname" name="nickname" value="' . esc_html( $token->get_nickname() ) . '" placeholder="' . esc_attr( __( 'Nickname', 'woocommerce-plugin-framework' ) ) . '" />';
			$html .= '<input type="hidden" name="token-id" value="' . esc_attr( $token->get_id() ) . '" />';
			$html .= '<input type="hidden" name="plugin-id" value="' . esc_attr( $this->get_plugin()->get_id_dasherized() ) . '" />';
		$html .= '</div>';

		/**
		 * Filter a token's payment method title HTML.
		 *
		 * @since 5.1.0
		 *
		 * @param string $html title HTML
		 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
		 */
		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_method_title_html', $html, $token );
	}


	/**
	 * Get a token's payment method "default" flag HTML.
	 *
	 * @since 5.1.0
	 *
	 * @param boolean $is_default true if the token is the default token
	 * @param SV_WC_Payment_Gateway_Payment_Token|null $token FW token object, only set if the token is a FW token
	 * @return string
	 */
	protected function get_payment_method_default_html( $is_default = false, SV_WC_Payment_Gateway_Payment_Token $token = null ) {

		$html = $is_default ? '<mark class="default">' . esc_html__( 'Default', 'woocommerce-plugin-framework' ) . '</mark>' : '';

		if ( $token instanceof SV_WC_Payment_Gateway_Payment_Token ) {

			/**
			 * Filter a FW token's payment method "default" flag HTML.
			 *
			 * @since 5.1.0
			 *
			 * @param string $html "default" flag HTML
			 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
			 */
			$html = apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_method_default_html', $html, $token );
		}

		return $html;
	}


	/**
	 * Gets a token's payment method details HTML.
	 *
	 * This includes the method type icon, last four digits, and "default"
	 * badge if applicable. Example:
	 *
	 * [icon] * * * 1234 [default]
	 *
	 * @since 5.1.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
	 * @return array
	 */
	protected function get_payment_method_details_html( SV_WC_Payment_Gateway_Payment_Token $token ) {

		$html = '';

		if ( $image_url = $token->get_image_url() ) {
			$html .= sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" width="40" height="25" />', esc_url( $image_url ), esc_attr( $token->get_type_full() ) );
		}

		if ( $last_four = $token->get_last_four() ) {
			$html .= "&bull; &bull; &bull; {$last_four}";
		}

		/**
		 * Filters a token's payment method details HTML.
		 *
		 * @since 5.1.0
		 *
		 * @param string $html details HTML
		 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
		 */
		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_details_html', $html, $token );
	}


	/**
	 * Get a token's payment method expiration date HTML.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-21 {WV 2020-02-21}
	 *
	 * @since 5.1.0
	 * @deprecated 5.8.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
	 * @return string
	 */
	protected function get_payment_method_expiry_html( SV_WC_Payment_Gateway_Payment_Token $token ) {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return '';
	}


	/**
	 * Get a token's payment method actions HTML.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-21 {WV 2020-02-21}
	 *
	 * @since 5.1.0
	 * @deprecated 5.8.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
	 * @return string
	 */
	protected function get_payment_method_actions_html( SV_WC_Payment_Gateway_Payment_Token $token ) {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return '';
	}


	/**
	 * Gets the actions for the given payment method token.
	 *
	 * TODO: remove this method by version 6.0.0 or by 2021-02-21 {WV 2020-02-21}
	 *
	 * @since 4.0.0
	 * @deprecated 5.8.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
	 * @return array
	 */
	protected function get_payment_method_actions( $token ) {

		wc_deprecated_function( __METHOD__, '5.8.0' );

		return [];
	}


	/** Payment Method actions ************************************************/


	/**
	 * Saves a payment method via AJAX.
	 *
	 * @internal
	 *
	 * @since 5.1.0
	 */
	public function ajax_save_payment_method() {

		check_ajax_referer( 'wc_' . $this->get_plugin()->get_id() . '_save_payment_method', 'nonce' );

		try {

			$this->load_tokens();

			$token_id = SV_WC_Helper::get_posted_value( 'token_id' );

			if ( empty( $this->tokens[ $token_id ] ) || ! $this->tokens[ $token_id ] instanceof SV_WC_Payment_Gateway_Payment_Token ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid token ID' );
			}

			$user_id  = get_current_user_id();
			$token    = $this->tokens[ $token_id ];
			$gateway  = $this->get_plugin()->get_gateway_from_token( $user_id, $token );

			// bail if the gateway or token couldn't be found for this user
			if ( ! $gateway || ! $gateway->get_payment_tokens_handler()->user_has_token( $user_id, $token ) ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid token' );
			}

			$data = array();

			parse_str( SV_WC_Helper::get_posted_value( 'data' ), $data );

			// set the data
			$token = $this->save_token_data( $token, $data );

			// persist the data
			$gateway->get_payment_tokens_handler()->update_token( $user_id, $token );

			wp_send_json_success( [
				'title' => $this->get_payment_method_title_html( $token ),
				'nonce' => wp_create_nonce( 'wc_' . $this->get_plugin()->get_id() . '_save_payment_method' ),
			] );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			wp_send_json_error( $e->getMessage() );
		}
	}


	/**
	 * Saves data to a token.
	 *
	 * Gateways can override this to set their own data if they add custom Edit
	 * fields. Note that this does not persist the data to the db, but only sets
	 * it for the object.
	 *
	 * @since 5.1.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token object
	 * @param array $data {
	 *    new data to store for the token
	 *
	 *    @type string $nickname method nickname
	 *    @type string $default  whether the method should be set as default
	 * }
	 * @return SV_WC_Payment_Gateway_Payment_Token
	 */
	protected function save_token_data( SV_WC_Payment_Gateway_Payment_Token $token, array $data ) {

		$raw_nickname   = ! empty( $data['nickname'] ) ? $data['nickname'] : '';
		$clean_nickname = wc_clean( $raw_nickname );

		// only set the nickname if there is a clean value, or it was deliberately cleared
		if ( $clean_nickname || ! $raw_nickname ) {
			$token->set_nickname( $clean_nickname );
		}

		return $token;
	}


	/**
	 * Handles custom payment methods actions.
	 *
	 * @internal
	 *
	 * @since 4.0.0
	 */
	public function handle_payment_method_actions() {

		$token  = trim( SV_WC_Helper::get_requested_value( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token' ) );
		$action = SV_WC_Helper::get_requested_value( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-action' );

		// process payment method actions
		if ( $token && $action && ! empty( $_GET['_wpnonce'] ) && is_user_logged_in() ) {

			// security check
			if ( false === wp_verify_nonce( $_GET['_wpnonce'], 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token-action' ) ) {

				SV_WC_Helper::wc_add_notice( esc_html__( 'Oops, you took too long, please try again.', 'woocommerce-plugin-framework' ), 'error' );

				$this->redirect_to_my_account();
			}

			$user_id = get_current_user_id();
			$gateway = $this->get_plugin()->get_gateway_from_token( $user_id, $token );

			// couldn't find an associated gateway for that token
			if ( ! is_object( $gateway ) ) {

				SV_WC_Helper::wc_add_notice( esc_html__( 'There was an error with your request, please try again.', 'woocommerce-plugin-framework' ), 'error' );

				$this->redirect_to_my_account();
			}

			/**
			 * My Payment Methods Custom Action.
			 *
			 * Fired when a custom action is requested for a payment method (e.g. other than delete/make default)
			 *
			 * @since 4.0.0
			 * @param \SV_WC_Payment_Gateway_My_Payment_Methods $this instance
			 */
			do_action( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_action_' . sanitize_title( $action ), $this );

			$this->redirect_to_my_account();
		}
	}


	/**
	 * Renders the JavaScript.
	 *
	 * @since 5.1.0
	 */
	public function render_js() {

		wc_enqueue_js( $this->get_safe_handler_js() );
	}


	/**
	 * Redirect back to the Payment Methods (WC 2.6+) or My Account page
	 *
	 * @since 4.0.0
	 */
	protected function redirect_to_my_account() {

		wp_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
		exit;
	}


	/**
	 * Return the gateway plugin, primarily a convenience method to other actors
	 * using filters
	 *
	 * @since 4.0.0
	 *
	 * @return SV_WC_Payment_Gateway_Plugin
	 */
	public function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Returns true if at least one of the plugin's gateways supports the
	 * add new payment method feature
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	protected function supports_add_payment_method() {

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( $gateway->is_direct_gateway() && $gateway->supports_add_payment_method() ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Determines if we're viewing the My Account -> Payment Methods page.
	 *
	 * @since 5.1.0
	 *
	 * @return bool
	 */
	protected function is_payment_methods_page() {
		global $wp;

		return is_user_logged_in() && is_account_page() && isset( $wp->query_vars['payment-methods'] );
	}


}


endif;
