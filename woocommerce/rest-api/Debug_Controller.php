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
 * @copyright Copyright (c) 2013-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_5_0\REST_API;

use SkyVerge\WooCommerce\PluginFramework\v5_5_0\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0\SV_WC_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0\SV_WC_Plugin_Exception;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_0\\REST_API\\Debug_Controller' ) ) :

/**
 * The plugin REST API Debug endpoint.
 *
 * @since 5.5.0-dev
 */
abstract class Debug_Controller extends \WC_REST_Controller {


	/** @var SV_WC_Plugin main instance */
	private $plugin;

	/** @var string endpoint namespace */
	protected $namespace;

	/** @var string the route base */
	protected $rest_base;


	/**
	 * Debug controller constructor.
	 *
	 * @since 5.5.0-dev
	 *
	 * @param SV_WC_Plugin $plugin main instance
	 */
	public function __construct( SV_WC_Plugin $plugin ) {

		$this->plugin    = $plugin;
		$this->namespace = 'wc/v1';
		$this->rest_base = $plugin->get_id();
	}


	/**
	 * Registers the routes for the plugin debug endpoint.
	 *
	 * @since 5.5.0-dev
	 */
	public function register_routes() {

		// endpoint: 'wc/v<n>/<plugin_id>/debug/'
		register_rest_route( $this->namespace, "/{$this->rest_base}/debug", [
			// GET the debug mode status
			[
				'methods'             => \WP_REST_Server::READABLE,
				/* @see \WC_REST_Controller::get_item() */
				'callback'            => [ $this, 'get_item' ],
				/* @see \WC_REST_Controller::get_item_permissions_check() */
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
			],
			// UPDATE (toggle) the debug mode
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				/** @see \WC_REST_Controller::update_item() */
				'callback'            => [ $this, 'update_item' ],
				/** @see \WC_REST_Controller::update_item_permissions_check() */
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			),
		], true );
	}


	/**
	 * Determines if a given request has access to read the plugin debug mode status.
	 *
	 * @since 5.5.0-dev
	 *
	 * @param \WP_REST_Request $request request object
	 * @return true|\WP_Error
	 */
	public function get_item_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new \WP_Error( 'wc_' . $this->get_plugin()->get_id() . '_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wc-plugin-framework' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}


	/**
	 * Gets the plugin debug mode status.
	 *
	 * @since 5.5.0-dev.1
	 *
	 * @param \WP_REST_Request $request API request
	 * @return \WP_Error|\WP_REST_Response API response
	 */
	public function get_item( $request ) {

		$plugin     = $this->get_plugin();
		$debug_mode = [
			'plugin' => $plugin->get_debug_mode(),
		];

		if ( $plugin instanceof SV_WC_Payment_Gateway_Plugin ) {
			foreach ( $plugin->get_gateways() as $gateway ) {
				$debug_mode[ $gateway->get_id() ] = $gateway->get_debug_mode();
			}
		}

		return rest_ensure_response( [
			'debug_mode' => $debug_mode,
			'debug_log'  => defined( 'WC_LOG_HANDLER' ) ? WC_LOG_HANDLER: 'file',
		] );
	}


	/**
	 * Determines if a given request has rights to enable or disable the plugin debug mode.
	 *
	 * @since 5.5.0-dev
	 *
	 * @param \WP_REST_Request $request request object
	 * @return true|\WP_Error
	 */
	public function update_item_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new \WP_Error( 'wc_' . $this->get_plugin()->get_id() . '_rest_cannot_update', __( 'Sorry, you cannot update resources.', 'wc-plugin-framework' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}


	/**
	 * Gets the plugin debug mode status.
	 *
	 * @since 5.5.0-dev.1
	 *
	 * @param \WP_REST_Request $request API request
	 * @return \WP_Error|\WP_REST_Response API response
	 */
	public function update_item( $request ) {

		try {

			$plugin    = $this->get_plugin();
			$plugin_id = $plugin->get_id();

			if ( empty( $request['debug_mode'] ) || ! is_array( $request['debug_mode'] ) ) {
				throw new \WC_REST_Exception( "woocommerce_rest_invalid_{$plugin_id}_debug_mode", __( 'Invalid debug mode data.', 'woocommerce-plugin-framework' ), 404 );
			}

			$mode = $request['debug_mode'];

			try {

				if ( isset( $mode['plugin'] ) ) {
					$plugin->set_debug_mode( $mode['plugin'] );
				}

				if ( $plugin instanceof SV_WC_Payment_Gateway_Plugin ) {

					foreach ( $plugin->get_gateways() as $gateway ) {

						if ( array_key_exists( $gateway->get_id(), $mode ) ) {

							$gateway->set_debug_mode( $mode[ $gateway->get_id() ] );
						}
					}
				}

			} catch ( \Exception $e ) {

				throw new \WC_REST_Exception( "woocommerce_rest_{$plugin_id}_set_debug_mode_error", sprintf( __( 'Error while setting debug mode: %s', 'woocommerce-plugin-framework' ), $e->getMessage() ), 404 );
			}

			$response = $this->get_item( $request );

		} catch ( \WC_REST_Exception $e ) {

			$response = new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		return rest_ensure_response( $response );
	}


	/**
	 * Gets the main plugin instance.
	 *
	 * @since 5.5.0-dev
	 *
	 * @return SV_WC_Plugin|SV_WC_Payment_Gateway_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


}

endif;