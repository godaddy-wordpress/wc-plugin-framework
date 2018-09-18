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
 * @copyright Copyright (c) 2013-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_2_2\Plugin;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_2_2 as Framework;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_2_2\\Plugin\\Connection_Handler' ) ) :

/**
 * The Connection Handler.
 *
 * This class is responsible to provide a common standard for plugins that need to connect to an external service (typically an API) to function.
 * Child implementations should use this class to provide common methods to connect to the external service and retrieve the connection state.
 *
 * @since 5.3.0-dev
 */
abstract class Connection_Handler {


	/** @var Framework\SV_WC_Plugin main plugin class */
	private $plugin;

	/** @var bool whether the plugin is connected to an external service */
	protected $is_connected;


	/**
	 * Sets up the Connection Handler.
	 *
	 * @since 5.3.0-dev
	 *
	 * @param Framework\SV_WC_Plugin $plugin main plugin class
	 */
	public function __construct( Framework\SV_WC_Plugin $plugin ) {

		// parent plugin
		$this->plugin = $plugin;
	}


	/**
	 * Gets the name of the service the plugin connects to.
	 *
	 * @since 5.3.0-dev.
	 *
	 * @return string e.g. "Google", "MailChimp", etc.
	 */
	abstract public function get_service_name();


	/**
	 * Connects the plugin to an external service.
	 *
	 * Child classes should implement the necessary logic (e.g. connect to an API) before returning the parent method.
	 *
	 * @since 5.3.0-dev
	 *
	 * @param null|mixed|array $args optional arguments that implementations could use to pass crendentials for connecting
	 * @return bool
	 */
	public function connect( $args = null ) {

		$this->is_connected = true;

		return $this->is_connected;
	}


	/**
	 * Disconnects the plugin from an external service.
	 *
	 * Child classes should implement the necessary logic (e.g. disconnect from an API) before returning the parent method.
	 *
	 * @since 5.3.0-dev
	 *
	 * @param null|mixed|array $args optional arguments that may be required when disconnecting
	 * @return bool
	 */
	public function disconnect( $args = null ) {

		$this->is_connected = false;

		return $this->is_connected;
	}


	/**
	 * Determines whether the plugin is connected to an external service.
	 *
	 * @since 5.3.0-dev
	 *
	 * @param null|mixed|array $args optional argument that could be used in implementations when a plugin may connect to multiple services
	 * @return bool
	 */
	public function is_connected( $args = null ) {

		return true === $this->is_connected;
	}


	/**
	 * Determines whether the plugin is disconnected from an external service.
	 *
	 * @since 5.3.0-dev
	 *
	 *
	 * @param null|mixed|array $args optional argument that could be used in implementations when a plugin may connect to multiple services
	 * @return bool
	 */
	public function is_disconnected( $args = null ) {

		return ! $this->is_connected();
	}


	/**
	 * Gets a connection error message.
	 *
	 * The error should point out why the connection failed.
	 *
	 * @since 1.1.0-dev.1
	 *
	 * @param mixed|array $args optional arguments
	 * @return string
	 */
	abstract public function get_connection_error( $args = null );


	/**
	 * Gets the plugin main instance.
	 *
	 * @since 5.3.0-dev
	 *
	 * @return Framework\SV_WC_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Gets the documentation URL to help setting a connection with the service.
	 *
	 * The abstract method returns the plugin's documentation URL by default, but this could be a subsection of the general documentation, or a different page.
	 *
	 * @since 5.3.0-dev
	 *
	 * @return string URL
	 */
	public function get_documentation_url() {

		return $this->get_plugin()->get_documentation_url();
	}


}

endif;