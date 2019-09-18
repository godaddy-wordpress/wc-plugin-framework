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
 * @copyright Copyright (c) 2013-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_5_0\REST_API\v3;

use SkyVerge\WooCommerce\PluginFramework\v5_5_0\SV_WC_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0\REST_API\Debug_Controller;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_0\\REST_API\\v3\\DebugController' ) ) :


/**
 * The plugin REST API Debug endpoint for v3 API
 *
 * @since 5.5.0-dev.1
 */
class Debug extends Debug_Controller {


	/**
	 * Debug controller constructor for v3.
	 *
	 * @since 5.5.0-dev
	 *
	 * @param SV_WC_Plugin $plugin main instance
	 */
	public function __construct( SV_WC_Plugin $plugin ) {

		parent::__construct( $plugin );

		$this->namespace = 'wc/v3';
	}


}


endif;