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
	private static $admin_notice_placeholder_rendered = false;

	/** @var boolean static member to enforce a single rendering of the admin notice javascript */
	private static $admin_notice_js_rendered = false;

	/** @var string option key */
	private static $admin_note_lock = 'sv_wc_admin_create_note_lock';


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

		// deletes an action that has been dismissed via dismiss action button
		add_action( 'woocommerce_admin_note_action',         [ $this, 'delete_admin_note'] );
		add_action( 'woocommerce_admin_note_action_dismiss', [ $this, 'delete_admin_note' ] );
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

		if ( self::should_use_admin_notes() ) {

			$args = wp_parse_args( $args, [
				'is_snoozable'  => empty( $args['dismissible'] ),
				'type'          => self::normalize_notice_type( $args ),
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
					$datetime             = new \DateTime( date( 'Y-m-d H:i:s', current_time( 'timestamp', true ) ), new \DateTimeZone( 'UTC' ) );
					$args['date_created'] = $datetime->format( 'c' );
				} catch ( \Exception $e ) {
					$args['date_created'] = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
				}
			}

		} else {

			$args = wp_parse_args( $args, [
				'always_show_on_settings' => true,
				'dismissible'             => isset( $args['is_snoozable'] ) ? (bool) $args['is_snoozable'] : true,
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
			'success',
			'warning',
			'notice',
			'notice-error',
			'notice-info',
			'notice-success',
			'notice-warning',
			'updated',
		];

		if ( self::should_use_admin_notes() ) {

			$note           = new \WC_Admin_Note();
			$accepted_types = $note::get_allowed_types();
			$default_type   = $note::E_WC_ADMIN_NOTE_INFORMATIONAL;

			// map legacy types
			switch ( $notice_type ) {
				case 'notice-error' :
					$notice_type = $note::E_WC_ADMIN_NOTE_ERROR;
				break;
				case 'notice-warning' :
					$notice_type = $note::E_WC_ADMIN_NOTE_WARNING;
				break;
				case 'notice-updated' :
				case 'updated' :
					$notice_type = $note::E_WC_ADMIN_NOTE_UPDATE;
				break;
			}
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
	 * @param string $content note content
	 * @param string $name note slug identifier
	 * @param array $args note arguments
	 * @return int|null the added note ID on success, null on failure
	 */
	public function add_admin_note( $content, $name = '', array $args = [] ) {

		update_option( self::$admin_note_lock, true );

		$note = new \WC_Admin_Note();
		$args = wp_parse_args( self::normalize_notice_arguments( $args ), [
			'name'         => empty( trim( $name ) ) ? uniqid( $this->get_plugin()->get_id_dasherized(), false ) : $name,
			'title'        => $this->get_plugin()->get_plugin_name(),
			'content'      => $content,
			'status'       => $note::E_WC_ADMIN_NOTE_UNACTIONED,
			'source'       => $this->get_plugin()->get_id_dasherized(),
		] );

		foreach ( $args as $prop => $data ) {

			$set_prop = "set_{$prop}";

			if ( 'actions' !== $prop && is_callable( [ $note, $set_prop ] ) ) {
				$note->$set_prop( $data );
			}
		}

		// maybe set an action to dismiss the note
		if ( ! isset( $args['actions'] ) && $note::E_WC_ADMIN_NOTE_UNACTIONED === $note->get_status() && empty( $note->get_actions() )  ) {

			$is_dismissible = ! isset( $args['dismissible'] ) || true === $args['dismissible'];

			if ( $is_dismissible ) {
				$note->add_action( 'dismiss', __( 'Dismiss', 'woocommerce-plugin-framework' ) );
			}

		} elseif ( ! empty( $args['actions'] ) && is_array( $args['actions'] ) ) {

			foreach ( $args['actions'] as $action ) {

				$action = wp_parse_args( $action, [
					'name'    => '',
					'label'   => '',
					'url'     => '',
					'status'  => $note::E_WC_ADMIN_NOTE_ACTIONED,
					'primary' => false,
				] );

				$note->add_action( $action['name'], $action['label'], $action['url'], $action['status'], $action['primary'] );
			}
		}

		$note_id = $note->save();

		delete_option( self::$admin_note_lock );

		return $note_id;
	}


	/**
	 * Gets an admin note by ID or name.
	 *
	 * @since 5.4.1-dev.1
	 *
	 * @param int|string|\WC_Admin_Note $note note ID or name
	 * @return \WC_Admin_Note
	 */
	public function get_admin_note( $note ) {

		// introduce recursion to avoid race conditions
		if ( get_option( self::$admin_note_lock ) ) {
			return $this->get_admin_note( $note );
		}

		$found_note = $note;

		if ( is_int( $note ) ) {

			$found_note = \WC_Admin_Notes::get_note( $note );

		} elseif ( is_string( $note ) ) {

			try {
				/** @var \WC_Admin_Notes_Data_Store $data_store check if an identical note already exists in db */
				$data_store = \WC_Data_Store::load( 'admin-note' );
				$found_note = $data_store ? $data_store->get_notes_with_name( $note ) : null;
			} catch ( \Exception $e ) {
				$found_note = null;
			}

			if ( is_array( $found_note ) ) {
				$found_note = current( $found_note );
			}

			if ( is_numeric( $found_note ) ) {
				$found_note = new \WC_Admin_Note( $found_note );
			}
		}

		return $found_note instanceof \WC_Admin_Note ? $found_note : null;
	}


	/**
	 * Gets admin notes for the current plugin.
	 *
	 * @see \WC_Admin_Notes::get_notes()
	 * @see \WC_Admin_Notes_Data_Store::get_notes()
	 *
	 * @since 5.4.1-dev.1
	 *
	 * @param array $args array of arguments
	 * @param string $context optional, view or edit
	 * @return \WC_Admin_Note[] associative array of note names and objects
	 */
	public function get_admin_notes( array $args = [], $context = 'view' ) {

		if ( get_option( self::$admin_note_lock ) ) {
			return $this->get_admin_notes( $args, $context );
		}

		$notes = [];
		$args  = wp_parse_args( $args, [
			'per_page' => PHP_INT_MAX,
			'source'   => $this->get_plugin()->get_id_dasherized(),
		] );

		/** @var \WC_Admin_Note[] $results */
		$results = \WC_Admin_Notes::get_notes( $context, $args );

		if ( ! empty( $results ) ) {
			foreach ( $results as $note ) {
				if ( $args['source'] !== $note->get_source() ) {
					$notes[ $note->get_name() ] = $note;
				}
			}
		}

		return $notes;
	}


	/**
	 * Deletes an admin note left by the current plugin.
	 *
	 * @since 5.4.1-dev.1
	 *
	 * @param int|string|\WC_Admin_Note $note admin note by ID or name
	 * @param string $action current action (optional argument set in hook)
	 * @return bool success
	 */
	public function delete_admin_note( $note, $action = '' ) {

		if ( get_option( self::$admin_note_lock ) ) {
			return $this->delete_admin_note( $note, $action );
		}

		$note   = $this->get_admin_note( $note );
		$delete = false;

		if ( $note && $this->get_plugin()->get_id_dasherized() === $note->get_source() ) {

			switch( current_action() ) {
				case 'woocommerce_admin_note_action':
					$has_dismiss_action = true;
				break;
				case 'woocommerce_admin_note_action_dismiss' :
					$has_dismiss_action = true;
					$action             = 'dismiss';
				break;
				default :
					$has_dismiss_action = false;
				break;
			}

			if ( ! $has_dismiss_action || ( $has_dismiss_action && 'dismiss' === $action ) ) {
				$delete = $note->delete( true );
			}
		}

		return $delete;
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

		if ( self::should_use_admin_notes() ) {

			if ( ! $this->get_admin_note( $message_id ) ) {
				$this->add_admin_note( $message, $message_id, $args );
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
	 * Determines whether admin note should be used instead of admin notices.
	 *
	 * @since 5.4.1-dev.1
	 *
	 * @return bool
	 */
	private static function should_use_admin_notes() {
		global $current_screen;

		// keep displaying notices on the WordPress plugins page
		if ( $current_screen && 'plugins' === $current_screen->id ) {
			$use_admin_notes = false;
		} else {
			$use_admin_notes = SV_WC_Plugin_Compatibility::is_wc_admin_available();
		}

		return $use_admin_notes;
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

		$display = false;

		if ( self::should_use_admin_notes() ) {

			$note    = $this->get_admin_note( $message_id );
			$display = ! $note || ! $this->is_notice_dismissed( $note->get_id() );

		} elseif ( current_user_can( 'manage_woocommerce' ) ) {

			$args = self::normalize_notice_arguments( $args );

			if ( $args['always_show_on_settings'] && $this->get_plugin()->is_plugin_settings() ) {
				// if the notice is always shown on the settings page, and we're on the settings page
				$display = true;
			} elseif ( ! $args['dismissible'] ) {
				// non-dismissible, always display
				$display = true;
			} else {
				// dismissible: display if notice has not been dismissed
				$display = ! $this->is_notice_dismissed( $message_id );
			}
		}

		return $display;
	}


	/**
	 * Renders any admin notices, as well as the admin notice placeholder.
	 *
	 * @since 3.0.0
	 *
	 * @param bool
	 */
	public function render_admin_notices( $is_visible = true ) {

		if ( self::should_use_admin_notes() ) {
			return;
		}

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

		if ( self::should_use_admin_notes() ) {
			return;
		}

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

		// bail if any of the following is true:
		// - there are no notices to display
		// - notices JavaScript code was already rendered
		// - admin notes are being used in place of admin notices
		if ( empty( $this->admin_notices ) || self::$admin_notice_js_rendered || self::should_use_admin_notes() ) {
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

		if ( self::should_use_admin_notes() ) {

			if ( $found_note = $this->get_admin_note( $message_id ) ) {

				$found_note->set_status( $found_note::E_WC_ADMIN_NOTE_ACTIONED );
				$found_note->save();
			}

		} else {

			if ( null === $user_id ) {
				$user_id = get_current_user_id();
			}

			$dismissed_notices = $this->get_dismissed_notices( $user_id );

			$dismissed_notices[ $message_id ] = true;

			update_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', $dismissed_notices );

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

		if ( self::should_use_admin_notes() ) {

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

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// always check for legacy notices first
		$dismissed_notices = get_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', true );
		$is_dismissed      = is_array( $dismissed_notices ) && isset( $dismissed_notices[ $message_id ] ) && $dismissed_notices[ $message_id ];

		// this avoids introducing notes which have been previously dismissed as notices, such as milestones
		if ( ! $is_dismissed && self::should_use_admin_notes() ) {
			$found_note   = $this->get_admin_note( $message_id );
			$is_dismissed = $found_note && in_array( $found_note->get_status(), [ $found_note::E_WC_ADMIN_NOTE_ACTIONED, $found_note::E_WC_ADMIN_NOTE_SNOOZED ], true );
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

		// get legacy admin notices first
		$items = get_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_plugin()->get_id() . '_dismissed_messages', true );
		$items = is_array( $items ) ? $items : [];

		if ( self::should_use_admin_notes() ) {

			$notes = $this->get_admin_notes();

			foreach ( $notes as $note ) {
				if ( ! array_key_exists( $note->get_name(), $notes ) ) {
					$items[ $note->get_name() ] = $this->is_notice_dismissed( $note->get_id() );
				}
			}
		}

		return $items;
	}


	/** AJAX methods ******************************************************/


	/**
	 * Dismisses the identified notice.
	 *
	 * @internal
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
