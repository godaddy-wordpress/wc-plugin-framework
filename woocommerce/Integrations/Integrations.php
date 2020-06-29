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

namespace SkyVerge\WooCommerce\PluginFramework\v5_7_1\Integrations;

use SkyVerge\WooCommerce\PluginFramework\v5_7_1 as Framework;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_7_1\\Integrations\\Integrations' ) ) :


/**
 * Plugin integrations class.
 *
 * @since 5.7.2-dev.1
 */
class Integrations {

	/** @var Framework\SV_WC_Plugin plugin instance */
	protected $plugin;

	/** @var array of plugin integration objects */
	protected $integrations = [];


	/**
	 * Bootstraps the class.
	 *
	 * @since 4.1.0
	 *
	 * @param SV_WC_Payment_Gateway $gateway direct gateway instance
	 */
	public function __construct( Framework\SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->init_integrations();
	}


	/** Integrations Feature **************************************************/


	/**
	 * Initializes supported integrations.
	 *
	 * @since 5.7.2-dev.1
	 */
	protected function init_integrations() {

	}


	/**
	 * Gets the plugin instance
	 *
	 * @since 5.7.2-dev.1
	 *
	 * @return Framework\SV_WC_Plugin
	 */
	public function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Gets an array of available integration objects
	 *
	 * @since 5.7.2-dev.1
	 *
	 * @return array
	 */
	public function get_integrations() {

		return $this->integrations;
	}


	/**
	 * Gets the integration object for the given ID.
	 *
	 * @since 5.7.2-dev.1
	 *
	 * @param string $id the integration ID, e.g. disable-admin-notices
	 * @return object|null
	 */
	public function get_integration( $id ) {

		return isset( $this->integrations[ $id ] ) ? $this->integrations[ $id ] : null;
	}


}


endif;
