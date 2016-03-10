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
 * Handle the admin user profile settings.
 *
 * @since 4.3.0-dev
 */
class SV_WC_Payment_Gateway_Admin_User_Handler {

	/** @var \SV_WC_Payment_Gateway_Plugin the plugin instance **/
	protected $plugin;

	/** @var array the token editor for each gateway **/
	protected $token_editors = array();

	/**
	 * Construct the user handler.
	 *
	 * @since 4.3.0-dev
	 * @param \SV_WC_Payment_Gateway_Plugin The plugin instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;

		if ( current_user_can( 'manage_woocommerce' ) ) {

			// Set up a token editor for each gateway
			add_action( 'admin_init', array( $this, 'init_token_editors' ) );

			// Add the settings section
			add_action( 'show_user_profile', array( $this, 'add_profile_section' ) );
			add_action( 'edit_user_profile', array( $this, 'add_profile_section' ) );

			// Save the settings
			add_action( 'personal_options_update',  array( $this, 'save_profile_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_profile_fields' ) );
		}

		// Display the token editor markup inside the  profile section
		add_action( 'sv_wc_payment_gateway_' . $this->get_plugin()->get_id() . '_user_profile', array( $this, 'display_token_editors' ) );

		// Display the customer ID field markup inside the  profile section
		add_action( 'sv_wc_payment_gateway_' . $this->get_plugin()->get_id() . '_user_profile', array( $this, 'display_customer_id_fields' ) );
	}


	/**
	 * Set up a token editor for each gateway.
	 *
	 * @since 4.3.0-dev
	 */
	public function init_token_editors() {

		$token_editors = array();

		// Check each gateway for tokenization support
		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! $gateway->is_enabled() || ! $gateway->tokenization_enabled() || ! $gateway->supports_token_editor() ) {
				continue;
			}

