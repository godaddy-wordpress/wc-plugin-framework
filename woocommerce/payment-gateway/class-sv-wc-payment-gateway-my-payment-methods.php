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

if ( ! class_exists( 'SV_WC_Payment_Gateway_My_Payment_Methods' ) ) :

/**
 * My Payment Methods Class
 *
 * Renders the My Payment Methods table on the My Account page and handles
 * any associated actions (deleting a payment method, etc)
 *
 * @since 4.0.0
 */
class SV_WC_Payment_Gateway_My_Payment_Methods {


	/** @var SV_WC_Payment_Gateway_Plugin */
	protected $plugin;

	/** @var array of SV_WC_Payment_Gateway_Token objects */
	protected $tokens;

	/** @var array of credit card SV_WC_Payment_Gateway_Token objects */
	protected $credit_card_tokens;

	/** @var array of eCheck SV_WC_Payment_Gateway_Token objects */
	protected $echeck_tokens;

	/** @var bool true if there are tokens */
	protected $has_tokens;


	/**
	 * Setup Class
	 *
	 * Note: this constructor executes during the `wp` action
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin gateway plugin
	 * @since 4.0.0
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		// load all tokens for the given plugin
		$this->load_tokens();

		$this->has_tokens = ! empty( $this->tokens );

		// render the My Payment Methods section
		add_action( 'woocommerce_after_my_account', array( $this, 'render' ) );

		// styles/scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_styles_scripts' ) );

		// handle payment method deletion, etc.
		$this->handle_payment_method_actions();
	}


	/**
	 * Enqueue frontend CSS/JS
	 *
	 * @since 4.0.0
	 */
	public function maybe_enqueue_styles_scripts() {

		wp_enqueue_style( 'dashicons' );

		// // Add confirm javascript when deleting payment methods
		if ( $this->has_tokens ) {
			wc_enqueue_js( '
			$( ".sv-wc-payment-gateway-payment-method-actions .delete-payment-method" ).on( "click", function( e ) {
				if ( ! confirm( "' . esc_js( __( 'Are you sure you want to delete this payment method?', $this->get_plugin()->get_text_domain() ) ) . '" ) ) {
					e.preventDefault();
				}
			} );
		' );
		}
	}


	/**
	 * Get the the available tokens for each plugin gateway and combine them
	 *
	 * Tokens are also separated into Credit Card and eCheck-specific class members
	 * for convenience.
	 *
	 * @since 4.0.0
	 */
	protected function load_tokens() {

		if ( ! empty( $this->tokens ) ) {
			return $this->tokens;
		}

		$this->credit_card_tokens = $this->echeck_tokens = array();

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! $gateway->is_available() || ! $gateway->tokenization_enabled() ) {
				continue;
			}

			foreach ( $gateway->get_payment_tokens( get_current_user_id() ) as $token ) {

				// prevent duplicates, as some gateways will return all tokens in each each gateway
				if ( isset( $this->credit_card_tokens[ $token->get_token() ] ) ||  isset( $this->echeck_tokens[ $token->get_token() ] ) ) {
					continue;
				}

				if ( $token->is_credit_card() ) {

					$this->credit_card_tokens[ $token->get_token() ] = $token;

				} elseif ( $token->is_check() ) {

					$this->echeck_tokens[ $token->get_token() ] = $token;
				}
			}
		}

