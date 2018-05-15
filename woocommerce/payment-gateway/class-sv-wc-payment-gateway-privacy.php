<?php
/**
 * WooCommerce Plugin Framework
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
 * @package   SkyVerge/WooCommerce/Plugin/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_1_3;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_1_3\\SV_WC_Payment_Gateway_Privacy' ) ) :

/**
 * The payment gateway privacy handler class.
 *
 * @since 5.1.4-dev
 */
class SV_WC_Payment_Gateway_Privacy extends \WC_Abstract_Privacy {


	/** @var SV_WC_Payment_Gateway_Plugin payment gateway plugin instance */
	private $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 5.1.4-dev
	 *
	 * @param SV_WC_Payment_Gateway_Plugin payment gateway plugin instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;

		parent::__construct( $plugin->get_plugin_name() );

		// add the token exporters & erasers
		$this->add_exporter( "wc-{$plugin->get_id_dasherized()}-customer-tokens", __( "{$plugin->get_plugin_name()} Payment Tokens", 'woocommerce-plugin-framework' ), array( $this, 'customer_tokens_exporter' ) );
		$this->add_eraser(   "wc-{$plugin->get_id_dasherized()}-customer-tokens", __( "{$plugin->get_plugin_name()} Payment Tokens", 'woocommerce-plugin-framework' ), array( $this, 'customer_tokens_eraser' ) );

		// add the action & filter hooks
		$this->add_hooks();
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.1.4-dev
	 */
	protected function add_hooks() {

		// add the gateway customer ID data to the customer data export
		add_filter( 'woocommerce_privacy_export_customer_personal_data', array( $this, 'add_customer_id_data' ), 10, 2 );
	}


	/**
	 * Adds the gateway customer ID data to the customer data export.
	 *
	 * @internal
	 *
	 * @since 5.1.4-dev
	 *
	 * @param array $data customer personal data to export
	 * @param \WC_Customer $customer customer object
	 * @return array
	 */
	public function add_customer_id_data( $data, $customer ) {

		if ( $customer instanceof \WC_Customer ) {

			foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

				// skip gateways that don't support customer ID
				if ( ! $gateway->supports_customer_id() ) {
					continue;
				}

				if ( $customer_id = $gateway->get_customer_id( $customer->get_id(), array( 'autocreate' => false ) ) ) {

					$data[] = array(
						'name'  => sprintf( __( '%s Customer ID', 'woocommerce-plugin-framework' ), $gateway->get_method_title() ),
						'value' => $customer_id,
					);
				}
			}
		}

		return $data;
	}


	/**
	 * Handles the customer token exporter.
	 *
	 * @internal
	 *
	 * @param string $email_address email address for the user to export
	 * @param int $page page offset - unused as we don't page tokens
	 * @return array token export data
	 */
	public function customer_tokens_exporter( $email_address, $page ) {

		$data = array();
		$user = get_user_by( 'email', $email_address );

		if ( $user instanceof \WP_User ) {

			foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

				// skip gateways that don't support tokenization
				if ( ! $gateway->supports_tokenization() ) {
					continue;
				}

				foreach ( $gateway->get_payment_tokens_handler()->get_tokens( $user->ID ) as $token ) {

					$token_data = array();

					if ( $token->get_type_full() ) {

						$token_data[] = array(
							'name'  => __( 'Type', 'woocommerce-plugin-framework' ),
							'value' => $token->get_type_full(),
						);
					}

					if ( $token->get_last_four() ) {

						$token_data[] = array(
							'name'  => __( 'Last Four', 'woocommerce-plugin-framework' ),
							'value' => $token->get_last_four(),
						);
					}

					if ( $token->get_nickname() ) {

						$token_data[] = array(
							'name'  => __( 'Nickname', 'woocommerce-plugin-framework' ),
							'value' => $token->get_nickname(),
						);
					}

					if ( ! empty( $token_data ) ) {

						$data[] = array(
							'group_id'    => 'wc_' . $gateway->get_id() . '_tokens',
							'group_label' => sprintf( __( '%s Payment Tokens', 'woocommerce-plugin-framework' ), $gateway->get_method_title() ),
							'item_id'     => 'token-' . $token->get_id(),
							'data'        => $token_data,
						);
					}
				}
			}
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}


	/**
	 * Handles the customer token eraser.
	 *
	 * @internal
	 *
	 * @param string $email_address email address for the user to erase
	 * @param int $page page offset - unused as we don't page tokens
	 * @return array token eraser data
	 */
	public function customer_tokens_eraser( $email_address, $page ) {

		$removed  = false;
		$messages = array();

		$user = get_user_by( 'email', $email_address );

		if ( $user instanceof \WP_User ) {

			foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

				// skip gateways that don't support tokenization
				if ( ! $gateway->supports_tokenization() ) {
					continue;
				}

				foreach ( $gateway->get_payment_tokens_handler()->get_tokens( $user->ID ) as $token ) {

					$gateway->get_payment_tokens_handler()->remove_token( $user->ID, $token );

					$messages[] = sprintf( __( 'Removed payment token "%d"', 'woocommerce-plugin-framework' ), $token->get_id() );
					$removed    = true;
				}

				// completely remove the user meta in case there is an API failure
				delete_user_meta( $user->ID, $gateway->get_payment_tokens_handler()->get_user_meta_name() );

				$gateway->get_payment_tokens_handler()->clear_transient( $user->ID );
			}
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => $messages,
			'done'           => true,
		);
	}


	/**
	 * Gets the payment gateway plugin instance.
	 *
	 * @since 5.1.4-dev
	 *
	 * @return SV_WC_Payment_Gateway_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


}

endif;