			$this->token_editors[] = $gateway->get_payment_tokens_handler()->get_token_editor();
		}
	}


	/**
	 * Display the customer profile settings markup.
	 *
	 * @since 4.3.0-dev
	 * @param \WP_User $user The user object
	 */
	public function add_profile_section( $user ) {

		if ( ! $this->is_supported() ) {
			return;
		}

		$user_id             = $user->ID;
		$plugin_id           = $this->get_plugin()->get_id();
		$section_title       = $this->get_title();
		$section_description = $this->get_description();

		include( $this->get_plugin()->get_payment_gateway_framework_path() . '/admin/views/html-user-profile-section.php' );
	}


	/**
	 * Display the token editor markup.
	 *
	 * @since 4.3.0-dev
	 * @param \WP_User $user The user object
	 */
	public function display_token_editors( $user ) {

		foreach ( $this->get_token_editors() as $gateway_id => $editor ) {
			$editor->display( $user->ID );
		}
	}


	/**
	 * Display the customer ID field(s).
	 *
	 * @since 4.3.0-dev
	 * @param \WP_User $user the user object
	 */
	public function display_customer_id_fields( $user ) {

		if ( ! $this->get_plugin()->supports( 'customer_id' ) ) {
			return;
		}

		foreach( $this->get_customer_id_fields( $user->ID ) as $field ) {

			$label = $field['label'];
			$name  = $field['name'];
			$value = $field['value'];

			include( $this->get_plugin()->get_payment_gateway_framework_path() . '/admin/views/html-user-profile-field-customer-id.php' );
		}
	}


	/**
	 * Save the user profile section fields.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 */
	public function save_profile_fields( $user_id ) {

		if ( ! $this->is_supported() ) {
			return;
		}

		// Save the token data from each token editor
		$this->save_tokens( $user_id );

		// Save the customer IDs
		if ( $this->get_plugin()->supports( 'customer_id' ) ) {
			$this->save_customer_ids( $user_id );
		}
	}


	/**
	 * Save the token data from each token editor.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 */
	protected function save_tokens( $user_id ) {

		foreach ( $this->get_token_editors() as $gateway_id => $editor ) {
			$editor->save( $user_id );
		}
	}


	/**
	 * Save the customer IDs.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 */
	protected function save_customer_ids( $user_id ) {

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( isset( $_POST[ $gateway->get_customer_id_user_meta_name() ] ) ) {
				$gateway->update_customer_id( $user_id, trim( $_POST[ $gateway->get_customer_id_user_meta_name() ] ) );
			}
		}
	}


	/** Getter methods ******************************************************/


	/**
	 * Get the token editor section title.
	 *
	 * @since 4.3.0-dev
	 * @return string
	 */
	protected function get_title() {

		$plugin_title = $environment_name = '';

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			// We only need to get one of each
			if ( $plugin_title && $environment_name ) {
				break;
			}

			$plugin_title     = $gateway->get_method_title();
			$environment_name = $gateway->get_environment_name();
		}

		$title = sprintf( __( '%s Payment Tokens', 'woocommerce-plugin-framework' ), $plugin_title );

		// Append the environment name if the same for each payment method
		$title .= ( ! $this->has_multiple_environments() ) ? ' ' . sprintf( __( '(%s)', 'woocommerce-plugin-framework' ), $environment_name ) : '';

		/**
		 * Filter the admin token editor title.
		 *
		 * @since 4.3.0-dev
		 * @param string $title The section title
		 * @param \SV_WC_Payment_Gateway_Plugin $plugin The gateway plugin instance
		 */
		return apply_filters( 'wc_payment_gateway_admin_user_profile_title', $title, $this->get_plugin() );
	}


	/**
	 * Get the token editor section description.
	 *
	 * @since 4.3.0-dev
	 * @return string
	 */
	protected function get_description() {

		/**
		 * Filter the admin token editor description.
		 *
		 * @since 4.3.0-dev
		 * @param string $description The section description
		 * @param \SV_WC_Payment_Gateway_Plugin $plugin The gateway plugin instance
		 */
		return apply_filters( 'wc_payment_gateway_admin_user_profile_description', '', $this->get_plugin() );
	}


	/**
	 * Get the token editor objects.
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	protected function get_token_editors() {
		return $this->token_editors;
	}


	/**
	 * Get the customer ID fields for the plugin's gateways.
	 *
	 * In most cases, this will be a single field unless the plugin has multiple gateways and they
	 * are set to different environments.
	 *
	 * @since 4.3.0-dev
	 * @param int $user_id the user ID
	 * @return array {
	 *     The fields data
	 *
	 *     @type string $label the field label
	 *     @type string $name  the input name
	 *     @type string $value the input value
	 * }
	 */
	protected function get_customer_id_fields( $user_id ) {

		$unique_meta_key = '';

		$fields = array();

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! $gateway->tokenization_enabled() ) {
				continue;
			}

			$meta_key = $gateway->get_customer_id_user_meta_name();

			// If a field with this meta key has already been set, skip this gateway
			if ( $meta_key === $unique_meta_key ) {
				continue;
			}

			$label = __( 'Customer ID', 'woocommerce-plugin-framework' );

			// If the plugin has multiple gateways configured for multiple environments, append the environment name to keep things straight
			$label .= ( $this->has_multiple_environments() ) ? ' ' . sprintf( __( '(%s)', 'woocommerce-plugin-framework' ), $gateway->get_environment_name() ) : '';

			$fields[] = array(
				'label' => $label,
				'name'  => $meta_key,
				'value' => $gateway->get_customer_id( $user_id, array(
					'autocreate' => false,
				) ),
			);

			$unique_meta_key = $meta_key;
		}

		return $fields;
	}


	/**
	 * Get the unique environments between the plugin's gateways.
	 *
	 * @since 4.3.0-dev
	 * @return array the environments in the format `$environment_id => $environment_name`
	 */
	protected function get_unique_environments() {

		$environments = array();

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! $gateway->tokenization_enabled() ) {
				continue;
			}

			$environments[ $gateway->get_environment() ] = $gateway->get_environment_name();
		}

		$environments = array_unique( $environments );

		return $environments;
	}


	/** Conditional methods ******************************************************/


	/**
	 * Determine if the user profile section is supported by at least one gateway.
	 *
	 * @since 4.3.0-dev
	 * @return bool
	 */
	protected function is_supported() {
		return $this->get_plugin()->tokenization_enabled();
	}


	/**
	 * Determine if the plugin has varying environments between its gateways.
	 *
	 * @since 4.3.0-dev
	 * @return bool
	 */
	protected function has_multiple_environments() {
		return 1 < count( $this->get_unique_environments() );
	}


	/**
	 * Get the plugin instance.
	 *
	 * @since 4.3.0-dev
	 * @return \SV_WC_Payment_Gateway_Plugin the plugin instance
	 */
	protected function get_plugin() {
		return $this->plugin;
	}
}
