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
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Admin;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\Admin\\Setup_Wizard' ) ) :


/**
 * The payment gateway plugin Setup Wizard class.
 *
 * Extends the base plugin class to add common gateway functionality.
 *
 * @since 5.2.2
 */
abstract class Setup_Wizard extends Framework\Admin\Setup_Wizard {


	/**
	 * Adds a 'Setup' link to the plugin action links if the wizard hasn't been completed.
	 *
	 * This will override the plugin's standard "Configure {gateway}" links with a link to
	 * this setup wizard.
	 *
	 * @internal
	 *
	 * @since 5.2.2
	 *
	 * @param array $action_links plugin action links
	 * @return array
	 */
	public function add_setup_link( $action_links ) {

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {
			unset( $action_links[ 'configure_' . $gateway->get_id() ] );
		}

		return parent::add_setup_link( $action_links );
	}


}


endif;
