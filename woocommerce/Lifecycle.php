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

namespace SkyVerge\WooCommerce\PluginFramework\v5_0_1\Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_0_1\\Plugin\\Lifecycle' ) ) :

/**
 * Plugin lifecycle handler.
 *
 * Registers and displays milestone notice prompts and eventually the plugin
 * install, upgrade, activation, and deactivation routines.
 *
 * @since 5.1.0-dev
 */
class Lifecycle {


	/** @var \SkyVerge\WooCommerce\PluginFramework\v5_0_1\SV_WC_Plugin plugin instance */
	private $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 5.1.0-dev
	 *
	 * @param \SkyVerge\WooCommerce\PluginFramework\v5_0_1\SV_WC_Plugin $plugin plugin instance
	 */
	public function __construct( \SkyVerge\WooCommerce\PluginFramework\v5_0_1\SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->add_hooks();
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.1.0-dev
	 */
	protected function add_hooks() {

		add_action( 'init', array( $this, 'add_admin_notices' ) );

		add_action( 'wc_' . $this->get_plugin()->get_id() . '_milestone_reached', array( $this, 'register_milestone_message' ), 10, 2 );
	}


	/**
	 * Adds any lifecycle admin notices.
	 *
	 * @since 5.1.0-dev
	 */
	public function add_admin_notices() {

		// display any milestone notices
		foreach ( $this->get_milestone_messages() as $id => $message ) {

			// TODO: detect upgrades so existing installs don't display these notices {CW 2018-01-30}

			// bail if this notice was already dismissed
			if ( ! $this->get_plugin()->get_admin_notice_handler()->should_display_notice( $id ) ) {
				continue;
			}

			/**
			 * Filters a milestone notice message.
			 *
			 * @since 5.1.0-dev
			 *
			 * @param string $message message text to be used for the milestone notice
			 * @param string $id milestone ID
			 */
			$message = apply_filters( 'wc_' . $this->get_plugin()->get_id() . '_milestone_message', $this->generate_milestone_notice_message( $message ), $id );

			if ( $message ) {

				$this->get_plugin()->get_admin_notice_handler()->add_admin_notice( $message, $id );

				// only display one notice at a time
				break;
			}
		}
	}


	/** Milestone Methods *****************************************************/


	/**
	 * Generates a milestone notice message.
	 *
	 * @since 5.1.0-dev
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
	 * @since 5.1.0-dev
	 * @see Lifecycle::generate_milestone_notice_message()
	 *
	 * @param string $id milestone ID
	 * @param string $message message to display to the user
	 * @return bool whether the message was successfully registered
	 */
	public function register_milestone_message( $id, $message ) {

		$messages = $this->get_milestone_messages();

		$messages[ $id ] = $message;

		return update_option( 'wc_' . $this->get_plugin()->get_id() . '_milestone_messages', $messages );
	}


	/**
	 * Gets the registered milestone messages.
	 *
	 * @since 5.1.0-dev
	 *
	 * @return array
	 */
	protected function get_milestone_messages() {

		return get_option( 'wc_' . $this->get_plugin()->get_id() . '_milestone_messages', array() );
	}


	/** Utility Methods *******************************************************/


	/**
	 * Gets the plugin instance.
	 *
	 * @since 5.1.0-dev
	 *
	 * @return \SkyVerge\WooCommerce\PluginFramework\v5_0_1\SV_WC_Plugin
	 */
	private function get_plugin() {

		return $this->plugin;
	}


}

endif;
