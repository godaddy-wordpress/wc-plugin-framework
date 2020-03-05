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

namespace SkyVerge\WooCommerce\PluginFramework\v5_6_0;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_6_0\\Language_Packs' ) ) :

/**
 * Language packs handler.
 *
 * @since x.y.z
 */
class Language_Packs {


	/** @var SV_WC_Plugin main plugin instance */
	private $plugin;

	/** @var array translations configuration */
	private $config;


	/**
	 * Language packs constructor.
	 *
	 * @since x.y.z
	 *
	 * @param SV_WC_Plugin $plugin main plugin instance
	 * @param array $config the plugin's translations configuration
	 */
	public function __construct( SV_WC_Plugin $plugin, array $config ) {

		$this->plugin = $plugin;
		$this->config = $config;

		if ( ! empty( $this->config ) ) {

			// adds the plugin to the list of plugins in the translations transient
			add_filter( 'site_transient_update_plugins', [ $this, 'add_translations' ], 1, 1 );
			// intercepts the translations API to update a plugin that is not listed in the WordPress plugins directory
			add_filter( 'translations_api', [ $this, 'update_translations' ], 1, 3 );
		}
	}


	/**
	 * Adds translations data to the plugins update transient.
	 *
	 * @internal
	 *
	 * @since x.y.z
	 *
	 * @param \stdClass $data transient data
	 * @return \stdClass
	 */
	public function add_translations( $data ) {

		return $data;
	}


	/**
	 * Intercepts the translations API requests to inject plugin translation updates.
	 *
	 * @internal
	 *
	 * @since x.y.z
	 *
	 * @param string|array|\stdClass $response request response
	 * @param string $request_type request type
	 * @param array $args request arguments
	 * @return mixed
	 */
	public function update_translations( $response, $request_type, $args ) {

		return $response;
	}


	/**
	 * Gets the main plugin instance.
	 *
	 * @since x.y.z
	 *
	 * @return SV_WC_Plugin
	 */
	private function get_plugin() {

		return $this->plugin;
	}


}

endif;