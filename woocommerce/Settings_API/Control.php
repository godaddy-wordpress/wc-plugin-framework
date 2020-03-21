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

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Plugin_Exception;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_6_1\\Settings_API\\Control' ) ) :

/**
 * The base control object.
 *
 * @since x.y.z
 */
class Control {


	/** @var string the text control type */
	const TYPE_TEXT = 'text';

	/** @var string the textarea control type */
	const TYPE_TEXTAREA = 'textarea';

	/** @var string the number control type */
	const TYPE_NUMBER = 'number';

	/** @var string the email control type */
	const TYPE_EMAIL = 'email';

	/** @var string the password control type */
	const TYPE_PASSWORD = 'password';

	/** @var string the date control type */
	const TYPE_DATE = 'date';

	/** @var string the checkbox control type */
	const TYPE_CHECKBOX = 'checkbox';

	/** @var string the radio control type */
	const TYPE_RADIO = 'radio';

	/** @var string the select control type */
	const TYPE_SELECT = 'select';

	/** @var string the file control type */
	const TYPE_FILE = 'file';

	/** @var string the color control type */
	const TYPE_COLOR = 'color';

	/** @var string the range control type */
	const TYPE_RANGE = 'range';


	/** @var string|null the setting ID to which this control belongs */
	protected $setting_id;

	/** @var string|null the control type */
	protected $type;

	/** @var string the control name */
	protected $name = '';

	/** @var string the control description */
	protected $description = '';

	/** @var array the control options, as $option => $label  */
	protected $options = [];


	/** Getter methods ************************************************************************************************/


	/**
	 * The setting ID to which this control belongs.
	 *
	 * @since x.y.z
	 *
	 * @return null|string
	 */
	public function get_setting_id() {

		return $this->setting_id;
	}


	/**
	 * Gets the control type.
	 *
	 * @since x.y.z
	 *
	 * @return null|string
	 */
	public function get_type() {

		return $this->type;
	}


	/**
	 * Gets the control name.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	public function get_name() {

		return $this->name;
	}


	/**
	 * Gets the control description.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	public function get_description() {

		return $this->description;
	}


	/**
	 * Gets the control options.
	 *
	 * As $option => $label for display.
	 *
	 * @since x.y.z
	 *
	 * @return array
	 */
	public function get_options() {

		return $this->options;
	}


	/** Setter methods ************************************************************************************************/


	/**
	 * Sets the setting ID.
	 *
	 * @since x.y.z
	 *
	 * @param string $value setting ID to set
	 * @throws SV_WC_Plugin_Exception
	 */
	public function set_setting_id( $value ) {

		if ( ! is_string( $value ) ) {
			throw new SV_WC_Plugin_Exception( 'Setting ID value must be a string' );
		}

		$this->setting_id = $value;
	}


	/**
	 * Sets the type.
	 *
	 * @since x.y.z
	 *
	 * @param string $value setting ID to set
	 */
	public function set_type( $value ) {

		// TODO: add validation and throw an exception

		$this->type = $value;
	}


	/**
	 * Sets the name.
	 *
	 * @since x.y.z
	 *
	 * @param string $value control name to set
	 * @throws SV_WC_Plugin_Exception
	 */
	public function set_name( $value ) {

		if ( ! is_string( $value ) ) {
			throw new SV_WC_Plugin_Exception( 'Control name value must be a string' );
		}

		$this->name = $value;
	}


	/**
	 * Sets the description.
	 *
	 * @since x.y.z
	 *
	 * @param string $value control description to set
	 * @throws SV_WC_Plugin_Exception
	 */
	public function set_description( $value ) {

		if ( ! is_string( $value ) ) {
			throw new SV_WC_Plugin_Exception( 'Control description value must be a string' );
		}

		$this->description = $value;
	}


	/**
	 * Sets the options.
	 *
	 * @since x.y.z
	 *
	 * @param array $options options to set
	 * @param array $valid_options valid option keys to check against
	 */
	public function set_options( array $options, array $valid_options = [] ) {

		foreach ( array_keys( $options ) as $key ) {

			if ( ! in_array( $key, $valid_options, true ) ) {
				unset( $options[ $key ] );
			}
		}

		$this->options = $options;
	}


}

endif;
