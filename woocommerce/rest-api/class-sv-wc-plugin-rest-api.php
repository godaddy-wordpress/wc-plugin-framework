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

namespace SkyVerge\WooCommerce\PluginFramework\v5_5_0;

use SkyVerge\WooCommerce\PluginFramework\v5_5_0\REST_API\Debug_Controller;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0\REST_API\Log_Controller;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0\REST_API\v3\Debug;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0\REST_API\v3\Controller;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_4_3\\REST_API' ) ) :


/**
 * The plugin REST API handler class.
 *
 * This is responsible for hooking in to the WC REST API to add data for existing
 * routes and/or register new routes.
 *
 * @since 5.2.0
 */
class REST_API {


	/** @var SV_WC_Plugin plugin instance */
	private $plugin;

	/** @var array associative array of supported framework endpoints for given API versions */
	private $supports = [];

	/** @var Debug_Controller[] debug endpoint handlers according to supported API version in use */
	private $debug_controller = [];

	/** @var Log_Controller[] log endpoint handlers according to supported API version in use */
	private $log_controller = [];


	/**
	 * Constructs the class.
	 *
	 * @since 5.2.0
	 *
	 * @param SV_WC_Plugin $plugin plugin instance
	 */
	public function __construct( SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->add_hooks();
	}


	/**
	 * Adds the action and filter hooks.
	 *
	 * @since 5.2.0
	 */
	protected function add_hooks() {

		// add plugin data to the system status
		add_filter( 'woocommerce_rest_prepare_system_status', [ $this, 'add_system_status_data' ], 10, 3 );

		// registers new WooCommerce REST API routes
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}


	/**
	 * Adds plugin data to the system status.
	 *
	 * @internal
	 *
	 * @since 5.2.0
	 *
	 * @param \WP_REST_Response $response REST API response object
	 * @param array $system_status system status data
	 * @param \WP_REST_Request $request REST API request object
	 * @return \WP_REST_Response
	 */
	public function add_system_status_data( $response, $system_status, $request ) {

		$data = [
			'is_payment_gateway' => $this->get_plugin() instanceof SV_WC_Payment_Gateway_Plugin,
			'lifecycle_events'   => $this->get_plugin()->get_lifecycle_handler()->get_event_history(),
		];

		$data = array_merge( $data, $this->get_system_status_data() );

		/**
		 * Filters the data added to the WooCommerce REST API System Status response.
		 *
		 * @since 5.2.0
		 *
		 * @param array $data system status response data
		 * @param \WP_REST_Response $response REST API response object
		 * @param \WP_REST_Request $request REST API request object
		 */
		$data = (array) apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_rest_api_system_status_data', $data, $response, $request );

		$response->data[ 'wc_' . $this->get_plugin()->get_id() ] = $data;

		return $response;
	}


	/**
	 * Gets the data to add to the WooCommerce REST API System Status response.
	 *
	 * Plugins can override this to add their own data.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	protected function get_system_status_data() {

		return [];
	}


	/**
	 * Registers new WC REST API routes.
	 *
	 * @since 5.2.0
	 */
	public function register_routes() {

		foreach ( $this->supports as $endpoint => $versions ) {

			if ( ! is_array( $versions ) ) {
				continue;
			}

			foreach ( $versions as $version ) {

				if ( 'debug' === $endpoint && ( $debug = $this->get_debug_controller_instance( $version ) ) ) {

					$debug->register_routes();

				} elseif ( 'log' === $endpoint && ( $log = $this->get_log_controller_instance( $version ) ) ) {

					$log->register_routes();
				}
			}
		}
	}


	/**
	 * Gets the debug controller endpoint handler instance.
	 *
	 * @since 5.5.0-dev
	 *
	 * @param string $api_version API version for the controller to be returned
	 * @return null|Debug_Controller
	 */
	public function get_debug_controller_instance( $api_version = 'v3' ) {

		$supported_versions = [ 'v3' ];

		if ( in_array( $api_version, $supported_versions, true ) ) {
			return null;
		}

		if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_0\\REST_API\\Debug_Controller' ) ) {
			require_once( $this->get_plugin()->get_framework_path() . '/rest-api/Debug_Controller.php' );
		}

		if ( ! isset( $this->debug_controller[ $api_version ] ) ) {

			require_once( $this->get_plugin()->get_framework_path() . '/rest-api/' . $api_version . '/Debug.php' );

			$controller = $api_version . '\\Debug';

			$this->debug_controller[ $api_version ] = new $controller( $this->get_plugin() );
		}

		return $this->debug_controller[ $api_version ];
	}


	/**
	 * Gets the debug controller endpoint handler instance.
	 *
	 * @since 5.5.0-dev
	 *
	 * @param string $api_version API version for the controller to be returned
	 * @return null|Log_Controller
	 */
	public function get_log_controller_instance( $api_version = 'v3' ) {

		$supported_versions = [ 'v3' ];

		if ( in_array( $api_version, $supported_versions, true ) ) {
			return null;
		}

		if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_0\\REST_API\\Log_Controller' ) ) {
			require_once( $this->get_plugin()->get_framework_path() . '/rest-api/Log_Controller.php' );
		}

		if ( ! isset( $this->log_controller[ $api_version ] ) ) {

			require_once( $this->get_plugin()->get_framework_path() . '/rest-api/' . $api_version . '/Log.php' );

			$controller = $api_version . '\\Log';

			$this->log_controller[ $api_version ] = new $controller( $this->get_plugin() );
		}

		return $this->log_controller[ $api_version ];
	}


	/**
	 * Gets the main plugin instance.
	 *
	 * @since 5.2.0
	 *
	 * @return SV_WC_Plugin|SV_WC_Payment_Gateway_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


}

endif;
