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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Admin
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The token editor.
 *
 * @since 4.3.0-dev
 */
class SV_WC_Payment_Gateway_Admin_Payment_Token_Editor {

	/** @var \SV_WC_Payment_Gateway_Direct the gateway object **/
	protected $gateway;


	/**
	 * Construct the editor.
	 *
	 * @since 4.3.0-dev
	 * @param \SV_WC_Payment_Gateway_Direct the gateway object
	 */
	public function __construct( SV_WC_Payment_Gateway_Direct $gateway ) {

		$this->gateway = $gateway;

		// Load the editor scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		// Remove a token via AJAX
		add_action( 'wp_ajax_sv_wc_payment_gateway_admin_remove_payment_token', array( $this, 'ajax_remove_token' ) );
	}


	/**
	 * Load the editor scripts and styles.
	 *
	 * @since 4.3.0-dev
	 */
	public function enqueue_scripts_styles() {

		wp_enqueue_style( 'sv-wc-payment-gateway-token-editor', $this->get_gateway()->get_plugin()->get_payment_gateway_framework_assets_url() . '/css/admin/sv-wc-payment-gateway-token-editor.css', array(), SV_WC_Plugin::VERSION );
	}


	/**
	 * Display the token editor.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 */
	public function display( $user_id ) {

		$title   = $this->get_gateway()->get_title();
		$tokens  = $this->get_tokens( $user_id );

		$fields     = $this->get_fields();
		$input_name = $this->get_input_name();
		$actions    = $this->get_token_actions();

		include( $this->get_gateway()->get_plugin()->get_payment_gateway_framework_path() . '/admin/views/html-user-payment-token-editor.php' );

		$this->output_js();
	}


	/**
	 * Output the inline JS.
	 *
	 * @since 4.3.0-dev
	 */
	protected function output_js() {

		// TODO: Just a proof-of-concept to ensure AJAX is compatible with the way this is instantiated.
		//       Future me will clean this up and move things to their own asset files :) ?>

		<script type="text/javascript">

			jQuery( document ).ready( function( $ ) {

				// Remove
				$( '.sv-wc-payment-gateway-token-action-button[data-action="remove"]' ).click( function( e ) {

					e.preventDefault();

					if ( ! confirm( '<?php esc_html_e( 'Are you sure you wish to do this?', 'woocommerce-plugin-framework' ); ?>' ) ) {
						return;
					}

					var row = $( this ).closest( 'tr' );

					var data = {
						action:   'sv_wc_payment_gateway_admin_remove_payment_token',
						user_id:  $( this ).data( 'user-id' ),
						token_id: $( this ).data( 'token-id' ),
						security: '<?php echo wp_create_nonce( 'sv_wc_payment_gateway_admin_remove_payment_token' ); ?>'
					};

					$.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function( response ) {

						if ( true === response.success ) {
							$( row ).remove();
						}

					} );

				} );

			} );

		</script>

