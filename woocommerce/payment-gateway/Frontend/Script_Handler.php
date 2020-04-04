<?php
/**
 * WooCommerce Payment Gateway Framework
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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_6_1\Frontend;

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Plugin_Exception;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_6_1\\Frontend\\Script_Handler' ) ) :


/**
 * Script Handler Abstract Class
 *
 * Handles initializing the payment registered JavaScripts
 *
 * @since x.y.z
 */
abstract class Script_Handler {


	/** @var string JS handler base class name, without the FW version */
	protected $js_handler_base_class_name = '';


	/**
	 * Returns the JS handler class name.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	protected function get_js_handler_class_name() {

		return sprintf( '%s_v5_6_1', $this->js_handler_base_class_name );
	}


	/**
	 * Gets the JS event triggered after the JS handler class is loaded.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	protected function get_js_loaded_event() {

		return sprintf( '%s_loaded', strtolower( $this->get_js_handler_class_name() ) );
	}


	/**
	 * Gets inline JavaScript code to issue an AJAX request to log a script error event.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	protected function get_js_handler_event_debug_log_request() {

		ob_start();

		?>

		var errorName    = '',
		    errorMessage = '';

		if ( 'undefined' === typeof err || 0 === err.length || ! err ) {
			errorName    = '<?php echo esc_js( 'A script error has occurred.' ); ?>';
			errorMessage = '<?php echo esc_js( sprintf( 'The script %s could not be loaded.', $this->get_js_handler_class_name() ) ); ?>';
		} else {
			errorName    = 'undefined' !== typeof err.name    ? err.name    : '';
			errorMessage = 'undefined' !== typeof err.message ? err.message : '';
		}

		jQuery.post( '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ) ; ?>', {
			action:   '<?php echo esc_js( 'wc_' . $this->get_id() . '_log_script_event' ); ?>',
			security: '<?php echo esc_js( wp_create_nonce( 'wc-' . $this->get_id_dasherized() . '-log-script-event' ) ); ?>',
			name:     errorName,
			message:  errorMessage,
		} );

		<?php

		return ob_get_clean();
	}


	/**
	 * Adds a log entry.
	 *
	 * @since x.y.z
	 *
	 * @param string $message message to log
	 */
	abstract protected function log_event( $message );


	/** Conditional methods *******************************************************************************************/


	/**
	 * Determines whether logging is enabled.
	 *
	 * @since x.y.z
	 *
	 * @return bool
	 */
	protected function is_logging_enabled() {

		return false;
	}


	/** Getter methods ************************************************************************************************/


	/**
	 * Gets the ID of this script handler.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	abstract public function get_id();


	/**
	 * Gets the ID, but dasherized.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	public function get_id_dasherized() {

		return str_replace( '_', '-', $this->get_id() );
	}


}

endif;
