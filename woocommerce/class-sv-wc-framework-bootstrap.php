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

if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) :


/**
 * # SkyVerge WooCommerce Plugin Framework Bootstrap
 *
 * The purpose of this class is to find and load the highest versioned
 * framework of the activated framework plugins, and then initialize any
 * compatible framework plugins.
 *
 * ## Usage
 *
 * To use, simply load this class and add a call like the following to the top
 * of your main plugin file:
 *
 * <code>
 * // Required library classss
 * if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
 *   require_once( 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
 * }
 *
 * SV_WC_Framework_Bootstrap::instance()->register_plugin( '2.2.0', __( 'WooCommerce My Plugin', 'woocommerce-my-plugin' ), __FILE__, 'init_woocommerce_my_plugin', array( 'minimum_wc_version' => '2.2' ) );
 *
 * ...
 *
 * function init_woocommerce_my_plugin() {
 *   declare and instantiate the plugin class...
 * </code>
 *
 * Where the first argument is the framework version of the plugin, the next
 * argument is the plugin name, the next argument is the plugin file, and the
 * final argument is an initialization callback.
 *
 * The initialization callback should declare the plugin main class file and
 * instantiate.
 *
 * ### Optional Parameters
 *
 * The `register_plugin()` call also supports an optional associative array of
 * arguments.  Currently supported arguments are:
 *
 * + `is_payment_gateway` - Set to true if this is a payment gateway, to load the payment gateway framework files
 * + `backwards_compatible` - Set to a version number to declare backwards compatibility support from that version number (and hence no support for earlier versions).
 * + `minimum_wc_version` - Set to a version number to require a minimum WooCommerce version for the given plugin
 *
 * ### Backwards Compatibility
 *
 * By architecting framework releases to be compatible with previous versions
 * we buy ourselves a lot of flexibility in terms of releasing individual
 * plugins with updated versions of the framework, so this should be the goal
 * whenever reasonable.
 *
 * If a breaking change is required (for instance changing the visibility of a
 * method from `protected` to `public`), backwards compatibility support can be
 * specified with the `backwards_compatible` optional parameter described in the
 * previous section.  Any framework plugin that does not meet or exceed this backwards
 * compatible version will not be initialized, and an admin error notice
 * requiring an update will be rendered.
 *
 * If the current release of the framework changes the declared backwards
 * compatibility then *all* framework plugins must be released with this
 * version or better, so that customers can update and use the plugins.
 *
 * ### Action Considerations
 *
 * Because the frameworked plugins aren't actually instantiated until after the
 * `plugins_loaded` action, that plus any actions that are fired before it are
 * ineligible for frameworked plugins to hook onto (this includes `woocommerce_loaded`).
 * Framework plugins that need to hook onto these actions may instead use the
 * `sv_wc_framework_plugins_loaded` action which is fired after all framework
 * plugins are loaded.
 *
 * @since 2.0.0
 */
class SV_WC_Framework_Bootstrap {


	/** @var SV_WC_Framework_Bootstrap The single instance of the class */
	protected static $instance = null;

	/** @var array registered framework plugins */
	protected $registered_plugins = array();

	/** @var array of plugins that need to be updated due to an outdated framework */
	protected $incompatible_framework_plugins = array();

	/** @var array of plugins that require a newer version of WC */
	protected $incompatible_wc_version_plugins = array();


	/**
	 * Hidden constructor
	 *
	 * @since 2.0.0
	 */
	private function __construct() {

		// load framework plugins once all plugins are loaded
		add_action( 'plugins_loaded', array( $this, 'load_framework_plugins' ) );
	}


	/**
	 * Instantiate the class singleton
	 *
	 * @since 2.0.0
	 * @return SV_WC_Framework_Bootstrap singleton instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Register a frameworked plugin
	 *
	 * @since 2.0.0
	 * @param string $version the framework version
	 * @param string $plugin_name the plugin name
	 * @param string $path the plugin path
	 * @param callable $callback function to initialize the plugin
	 * @param array $args optional plugin arguments.  Possible arguments: 'is_payment_gateway', 'backwards_compatible'
	 */
	public function register_plugin( $version, $plugin_name, $path, $callback, $args = array() ) {
		$this->registered_plugins[] = array( 'version' => $version, 'plugin_name' => $plugin_name, 'path' => $path, 'callback' => $callback, 'args' => $args );
	}


