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

namespace SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API;

use SkyVerge\WooCommerce\PluginFramework\v5_6_1 as Framework;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_6_1\\Settings_API\\Abstract_Settings' ) ) :

/**
 * The base settings handler.
 *
 * @since x.y.z
 */
abstract class Abstract_Settings {


	/** @var string settings ID */
	public $id = '';

	/** @var Setting[] registered settings */
	protected $settings = [];


	/**
	 * Constructs the class.
	 *
	 * @since x.y.z
	 *
	 * @param string $id the ID of plugin or payment gateway that owns these settings
	 */
	public function __construct( $id ) {

		$this->id = $id;

		$this->register_settings();
		$this->load_settings();
	}


	/**
	 * Registers the settings.
	 *
	 * Plugins or payment gateways should overwrite this method to register their settings.
	 *
	 * @since x.y.z
	 */
	abstract protected function register_settings();


	/**
	 * Loads the values for all registered settings.
	 *
	 * @since x.y.z
	 */
	abstract protected function load_settings();


	/**
	 * Unregisters a setting.
	 *
	 * @since x.y.z
	 *
	 * @param string $id setting ID to unregister
	 */
	public function unregister_setting( $id ) {

		unset( $this->settings[ $id ] );
	}


	/**
	 * Gets the settings ID.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->id;
	}


	/**
	 * Gets all registered settings.
	 *
	 * @param array $ids setting IDs to get
	 * @return Setting[]
	 */
	public function get_settings( array $ids = [] ) {

		$settings = $this->settings;

		if ( ! empty( $ids ) ) {

			foreach ( array_keys( $this->settings ) as $id ) {

				if ( ! in_array( $id, $ids, true ) ) {
					unset( $settings[ $id ] );
				}
			}
		}

		return $settings;
	}


	/**
	 * Gets a setting object.
	 *
	 * @since x.y.z
	 *
	 * @param string $id setting ID to get
	 * @return Setting|null
	 */
	public function get_setting( $id ) {

		return ! empty( $this->settings[ $id ] ) ? $this->settings[ $id ] : null;
	}


	/**
	 * Deletes the stored value for a setting.
	 *
	 * @since x.y.z
	 *
	 * @param string $setting_id setting ID
	 * @return bool
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function delete_value( $setting_id ) {

		$setting = $this->get_setting( $setting_id );

		if ( ! $setting ) {
			throw new Framework\SV_WC_Plugin_Exception( "Setting {$setting_id} does not exist" );
		}

		$setting->set_value( null );

		return delete_option( "{$this->id}_{$setting->get_id()}" );
	}


	/**
	 * Converts the value of a setting to be stored in an option.
	 *
	 * @since x.y.z
	 *
	 * @param Setting $setting
	 * @return mixed
	 */
	protected function get_value_for_database( Setting $setting ) {

		$value = $setting->get_value();

		if ( null !== $value && Setting::TYPE_BOOLEAN === $setting->get_type() ) {
			$value = wc_bool_to_string( $value );
		}

		return $value;
	}


	/**
	 * Converts the stored value of a setting to the proper setting type.
	 *
	 * @since x.y.z
	 *
	 * @param mixed $value the value stored in an option
	 * @param Setting $setting
	 * @return mixed
	 */
	protected function get_value_from_database( $value, Setting $setting ) {

		if ( null !== $value && Setting::TYPE_BOOLEAN === $setting->get_type() ) {
			$value = wc_string_to_bool( $value );
		}

		return $value;
	}


	/**
	 * Gets the list of valid setting types.
	 *
	 * @since x.y.z
	 *
	 * @return array
	 */
	public function get_setting_types() {

		$setting_types = [
			Setting::TYPE_STRING,
			Setting::TYPE_URL,
			Setting::TYPE_EMAIL,
			Setting::TYPE_INTEGER,
			Setting::TYPE_FLOAT,
			Setting::TYPE_BOOLEAN,
		];

		/**
		 * Filters the list of valid setting types.
		 *
		 * @param array $setting_types valid setting types
		 * @param Abstract_Settings $settings the settings handler instance
		 */
		return apply_filters( "wc_{$this->get_id()}_settings_api_setting_types", $setting_types, $this );
	}


	/**
	 * Gets the list of valid control types.
	 *
	 * @since x.y.z
	 *
	 * @return array
	 */
	public function get_control_types() {

		$control_types = [
			Control::TYPE_TEXT,
			Control::TYPE_TEXTAREA,
			Control::TYPE_NUMBER,
			Control::TYPE_EMAIL,
			Control::TYPE_PASSWORD,
			Control::TYPE_DATE,
			Control::TYPE_CHECKBOX,
			Control::TYPE_RADIO,
			Control::TYPE_SELECT,
			Control::TYPE_FILE,
			Control::TYPE_COLOR,
			Control::TYPE_RANGE,
		];

		/**
		 * Filters the list of valid contorl types.
		 *
		 * @param array $setting_types valid control types
		 * @param Abstract_Settings $settings the settings handler instance
		 */
		return apply_filters( "wc_{$this->get_id()}_settings_api_control_types", $control_types, $this );
	}


	/**
	 * Gets the prefix for db option names.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	public function get_option_name_prefix() {

		return "wc_{$this->id}";
	}


}

endif;
