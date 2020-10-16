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

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Handlers;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Exception;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Handlers\\Script_Handler' ) ) :


/**
 * Script Handler Abstract Class
 *
 * Handles initializing the payment registered JavaScripts
 *
 * @since 5.7.0
 */
abstract class Script_Handler {


	/** @var string JS handler base class name, without the FW version */
	protected $js_handler_base_class_name = '';


	/**
	 * Script_Handler constructor.
	 *
	 * @since 5.7.0
	 */
	public function __construct() {

		// add the action and filter hooks
		$this->add_hooks();
	}


	/**
	 * Adds the action and filter hooks.
	 *
	 * @since 5.7.0
	 */
	protected function add_hooks() {

		add_action( 'wp_ajax_wc_' . $this->get_id() . '_log_script_event',         [ $this, 'ajax_log_event' ] );
		add_action( 'wp_ajax_nopriv_wc_' . $this->get_id()  . '_log_script_event', [ $this, 'ajax_log_event' ] );
	}


	/**
	 * Returns the JS handler class name.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	protected function get_js_handler_class_name() {

		return sprintf( '%s_v5_10_0', $this->js_handler_base_class_name );
	}


	/**
	 * Returns the JS handler object name.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	protected function get_js_handler_object_name() {

		return 'wc_' . $this->get_id() . '_handler';
	}


	/**
	 * Gets the JS event triggered after the JS handler class is loaded.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	protected function get_js_loaded_event() {

		return sprintf( '%s_loaded', strtolower( $this->get_js_handler_class_name() ) );
	}


	/**
	 * Gets the handler instantiation JS wrapped in a safe load technique.
	 *
	 * @since 5.7.0
	 *
	 * @param array $additional_args additional handler arguments, if any
	 * @param string $handler_name handler name, if different from self::get_js_handler_class_name()
	 * @param string $object_name object name, if different from self::get_js_handler_object_name()
	 * @return string
	 */
	protected function get_safe_handler_js( array $additional_args = [], $handler_name = '', $object_name = '' ) {

		if ( ! $handler_name ) {
			$handler_name = $this->get_js_handler_class_name();
		}

		$load_function = 'load_' . $this->get_id() . '_handler';

		ob_start();

		?>
		function <?php echo esc_js( $load_function ) ?>() {
			<?php echo $this->get_handler_js( $additional_args, $handler_name, $object_name ); ?>
		}

		try {

			if ( 'undefined' !== typeof <?php echo esc_js( $handler_name ); ?> ) {
				<?php echo esc_js( $load_function ); ?>();
			} else {
				window.jQuery( document.body ).on( '<?php echo esc_js( $this->get_js_loaded_event() ); ?>', <?php echo esc_js( $load_function ); ?> );
			}

		} catch ( err ) {

			<?php echo $this->get_js_handler_event_debug_log_request(); ?>
		}
		<?php

		return ob_get_clean();
	}


	/**
	 * Gets the handler instantiation JS.
	 *
	 * @since 5.7.0
	 *
	 * @param array $additional_args additional handler arguments, if any
	 * @param string $handler_name handler name, if different from self::get_js_handler_class_name()
	 * @param string $object_name object name, if different from self::get_js_handler_object_name()
	 * @return string
	 */
	protected function get_handler_js( array $additional_args = [], $handler_name = '', $object_name = '' ) {

		$args = array_merge( $additional_args, $this->get_js_handler_args() );

		/**
		 * Filters the JavaScript handler arguments.
		 *
		 * @since 5.7.0
		 *
		 * @param array $args arguments to pass to the JS handler
		 * @param Script_Handler $handler script handler instance
		 */
		$args = apply_filters( 'wc_' . $this->get_id() . '_js_args', $args, $this );

		if ( ! $handler_name ) {
			$handler_name = $this->get_js_handler_class_name();
		}

		if ( ! $object_name ) {
			$object_name = $this->get_js_handler_object_name();
		}

		return sprintf( 'window.%1$s = new %2$s( %3$s );', esc_js( $object_name ), esc_js( $handler_name ), json_encode( $args ) );
	}


	/**
	 * Gets the JS handler arguments.
	 *
	 * @since 5.7.0
	 *
	 * @return array
	 */
	protected function get_js_handler_args() {

		return [];
	}


	/**
	 * Gets inline JavaScript code to issue an AJAX request to log a script error event.
	 *
	 * @since 5.7.0
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

		<?php if ( $this->is_logging_enabled() ) : ?>

		console.log( [ errorName, errorMessage ].filter( Boolean ).join( ' ' ) );

		<?php endif; ?>

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
	 * Logs an event via AJAX.
	 *
	 * @internal
	 *
	 * @since 5.7.0
	 */
	public function ajax_log_event() {

		// silently bail if nothing should be logged
		if ( ! $this->is_logging_enabled() ) {
			return;
		}

		try {

			if ( ! wp_verify_nonce( SV_WC_Helper::get_posted_value( 'security' ), 'wc-' . $this->get_id_dasherized() . '-log-script-event' ) ) {
				throw new SV_WC_Plugin_Exception( 'Invalid nonce.' );
			}

			$name    = isset( $_POST['name'] )    && is_string( $_POST['name'] )    ? trim( $_POST['name'] )    : '';
			$message = isset( $_POST['message'] ) && is_string( $_POST['message'] ) ? trim( $_POST['message'] ) : '';

			if ( ! $message ) {
				throw new SV_WC_Plugin_Exception( 'A message is required.' );
			}

			if ( $name ) {
				$message = "{$name} {$message}";
			}

			$this->log_event( $message );

			wp_send_json_success();

		} catch ( SV_WC_Plugin_Exception $exception ) {

			wp_send_json_error( $exception->getMessage() );
		}
	}


	/**
	 * Adds a log entry.
	 *
	 * @since 5.7.0
	 *
	 * @param string $message message to log
	 */
	abstract protected function log_event( $message );


	/** Conditional methods *******************************************************************************************/


	/**
	 * Determines whether logging is enabled.
	 *
	 * @since 5.7.0
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
	 * @since 5.7.0
	 *
	 * @return string
	 */
	abstract public function get_id();


	/**
	 * Gets the ID, but dasherized.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_id_dasherized() {

		return str_replace( '_', '-', $this->get_id() );
	}


}

endif;
