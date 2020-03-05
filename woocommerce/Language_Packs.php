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

class Language_Packs {


	/** @var SV_WC_Plugin main plugin instance */
	private $plugin;


	/**
	 * Language packs constructor.
	 *
	 * @since x.y.z
	 *
	 * @param SV_WC_Plugin $plugin main plugin instance
	 */
	public function __construct( SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;
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
