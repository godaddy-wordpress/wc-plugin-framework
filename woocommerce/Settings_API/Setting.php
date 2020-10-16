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

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Settings_API;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Settings_API\\Setting' ) ) :

/**
 * The base setting object.
 *
 * @since 5.7.0
 */
class Setting {


	/** @var string the string setting type */
	const TYPE_STRING = 'string';

	/** @var string the URL setting type */
	const TYPE_URL = 'url';

	/** @var string the email setting type */
	const TYPE_EMAIL = 'email';

	/** @var string the integer setting type */
	const TYPE_INTEGER = 'integer';

	/** @var string the float setting type */
	const TYPE_FLOAT = 'float';

	/** @var string the boolean setting type */
	const TYPE_BOOLEAN = 'boolean';


	/** @var string unique setting ID */
	protected $id;

	/** @var string setting type */
	protected $type;

	/** @var string setting name */
	protected $name;

	/** @var string setting description */
	protected $description;

	/** @var bool whether the setting holds an array of multiple values */
	protected $is_multi = false;

	/** @var array valid setting options */
	protected $options = [];

	/** @var int|float|string|bool|array setting default value */
	protected $default;

	/** @var int|float|string|bool|array setting current value */
	protected $value;

	/** @var Control control object */
	protected $control;


	/** Getter Methods ************************************************************************************************/


	/**
	 * Gets the setting ID.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->id;
	}


	/**
	 * Gets the setting type.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_type() {

		return $this->type;
	}


	/**
	 * Gets the setting name.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_name() {

		return $this->name;
	}


	/**
	 * Gets the setting description.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_description() {

		return $this->description;
	}


	/**
	 * Returns whether the setting holds an array of multiple values.
	 *
	 * @since 5.7.0
	 *
	 * @return bool
	 */
	public function is_is_multi() {

		return $this->is_multi;
	}


	/**
	 * Gets the setting options.
	 *
	 * @since 5.7.0
	 *
	 * @return array
	 */
	public function get_options() {

		return $this->options;
	}


	/**
	 * Gets the setting default value.
	 *
	 * @since 5.7.0
	 *
	 * @return array|bool|float|int|string|null
	 */
	public function get_default() {

		return $this->default;
	}


	/**
	 * Gets the setting current value.
	 *
	 * @since 5.7.0
	 *
	 * @return array|bool|float|int|string
	 */
	public function get_value() {

		return $this->value;
	}


	/**
	 * Gets the setting control.
	 *
	 * @since 5.7.0
	 *
	 * @return Control
	 */
	public function get_control() {

		return $this->control;
	}


	/** Setter Methods ************************************************************************************************/


	/**
	 * Sets the setting ID.
	 *
	 * @since 5.7.0
	 *
	 * @param string $id
	 */
	public function set_id( $id ) {

		$this->id = $id;
	}


	/**
	 * Sets the setting type.
	 *
	 * @since 5.7.0
	 *
	 * @param string $type
	 */
	public function set_type( $type ) {

		$this->type = $type;
	}


	/**
	 * Sets the setting name.
	 *
	 * @since 5.7.0
	 *
	 * @param string $name
	 */
	public function set_name( $name ) {

		$this->name = $name;
	}


	/**
	 * Sets the setting description.
	 *
	 * @since 5.7.0
	 *
	 * @param string $description
	 */
	public function set_description( $description ) {

		$this->description = $description;
	}


	/**
	 * Sets whether the setting holds an array of multiple values.
	 *
	 * @since 5.7.0
	 *
	 * @param bool $is_multi
	 */
	public function set_is_multi( $is_multi ) {

		$this->is_multi = $is_multi;
	}


	/**
	 * Sets the setting options.
	 *
	 * @since 5.7.0
	 *
	 * @param array $options
	 */
	public function set_options( $options ) {

		foreach ( $options as $key => $option ) {

			if ( ! $this->validate_value( $option ) ) {
				unset( $options[ $key ] );
			}
		}

		$this->options = $options;
	}


	/**
	 * Sets the setting default value.
	 *
	 * @since 5.7.0
	 *
	 * @param array|bool|float|int|string|null $value default value to set
	 */
	public function set_default( $value ) {

		if ( $this->is_is_multi() ) {

			$_value = array_filter( (array) $value, [ $this, 'validate_value' ] );

			// clear the default if all values were invalid
			$value = ! empty( $_value ) ? $_value : null;

		} elseif ( ! $this->validate_value( $value ) ) {

			$value = null;
		}

		$this->default = $value;
	}


	/**
	 * Sets the setting current value.
	 *
	 * @since 5.7.0
	 *
	 * @param array|bool|float|int|string $value
	 */
	public function set_value( $value ) {

		$this->value = $value;
	}


	/**
	 * Sets the setting control.
	 *
	 * @since 5.7.0
	 *
	 * @param Control $control
	 */
	public function set_control( $control ) {

		$this->control = $control;
	}


	/**
	 * Sets the setting current value, after validating it against the type and, if set, options.
	 *
	 * @since 5.7.0
	 *
	 * @param array|bool|float|int|string $value
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function update_value( $value ) {

		if ( ! $this->validate_value( $value ) ) {

			throw new Framework\SV_WC_Plugin_Exception( "Setting value for setting {$this->id} is not valid for the setting type {$this->type}", 400 );

		} elseif ( ! empty( $this->options ) && ! in_array( $value, $this->options ) ) {

			throw new Framework\SV_WC_Plugin_Exception( sprintf(
				'Setting value for setting %s must be one of %s',
				$this->id,
				Framework\SV_WC_Helper::list_array_items( $this->options, 'or' )
			), 400 );

		} else {

			$this->set_value( $value );
		}
	}


	/**
	 * Validates the setting value.
	 *
	 * @since 5.7.0
	 *
	 * @param array|bool|float|int|string $value
	 * @return bool
	 */
	public function validate_value( $value ) {

		$validate_method = "validate_{$this->get_type()}_value";

		return is_callable( [ $this, $validate_method ] ) ? $this->$validate_method( $value ) : true;
	}


	/**
	 * Validates a string value.
	 *
	 * @since 5.7.0
	 *
	 * @param array|bool|float|int|string $value value to validate
	 * @return bool
	 */
	protected function validate_string_value( $value ) {

		return is_string( $value );
	}


	/**
	 * Validates a URL value.
	 *
	 * @since 5.7.0
	 *
	 * @param array|bool|float|int|string $value value to validate
	 * @return bool
	 */
	protected function validate_url_value( $value ) {

		return wc_is_valid_url( $value );
	}


	/**
	 * Validates an email value.
	 *
	 * @since 5.7.0
	 *
	 * @param mixed $value value to validate
	 * @return bool
	 */
	protected function validate_email_value( $value ) {

		return (bool) is_email( $value );
	}


	/**
	 * Validates an integer value.
	 *
	 * @since 5.7.0
	 *
	 * @param mixed $value value to validate
	 * @return bool
	 */
	public function validate_integer_value( $value ) {

		return is_int( $value );
	}


	/**
	 * Validates a float value.
	 *
	 * @since 5.7.0
	 *
	 * @param mixed $value value to validate
	 * @return bool
	 */
	protected function validate_float_value( $value ) {

		return is_int( $value ) || is_float( $value );
	}


	/**
	 * Validates a boolean value.
	 *
	 * @since 5.7.0
	 *
	 * @param mixed $value value to validate
	 * @return bool
	 */
	protected function validate_boolean_value( $value ) {

		return is_bool( $value );
	}


}

endif;
