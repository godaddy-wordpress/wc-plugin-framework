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

use SkyVerge\WooCommerce\PluginFramework\v5_5_0\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0\SV_WC_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_0\\REST_API\\Debug_Controller' ) ) :


/**
 * The plugin REST API Debug endpoint.
 *
 * @since 5.5.0-dev
 */
abstract class Debug_Controller extends \WC_REST_Controller {


	/** @var SV_WC_Plugin main instance */
	protected $plugin;

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
				/* @see Debug_Controller::get_item() */
				'callback'            => [ $this, 'get_item' ],
				/* @see Debug_Controller::get_item_permissions_check() */
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
			],
			// UPDATE (toggle) the debug mode
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				/** @see Debug_Controller::update_item() */
				'callback'            => [ $this, 'update_item' ],
				/** @see Debug_Controller::update_item_permissions_check() */
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			],
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
		$plugin_id  = $plugin->get_id();
		$debug_mode = [
			'debug_mode_enabled' => $plugin->is_debug_mode( 'enabled' ),
			'debug_mode_status'  => $plugin->get_debug_mode(),
			'debug_modes'        => $plugin->get_debug_modes(),
			'gateways'           => null,
		];

		if ( $plugin instanceof SV_WC_Payment_Gateway_Plugin ) {

			$debug_mode['gateways'] = [];

			foreach ( $plugin->get_gateways() as $gateway ) {

				$gateway_id = $gateway->get_id();

				$debug_mode['gateways'][ $gateway_id ]['debug_mode_enabled'] = $gateway->is_debug_mode( 'enabled' );
				$debug_mode['gateways'][ $gateway_id ]['debug_mode_status']  = $gateway->get_debug_mode();
				$debug_mode['gateways'][ $gateway_id ]['debug_modes']        = $gateway->get_debug_modes();
			}
		}

		/**
		 * Filters the REST API response for the debug mode endpoint.
		 *
		 * @since 5.5.0-dev
		 *
		 * @param \WP_Error|array $response_data associative array of debug mode data or error object
		 * @param \WP_REST_Request $request request object
		 */
		return rest_ensure_response( apply_filters( "wc_{$plugin_id}_rest_api_debug_mode_data", [
			'debug_mode' => $debug_mode,
			'debug_log'  => defined( 'WC_LOG_HANDLER' ) ? WC_LOG_HANDLER : 'file',
		], $request ) );
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

			$plugin     = $this->get_plugin();
			$plugin_id  = $plugin->get_id();
			$params     = $request->get_params();

			if ( empty( $params['debug_mode'] ) ) {
				throw new \WC_REST_Exception( "woocommerce_rest_missing_{$plugin_id}_debug_mode", __( 'Missing debug mode parameter.', 'woocommerce-plugin-framework' ), 404 );
			}

			$debug_mode = (array) $params['debug_mode'];

			try {

				if ( isset( $debug_mode['plugin'] ) ) {
					$plugin->set_debug_mode( $debug_mode['plugin'] );
				}

				if ( $plugin instanceof SV_WC_Payment_Gateway_Plugin && isset( $debug_mode['gateways'] ) ) {

					foreach ( $plugin->get_gateways() as $gateway ) {

						if ( array_key_exists( $gateway->get_id(), $debug_mode['gateways'] ) ) {

							$gateway->set_debug_mode( $debug_mode['gateways'][ $gateway->get_id() ] );
						}
					}
				}

				/**
				 * Fires when setting the debug mode via REST API.
				 *
				 * @since 5.5.0-dev
				 *
				 * @param \WP_REST_Request $request request object
				 */
				do_action( "wc_{$plugin_id}_rest_api_set_debug_mode", $request );

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