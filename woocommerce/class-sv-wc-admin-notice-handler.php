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

namespace SkyVerge\WooCommerce\PluginFramework\v5_4_1;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_4_1\\SV_WC_Admin_Notice_Handler' ) ) :

/**
 * SkyVerge Admin Notice Handler Class.
 *
 * The purpose of this class is to provide a facility for displaying conditional (often dismissible) admin notices during a single page request.
 *
 * @since 3.0.0
 */
class SV_WC_Admin_Notice_Handler {


	/** @var SV_WC_Plugin the plugin */
	private $plugin;

	/** @var array associative array of id to notice text */
	private $admin_notices = [];

	/** @var boolean static member to enforce a single rendering of the admin notice placeholder element */
	static private $admin_notice_placeholder_rendered = false;

	/** @var boolean static member to enforce a single rendering of the admin notice javascript */
	static private $admin_notice_js_rendered = false;


	/**
	 * Initializes and sets up the admin notice Handler.
	 *
	 * @since 3.0.0
	 *
	 * @param SV_WC_Plugin $plugin main class
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		// render any admin notices, delayed notices, and
		add_action( 'admin_notices', [ $this, 'render_admin_notices'         ], 15 );
		add_action( 'admin_footer',  [ $this, 'render_delayed_admin_notices' ], 15 );
		add_action( 'admin_footer',  [ $this, 'render_admin_notice_js'       ], 20 );

		// AJAX handler to dismiss any warning/error notices
		add_action( 'wp_ajax_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismiss_notice', [ $this, 'handle_dismiss_notice' ] );
	}


	/**
	 * Normalizes admin notices arguments.
	 *
	 * @see \WC_Admin_Note::__construct() for available properties when using WooCommerce Admin
	 *
	 * @since 5.4.1-dev.1
	 *
	 * @param array $args associative array of arguments
	 * @return array
	 */
	private static function normalize_notice_arguments( $args ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_admin_available() ) {

			$args = wp_parse_args( $args, [
				'locale'       => get_locale(),
				'is_snoozable' => isset( $args['dismissible'] ) ? $args['dismissible'] : true,
				'type'         => self::normalize_notice_type( $args ),
			] );

			if ( empty( $args['icon'] ) ) {
				switch ( $args['type'] ) {
					case 'error' :
					case 'warning' :
					case 'notice' :
					case 'update' :
						$args['icon'] = 'notice';
					break;
					case 'info' :
					default :
						$args['icon'] = 'info';
					break;
				}
			}

			if ( empty( $args['date_created'] ) ) {
				try {
					$datetime             = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
					$args['date_created'] = $datetime->format( 'c' );
				} catch ( \Exception $e ) {
					$args['date_created'] = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
				}
			}

		} else {

			$args = wp_parse_args( $args, [
				'always_show_on_settings' => true,
				'dismissible'             => isset( $args['is_snoozable'] ) ? $args['is_snoozable'] : true,
				'notice_class'            => self::normalize_notice_type( $args ),
				'is_visible'              => true,
			] );
		}