	/**
	 * Loads all registered framework plugins, first initializing the plugin
	 * framework by loading the highest versioned one.
	 *
	 * @since 2.0.0
	 */
	public function load_framework_plugins() {

		// first sort the registered plugins by framework version
		usort( $this->registered_plugins, array( $this, 'compare_frameworks' ) );

		$loaded_framework = null;

		foreach ( $this->registered_plugins as $plugin ) {

			// load the first found (highest versioned) plugin framework class
			if ( ! class_exists( 'SV_WC_Plugin' ) ) {
				require_once( $this->get_plugin_path( $plugin['path'] ) . '/lib/skyverge/woocommerce/class-sv-wc-plugin.php' );
				$loaded_framework = $plugin;
			}

			// if the loaded version of the framework has a backwards compatibility requirement
			//  which is not met by the current plugin add an admin notice and move on without
			//  loading the plugin
			if ( ! empty( $loaded_framework['args']['backwards_compatible'] ) && version_compare( $loaded_framework['args']['backwards_compatible'], $plugin['version'], '>' ) ) {

				$this->incompatible_framework_plugins[] = $plugin;

				// next plugin
				continue;
			}

			// if a plugin defines a minimum WC version, render a notice and skip loading the plugin
			if ( ! empty( $plugin['args']['minimum_wc_version'] ) && version_compare( $this->get_wc_version(), $plugin['args']['minimum_wc_version'], '<' ) ) {

				$this->incompatible_wc_version_plugins[] = $plugin;

				// next plugin
				continue;
			}

			// load the first found (highest versioned) payment gateway framework class, as needed
			if ( isset( $plugin['args']['is_payment_gateway'] ) && ! class_exists( 'SV_WC_Payment_Gateway' ) ) {
				require_once( $this->get_plugin_path( $plugin['path'] ) . '/lib/skyverge/woocommerce/payment-gateway/class-sv-wc-payment-gateway-plugin.php' );
			}

			// initialize the plugin
			$plugin['callback']();
		}

		// render update notices
		if ( ( $this->incompatible_framework_plugins || $this->incompatible_wc_version_plugins ) && is_admin() && ! defined( 'DOING_AJAX' ) && ! has_action( 'admin_notices', array( $this, 'render_update_notices' ) ) ) {

			add_action( 'admin_notices', array( $this, 'render_update_notices' ) );
		}

		// frameworked plugins can hook onto this action rather than 'plugins_loaded'/'woocommerce_loaded' when necessary
		do_action( 'sv_wc_framework_plugins_loaded' );
	}


	/** Admin methods ******************************************************/


	/**
	 * Render a notice to update any plugins with incompatible framework
	 * versions
	 *
	 * Note that no localization is available because there's no text domain
	 * for the bootstrap.
	 *
	 * @since 2.0.0
	 */
	public function render_update_notices() {

		// must update plugin notice
		if ( ! empty( $this->incompatible_framework_plugins ) ) {

			printf( '<div class="error"><p>%s</p><ul>', count( $this->incompatible_framework_plugins ) > 1 ? 'The following plugins are inactive because they require a newer version to function properly:' : 'The following plugin is inactive because it requires a newer version to function properly:' );

			foreach ( $this->incompatible_framework_plugins as $plugin ) {
				printf( '<li>%s</li>', $plugin['plugin_name'] );
			}

			echo '</ul><p>Please <a href="' . admin_url( 'update-core.php' ) . '">update&nbsp;&raquo;</a></p></div>';
		}

		// must update WC notice
		if ( ! empty( $this->incompatible_wc_version_plugins ) ) {

			printf( '<div class="error"><p>%s</p><ul>', count( $this->incompatible_wc_version_plugins ) > 1 ? 'The following plugins are inactive because they require a newer version of WooCommerce:' : 'The following plugin is inactive because it requires a newer version of WooCommerce:' );

			foreach ( $this->incompatible_wc_version_plugins as $plugin ) {
				printf( '<li>%s requires WooCommerce %s or newer</li>', $plugin['plugin_name'], $plugin['args']['minimum_wc_version'] );
			}

			echo '</ul><p>Please <a href="' . admin_url( 'update-core.php' ) . '">update WooCommerce&nbsp;&raquo;</a></p></div>';
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Compare the two framework versions.  Returns -1 if $a is less than $b, 0 if
	 * they're equal, and 1 if $a is greater than $b
	 *
	 * @since 2.0.0
	 * @param array $a first registered plugin to compare
	 * @param array $b second registered plugin to compare
	 * @return int -1 if $a is less than $b, 0 if they're equal, and 1 if $a is greater than $b
	 */
	public function compare_frameworks( $a, $b ) {
		// compare versions without the operator argument, so we get a -1, 0 or 1 result
		return version_compare( $b['version'], $a['version'] );
	}


	/**
	 * Returns the plugin path for the given $file
	 *
	 * @since 2.0.0
	 * @param string $file the file
	 * @return string plugin path
	 */
	public function get_plugin_path( $file ) {
		return untrailingslashit( plugin_dir_path( $file ) );
	}


	/**
	 * Returns the WooCommerce version number, backwards compatible to
	 * WC 1.5
	 *
	 * @since 3.0.0
	 * @return null|string
	 */
	private function get_wc_version() {

		if ( defined( 'WC_VERSION' )          && WC_VERSION )          return WC_VERSION;
		if ( defined( 'WOOCOMMERCE_VERSION' ) && WOOCOMMERCE_VERSION ) return WOOCOMMERCE_VERSION;

		return null;
	}

}


// instantiate the class
SV_WC_Framework_Bootstrap::instance();

endif;
