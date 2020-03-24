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
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_6_1\REST_API\Controllers;

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Abstract_Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API\Setting;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_6_1\\REST_API\\Controllers\\Settings' ) ) :

/**
 * The settings controller class.
 *
 * @since x.y.z
 */
class Settings extends \WP_REST_Controller {


	/** @var Abstract_Settings settings handler */
	protected $settings;


	/**
	 * Settings constructor.
	 *
	 * @since x.y.z
	 *
	 * @param Abstract_Settings $settings settings handler
	 */
	public function __construct( Abstract_Settings $settings ) {

		$this->settings  = $settings;
		$this->namespace = 'wc/v3';
		$this->rest_base = "{$settings->get_id()}/settings";
	}


	/**
	 * Registers the API routes.
	 *
	 * @since x.y.z
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace, "/{$this->rest_base}", [
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace, "/{$this->rest_base}/(?P<id>[\w-]+)", [
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}


	/** Read methods **************************************************************************************************/


	/**
	 * Checks whether the user has permissions to get settings.
	 *
	 * @since x.y.z
	 *
	 * @param \WP_REST_Request $request request object
	 * @return bool|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new \WP_Error( 'wc_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce-plugin-framework' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}


	// TODO: get_items()


	/**
	 * Gets a single setting.
	 *
	 * @since x.y.z
	 *
	 * @param \WP_REST_Request $request request object
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {

	}


	/** Update methods ************************************************************************************************/


	/**
	 * Checks whether the user has permissions to update a setting.
	 *
	 * @since x.y.z
	 *
	 * @param \WP_REST_Request $request request object
	 * @return bool|\WP_Error
	 */
	public function update_item_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new \WP_Error( 'wc_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'woocommerce-plugin-framework' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}


	// TODO: update_item()


	/** Utility methods ***********************************************************************************************/


	/**
	 * @since x.y.z
	 *
	 * @param Setting $setting a setting object
	 * @param \WP_REST_Request $request request object
	 * @return WP_Error|WP_REST_Response
	 */
	public function prepare_item_for_response( $setting, $request ) {

		if ( $setting instanceof Setting ) {

			$item = [
				'id'          => $setting->get_id(),
				'type'        => $setting->get_type(),
				'name'        => $setting->get_name(),
				'description' => $setting->get_description(),
				'is_multi'    => $setting->is_is_multi(),
				'options'     => $setting->get_options(),
				'default'     => $setting->get_default(),
				'value'       => $setting->get_value(),
				'control'     => null,
			];

			if ( $control = $setting->get_control() ) {
				$item['control'] = [
					'type'        => $control->get_type(),
					'name'        => $control->get_name(),
					'description' => $control->get_description(),
					'options'     => $control->geT_options(),
				];
			}

		} else {

			$item = [];
		}

		return rest_ensure_response( $item );
	}


	// TODO: get_item_schema()


}

endif;