		<?php
	}


	/**
	 * Save the token editor.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 */
	public function save( $user_id ) {

		$tokens = ( isset( $_POST[ $this->get_input_name() ] ) ) ? $_POST[ $this->get_input_name() ] : array();

		$built_tokens = array();

		foreach ( $tokens as $token_id => $data ) {

			$token_id = $data['id'];

			unset( $data['id'] );

			if ( 'credit_card' === $data['type'] ) {
				$data = $this->prepare_expiry_date( $data );
			}

			$built_tokens[ $token_id ] = $this->build_token( $user_id, $token_id, $data );
		}

		$this->update_tokens( $user_id, $built_tokens );
	}


	/**
	 * Add a token via AJAX.
	 *
	 * @since 4.3.0-dev
	 */
	public function ajax_add_token() {

		check_ajax_referer( 'sv_wc_payment_gateway_admin_add_payment_token', 'security' );

		// TODO: fancy token adding

		wp_send_json_success();
	}


	/**
	 * Remove a token via AJAX.
	 *
	 * @since 4.3.0-dev
	 */
	public function ajax_remove_token() {

		check_ajax_referer( 'sv_wc_payment_gateway_admin_remove_payment_token', 'security' );

		$user_id  = SV_WC_Helper::get_request( 'user_id' );
		$token_id = SV_WC_Helper::get_request( 'token_id' );

		if ( $this->remove_token( $user_id, $token_id ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}


	/**
	 * Build a token object from data saved in the admin.
	 *
	 * This method allows concrete gateways to add special token data.
	 * See Authorize.net CIM for an example.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 * @param string $token_id the token ID
	 * @param array $data the token data
	 * @return \SV_WC_Payment_Gateway_Payment_Token the payment token object
	 */
	protected function build_token( $user_id, $token_id, $data ) {

		return $this->get_gateway()->get_payment_tokens_handler()->build_token( $token_id, $data );
	}


	/**
	 * Update the user's token data.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 * @param array the token objects
	 */
	protected function update_tokens( $user_id, $tokens ) {

		$this->get_gateway()->get_payment_tokens_handler()->update_tokens( $user_id, $tokens, $this->get_gateway()->get_environment() );
	}


	/**
	 * Remove a specific token.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 * @param string $token_id the token ID
	 * @return bool whether the token was successfully removed
	 */
	protected function remove_token( $user_id, $token_id ) {

		return $this->get_gateway()->get_payment_tokens_handler()->remove_token( $user_id, $token_id, $this->get_gateway()->get_environment() );
	}


	/**
	 * Correctly format a credit card expiration date for storage.
	 *
	 * @since 4.3.0-dev
	 * @param array $data
	 * @return array
	 */
	protected function prepare_expiry_date( $data ) {

		// TODO: more complete sanitization here

		if ( ! $data['expiry'] ) {
			return $data;
		}

		$expiry = explode( '/', $data['expiry'] );

		$data['exp_month'] = $expiry[0];
		$data['exp_year']  = $expiry[1];

		unset( $data['expiry'] );

		return $data;
	}


	/**
	 * Get the stored tokens for a user.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 * @return array the tokens in db format
	 */
	protected function get_tokens( $user_id ) {

		$tokens = get_user_meta( $user_id, $this->get_gateway()->get_payment_tokens_handler()->get_user_meta_name( $this->get_gateway()->get_environment() ), true );

		if ( ! $tokens ) {
			$tokens = array();
		}

		// Format the expiration date for display
		foreach( $tokens as $token_id => $token ) {

			$tokens[ $token_id ]['id'] = $token_id;

			if ( 'credit_card' === $token['type'] && isset( $token['exp_month'] ) && isset( $token['exp_year'] ) ) {
				$tokens[ $token_id ]['expiry'] = $token['exp_month'] . '/' . $token['exp_year'];
			}
		}

		return $tokens;
	}


	/**
	 * Get the admin token editor fields and column names.
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	protected function get_fields( $type = '' ) {

		if ( ! $type ) {
			$type = $this->get_gateway()->get_payment_type();
		}

		switch ( $type ) {

			case 'credit-card' :

				// Define the credit card fields
				$fields = array(
					'id' => array(
						'label'       => __( 'Token ID', 'woocommerce-plugin-framework' ),
						'is_editable' => ! $this->get_gateway()->get_api()->supports_get_tokenized_payment_methods(),
					),
					'card_type'   => array(
						'label'   => __( 'Card Type', 'woocommerce-plugin-framework' ),
						'type'    => 'select',
						'options' => $this->get_card_type_options(),
					),
					'last_four' => array(
						'label' => __( 'Last Four', 'woocommerce-plugin-framework' ),
					),
					'expiry' => array(
						'label' => __( 'Expiration', 'woocommerce-plugin-framework' ),
					),
				);

			break;

			case 'echeck' :

				// Define the echeck fields
				$fields = array(
					'id' => array(
						'label'       => __( 'Token ID', 'woocommerce-plugin-framework' ),
						'is_editable' => ! $this->get_gateway()->get_api()->supports_get_tokenized_payment_methods(),
					),
					'account_type'   => array(
						'label'   => __( 'Account Type', 'woocommerce-plugin-framework' ),
						'type'    => 'select',
						'options' => array(
							'checking' => __( 'Checking', 'woocommerce-plugin-framework' ),
							'savings'  => __( 'Savings', 'woocommerce-plugin-framework' ),
						),
					),
					'last_four' => array(
						'label' => __( 'Last Four', 'woocommerce-plugin-framework' ),
					),
				);

			break;

			default :
				$fields = array();
		}

		/**
		 * Filter the admin token editor fields.
		 *
		 * @since 4.3.0-dev
		 * @param array $fields
		 * @param \SV_WC_Payment_Gateway $gateway the payment gateway instance
		 */
		$fields = apply_filters( 'sv_wc_payment_gateway_admin_token_editor_fields', $fields, $this->get_gateway() );

		return $fields;
	}


	/**
	 * Get the credit card type field options.
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	protected function get_card_type_options() {

		$card_types = $this->get_gateway()->get_card_types();

		foreach ( $card_types as $card_type ) {
			$options[ strtolower( $card_type ) ] = SV_WC_Payment_Gateway_Helper::payment_type_to_name( $card_type );
		}

		return $options;
	}


	/**
	 * Get the HTML name for the token fields.
	 *
	 * @since 4.3.0-dev
	 * @return string
	 */
	protected function get_input_name() {

		return 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_tokens';
	}


	/**
	 * Get the available token actions.
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	protected function get_token_actions() {

		$actions = array(
			'remove' => __( 'Remove', 'woocommerce-plugin-framework' ),
		);

		/**
		 * Filter the token actions.
		 *
		 * @since 4.3.0-dev
		 * @param array $actions the token actions
		 */
		return apply_filters( 'sv_wc_payment_gateway_' . $this->get_gateway()->get_id() . 'token_editor_token_actions', $actions );
	}


	/**
	 * Get the gateway object.
	 *
	 * @since 4.3.0-dev
	 * @return \SV_WC_Payment_Gateway_Direct the gateway object
	 */
	protected function get_gateway() {
		return $this->gateway;
	}
}
