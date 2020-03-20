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


}

endif;