		return $this->tokens = array_merge( $this->credit_card_tokens, $this->echeck_tokens );
	}


	/**
	 * Render the My Payment Methods section
	 *
	 * @since 4.0.0
	 */
	public function render() {

		if ( $this->has_tokens ) {

			echo $this->get_table_title_html();

			do_action( 'wc_' . $this->get_plugin()->get_id() . '_before_my_payment_method_table', $this );

			echo $this->get_table_html();

			do_action( 'wc_' . $this->get_plugin()->get_id() . '_after_my_payment_method_table', $this );

		} else {

			echo $this->get_table_title_html();

			echo $this->get_no_payment_methods_html();
		}
	}


	/**
	 * Return the no payment methods section HTML
	 *
	 * @since 4.0.0
	 * @return string no payment methods HTML
	 */
	protected function get_no_payment_methods_html() {

		$html = '<p>' . apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_no_payment_methods_text', esc_html__( 'You do not have any saved payment methods.', $this->get_plugin()->get_text_domain() ), $this ) . '</p>';

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_no_payment_methods_html', $html, $this );
	}


	/** Table HTML methods ****************************************************/


	/**
	 * Return the table title HTML, text defaults to "My Payment Methods"
	 *
	 * @since 4.0.0
	 * @return string table title HTML
	 */
	protected function get_table_title_html() {

		$title = apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_title', __( 'My Payment Methods', $this->get_plugin()->get_text_domain() ), $this );

		$html = '<div class="sv-wc-payment-gateway-my-payment-methods-table-title">';

		$html .= sprintf( '<h2 id="wc-%s-my-payment-methods">%s</h2>', $this->get_plugin()->get_id_dasherized(), esc_html( $title ) );

		if ( $this->supports_add_payment_method() ) {
			$html .= sprintf( '<a class="button sv-wc-payment-gateway-my-payment-methods-add-payment-method-button dashicons-before dashicons-plus-alt" href="%s">%s</a>', esc_url( wc_get_endpoint_url( 'add-payment-method' ) ), esc_html_x( 'Add New Payment Method', 'Supports add new payment method feature', $this->get_plugin()->get_text_domain() ) );
		}

		$html .= '</div>';

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_title_html', $html, $this );
	}


	/**
	 * Return the table HTML
	 *
	 * @since 4.0.0
	 * @return string table HTML
	 */
	public function get_table_html() {

		$html = sprintf( '<table class="shop_table shop_table_responsive sv-wc-payment-gateway-my-payment-methods-table wc-%s-my-payment-methods">', sanitize_html_class( $this->get_plugin()->get_id_dasherized() ) );

		$html .= $this->get_table_head_html();

		$html .= $this->get_table_body_html();

		$html .= '</table>';

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_html', $html, $this );
	}


	/**
	 * Return the table head HTML
	 *
	 * @since 4.0.0
	 * @return string table thead HTML
	 */
	protected function get_table_head_html() {

		$html = '<thead><tr>';

		foreach ( $this->get_table_headers() as $key => $title ) {

			$html .= sprintf( '<th class="sv-wc-payment-gateway-my-payment-method-table-header wc-%s-payment-method-%s"><span class="nobr">%s</span></th>', sanitize_html_class( $this->get_plugin()->get_id_dasherized() ), sanitize_html_class( $key ), esc_html( $title ) );
		}

		$html .= '</tr></thead>';

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_head_html', $html, $this );
	}


	/**
	 * Return the table headers
	 *
	 * @since 4.0.0
	 * @return array of table headers in key => Title format
	 */
	protected function get_table_headers() {

		$headers = array(
			'title'   => __( 'Method', $this->get_plugin()->get_text_domain() ),
			'expiry'  => __( 'Expires', $this->get_plugin()->get_text_domain() ),
			'actions' => '&nbsp;'
		);

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_headers', $headers, $this );
	}


	/**
	 * Return the table body HTML
	 *
	 * @since 4.0.0
	 * @return string table tbody HTML
	 */
	protected function get_table_body_html() {

		$html = '<tbody>';

		if ( $this->credit_card_tokens && $this->echeck_tokens ) {

			$html .= sprintf( '<tr class="sv-wc-payment-gateway-my-payment-methods-type-divider wc-%s-my-payment-methods-type-divider"><td colspan="%d">%s</td><tr>',
				sanitize_html_class( $this->get_plugin()->get_id_dasherized() ), count( $this->get_table_headers() ), esc_html__( 'Credit/Debit Cards', $this->get_plugin()->get_text_domain() )
			);

			$html .= $this->get_table_body_row_html( $this->credit_card_tokens );

			$html .= sprintf( '<tr class="sv-wc-payment-gateway-my-payment-methods-type-divider wc-%s-my-payment-methods-type-divider"><td colspan="%d">%s</td><tr>',
				sanitize_html_class( $this->get_plugin()->get_id_dasherized() ), count( $this->get_table_headers() ), esc_html__( 'Bank Accounts', $this->get_plugin()->get_text_domain() )
			);

			$html .= $this->get_table_body_row_html( $this->echeck_tokens );

		} else {

			$html .= $this->get_table_body_row_html( $this->tokens );
		}

		$html .= '</tbody>';

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_body_html', $html, $this );
	}


	/**
	 * Return the table body row HTML, each row represents a single payment method
	 *
	 * @since 4.0.0
	 * @return string table tbody > tr HTML
	 */
	protected function get_table_body_row_html( $tokens ) {

		$html = '';

		// for responsive table data-title attributes
		$headers = $this->get_table_headers();

		foreach ( $this->get_table_body_row_data( $tokens ) as $method ) {

			$html .= sprintf( '<tr class="sv-wc-payment-gateway-my-payment-methods-method wc-%s-my-payment-methods-method">', sanitize_html_class( $this->get_plugin()->get_id_dasherized() ) );

			foreach ( $method as $attribute => $value ) {

				$html .= sprintf( '<td class="sv-wc-payment-gateway-payment-method-%1$s wc-%2$s-payment-method-%1$s" data-title="%4$s">%3$s</td>', sanitize_html_class( $attribute ), $this->get_plugin()->get_id_dasherized(), $value, esc_attr( isset( $headers[ $attribute ] ) ? $headers[ $attribute ] : '' ) );
			}

			$html .= '</tr>';
		}

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_row_html', $html, $tokens, $this );
	}


	/**
	 * Return the payment method data for a given set of tokens
	 *
	 * @since 4.0.0
	 * @param array $tokens array of tokens
	 * @return array payment method data suitable for HTML output
	 */
	protected function get_table_body_row_data( $tokens ) {

		$methods = array();

		foreach ( $tokens as $token ) {

			$actions = array();

			foreach ( $this->get_payment_method_actions( $token ) as $action ) {

				$actions[] = sprintf( '<a href="%s" class="button %s">%s</a>', esc_url( $action['url'] ), implode( ' ', array_map( 'sanitize_html_class', (array) $action['class'] ) ), esc_html( $action['name'] ) );
			}

			$methods[] = array(
				'title'   => $this->get_payment_method_title( $token ),
				'expiry'  => $token->get_exp_month() && $token->get_exp_year() ? $token->get_exp_date() : __( 'N/A', $this->get_plugin()->get_text_domain() ),
				'actions' => implode( '', $actions ),
			);
		}

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_body_row_data', $methods, $this->tokens, $this );
	}


	/**
	 * Return the actions for the given payment method token, currently this is
	 *
	 * `make-default` - available if the token isn't already the default token
	 * `delete` - delete the token
	 *
	 * @since 4.0.0
	 * @param SV_WC_Payment_Gateway_Payment_Token $token
	 * @return array payment method actions
	 */
	protected function get_payment_method_actions( $token ) {

		$actions = array();

		// make default
		if ( ! $token->is_default() ) {

			$actions[] = array(
				'url'   => wp_nonce_url( add_query_arg( array(
					'wc-' . $this->get_plugin()->get_id_dasherized() . '-token'  => $token->get_token(),
					'wc-' . $this->get_plugin()->get_id_dasherized() . '-action' => 'make-default'
				) ), 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token-action' ),
				'class' => array( 'make-payment-method-default' ),
				'name'  => __( 'Make Default', $this->get_plugin()->get_text_domain() )
			);
		}

		// delete
		$actions[] = array(
			'url'   => wp_nonce_url( add_query_arg( array(
				'wc-' . $this->get_plugin()->get_id_dasherized() . '-token'  => $token->get_token(),
				'wc-' . $this->get_plugin()->get_id_dasherized() . '-action' => 'delete'
			) ), 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token-action' ),
			'class' => array( 'delete-payment-method' ),
			'name'  => __( 'Delete', $this->get_plugin()->get_text_domain() ),
		);


		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_method_actions', $actions, $token, $this );
	}


	/**
	 * Get the payment method title for a given token, e.g:
	 *
	 * <Amex logo> American Express ending in 6666
	 *
	 * @since 4.0.0
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token
	 * @return string payment method title
	 */
	protected function get_payment_method_title( $token ) {

		$image_url = $token->get_image_url();
		$last_four = $token->get_last_four();
		$type      = $token->get_type_full();

		if ( $image_url ) {

			// format like "<Amex logo image> American Express"
			$title = sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" width="40" height="25" />%3$s', esc_url( $image_url ), esc_attr__( $type, $this->get_plugin()->get_text_domain() ), esc_html__( $type, $this->get_plugin()->get_text_domain() ) );

		} else {

			// missing payment method image, format like "American Express"
			$title = esc_html__( $type, $this->get_plugin()->get_text_domain() );
		}

		// add "ending in XXXX" if available
		if ( $last_four ) {

			$title .= '&nbsp;' . sprintf( __( 'ending in %s', $this->get_plugin()->get_text_domain() ), $last_four );
		}

		// add "(default)" if token is set as default
		if ( $token->is_default() ) {

			$title .= ' ' . __( '(default)', $this->get_plugin()->get_text_domain() );
		}

		return apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_table_method_title', $title, $token, $this );
	}


	/** Payment Method actions ************************************************/


	/**
	 * Handle payment methods actions, e.g. deleting a payment method or setting
	 * one as default
	 *
	 * @since 4.0.0
	 */
	public function handle_payment_method_actions() {

		if ( ! $this->has_tokens ) {
			return;
		}

		$token  = isset( $_GET[ 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token' ] )  ? trim( $_GET[ 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token' ] ) : '';
		$action = isset( $_GET[ 'wc-' . $this->get_plugin()->get_id_dasherized() . '-action' ] ) ? $_GET[ 'wc-' . $this->get_plugin()->get_id_dasherized() . '-action' ] : '';

		// process payment method actions
		if ( $token && $action && ! empty( $_GET['_wpnonce'] ) && is_user_logged_in() ) {

			// security check
			if ( false === wp_verify_nonce( $_GET['_wpnonce'], 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token-action' ) ) {

				SV_WC_Helper::wc_add_notice( _x( 'Oops, you took too long, please try again.', 'Supports my payment method feature', $this->get_plugin()->get_text_domain() ), 'error' );

				$this->redirect_to_my_account();
			}

			// current logged in user
			$user_id = get_current_user_id();

			$gateway = $this->get_plugin()->get_gateway_from_token( $user_id, $token );

			// couldn't find an associated gateway for that token
			if ( ! is_object( $gateway ) ) {

				SV_WC_Helper::wc_add_notice( _x( 'There was an error with your request, please try again.', 'Supports my payment method feature', $this->get_plugin()->get_text_domain() ), 'error' );

				$this->redirect_to_my_account();
			}

			switch ( $action ) {

				// handle deletion
				case 'delete':

					if ( ! $gateway->remove_payment_token( $user_id, $token ) ) {

						SV_WC_Helper::wc_add_notice( _x( 'Error removing payment method', 'Supports my payment method feature', $this->get_plugin()->get_text_domain() ), 'error' );

					} else {
						SV_WC_Helper::wc_add_notice( _x( 'Payment method deleted.', 'Supports my payment method feature', $this->get_plugin()->get_text_domain() ) );
					}

				break;

				// set default payment method
				case 'make-default':
					$gateway->set_default_payment_token( $user_id, $token );

					SV_WC_Helper::wc_add_notice( _x( 'Default payment method updated.', 'Supports my payment method feature', $this->get_plugin()->get_text_domain() ) );
				break;

				// custom actions
				default:
					do_action( 'wc_' . $this->get_plugin()->get_id() . '_my_payment_methods_action_' . sanitize_title( $action ), $this );
				break;
			}

			$this->redirect_to_my_account();
		}
	}


	/**
	 * Redirect back to the My Account page
	 *
	 * @since 4.0.0
	 */
	protected function redirect_to_my_account() {

		wp_redirect( SV_WC_Plugin_Compatibility::wc_get_page_permalink( 'myaccount' ) );
		exit;
	}


	/**
	 * Return the gateway plugin, primarily a convenience method to other actors
	 * using filters
	 *
	 * @since 4.0.0
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


}

endif;  // class exists check
