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
 * @copyright Copyright (c) 2013-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_12_1\REST_API\Controllers;

use SkyVerge\WooCommerce\PluginFramework\v5_12_1\Settings_API\Abstract_Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\Settings_API\Setting;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_12_1\\REST_API\\Controllers\\Settings' ) ) :


/**
 * The settings controller class.
 *
 * @since 5.7.0
 */
#[\AllowDynamicProperties]
class Settings extends \WP_REST_Controller {


	/** @var Abstract_Settings settings handler */
	protected $settings;


	/**
	 * Settings constructor.
	 *
	 * @since 5.7.0
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
	 * @since 5.7.0
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
	 * @since 5.7.0
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


	/**
	 * Gets all registered settings.
	 *
	 * @since 5.7.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {

		$items = [];

		foreach ( $this->settings->get_settings() as $setting ) {
			$items[] = $this->prepare_setting_item( $setting, $request );
		}

		return rest_ensure_response( $items );
	}


	/**
	 * Gets a single setting.
	 *
	 * @since 5.7.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {

		$setting_id = $request->get_param( 'id' );

		if ( $setting = $this->settings->get_setting( $setting_id ) ) {

			return rest_ensure_response( $this->prepare_setting_item( $setting, $request ) );

		} else {

			return new \WP_Error(
				'wc_rest_setting_not_found',
				sprintf(
					/* translators: Placeholder: %s - setting ID */
					__( 'Setting %s does not exist', 'woocommerce-plugin-framework' ),
					$setting_id
				),
				[ 'status' => 404 ]
			);
		}
	}


	/** Update methods ************************************************************************************************/


	/**
	 * Checks whether the user has permissions to update a setting.
	 *
	 * @since 5.7.0
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


	/**
	 * Updates a single setting.
	 *
	 * @since 5.7.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {

		try {

			$setting_id = $request->get_param( 'id' );
			$value      = $request->get_param( 'value' );

			// throws an exception if the setting doesn't exist or the value is not valid
			$this->settings->update_value( $setting_id, $value );

			return rest_ensure_response( $this->prepare_setting_item( $this->settings->get_setting( $setting_id ), $request ) );

		} catch ( \Exception $e ) {

			return new \WP_Error(
				'wc_rest_setting_could_not_update',
				sprintf(
					/* translators: Placeholder: %s - Error message */
					__( 'Could not update setting: %s', 'woocommerce-plugin-framework' ),
					$e->getMessage()
				),
				[ 'status' => $e->getCode() ?: 400 ]
			);
		}
	}


	/** Utility methods ***********************************************************************************************/


	/**
	 * Prepares the item for the REST response.
	 *
	 * @since 5.7.0
	 *
	 * @param Setting $setting a setting object
	 * @param \WP_REST_Request $request request object
	 * @return array
	 */
	public function prepare_setting_item( $setting, $request ) {

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

		return $item;
	}


	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @since 5.7.0
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => "{$this->settings->get_id()}_setting",
			'type'       => 'object',
			'properties' => [
				'id'          => [
					'description' => __( 'Unique identifier of the setting.', 'woocommerce-plugin-framework' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'type'        => [
					'description' => __( 'The type of the setting.', 'woocommerce-plugin-framework' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'enum'        => $this->settings->get_setting_types(),
					'readonly'    => true,
				],
				'name'        => [
					'description' => __( 'The name of the setting.', 'woocommerce-plugin-framework' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'description' => [
					'description' => __( 'The description of the setting. It may or may not be used for display.', 'woocommerce-plugin-framework' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'is_multi'    => [
					'description' => __( 'Whether the setting stores an array of values or a single value.', 'woocommerce-plugin-framework' ),
					'type'        => 'boolean',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'options'     => [
					'description' => __( 'A list of valid options, used for validation before storing the value.', 'woocommerce-plugin-framework' ),
					'type'        => 'array',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'default'     => [
					'description' => __( 'Optional default value for the setting.', 'woocommerce-plugin-framework' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'value'       => [
					'description' => __( 'The value of the setting.', 'woocommerce-plugin-framework' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
				'control'     => [
					'description' => __( 'Optional object that defines how the user will interact with and update the setting.', 'woocommerce-memberships' ),
					'type'        => 'object',
					'properties'  => [
						'type'        => [
							'description' => __( 'The type of the control.', 'woocommerce-plugin-framework' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'enum'        => $this->settings->get_control_types(),
							'readonly'    => true,
						],
						'name'        => [
							'description' => __( "The name of the control. Inherits the setting's name.", 'woocommerce-plugin-framework' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'description' => [
							'description' => __( "The description of the control. Inherits the setting's description.", 'woocommerce-plugin-framework' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'options'     => [
							'description' => __( 'A list of key/value pairs defining the display value of each setting option. The keys should match the options defined in the base setting for validation.', 'woocommerce-plugin-framework' ),
							'type'        => 'array',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}


}

endif;
