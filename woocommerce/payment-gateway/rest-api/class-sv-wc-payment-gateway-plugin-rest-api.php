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

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\REST_API as Plugin_REST_API;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\REST_API' ) ) :


/**
 * The payment gateway plugin REST API handler class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\REST_API
 *
 * @since 5.2.0
 */
class REST_API extends Plugin_REST_API {


	/**
	 * Gets the data to add to the WooCommerce REST API System Status response.
	 *
	 * Plugins can override this to add their own data.
	 *
	 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\REST_API::get_system_status_data()
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function get_system_status_data() {

		$data = parent::get_system_status_data();

		$data['gateways'] = array();

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( $gateway->debug_log() && $gateway->debug_checkout() ) {
				$debug_mode = 'both';
			} elseif ( $gateway->debug_log() || $gateway->debug_checkout() ) {
				$debug_mode = $gateway->debug_log() ? 'log' : 'checkout';
			} else {
				$debug_mode = false;
			}

			$gateway_data = array(
				'is_enabled'              => $gateway->is_enabled(),
				'is_available'            => $gateway->is_available(),
				'environment'             => $gateway->is_test_environment() ? 'sandbox' : 'production',
				'debug_mode'              => $debug_mode,
				'supports_tokenization'   => $gateway->supports_tokenization(),
				'is_tokenization_enabled' => $gateway->supports_tokenization() ? (bool) $gateway->tokenization_enabled() : null,
			);

			$gateway_data = apply_filters( 'wc_' . $gateway->get_id() . '_rest_api_system_status_data', $gateway_data );

			$data['gateways'][ $gateway->get_id() ] = $gateway_data;
		}

		return $data;
	}


}


endif;