		return $args;
	}


	/**
	 * Normalizes a notice class or type, according to whether WooCommerce Admin is present.
	 *
	 * @since 5.4.1-dev.1
	 *
	 * @param array $notice_args associative array of arguments
	 * @return string
	 */
	private static function normalize_notice_type( $notice_args ) {

		if ( ! empty( $notice_args['type'] ) ) {
			$notice_type = $notice_args['type'];
		} elseif ( ! empty( $notice_args['notice_class'] ) ) {
			$notice_type = $notice_args['notice_class'];
		} else {
			$notice_type = 'updated';
		}

		$default_type   = 'updated';
		$accepted_types = [
			'error',
			'info',
			'notice',
			'notice-info',
			'notice-warning',
			'updated',
		];

		if ( SV_WC_Plugin_Compatibility::is_wc_admin_available() ) {
			$note           = new \WC_Admin_Note();
			$accepted_types = $note::get_allowed_types();
			$default_type   = $note::E_WC_ADMIN_NOTE_INFORMATIONAL;
		}

		return in_array( $notice_type, $accepted_types, true ) ? $notice_type : $default_type;
	}


	/**
	 * Adds a message to be displayed as a WooCommerce Admin Note.
	 *
	 * @see \WC_Admin_Note::__construct() for available arguments
	 *
	 * @since 5.4.1-dev.1
	 *
	 * @param string $name note slug identifier
	 * @param string $content note content
	 * @param array $data additional arguments
	 * @return int the added note ID
	 */
	public function add_admin_note( $name, $content, $data ) {

		$note = new \WC_Admin_Note( self::normalize_notice_arguments( wp_parse_args( $data, [
			'name'    => $name,
			'title'   => $this->get_plugin()->get_plugin_name(),
			'content' => $content,
			'source'  => $this->get_plugin()->get_id_dasherized(),
		] ) ) );

		return $note->save();
	}


	/**
	 * Gets an admin note by ID or name.
	 *
	 * @since 5.4.1-dev.1
	 *
	 * @param int|string $message_id note ID or name
	 * @return \WC_Admin_Note
	 */
	public function get_admin_note( $message_id ) {

		if ( is_int( $message_id ) ) {

			$found_note = \WC_Admin_Notes::get_note( $message_id );

		} else {

			try {
				/** @var \WC_Admin_Notes_Data_Store $data_store check if an identical note already exists in db */
				$data_store = \WC_Data_Store::load( 'admin-note' );
				$found_note = $data_store ? $data_store->get_notes_with_name( $message_id ) : null;
			} catch ( \Exception $e ) {
				$found_note = null;
			}

			if ( is_array( $found_note ) ) {
				$found_note = current( $found_note );
			}
		}

		return $found_note instanceof \WC_Admin_Note ? $found_note : null;
	}


	/**
	 * Adds a message to be displayed as an admin notice.
	 *
	 * If WooCommerce Admin is used, the notice will be passed as an admin note instead.
	 * @see \WC_Admin_Note::__construct() for available parameters
	 * @see SV_WC_Admin_Notice_Handler::add_admin_note()
	 *
	 * @since 3.0.0
	 *
	 * @param string $message the notice message to display
	 * @param string $message_id the message id
	 * @param array $args optional arguments
	 */
	public function add_admin_notice( $message, $message_id, $args = [] ) {

		$args = self::normalize_notice_arguments( $args );

		if ( SV_WC_Plugin_Compatibility::is_wc_admin_available() ) {

			if ( ! $this->get_admin_note( $message_id ) ) {
				$this->add_admin_note( $message_id, $message, $args );
			}

		} elseif ( $this->should_display_notice( $message_id, $args ) ) {

			$this->admin_notices[ $message_id ] = [
				'message'  => $message,
				'rendered' => false,
				'params'   => $args,
			];
		}
	}


	/**
	 * Determines whether an admin notice should be displayed.
	 *
	 * This is normally the case when one of the following conditions is true:
	 * - the identified notice hasn't been cleared
	 * - the plugin settings page (where notices are always displayed)
	 *
	 * @since 3.0.0
	 *
	 * @param string $message_id the message id
	 * @param array $args optional arguments
	 * @return bool
	 */
	public function should_display_notice( $message_id, $args = [] ) {

		// bail out if user is not a shop manager
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		$args = self::normalize_notice_arguments( $args );

		// if the notice is always shown on the settings page, and we're on the settings page
		if ( $args['always_show_on_settings'] && $this->get_plugin()->is_plugin_settings() ) {
			return true;
		}

		// non-dismissible, always display
		if ( ! $args['dismissible'] ) {
			return true;
		}

		// dismissible: display if notice has not been dismissed
		return ! $this->is_notice_dismissed( $message_id );
	}


	/**
	 * Renders any admin notices, as well as the admin notice placeholder.
	 *
	 * @since 3.0.0
	 *
	 * @param bool
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
			echo '<div class="js-wc-' . esc_attr( $this->get_plugin()->get_id_dasherized() ) . '-admin-notice-placeholder"></div>';

			self::$admin_notice_placeholder_rendered = true;
		}
	}


	/**
	 * Renders any delayed admin notices, which have not yet already been rendered.
	 *
	 * @since 3.0.0
	 */
	public function render_delayed_admin_notices() {

		$this->render_admin_notices( false );
	}


	/**
	 * Renders a single admin notice.
	 *
	 * @since 3.0.0
	 *
	 * @param string $message the notice message to display
	 * @param string $message_id the message id
	 * @param array $args optional arguments
	 */
	public function render_admin_notice( $message, $message_id, $args = [] ) {

		$args    = self::normalize_notice_arguments( $args );
		$classes = [
			'notice',
			'js-wc-plugin-framework-admin-notice',
			$args['notice_class'],
		];

		// maybe make this notice dismissible
		// uses a WP core class which handles the markup and styling
		if ( $args['dismissible'] && ( ! $args['always_show_on_settings'] || ! $this->get_plugin()->is_plugin_settings() ) ) {
			$classes[] = 'is-dismissible';
		}

		printf(
			'<div class="%1$s" data-plugin-id="%2$s" data-message-id="%3$s" %4$s><p>%5$s</p></div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $this->get_plugin()->get_id() ),
			esc_attr( $message_id ),
			( ! $args['is_visible'] ) ? 'style="display:none;"' : '',
			wp_kses_post( $message )
		);
	}


	/**
	 * Renders the JavaScript to handle the notice "dismiss" functionality.
	 *
	 * @since 3.0.0
	 */
	public function render_admin_notice_js() {

		// if there were no notices, or we've already rendered the js, there's nothing to do
		if ( empty( $this->admin_notices ) || self::$admin_notice_js_rendered ) {
			return;
		}

		$plugin_slug = $this->get_plugin()->get_id_dasherized();

		self::$admin_notice_js_rendered = true;

		ob_start();

		?>
		// Log dismissed notices
		$( '.js-wc-plugin-framework-admin-notice' ).on( 'click.wp-dismiss-notice', '.notice-dismiss', function( e ) {

			var $notice = $( this ).closest( '.js-wc-plugin-framework-admin-notice' );

			log_dismissed_notice(
				$( $notice ).data( 'plugin-id' ),
				$( $notice ).data( 'message-id' )
			);

		} );

		// Log and hide legacy notices
		$( 'a.js-wc-plugin-framework-notice-dismiss' ).click( function( e ) {

			e.preventDefault();

			var $notice = $( this ).closest( '.js-wc-plugin-framework-admin-notice' );

			log_dismissed_notice(
				$( $notice ).data( 'plugin-id' ),
				$( $notice ).data( 'message-id' )
			);

			$( $notice ).fadeOut();

		} );

		function log_dismissed_notice( pluginID, messageID ) {

			$.get(
				ajaxurl,
				{
					action:    'wc_plugin_framework_' + pluginID + '_dismiss_notice',
					messageid: messageID
				}
			);
		}

		// move any delayed notices up into position .show();
		$( '.js-wc-plugin-framework-admin-notice:hidden' ).insertAfter( '.js-wc-<?php echo esc_js( $plugin_slug ); ?>-admin-notice-placeholder' ).show();
		<?php

		wc_enqueue_js( ob_get_clean() );
	}


	/**
	 * Marks the identified admin notice as dismissed for the given user.
	 *
	 * @since 3.0.0
	 *
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 */
	public function dismiss_notice( $message_id, $user_id = null ) {

		$dismissed = false;

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( SV_WC_Plugin_Compatibility::is_wc_admin_available() ) {

			if ( $found_note = $this->get_admin_note( $message_id ) ) {

				$found_note->set_status( $found_note::E_WC_ADMIN_NOTE_ACTIONED );

				$dismissed = (bool) $found_note->save();
			}

		} else {

			$dismissed_notices = $this->get_dismissed_notices( $user_id );

			$dismissed_notices[ $message_id ] = true;

			update_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', $dismissed_notices );

			$dismissed = true;
		}

		if ( $dismissed ) {

			/**
			 * Fired when a user dismisses an admin notice.
			 *
			 * @since 3.0.0
			 *
			 * @param string $message_id notice identifier
			 * @param int $user_id user identifier
			 */
			do_action( 'wc_' . $this->get_plugin()->get_id(). '_dismiss_notice', $message_id, $user_id );
		}
	}


	/**
	 * Marks the identified admin notice as not dismissed for the identified user.
	 *
	 * @since 3.0.0
	 *
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 */
	public function undismiss_notice( $message_id, $user_id = null ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_admin_available() ) {

			if ( $found_note = $this->get_admin_note( $message_id ) ) {
				$found_note->set_status( $found_note::E_WC_ADMIN_NOTE_UNACTIONED );
				$found_note->save();
			}

		} else {

			if ( null === $user_id ) {
				$user_id = get_current_user_id();
			}

			$dismissed_notices = $this->get_dismissed_notices( $user_id );

			$dismissed_notices[ $message_id ] = false;

			update_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', $dismissed_notices );
		}
	}


	/**
	 * Determines whether a notice was dismissed by a matching user.
	 *
	 * @since 3.0.0
	 *
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return bool
	 */
	public function is_notice_dismissed( $message_id, $user_id = null ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_admin_available() ) {

			if ( $found_note = $this->get_admin_note( $message_id ) ) {
				$is_dismissed = $found_note::E_WC_ADMIN_NOTE_ACTIONED === $found_note->get_status();
			} else {
				$is_dismissed = false;
			}

		} else {

			$dismissed_notices = $this->get_dismissed_notices( $user_id );
			$is_dismissed      = isset( $dismissed_notices[ $message_id ] ) && $dismissed_notices[ $message_id ];
		}

		return $is_dismissed;
	}


	/**
	 * Gets dismissed notices for a given user, for the current plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return array of notice identifiers with dismissed status (true or false)
	 */
	public function get_dismissed_notices( $user_id = null ) {

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$dismissed_notices = get_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', true );

		return empty( $dismissed_notices ) || ! is_array( $dismissed_notices ) ? [] : $dismissed_notices;
	}


	/** AJAX methods ******************************************************/


	/**
	 * Dismisses the identified notice.
	 *
	 * @since 3.0.0
	 */
	public function handle_dismiss_notice() {

		$this->dismiss_notice( $_REQUEST['messageid'] );
	}


	/** Getter methods ******************************************************/


	/**
	 * Gets the plugin main instance.
	 *
	 * @since 3.0.0
	 *
	 * @return SV_WC_Plugin returns the plugin instance
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


}

endif; // Class exists check
