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
 * @copyright Copyright (c) 2013-2022, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_12\Settings_API;

use SkyVerge\WooCommerce\PluginFramework\v5_10_12 as Framework;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_12\\Settings_API\\Abstract_Settings' ) ) :

/**
 * The base settings handler.
 *
 * @since 5.7.0
 */
abstract class Abstract_Settings {


	/** @var string settings ID */
	public $id;

	/** @var Setting[] registered settings */
	protected $settings = [];


	/**
	 * Constructs the class.
	 *
	 * @since 5.7.0
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
	 * @since 5.7.0
	 */
	abstract protected function register_settings();


	/**
	 * Loads the values for all registered settings.
	 *
	 * @since 5.7.0
	 */
	protected function load_settings() {

		foreach ( $this->settings as $setting_id => $setting ) {

			$value = get_option( $this->get_option_name_prefix() . '_' . $setting_id, null );
			$value = $this->get_value_from_database( $value, $setting );

			$this->settings[ $setting_id ]->set_value( $value );
		}
	}


	/**
	 * Registers a setting.
	 *
	 * @param string $id unique setting ID
	 * @param string $type setting type
	 * @param array $args setting arguments
	 * @return bool
	 */
	public function register_setting( $id, $type, array $args = [] ) {

		try {

			if ( ! empty( $this->settings[ $id ] ) ) {
				throw new Framework\SV_WC_Plugin_Exception( "Setting {$id} is already registered" );
			}

			if ( ! in_array( $type, $this->get_setting_types(), true ) ) {
				throw new Framework\SV_WC_Plugin_Exception( "{$type} is not a valid setting type" );
			}

			$setting = new Setting();

			$setting->set_id( $id );
			$setting->set_type( $type );

			$args = wp_parse_args( $args, [
				'name'         => '',
				'description'  => '',
				'is_multi'     => false,
				'options'      => [],
				'default'      => null,
			] );

			$setting->set_name( $args['name'] );
			$setting->set_description( $args['description'] );
			$setting->set_default( $args['default'] );
			$setting->set_is_multi( $args['is_multi'] );

			if ( is_array( $args['options'] ) ) {
				$setting->set_options( $args['options'] );
			}

			$this->settings[ $id ] = $setting;

			return true;

		} catch ( \Exception $exception ) {

			wc_doing_it_wrong( __METHOD__, 'Could not register setting: ' . $exception->getMessage(), '5.7.0' );

			return false;
		}
	}


	/**
	 * Unregisters a setting.
	 *
	 * @since 5.7.0
	 *
	 * @param string $id setting ID to unregister
	 */
	public function unregister_setting( $id ) {

		unset( $this->settings[ $id ] );
	}


	/**
	 * Registers a control for a setting.
	 *
	 * @since 5.7.0
	 *
	 * @param string $setting_id the setting ID
	 * @param string $type the control type
	 * @param array $args optional args for the control
	 * @return bool
	 */
	public function register_control( $setting_id, $type, array $args = [] ) {

		try {

			if ( ! in_array( $type, $this->get_control_types(), true ) ) {
				throw new \UnexpectedValueException( "{$type} is not a valid control type" );
			}

			$setting = $this->get_setting( $setting_id );

			if ( ! $setting ) {
				throw new \InvalidArgumentException( "Setting {$setting_id} does not exist" );
			}

			$setting_control_types = $this->get_setting_control_types( $setting );
			if ( ! empty( $setting_control_types ) && ! in_array( $type, $setting_control_types, true ) ) {
				throw new \UnexpectedValueException( "{$type} is not a valid control type for setting {$setting->get_id()} of type {$setting->get_type()}" );
			}

			$args = wp_parse_args( $args, [
				'name'        => $setting->get_name(),
				'description' => $setting->get_description(),
				'options'     => [],
			] );

			$control = new Control();

			$control->set_setting_id( $setting_id );
			$control->set_type( $type );
			$control->set_name( $args['name'] );
			$control->set_description( $args['description'] );

			if ( is_array( $args['options'] ) ) {
				$control->set_options( $args['options'], $setting->get_options() );
			}

			$setting->set_control( $control );

			return true;

		} catch ( \Exception $exception ) {

			wc_doing_it_wrong( __METHOD__, 'Could not register setting control: ' . $exception->getMessage(), '5.7.0' );

			return false;
		}
	}


	/**
	 * Gets the settings ID.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->id;
	}


	/**
	 * Gets registered settings.
	 *
	 * It returns all settings by default, but you can pass an array of IDs to filter the results.
	 *
	 * @param string[] $ids setting IDs to get
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
	 * @since 5.7.0
	 *
	 * @param string $id setting ID to get
	 * @return Setting|null
	 */
	public function get_setting( $id ) {

		return ! empty( $this->settings[ $id ] ) ? $this->settings[ $id ] : null;
	}


