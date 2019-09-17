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

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_0\\REST_API\\Log_Controller' ) ) :

/**
 * The plugin REST API Debug endpoint.
 *
 * @since 5.5.0-dev
 */
abstract class Log_Controller extends \WC_REST_Controller {


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

		// endpoint: 'wc/v<n>/<plugin_id>/log/'
		register_rest_route( $this->namespace, "/{$this->rest_base}/log", [
			// GET the plugin logs
			[
				'methods'             => \WP_REST_Server::READABLE,
				/* @see \WC_REST_Controller::get_items() */
				'callback'            => [ $this, 'get_items' ],
				/* @see \WC_REST_Controller::get_items_permissions_check() */
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			]
		], true );
	}


	/**
	 * Determines if a given request has access to read the plugin logs.
	 *
	 * @since 5.5.0-dev
	 *
	 * @param \WP_REST_Request $request request object
	 * @return true|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new \WP_Error( 'wc_' . $this->get_plugin()->get_id() . '_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce-plugin-framework' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}


	/**
	 * Gets the plugin logs.
	 *
	 * @since 5.5.0-dev.1
	 *
	 * @param \WP_REST_Request $request API request
	 * @return \WP_Error|\WP_REST_Response API response
	 */
	public function get_items( $request ) {

		$params   = $request->get_params();
		$log_src  = isset( $params['source'] ) ? $params['source'] : null;
		$log_date = null;

		if ( isset( $params['date'] ) && preg_match( '/\d{4}-\d{2}-\d{2}/', $params['date'] ) ) {
			$log_date = $params['date'];
		}

		try {

			$response  = [];
			$plugin    = $this->get_plugin();
			$plugin_id = $plugin->get_id();

			if ( defined( 'WC_LOG_HANDLER' ) && 'WC_Log_Handler_DB' === WC_LOG_HANDLER ) {

				// TODO add support for log handler database logs {FN 2019-09-17}

			} else {

				$file_path = \WC_Log_Handler_File::get_log_file_path( $plugin->get_id() );

				if ( ! $file_path ) {
					throw new \WC_REST_Exception( "woocommerce_rest_{$plugin_id}_log_file_not_found", __( 'The resource does not exist.', 'woocommerce-plugin-framework' ), 404 );
				}

				$log_files = preg_grep( '~^' . $plugin->get_id() . '-.*\.php$~', scandir( $file_path ) );

				foreach ( $log_files as $log_file ) {

					if ( ( $log_src && $log_src !== $plugin_id ) || ( is_string( $log_date ) && false === strpos( $log_file, $log_date ) ) ) {
						continue;
					}

					$response[ $plugin_id ][] = [
						'type'       => 'file',
						'origin'     => basename( $log_file ),
						'contents'   => file_get_contents( $log_file ) ?: '',
						'updated_at' => date( 'Y-m-d\TH:i:s\Z', filemtime( $file_path ) ),
					];
				}

				if ( $plugin instanceof SV_WC_Payment_Gateway_Plugin ) {

					foreach ( $plugin->get_gateways() as $gateway ) {

						$gateway_id = $gateway->get_id();

						if ( $log_src && $log_src !== $gateway_id ) {
							continue;
						}

						$file_path = \WC_Log_Handler_File::get_log_file_path( $gateway_id );

						if ( ! $file_path ) {
							throw new \WC_REST_Exception( "woocommerce_rest_{$plugin_id}_{$gateway_id}_log_file_not_found", __( 'The resource does not exist.', 'woocommerce-plugin-framework' ), 404 );
						}

						$log_files = preg_grep( '~^' . $gateway_id . '-.*\.php$~', scandir( $file_path ) );

						foreach ( $log_files as $log_file ) {

							if ( is_string( $log_date ) && false === strpos( $log_file, $log_date ) ) {
								continue;
							}

							$response[ $gateway_id ][] = [
								'type'       => 'file',
								'origin'     => basename( $log_file ),
								'contents'   => file_get_contents( $log_file ) ?: '',
								'updated_at' => date( 'Y-m-d\TH:i:s\Z', filemtime( $file_path ) ),
							];
						}
					}
				}
			}

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