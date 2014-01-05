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

if ( ! class_exists( 'SV_WC_Plugin' ) ) :

/**
 * # WooCommerce Plugin Framework
 *
 * This framework class provides a base level of configurable and overrideable
 * functionality and features suitable for the implementation of a WooCommerce
 * plugin.  This class handles all the "non-feature" support tasks such
 * as verifying dependencies are met, loading the text domain, etc.
 *
 * ## Usage
 *
 * Extend this class and implement the following abstract methods:
 *
 * + `get_file()` - the implementation should be: <code>return __FILE__;</code>
 * + `get_plugin_name()` - returns the plugin name (implemented this way so it can be localized)
 * + `load_translation()` - load the plugin text domain
 *
 * Optional Methods to Override:
 *
 * + `__construct()` - If overriding the constructor, you must call the parent constructor, followed by a version check:
 *   // ensure the minimum version requirement is met
 *   if ( ! $this->check_version( self::MINIMUM_FRAMEWORK_VERSION ) )
 *     return;
 * + `is_plugin_settings()` - if the plugin has an admin settings page you can return true when on it
 * + `get_settings_url()` - return the plugin admin settings URL, if any
 * + `render_admin_notices()` - override to perform custom admin plugin requirement checks (defaults to checking for php extension depenencies).  Use the is_message_dismissed() and add_dismissible_notice() methods
 *
 * @version 1.0-1
 */
abstract class SV_WC_Plugin {

	/** Plugin Framework Version */
	const VERSION = '1.0-1';

	/** @var string plugin id */
	private $id;

	/** @var string plugin text domain */
	protected $text_domain;

	/** @var string version number */
	private $version;

	/** @var string plugin path without trailing slash */
	private $plugin_path;

	/** @var string plugin uri */
	private $plugin_url;

	/** @var \WC_Logger instance */
	private $logger;

	/** @var array string names of required PHP extensions */
	private $dependencies = array();

	/** @var boolean whether a dismissible notice has been rendered */
	private $dismissible_notice_rendered = false;


	/**
	 * Initialize the plugin
	 *
	 * Optional args:
	 *
	 * + `dependencies` - array string names of required PHP extensions
	 *
	 * Child plugin classes may add their own optional arguments
	 *
	 * @since 1.0-1
	 * @param string $minimum_version the minimum Framework version required by the concrete plugin
	 * @param string $id plugin id
	 * @param string $version plugin version number
	 * @param string $text_domain the plugin text domain
	 * @param array $args optional plugin arguments
	 */
	public function __construct( $minimum_version, $id, $version, $text_domain, $args = array() ) {

		// required params
		$this->id          = $id;
		$this->version     = $version;
		$this->text_domain = $text_domain;

		// check that the current version of the framework meets the minimum
		//  required by the concrete plugin.

		if ( ! $this->check_version( $minimum_version ) ) {

			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

				// render any admin notices
				add_action( 'admin_notices', array( $this, 'render_minimum_version_notice' ) );

				// AJAX handler to dismiss any warning/error notices
				add_action( 'wp_ajax_wc_plugin_framework_' . $this->get_id() . '_dismiss_message', array( $this, 'handle_dismiss_message' ) );

			}

			return;
		}

		if ( isset( $args['dependencies'] ) )       $this->dependencies = $args['dependencies'];

		// include library files after woocommerce is loaded
		add_action( 'woocommerce_loaded', array( $this, 'lib_includes' ) );

		// includes that are required to be available at all times
		$this->includes();

		// Admin
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			// render any admin notices
			add_action( 'admin_notices', array( $this, 'render_admin_notices'               ), 10 );
			add_action( 'admin_notices', array( $this, 'render_admin_dismissible_notice_js' ), 15 );

			// add a 'Configure' link to the plugin action links
			add_filter( 'plugin_action_links_' . plugin_basename( $this->get_file() ), array( $this, 'plugin_action_links' ) );

