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
 * @package   SkyVerge/WooCommerce/Exporter/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Export_Cron' ) ) :

/**
 * Export Cron Class
 *
 * Adds custom schedule and schedules the export event
 *
 * ## Options table
 *
 * Uses the following options from the database. This means they must be set
 * outside the scope of this class, usually in the plugin using this class.
 * All options use the option prefix, in the following format:
 *
 * `{$prefix}option_name`, example: `wc_customer_order_csv_export_auto_export_method`
 *
 * + `{$prefix}auto_export_method` - export method for auto-exports
 * + `{$prefix}auto_export_interval` - export interval for auto-exports, in minutes
 * + `{$prefix}auto_export_start_time` - export start time, in human readable format, such as 1:45pm
 * + `{$prefix}auto_export_statuses` - array of order statuses that are valid for auto-export
 *
 * ## Cron
 *
 * `{$prefix}auto_export_interval` - custom interval for auto-export action
 * `{$prefix}auto_export_orders` - custom hook for auto-exporting orders
 *
 * @since 4.3.0-1
 */
abstract class SV_WC_Export_Cron {


	/** @var SV_WC_Plugin the plugin associated with this export cron handler */
	private $plugin;

	/** @var bool whether automatic exports are enabled or not */
	protected $exports_enabled;


	/**
	 * Setup hooks and filters specific to WP-cron functions
	 *
	 * @since 4.3.0-1
	 * @param \SV_WC_Plugin $plugin plugin instance associated with this export cron handler
	 */
	public function __construct( SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

		// Add custom schedule, e.g. every 10 minutes
		add_filter( 'cron_schedules', array( $this, 'add_auto_export_schedule' ) );

		// Schedule auto-update events if they don't exist, run in both frontend and
		// backend so events are still scheduled when an admin reactivates the plugin
		add_action( 'init', array( $this, 'add_scheduled_export' ) );

		// Trigger export + upload of non-exported orders, wp-cron fires this action
		// on the given recurring schedule
		add_action( $this->get_prefix() . 'auto_export_orders', array( $this, 'auto_export_orders' ) );

		$this->exports_enabled = ( 'disabled' != get_option( $this->get_prefix() . 'auto_export_method' ) );
	}


	/**
	 * If automatic exports are enabled, add the custom interval
	 * (e.g. every 15 minutes) set on the admin settings page
	 *
	 * @since 4.3.0-1
	 * @param array $schedules WP-Cron schedules array
	 * @return array $schedules now including our custom schedule
	 */
	public function add_auto_export_schedule( $schedules ) {

		if ( $this->exports_enabled ) {

			$export_interval = get_option( $this->get_prefix() . 'auto_export_interval' );

			if ( $export_interval ) {

				$schedules[ $this->get_prefix() . 'auto_export_interval' ] = array(
					'interval' => (int) $export_interval * 60,
					'display'  => sprintf( __( 'Every %d minutes', 'woocommerce-plugin-framework' ), (int) $export_interval )
				);
			}
		}

		return $schedules;
	}


	/**
	 * If automatic exports are enabled, add the event if not already scheduled
	 *
	 * This performs a `do_action( '[exporter_prefix_]auto_export_orders' )`
	 * on our custom schedule
	 *
	 * @since 4.3.0-1
	 */
	public function add_scheduled_export() {

		if ( $this->exports_enabled ) {

			// Schedule export
			if ( ! wp_next_scheduled( $this->get_prefix() . 'auto_export_orders' ) ) {

				$start_time = get_option( $this->get_prefix() . 'auto_export_start_time' );
				$curr_time  = current_time( 'timestamp' );

				if ( $start_time ) {

					if ( $curr_time > strtotime( 'today ' . $start_time, $curr_time ) ) {

						$start_timestamp = strtotime( 'tomorrow ' . $start_time, $curr_time ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

					} else {

						$start_timestamp = strtotime( 'today ' . $start_time, $curr_time ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

					}

				} else {

					$export_interval = get_option( $this->get_prefix() . 'auto_export_interval' );

					$start_timestamp = strtotime( "now +{$export_interval} minutes" );
				}

				wp_schedule_event( $start_timestamp, $this->get_prefix() . 'auto_export_interval', $this->get_prefix() . 'auto_export_orders' );

			}

		}
	}

	/**
	 * Exports any non-exported orders and performs the chosen action
	 * (upload, HTTP POST, email)
	 *
	 * @since 4.3.0-1
	 */
	public function auto_export_orders() {

		$order_statuses = (array) get_option( $this->get_prefix() . 'auto_export_statuses' );

		/**
		 * Query order IDs to export
		 *
		 * By default we get un-exported order IDs,
		 * but this filter can change the query behavior
		 *
		 * @since 4.3.0-1
		 * @param array $query_args WP_Query args to fetch order IDs to export automatically
		 * @param array $order_statuses Order statuses to export
		 */
		$query_args = apply_filters( $this->get_prefix() . 'auto_export_order_query_args', array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => empty( $order_statuses ) ? 'any' : $order_statuses,
			'nopaging'    => true,
			'meta_key'    => "_{$this->prefix}is_exported",
			'meta_value'  => 0
		), $order_statuses );

		$query = new WP_Query( $query_args );

		if ( ! empty( $query->posts ) ) {

			// Export the queried orders
			$export = $this->load_export_handler( $query->posts, 'orders' );

			$export->export_via( get_option( $this->get_prefix() . 'auto_export_method' ) );
		}

		/**
		 * Auto-Export Action.
		 *
		 * Fired when orders are auto-exported
		 *
		 * @since 4.3.0-1
		 * @param array $order_ids order IDs that were exported
		 */
		do_action( $this->get_prefix() . 'orders_exported', $query->posts );
	}


	/**
	 * Clear scheduled events upon deactivation
	 *
	 * @since 4.3.0-1
	 */
	public function clear_scheduled_export() {

		wp_clear_scheduled_hook( $this->get_prefix() . 'auto_export_orders' );
	}


	/**
	 * Return the prefix to use when loading settings from wp_options,
	 * calling hooks or scheduling cron events.
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	protected function get_prefix() {
		return 'wc_' . $this->get_plugin()->get_id() . '_';
	}


	/**
	 * Return the plugin class instance associated with this Cron Handler
	 *
	 * This is used for defining the plugin ID used in action/filter names, as well
	 * as handling logging.
	 *
	 * @since 4.3.0-1
	 * @return \SV_WC_Plugin
	 */
	public function get_plugin() {
		return $this->plugin;
	}


	/**
	 * Load the export handler class associated with this export cron and create an instance of it
	 * with the passed in params
	 *
	 * Child classes must implement this to return their export handler class instance
	 *
	 * @since 4.3.0-1
	 * @param int|array $ids orders/customer IDs to export / download
	 * @param string $export_type what is being exported, `orders` or `customers`
	 * @return \SV_WC_Export_Handler
	 */
	abstract protected function load_export_handler( $ids, $export_type = null );

} // end \SV_WC_Export_Cron class

endif;
