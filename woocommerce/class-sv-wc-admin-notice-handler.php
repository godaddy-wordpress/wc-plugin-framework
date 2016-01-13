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
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_Admin_Notice_Handler' ) ) :

/**
 * SkyVerge Admin Notice Handler Class
 *
 * The purpose of this class is to provide a facility for displaying
 * conditional (often dismissible) admin notices during a single page
 * request
 *
 * @since 3.0.0
 */
class SV_WC_Admin_Notice_Handler {


	/** @var SV_WC_Plugin the plugin */
	private $plugin;

	/** @var array associative array of id to notice text */
	private $admin_notices = array();

	/** @var boolean static member to enforce a single rendering of the admin notice placeholder element */
	static private $admin_notice_placeholder_rendered = false;

	/** @var boolean static member to enforce a single rendering of the admin notice javascript */
	static private $admin_notice_js_rendered = false;


	/**
	 * Initialize and setup the Admin Notice Handler
	 *
	 * @since 3.0.0
	 */
	public function __construct( $plugin ) {

		$this->plugin      = $plugin;

		// render any admin notices, delayed notices, and
		add_action( 'admin_notices', array( $this, 'render_admin_notices'         ), 15 );
		add_action( 'admin_footer',  array( $this, 'render_delayed_admin_notices' ), 15 );
		add_action( 'admin_footer',  array( $this, 'render_admin_notice_js'       ), 20 );

		// AJAX handler to dismiss any warning/error notices
		add_action( 'wp_ajax_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismiss_notice', array( $this, 'handle_dismiss_notice' ) );
	}


	/**
	 * Adds the given $message as a dismissible notice identified by $message_id,
	 * unless the notice has been dismissed, or we're on the plugin settings page
	 *
	 * @since 3.0.0
	 * @param string $message the notice message to display
	 * @param string $message_id the message id
	 * @param array $params optional parameters array.  Defaults to array( 'dismissible' => true, 'always_show_on_settings' => true )
	 */
	public function add_admin_notice( $message, $message_id, $params = array() ) {

		if ( ! isset( $params['dismissible'] ) ) {
			$params['dismissible'] = true;
		}
		if ( ! isset( $params['always_show_on_settings'] ) ) {
			$params['always_show_on_settings'] = true;
		}

		if ( $this->should_display_notice( $message_id, $params ) ) {
			$this->admin_notices[ $message_id ] = array(
				'message'  => $message,
				'rendered' => false,
				'params'   => $params,
			);
		}
	}


	/**
	 * Returns true if the identified notice hasn't been cleared, or we're on
	 * the plugin settings page (where notices are always displayed)
	 *
	 * @since 3.0.0
	 * @param string $message_id the message id
	 * @param array $params optional parameters array.  Defaults to array( 'dismissible' => true, 'always_show_on_settings' => true, 'notice_class' => 'updated' )
	 */
	public function should_display_notice( $message_id, $params = array() ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		// default to dismissible, always on settings
		if ( ! isset( $params['dismissible'] ) ) {
			$params['dismissible'] = true;
		}
		if ( ! isset( $params['always_show_on_settings'] ) ) {
			$params['always_show_on_settings'] = true;
		}

		// if the notice is always shown on the settings page, and we're on the settings page
		if ( $params['always_show_on_settings'] && $this->get_plugin()->is_plugin_settings() ) {
			return true;
		}

		// non-dismissible, always display
		if ( ! $params['dismissible'] ) {
			return true;
		}

		// dismissible: display if notice has not been dismissed
		return ! $this->is_notice_dismissed( $message_id );
	}


	/**
	 * Render any admin notices, as well as the admin notice placeholder
	 *
	 * @since 3.0.0
	 * @param boolean $is_visible true if the notices should be immediately visible, false otherwise
	 */
	public function render_admin_notices( $is_visible = true ) {

		// default for actions
		if ( ! is_bool( $is_visible ) ) {
			$is_visible = true;
		}

		foreach ( $this->admin_notices as $message_id => $message_data ) {
			if ( ! $message_data['rendered'] ) {
				$message_data['params']['is_visible'] = $is_visible;
				$this->render_admin_notice( $message_data['message'], $message_id, $message_data['params'] );
				$this->admin_notices[ $message_id ]['rendered'] = true;
			}
		}

		if ( $is_visible && ! self::$admin_notice_placeholder_rendered ) {
			// placeholder for moving delayed notices up into place
			echo '<div class="js-wc-plugin-framework-admin-notice-placeholder"></div>';
			self::$admin_notice_placeholder_rendered = true;
		}

	}


	/**
	 * Render any delayed admin notices, which have not yet already been rendered
	 *
	 * @since 3.0.0
	 */
	public function render_delayed_admin_notices() {
		$this->render_admin_notices( false );
	}


