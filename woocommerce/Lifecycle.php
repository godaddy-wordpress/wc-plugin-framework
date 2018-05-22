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
 * @copyright Copyright (c) 2013-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_1_4\Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_1_4\\Plugin\\Lifecycle' ) ) :

/**
 * Plugin lifecycle handler.
 *
 * Registers and displays milestone notice prompts and eventually the plugin
 * install, upgrade, activation, and deactivation routines.
 *
 * @since 5.1.0
 */
class Lifecycle {


	/** @var string minimum milestone version */
	private $milestone_version;

	/** @var \SkyVerge\WooCommerce\PluginFramework\v5_1_4\SV_WC_Plugin plugin instance */
	private $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 5.1.0
	 *
	 * @param \SkyVerge\WooCommerce\PluginFramework\v5_1_4\SV_WC_Plugin $plugin plugin instance
	 */
	public function __construct( \SkyVerge\WooCommerce\PluginFramework\v5_1_4\SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->add_hooks();
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.1.0
	 */
	protected function add_hooks() {

		add_action( 'wc_' . $this->get_plugin()->get_id() . '_updated', array( $this, 'do_update' ) );

		add_action( 'init', array( $this, 'add_admin_notices' ) );

		add_action( 'wc_' . $this->get_plugin()->get_id() . '_milestone_reached', array( $this, 'trigger_milestone' ), 10, 3 );
	}


	/**
	 * Handles tasks after the plugin has been updated.
	 *
	 * @internal
	 *
	 * @since 5.1.0
	 */
	public function do_update() {

		// if the plugin never had any previous milestones, consider them all reached so their notices aren't displayed
		if ( ! $this->get_milestone_version() ) {
			$this->set_milestone_version( $this->get_plugin()->get_version() );
		}
	}


	/**
	 * Adds any lifecycle admin notices.
	 *
	 * @since 5.1.0
	 */
	public function add_admin_notices() {

		// display any milestone notices
		foreach ( $this->get_milestone_messages() as $id => $message ) {

			// bail if this notice was already dismissed
			if ( ! $this->get_plugin()->get_admin_notice_handler()->should_display_notice( $id ) ) {
				continue;
			}

			/**
			 * Filters a milestone notice message.
			 *
			 * @since 5.1.0
			 *
			 * @param string $message message text to be used for the milestone notice
			 * @param string $id milestone ID
			 */
			$message = apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_milestone_message', $this->generate_milestone_notice_message( $message ), $id );

			if ( $message ) {

				$this->get_plugin()->get_admin_notice_handler()->add_admin_notice( $message, $id, array(
					'always_show_on_settings' => false,
				) );

				// only display one notice at a time
				break;
			}
		}
	}


	/** Milestone Methods *****************************************************/


	/**
	 * Triggers a milestone.
	 *
	 * This will only be triggered if the install's "milestone version" is lower
	 * than $since. Plugins can specify $since as the version at which a
	 * milestone's feature was added. This prevents existing installs from
	 * triggering notices for milestones that have long passed, like a payment
	 * gateway's first successful payment. Omitting $since will assume the
	 * milestone has always existed and should only trigger for fresh installs.
	 *
	 * @since 5.1.0
	 *
	 * @param string $id milestone ID
	 * @param string $message message to display to the user
	 * @param string $since the version since this milestone has existed in the plugin
	 * @return bool
	 */
	public function trigger_milestone( $id, $message, $since = '1.0.0' ) {

		// if the plugin was had milestones before this milestone was added, don't trigger it
		if ( version_compare( $this->get_milestone_version(), $since, '>' ) ) {
			return false;
		}

		return $this->register_milestone_message( $id, $message );
	}


	/**
	 * Generates a milestone notice message.
	 *
	 * @since 5.1.0
	 *
	 * @param string $custom_message custom text that notes what milestone was completed.
	 * @return string
	 */
	protected function generate_milestone_notice_message( $custom_message ) {

		$message = '';

		if ( $this->get_plugin()->get_reviews_url() ) {

			// to be prepended at random to each milestone notice
			$exclamations = array(
				__( 'Awesome', 'woocommerce-plugin-framework' ),
				__( 'Fantastic', 'woocommerce-plugin-framework' ),
				__( 'Cowabunga', 'woocommerce-plugin-framework' ),
				__( 'Congratulations', 'woocommerce-plugin-framework' ),
				__( 'Hot dog', 'woocommerce-plugin-framework' ),
			);

			$message = $exclamations[ array_rand( $exclamations ) ] . ', ' . esc_html( $custom_message ) . ' ';

			$message .= sprintf(
				/* translators: Placeholders: %1$s - plugin name, %2$s - <a> tag, %3$s - </a> tag, %4$s - <a> tag, %5$s - </a> tag */
				__( 'Are you having a great experience with %1$s so far? Please consider %2$sleaving a review%3$s! If things aren\'t going quite as expected, we\'re happy to help -- please %4$sreach out to our support team%5$s.', 'woocommerce-plugin-framework' ),
				'<strong>' . esc_html( $this->get_plugin()->get_plugin_name() ) . '</strong>',
				'<a href="' . esc_url( $this->get_plugin()->get_reviews_url() ) . '">', '</a>',
				'<a href="' . esc_url( $this->get_plugin()->get_support_url() ) . '">', '</a>'
			);
		}

		return $message;
	}


	/**
	 * Registers a milestone message to be displayed in the admin.
	 *
	 * @since 5.1.0
	 * @see Lifecycle::generate_milestone_notice_message()
	 *
	 * @param string $id milestone ID
	 * @param string $message message to display to the user
	 * @return bool whether the message was successfully registered
	 */
	public function register_milestone_message( $id, $message ) {

		$milestone_messages = $this->get_milestone_messages();
		$dismissed_notices  = array_keys( $this->get_plugin()->get_admin_notice_handler()->get_dismissed_notices() );

		// get the total number of dismissed milestone messages
		$dismissed_milestone_messages = array_intersect( array_keys( $milestone_messages ), $dismissed_notices );

		// if the user has dismissed more than three milestone messages already, don't add any more
		if ( count( $dismissed_milestone_messages ) > 3 ) {
			return false;
		}

		$milestone_messages[ $id ] = $message;

		return update_option( 'wc_' . $this->get_plugin()->get_id() . '_milestone_messages', $milestone_messages );
	}


	/** Utility Methods *******************************************************/


	/**
	 * Gets the registered milestone messages.
	 *
	 * @since 5.1.0
	 *
	 * @return array
	 */
	protected function get_milestone_messages() {

		return get_option( 'wc_' . $this->get_plugin()->get_id() . '_milestone_messages', array() );
	}


	/**
	 * Sets the milestone version.
	 *
	 * @since 5.1.0
	 *
	 * @param string $version plugin version
	 * @return bool
	 */
	public function set_milestone_version( $version ) {

		$this->milestone_version = $version;

		return update_option( 'wc_' . $this->get_plugin()->get_id() . '_milestone_version', $version );
	}


	/**
	 * Gets the milestone version.
	 *
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function get_milestone_version() {

		if ( ! $this->milestone_version ) {
			$this->milestone_version = get_option( 'wc_' . $this->get_plugin()->get_id() . '_milestone_version', '' );
		}

		return $this->milestone_version;
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @since 5.1.0
	 *
	 * @return \SkyVerge\WooCommerce\PluginFramework\v5_1_4\SV_WC_Plugin
	 */
	private function get_plugin() {

		return $this->plugin;
	}


}

endif;