			// run every time
			$this->do_install();
		}

		// AJAX handler to dismiss any warning/error notices
		add_action( 'wp_ajax_wc_plugin_framework_' . $this->get_id() . '_dismiss_message', array( $this, 'handle_dismiss_message' ) );

		// Load translation files
		add_action( 'init', array( $this, 'load_translation' ) );
	}


	/**
	 * Load plugin text domain.  This implementation should look simply like:
	 *
	 * load_plugin_textdomain( 'text-domain-string', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	 *
	 * Note that the actual text domain string should be used, and not a
	 * variable or constant, otherwise localization plugins (Codestyling) will
	 * not be able to detect the localization directory.
	 *
	 * @since 1.0
	 */
	abstract public function load_translation();


	/**
	 * Include required library files
	 *
	 * @since 1.0-1
	 */
	public function lib_includes() {
		// stub method
	}


	/**
	 * Include any critical files which must be available as early as possible
	 *
	 * @since 1.0-1
	 */
	private function includes() {
		require_once( 'class-wc-plugin-compatibility.php' );
	}


	/** Admin methods ******************************************************/


	/**
	 * Returns true if on the admin plugin settings page, if any
	 *
	 * @since 1.0-1
	 * @return boolean true if on the admin plugin settings page
	 */
	public function is_plugin_settings() {
		// optional method, not all plugins *have* a settings page
		return false;
	}


	/**
	 * Checks if required PHP extensions are loaded and adds an admin notice
	 * for any missing extensions.  Also plugin settings can be checked
	 * as well.
	 *
	 * @since 1.0-1
	 */
	public function render_admin_notices() {

		// notices for any missing dependencies
		$this->render_dependencies_admin_notices();
	}


	/**
	 * Checks if required PHP extensions are not loaded and adds a dismissible admin
	 * notice if so.  Notice will not be rendered to the admin user once dismissed
	 * unless on the plugin settings page, if any
	 *
	 * @since 1.0-1
	 * @see SV_WC_Plugin::render_admin_notices()
	 */
	protected function render_dependencies_admin_notices() {

		// report any missing extensions
		$missing_extensions = $this->get_missing_dependencies();

		if ( count( $missing_extensions ) > 0 && ( ! $this->is_message_dismissed( 'missing-extensions' ) || $this->is_plugin_settings() ) ) {

			$message = sprintf(
				_n(
					'%s requires the %s PHP extension to function.  Contact your host or server administrator to configure and install the missing extension.',
					'%s requires the following PHP extensions to function: %s.  Contact your host or server administrator to configure and install the missing extensions.',
					count( $missing_extensions ),
					$this->text_domain
				),
				$this->get_plugin_name(),
				'<strong>' . implode( ', ', $missing_extensions ) . '</strong>'
			);

			$this->add_dismissible_notice( $message, 'missing-extensions' );

		}
	}


	/**
	 * Adds the given $message as a dismissible notice identified by $message_id
	 *
	 * @since 1.0-1
	 */
	public function add_dismissible_notice( $message, $message_id ) {

		// dismiss link unless we're on the plugin settings page, in which case we'll always display the notice
		$dismiss_link = sprintf( '<a href="#" class="js-wc-plugin-framework-%s-message-dismiss" data-message-id="%s">%s</a>', $this->get_id(), $message_id, __( 'Dismiss', $this->text_domain ) );

		if ( $this->is_plugin_settings() ) {
			$dismiss_link = '';
		}

		echo sprintf( '<div class="error"><p>%s %s</p></div>', $message, $dismiss_link );

		$this->dismissible_notice_rendered = true;
	}


	/**
	 * Render the javascript to handle the notice "dismiss" functionality
	 *
	 * @since 1.0-1
	 */
	public function render_admin_dismissible_notice_js() {

		// if a notice was rendered, add the javascript code to handle the notice dismiss action
		if ( ! $this->dismissible_notice_rendered ) {
			return;
		}

		ob_start();
		?>
		// hide notice
		$( 'a.js-wc-plugin-framework-<?php echo $this->get_id(); ?>-message-dismiss' ).click( function() {

			$.get(
				ajaxurl,
				{
					action: 'wc_plugin_framework_<?php echo $this->get_id(); ?>_dismiss_message',
					messageid: $( this ).data( 'message-id' )
				}
			);

			$( this ).closest( 'div.error' ).fadeOut();

			return false;
		} );
		<?php
		$javascript = ob_get_clean();

		SV_WC_Plugin_Compatibility::wc_enqueue_js( $javascript );
	}


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @since 1.0-1
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
	 */
	public function plugin_action_links( $actions ) {

		$custom_actions = array();

		// settings url(s)
		if ( $this->get_settings_link( $this->get_id() ) ) {
			$custom_actions['configure'] = $this->get_settings_link( $this->get_id() );
		}

		// documentation url if any
		if ( $this->get_documentation_url() ) {
			$custom_actions['docs'] = sprintf( '<a href="%s">%s</a>', $this->get_documentation_url(), __( 'Docs', $this->text_domain ) );
		}

		// support url
		$custom_actions['support'] = sprintf( '<a href="%s">%s</a>', 'http://support.woothemes.com/', __( 'Support', $this->text_domain ) );

		// optional review link
		if ( $this->get_review_url() ) {
			$custom_actions['review'] = sprintf( '<a href="%s">%s</a>', $this->get_review_url(), __( 'Write a Review', $this->text_domain ) );
		}

		// add the links to the front of the actions list
		return array_merge( $custom_actions, $actions );
	}


	/** AJAX methods ******************************************************/


	/**
	 * Dismiss the identified message
	 *
	 * @since 1.0-1
	 */
	public function handle_dismiss_message() {

		$this->dismiss_message( $_REQUEST['messageid'] );

	}


	/** Helper methods ******************************************************/


	/**
	 * Gets the string name of any required PHP extensions that are not loaded
	 *
	 * @since 1.0-1
	 * @return array of missing dependencies
	 */
	public function get_missing_dependencies() {

		$missing_extensions = array();

		foreach ( $this->get_dependencies() as $ext ) {

			if ( ! extension_loaded( $ext ) ) {
				$missing_extensions[] = $ext;
			}
		}

		return $missing_extensions;
	}


	/**
	 * Saves errors or messages to WooCommerce Log (woocommerce/logs/plugin-id-xxx.txt)
	 *
	 * @since 1.0-1
	 * @param string $message error or message to save to log
	 * @param string $log_id optional log id to segment the files by, defaults to plugin id
	 */
	public function log( $message, $log_id = null ) {

		if ( is_null( $log_id ) ) {
			$log_id = $this->get_id();
		}

		if ( ! is_object( $this->logger ) ) {
			$this->logger = SV_WC_Plugin_Compatibility::new_wc_logger();
		}

		$this->logger->add( $log_id, $message );

	}


	/**
	 * Marks the identified admin message as dismissed for the given user
	 *
	 * @since 1.0-1
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return boolean true if the message has been dismissed by the admin user
	 */
	protected function dismiss_message( $message_id, $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$dismissed_messages = get_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_id() . '_dismissed_messages', true );

		$dismissed_messages[ $message_id ] = true;

		update_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_id() . '_dismissed_messages', $dismissed_messages );
	}


	/**
	 * Returns true if the identified admin message has been dismissed for the
	 * given user
	 *
	 * @since 1.0-1
	 * @param string $message_id the message identifier
	 * @param int $user_id optional user identifier, defaults to current user
	 * @return boolean true if the message has been dismissed by the admin user
	 */
	protected function is_message_dismissed( $message_id, $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$dismissed_messages = get_user_meta( $user_id, '_wc_plugin_framework_' . $this->get_id() . '_dismissed_messages', true );

		return isset( $dismissed_messages[ $message_id ] ) && $dismissed_messages[ $message_id ];
	}


	/** Getter methods ******************************************************/


	/**
	 * The implementation for this abstract method should simply be:
	 *
	 * return __FILE__;
	 *
	 * @since 1.0-1
	 * @return string the full path and filename of the plugin file
	 */
	abstract protected function get_file();


	/**
	 * Returns the plugin id
	 *
	 * @since 1.0-1
	 * @return string plugin id
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the plugin id with dashes in place of underscores, and
	 * appropriate for use in frontend element names, classes and ids
	 *
	 * @since 1.0-1
	 * @return string plugin id with dashes in place of underscores
	 */
	public function get_id_dasherized() {
		return str_replace( '_', '-', $this->get_id() );
	}


	/**
	 * Returns the plugin full name including "WooCommerce", ie
	 * "WooCommerce X".  This method is defined abstract for localization purposes
	 *
	 * @since 1.0-1
	 * @return string plugin name
	 */
	abstract public function get_plugin_name();


	/**
	 * Returns the plugin version name.  Defaults to wc_{plugin id}_version
	 *
	 * @since 1.0-1
	 * @return string the plugin version name
	 */
	protected function get_plugin_version_name() {
		return 'wc_' . $this->get_id() . '_version';
	}


	/**
	 * Returns the current version of the plugin
	 *
	 * @since 1.0-1
	 * @return string plugin version
	 */
	public function get_version() {
		return $this->version;
	}


	/**
	 * Get the PHP dependencies for extension depending on the gateway being used
	 *
	 * @since 1.0-1
	 * @return array of required PHP extension names, based on the gateway in use
	 */
	protected function get_dependencies() {
		return $this->dependencies;
	}


	/**
	 * Returns the "Configure" plugin action link to go directly to the plugin
	 * settings page (if any)
	 *
	 * @since 1.0-1
	 * @see SV_WC_Plugin::get_settings_url()
	 * @param string $plugin_id optional plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin configure link
	 */
	public function get_settings_link( $plugin_id = null ) {

		$settings_url = $this->get_settings_url( $plugin_id );

		if ( $settings_url ) {
			return sprintf( '<a href="%s">%s</a>', $settings_url, __( 'Configure', $this->text_domain ) );
		}

		// no settings
		return '';
	}


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 1.0-1
	 * @see SV_WC_Plugin::get_settings_link()
	 * @param string $plugin_id optional plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {

		// stub method
		return '';
	}


	/**
	 * Gets the plugin documentation url, which defaults to:
	 * http://docs.woothemes.com/document/woocommerce-{dasherized plugin id}/
	 *
	 * @since 1.0-1
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return 'http://docs.woothemes.com/document/woocommerce-' . $this->get_id_dasherized() . '/';
	}


	/**
	 * Gets the plugin review URL, which defaults to:
	 * {product page url}#review_form
	 *
	 * @since 1.0-1
	 * @return string review url
	 */
	public function get_review_url() {

		return $this->get_product_page_url() . '#review_form';
	}


	/**
	 * Gets the skyverge.com product page URL, which defaults to:
	 * http://www.skyverge.com/product/{dasherized plugin id}/
	 *
	 * @since 1.0-1
	 * @return string skyverge.com product page url
	 */
	public function get_product_page_url() {

		return 'http://www.skyverge.com/product/' . $this->get_id_dasherized() . '/';
	}


	/**
	 * Returns the plugin's path without a trailing slash, i.e.
	 * /path/to/wp-content/plugins/plugin-directory
	 *
	 * @since 1.0-1
	 * @return string the plugin path
	 */
	public function get_plugin_path() {

		if ( $this->plugin_path ) {
			return $this->plugin_path;
		}

		return $this->plugin_path = untrailingslashit( plugin_dir_path( $this->get_file() ) );
	}


	/**
	 * Returns the plugin's url without a trailing slash, i.e.
	 * http://skyverge.com/wp-content/plugins/plugin-directory
	 *
	 * @since 1.0-1
	 * @return string the plugin URL
	 */
	public function get_plugin_url() {

		if ( $this->plugin_url ) {
			return $this->plugin_url;
		}

		return $this->plugin_url = untrailingslashit( plugins_url( '/', $this->get_file() ) );
	}


	/**
	 * Returns the woocommerce uploads path, sans trailing slash.  Oddly WooCommerce
	 * core does not provide a way to get this
	 *
	 * @since 1.0-1
	 * @return string upload path for woocommerce
	 */
	public static function get_woocommerce_uploads_path() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/woocommerce_uploads';
	}


	/**
	 * Returns the relative path to the framework image directory, with a
	 * trailing slash
	 *
	 * @since 1.0-1
	 * @return string relative path to framework image directory
	 */
	public function get_framework_image_path() {
		return 'lib/skyverge/woocommerce/assets/images/';
	}


	/**
	 * Helper function to determine whether a plugin is active
	 *
	 * @since 1.0-1
	 * @param string $plugin_name the plugin name, as the plugin-dir/plugin-class.php
	 * @return boolean true if the named plugin is installed and active
	 */
	public function is_plugin_active( $plugin_name ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( $plugin_name, $active_plugins ) || array_key_exists( $plugin_name, $active_plugins );

	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Check that the framework meets the required $minimum_version.
	 *
	 * This is done because there is a chance that a shop could have two
	 * framework plugins installed, with different versions of the framework,
	 * only one of which will be loaded (probably based on the plugin name,
	 * alphabetically).  If an older version of the framework happens to be
	 * loaded in an install with another plugin requiring a higher version, this
	 * situation could lead to fatal errors that shut the whole site down.  To
	 * guard against this, every client of the framework must verify that the
	 * currently loaded framework meets its required minimum version, and if
	 * not, an admin error message will be displayed and the plugin must not
	 * operate.
	 *
	 * @since 1.0
	 * @param string $minimum_version the minimum framework version required by the concrete plugin
	 * @return boolean true if the framework version is greater than or equal to $minimum version
	 */
	final protected function check_version( $minimum_version ) {

		// installed version lower than minimum required?
		if ( -1 === version_compare( self::VERSION, $minimum_version ) )
			return false;

		return true;
	}


	/**
	 * Render the minimum version notice
	 *
	 * @since 1.0-1
	 */
	final public function render_minimum_version_notice() {

		if ( ! $this->is_message_dismissed( 'minimum-version' ) || $this->is_plugin_settings() ) {

			// a bit hacky, but get the directory name of the plugin which happened to load the framework
			$framework_plugin = explode( '/', __FILE__ );
			$framework_plugin = $framework_plugin[ count( $framework_plugin ) - 5 ];

			$message = sprintf(
				__( '%s requires that you update %s to the latest version, in order to function.  Until then, %s will remain non-functional.', $this->text_domain ),
				'<strong>' . $this->get_plugin_name() . '</strong>',
				'<strong>' . $framework_plugin . '</strong>',
				'<strong>' . $this->get_plugin_name() . '</strong>'
			);

			$this->add_dismissible_notice( $message, 'minimum-version' );

			$this->render_admin_dismissible_notice_js();

		}

	}


	/**
	 * Handles version checking
	 *
	 * @since 1.0-1
	 */
	protected function do_install() {

		$installed_version = get_option( $this->get_plugin_version_name() );

		// installed version lower than plugin version?
		if ( version_compare( $installed_version, $this->get_version(), '<' ) ) {

			if ( ! $installed_version ) {
				$this->install();
			} else {
				$this->upgrade( $installed_version );
			}

			// new version number
			update_option( $this->get_plugin_version_name(), $this->get_version() );
		}

	}


	/**
	 * Plugin install method.  Perform any installation tasks here
	 *
	 * @since 1.0-1
	 */
	protected function install() {
		// stub
	}


	/**
	 * Plugin upgrade method.  Perform any required upgrades here
	 *
	 * @since 1.0-1
	 * @param string $installed_version the currently installed version
	 */
	protected function upgrade( $installed_version ) {
		// stub
	}


}

endif; // Class exists check