	/**
	 * Render a single admin notice
	 *
	 * @since 3.0.0
	 * @param string $message the notice message to display
	 * @param string $message_id the message id
	 * @param array $params optional parameters array.  Options: 'dismissible', 'is_visible', 'always_show_on_settings', 'notice_class'
	 */
	public function render_admin_notice( $message, $message_id, $params = array() ) {

		$dismiss_link = '';

		// dismissible link if the notice is dismissible and it's not always shown on the settings page, or we're on the settings page
		if ( isset( $params['dismissible'] ) && $params['dismissible'] && ( ! isset( $params['always_show_on_settings'] ) || ! $params['always_show_on_settings'] || ! $this->get_plugin()->is_plugin_settings() ) ) {

			/* translators: this is an action that dismisses a message */
			$dismiss_link = sprintf( '<a href="#" class="js-wc-plugin-framework-notice-dismiss" data-message-id="%s" style="float: right;">%s</a>', $message_id, esc_html__( 'Dismiss', 'woocommerce-plugin-framework' ) );
		}

		$class = isset( $params['notice_class'] ) ? $params['notice_class'] : 'error';

		echo sprintf( '<div data-plugin-id="' . $this->get_plugin()->get_id() . '" class="' . $class . ' js-wc-plugin-framework-admin-notice"%s><p>%s %s</p></div>', ! isset( $params['is_visible'] ) || ! $params['is_visible'] ? ' style="display:none;"' : '', $message, $dismiss_link );
	}


	/**
	 * Render the javascript to handle the notice "dismiss" functionality
	 *
	 * @since 3.0.0
	 */
	public function render_admin_notice_js() {

		// if there were no notices, or we've already rendered the js, there's nothing to do
		if ( empty( $this->admin_notices ) || self::$admin_notice_js_rendered ) {
			return;
		}

		self::$admin_notice_js_rendered = true;

		ob_start();
		?>
		// hide notice
		$( 'a.js-wc-plugin-framework-notice-dismiss' ).click( function() {

			$.get(
				ajaxurl,
				{
					action: 'wc_plugin_framework_' + $( this ).closest( '.js-wc-plugin-framework-admin-notice' ).data( 'plugin-id') + '_dismiss_notice',
					messageid: $( this ).data( 'message-id' )
				}
			);

			$( this ).closest( 'div.js-wc-plugin-framework-admin-notice' ).fadeOut();

			return false;
		} );

		// move any delayed notices up into position .show();
		$( '.js-wc-plugin-framework-admin-notice:hidden' ).insertAfter( '.js-wc-plugin-framework-admin-notice-placeholder' ).show();
		<?php
		$javascript = ob_get_clean();

		wc_enqueue_js( $javascript );
	}


	/**
	 * Marks the identified admin notice as dismissed for the given user
	 *
	 * @since 3.0.0
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return boolean true if the message has been dismissed by the admin user
	 */
	public function dismiss_notice( $message_id, $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$dismissed_notices = $this->get_dismissed_notices( $user_id );

		$dismissed_notices[ $message_id ] = true;

		update_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', $dismissed_notices );

		/**
		 * Admin Notice Dismissed Action.
		 *
		 * Fired when a user dismisses an admin notice.
		 *
		 * @since 3.0.0
		 * @param string $message_id notice identifier
		 * @param string|int $user_id
		 */
		do_action( 'wc_' . $this->get_plugin()->get_id(). '_dismiss_notice', $message_id, $user_id );
	}


	/**
	 * Marks the identified admin notice as not dismissed for the identified user
	 *
	 * @since 3.0.0
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 */
	public function undismiss_notice( $message_id, $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$dismissed_notices = $this->get_dismissed_notices( $user_id );

		$dismissed_notices[ $message_id ] = false;

		update_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', $dismissed_notices );
	}


	/**
	 * Returns true if the identified admin notice has been dismissed for the
	 * given user
	 *
	 * @since 3.0.0
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return boolean true if the message has been dismissed by the admin user
	 */
	public function is_notice_dismissed( $message_id, $user_id = null ) {

		$dismissed_notices = $this->get_dismissed_notices( $user_id );

		return isset( $dismissed_notices[ $message_id ] ) && $dismissed_notices[ $message_id ];
	}


	/**
	 * Returns the full set of dismissed notices for the user identified by
	 * $user_id, for this plugin
	 *
	 * @since 3.0.0
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return array of message id to dismissed status (true or false)
	 */
	public function get_dismissed_notices( $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$dismissed_notices = get_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', true );

		if ( empty( $dismissed_notices ) ) {
			return array();
		} else {
			return $dismissed_notices;
		}
	}


	/** AJAX methods ******************************************************/


	/**
	 * Dismiss the identified notice
	 *
	 * @since 3.0.0
	 */
	public function handle_dismiss_notice() {

		$this->dismiss_notice( $_REQUEST['messageid'] );

	}


	/** Getter methods ******************************************************/


	/**
	 * Get the plugin
	 *
	 * @since 3.0.0
	 * @return SV_WC_Plugin returns the plugin instance
	 */
	protected function get_plugin() {
		return $this->plugin;
	}

}

endif; // Class exists check
