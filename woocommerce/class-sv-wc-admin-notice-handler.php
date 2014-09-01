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
 * @copyright Copyright (c) 2013-2014, SkyVerge, Inc.
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
 * @since 2.2.0-2
 */
class SV_WC_Admin_Notice_Handler {


	/** @var SV_WC_Plugin the plugin */
	private $plugin;

	/** @var string plugin text domain */
	protected $text_domain;

	/** @var array associative array of id to notice text */
	private $admin_notices = array();

	/** @var boolean static member to enforce a single rendering of the admin message placeholder element */
	static private $admin_message_placeholder_rendered = false;

	/** @var boolean static member to enforce a single rendering of the admin message javascript */
	static private $admin_message_js_rendered = false;


	/**
	 * Initialize and setup the Admin Notice Handler
	 *
	 * @since 2.2.0-2
	 */
	public function __construct( $plugin, $text_domain ) {

		$this->plugin      = $plugin;
		$this->text_domain = $text_domain;

		// render any admin notices, delayed notices, and
		add_action( 'admin_notices', array( $this, 'render_admin_notices'         ), 15 );
		add_action( 'admin_footer',  array( $this, 'render_delayed_admin_notices' ), 15 );
		add_action( 'admin_footer',  array( $this, 'render_admin_notice_js'       ), 20 );

		// AJAX handler to dismiss any warning/error notices
		add_action( 'wp_ajax_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismiss_message', array( $this, 'handle_dismiss_message' ) );
	}


	/**
	 * Adds the given $message as a dismissible notice identified by $message_id,
	 * unless the message has been dismissed, or we're on the plugin settings page
	 *
	 * @since 2.2.0-2
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
	 * Returns true if the identified message hasn't been cleared, or we're on
	 * the plugin settings page (where messages are always displayed)
	 *
	 * @since 2.2.0-2
	 * @param string $message_id the message id
	 * @param array $params optional parameters array.  Defaults to array( 'dismissible' => true, 'always_show_on_settings' => true )
	 */
	public function should_display_notice( $message_id, $params = array() ) {

		// default to dismissible, always on settings
		if ( ! isset( $params['dismissible'] ) ) {
			$params['dismissible'] = true;
		}
		if ( ! isset( $params['always_show_on_settings'] ) ) {
			$params['always_show_on_settings'] = true;
		}

		// if the message is always shown on the settings page, and we're on the settings page
		if ( $params['always_show_on_settings'] && $this->get_plugin()->is_plugin_settings() ) {
			return true;
		}

		// non-dismissible, always display
		if ( ! $params['dismissible'] ) {
			return true;
		}

		// dismissible: display if message has not been dismissed
		return ! $this->is_message_dismissed( $message_id );
	}


	/**
	 * Render any admin notices, as well as the admin notice placeholder
	 *
	 * @since 2.2.0-2
	 * @param boolean $is_visible true if the messages should be immediately visible, false otherwise
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

		if ( $is_visible && ! self::$admin_message_placeholder_rendered ) {
			// placeholder for moving delayed messages up into place
			echo '<div class="js-wc-plugin-framework-admin-message-placeholder"></div>';
			self::$admin_message_placeholder_rendered = true;
		}

	}


	/**
	 * Render any delayed admin notices, which have not yet already been rendered
	 *
	 * @since 2.2.0-2
	 */
	public function render_delayed_admin_notices() {
		$this->render_admin_notices( false );
	}


	/**
	 * Render a single admin notice
	 *
	 * @since 2.2.0-2
	 * @param string $message the notice message to display
	 * @param string $message_id the message id
	 * @param array $params optional parameters array.  Options: 'dismissible', 'is_visible', 'always_show_on_settings'
	 */
	public function render_admin_notice( $message, $message_id, $params = array() ) {

		$dismiss_link = '';

		// dismissible link if the message is dismissible and it's not always shown on the settings page, or we're on the settings page
		if ( isset( $params['dismissible'] ) && $params['dismissible'] && ( ! isset( $params['always_show_on_settings'] ) || ! $params['always_show_on_settings'] || ! $this->get_plugin()->is_plugin_settings() ) ) {
			$dismiss_link = sprintf( '<a href="#" class="js-wc-plugin-framework-message-dismiss" data-message-id="%s" style="float: right;">%s</a>', $message_id, __( 'Dismiss', $this->text_domain ) );
		}

		echo sprintf( '<div data-plugin-id="' . $this->get_plugin()->get_id() . '" class="error js-wc-plugin-framework-admin-message"%s><p>%s %s</p></div>', ! isset( $params['is_visible'] ) || ! $params['is_visible'] ? ' style="display:none;"' : '', $message, $dismiss_link );
	}


	/**
	 * Render the javascript to handle the notice "dismiss" functionality
	 *
	 * @since 2.2.0-2
	 */
	public function render_admin_notice_js() {

		// if there were no notices, or we've already rendered the js, there's nothing to do
		if ( empty( $this->admin_notices ) || self::$admin_message_js_rendered ) {
			return;
		}

		self::$admin_message_js_rendered = true;

		ob_start();
		?>
		// hide notice
		$( 'a.js-wc-plugin-framework-message-dismiss' ).click( function() {

			$.get(
				ajaxurl,
				{
					action: 'wc_plugin_framework_' + $( this ).closest( '.js-wc-plugin-framework-admin-message' ).data( 'plugin-id') + '_dismiss_message',
					messageid: $( this ).data( 'message-id' )
				}
			);

			$( this ).closest( 'div.error' ).fadeOut();

			return false;
		} );

		// move any delayed messages up into position .show();
		$( '.js-wc-plugin-framework-admin-message:hidden' ).insertAfter( '.js-wc-plugin-framework-admin-message-placeholder' ).show();
		<?php
		$javascript = ob_get_clean();

		wc_enqueue_js( $javascript );
	}


	/**
	 * Marks the identified admin message as dismissed for the given user
	 *
	 * @since 2.2.0-2
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return boolean true if the message has been dismissed by the admin user
	 */
	public function dismiss_message( $message_id, $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$dismissed_messages = get_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', true );

		$dismissed_messages[ $message_id ] = true;

		update_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', $dismissed_messages );

		do_action( 'wc_' . $this->get_plugin()->get_id(). '_dismiss_message', $message_id, $user_id );
	}


	/**
	 * Returns true if the identified admin message has been dismissed for the
	 * given user
	 *
	 * @since 2.2.0-2
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return boolean true if the message has been dismissed by the admin user
	 */
	public function is_message_dismissed( $message_id, $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$dismissed_messages = get_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', true );

		return isset( $dismissed_messages[ $message_id ] ) && $dismissed_messages[ $message_id ];
	}


	/** AJAX methods ******************************************************/


	/**
	 * Dismiss the identified message
	 *
	 * @since 2.2.0-2
	 */
	public function handle_dismiss_message() {

		$this->dismiss_message( $_REQUEST['messageid'] );

	}


	/** Getter methods ******************************************************/


	/**
	 * Get the plugin
	 *
	 * @since 2.2.0-2
	 * @return SV_WC_Plugin returns the plugin instance
	 */
	private function get_plugin() {
		return $this->plugin;
	}

}

endif; // Class exists check