	/**
	 * Gets the stored value for a setting.
	 *
	 * Optionally, will return the setting's default value if nothing is stored.
	 *
	 * @since 5.7.0
	 *
	 * @param string $setting_id setting ID
	 * @param bool $with_default whether to return the default value if nothing is stored
	 * @return array|bool|float|int|string
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function get_value( $setting_id, $with_default = true ) {

		$setting = $this->get_setting( $setting_id );

		if ( ! $setting ) {
			throw new Framework\SV_WC_Plugin_Exception( "Setting {$setting_id} does not exist" );
		}

		$value = $setting->get_value();

		if ( $with_default && null === $value ) {
			$value = $setting->get_default();
		}

		return $value;
	}


	/**
	 * Updates the stored value for a setting.
	 *
	 * @since 5.7.0
	 *
	 * @param string $setting_id setting ID
	 * @param array|bool|float|int|string $value
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function update_value( $setting_id, $value ) {

		$setting = $this->get_setting( $setting_id );

		if ( ! $setting ) {
			throw new Framework\SV_WC_Plugin_Exception( "Setting {$setting_id} does not exist", 404 );
		}

		// performs the validations and updates the value
		$setting->update_value( $value );

		$this->save( $setting_id );
	}


	/**
	 * Deletes the stored value for a setting.
	 *
	 * @since 5.7.0
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

		return delete_option( "{$this->get_option_name_prefix()}_{$setting->get_id()}" );
	}


	/**
	 * Saves registered settings in their current state.
	 *
	 * It saves all settings by default, but you can pass a setting ID to save a specific setting.
	 *
	 * @since 5.7.0
	 *
	 * @param string $setting_id setting ID
	 */
	public function save( $setting_id = '' ) {

		if ( ! empty( $setting_id ) ) {
			$settings = [ $this->get_setting( $setting_id ) ];
		} else {
			$settings = $this->settings;
		}

		$settings = array_filter( $settings );

		foreach ( $settings as $setting ) {

			$option_name   = "{$this->get_option_name_prefix()}_{$setting->get_id()}";
			$setting_value = $setting->get_value();

			if ( null === $setting_value ) {

				delete_option( $option_name );

			} else {

				update_option( $option_name, $this->get_value_for_database( $setting ) );
			}
		}
	}


	/**
	 * Converts the value of a setting to be stored in an option.
	 *
	 * @since 5.7.0
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
	 * @since 5.7.0
	 *
	 * @param mixed $value the value stored in an option
	 * @param Setting $setting
	 * @return mixed
	 */
	protected function get_value_from_database( $value, Setting $setting ) {

		if ( null !== $value ) {

			switch ( $setting->get_type() ) {

				case Setting::TYPE_BOOLEAN:
					$value = wc_string_to_bool( $value );
				break;

				case Setting::TYPE_INTEGER:
					$value = is_numeric( $value ) ? (int) $value : null;
				break;

				case Setting::TYPE_FLOAT:
					$value = is_numeric( $value ) ? (float) $value : null;
				break;
			}
		}

		return $value;
	}


	/**
	 * Gets the list of valid setting types.
	 *
	 * @since 5.7.0
	 *
	 * @return string[]
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
		 * @param string[] $setting_types valid setting types
		 * @param Abstract_Settings $settings the settings handler instance
		 */
		return apply_filters( "wc_{$this->get_id()}_settings_api_setting_types", $setting_types, $this );
	}


	/**
	 * Gets the list of valid control types.
	 *
	 * @since 5.7.0
	 *
	 * @return string[]
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
		 * Filters the list of valid control types.
		 *
		 * @param string[] $control_types valid control types
		 * @param Abstract_Settings $settings the settings handler instance
		 */
		return apply_filters( "wc_{$this->get_id()}_settings_api_control_types", $control_types, $this );
	}


	/**
	 * Returns the valid control types for a setting.
	 *
	 * @since 5.7.0
	 *
	 * @param Setting $setting setting object
	 * @return string[]
	 */
	public function get_setting_control_types( $setting ) {

		/**
		 * Filters the list of valid control types for a setting.
		 *
		 * @param string[] $control_types valid control types
		 * @param string $setting_type setting type
		 * @param Setting $setting setting object
		 * @param Abstract_Settings $settings the settings handler instance
		 */
		return apply_filters( "wc_{$this->get_id()}_settings_api_setting_control_types", [], $setting->get_type(), $setting, $this );
	}


	/**
	 * Gets the prefix for db option names.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_option_name_prefix() {

		return "wc_{$this->id}";
	}


}

endif;
