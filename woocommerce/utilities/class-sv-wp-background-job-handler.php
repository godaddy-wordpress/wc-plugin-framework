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
 * @package   SkyVerge/WooCommerce/Utilities
 * @author    SkyVerge / Delicious Brains
 * @copyright Copyright (c) 2015-2016 Delicious Brains Inc.
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WP_Background_Job_Handler' ) ) :

/**
 * SkyVerge WordPress Background Job Handler class
 *
 * Based on the wonderful WP_Background_Process class by deliciousbrains:
 * https://github.com/A5hleyRich/wp-background-processing
 *
 * Subclasses SV_WP_Async_Request. Instead of the concept of `batches` used in
 * the Delicious Brains' version, however, this takes a more object-oriented approach
 * of background `jobs`, allowing greater control over manipulating job data and
 * processing.
 *
 * A batch implicitly expected an array of items to process, whereas a job does
 * not expect any particular data structure (although it does default to
 * looping over job data) and allows subclasses to provide their own
 * processing logic.
 *
 * # Sample usage:
 *
 * $background_job_handler = new SV_WP_Background_Job_Handler();
 * $job = $background_job_handler->create_job( $order_ids, array( 'send_payment_reminders' => true ) );
 * $background_job_handler->dispatch();
 *
 * @since 4.4.0
 */
abstract class SV_WP_Background_Job_Handler extends SV_WP_Async_Request {


	/** @var string async request prefix */
	protected $prefix = 'sv_wp';

	/** @var string async request action */
	protected $action = 'background_job';

	/** @var int start time of current process */
	protected $start_time = 0;

	/** @var string cron hook identifier */
	protected $cron_hook_identifier;

	/** @var string cron interval identifier */
	protected $cron_interval_identifier;


	/**
	 * Initiate new background job handler
	 *
	 * @since 4.4.0
	 */
	public function __construct() {

		parent::__construct();

		$this->cron_hook_identifier     = $this->identifier . '_cron';
		$this->cron_interval_identifier = $this->identifier . '_cron_interval';

		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );
	}


	/**
	 * Dispatch
	 *
	 * @since 4.4.0
	 * @return array|WP_Error
	 */
	public function dispatch() {

		// schedule the cron healthcheck
		$this->schedule_event();

		// perform remote post
		parent::dispatch();
	}


	/**
	 * Maybe process job queue
	 *
	 * Checks whether data exists within the job queue and that
	 * the background process is not already running.
	 *
	 * @since 4.4.0
	 */
	public function maybe_handle() {

		if ( $this->is_process_running() ) {
			// background process already running
			wp_die();
		}

		if ( $this->is_queue_empty() ) {
			// no data to process
			wp_die();
		}

		check_ajax_referer( $this->identifier, 'nonce' );

		$this->handle();

		wp_die();
	}


	/**
	 * Check whether job queue is empty or not
	 *
	 * @since 4.4.0
	 * @return bool True if queue is empty, false otherwise
	 */
	protected function is_queue_empty() {
		global $wpdb;

		$key = $this->identifier . '_job_%';

		// only queued or processing jobs count
		$queued     = '%"status":"queued"%';
		$processing = '%"status":"processing"%';

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$wpdb->options}
			WHERE option_name LIKE %s
			AND ( option_value LIKE %s OR option_value LIKE %s )
		", $key, $queued, $processing ) );

		return ( $count > 0 ) ? false : true;
	}


	/**
	 * Check whether background process is running or not
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 *
	 * @since 4.4.0
	 * @return bool True if processing is running, false otherwise
	 */
	protected function is_process_running() {
		return (bool) get_transient( "{$this->identifier}_process_lock" );
	}


	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 *
	 * @since 4.4.0
	 */
	protected function lock_process() {

		// set start time of current process
		$this->start_time = time();

		// set lock duration to 1 minute by default
		$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60;

		/**
		 * Filter the queue lock time
		 *
		 * @since 4.4.0
		 * @param int $lock_duration Lock duration in seconds
		 */
		$lock_duration = apply_filters( "{$this->identifier}_queue_lock_time", $lock_duration );

		set_transient( "{$this->identifier}_process_lock", microtime(), $lock_duration );
	}


	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @since 4.4.0
	 * @return \SV_WP_Background_Job_Handler
	 */
	protected function unlock_process() {

		delete_transient( "{$this->identifier}_process_lock" );

		return $this;
	}


	/**
	 * Check if memory limit is exceeded
	 *
	 * Ensures the background job handler process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @since 4.4.0
	 * @return bool True if exceeded memory limit, false otherwise
	 */
	protected function memory_exceeded() {

		$memory_limit   =  $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		/**
		 * Filter whether memory limit has been exceeded or not
		 *
		 * @since 4.4.0
		 * @param bool $exceeded
		 */
		return apply_filters( "{$this->identifier}_memory_exceeded", $return );
	}


	/**
	 * Get memory limit
	 *
	 * @since 4.4.0
	 * @return int memory limit in bytes
	 */
	protected function get_memory_limit() {

		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// sensible default
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 == $memory_limit ) {
			// unlimited, set to 32GB
			$memory_limit = '32000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}


	/**
	 * Check whether request time limit has been exceeded or not
	 *
	 * Ensures the background job handler never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @since 4.4.0
	 * @return bool True, if time limit exceeded, false otherwise
	 */
	protected function time_exceeded() {

		/**
		 * Filter default time limit for background job execution, defaults to
		 * 20 seconds
		 *
		 * @since 4.4.0
		 * @param int $time Time in seconds
		 */
		$finish = $this->start_time + apply_filters( "{$this->identifier}_default_time_limit", 20 );
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		/**
		 * Filter whether maximum execution time has exceeded or not
		 *
		 * @since 4.4.0
		 * @param bool $exceeded true if execution time exceeded, false otherwise
		 */
		return apply_filters( "{$this->identifier}_time_exceeded", $return );
	}


	/**
	 * Create a background job
	 *
	 * Delicious Brains' versions alternative would be using ->data()->save().
	 * Allows passing in job options that are available at item data processing time.
	 * This allows sharing common options between items without the need to repeat
	 * the same information for every single item in queue.
	 *
	 * Instead of returning self, returns the job instance, which gives greater
	 * control over the job.
	 *
	 * @since 4.4.0
	 * @param mixed $data Job data. Usually an array of items to process.
	 * @param array $options Optional. Job options.
	 * @return object
	 */
	public function create_job( $data, $options = null ) {

		if ( empty( $data ) ) {
			return;
		}

		// generate a unique ID for the job
		$job_id = md5( microtime() . mt_rand() );

		/**
		 * Filter new background job attributes
		 *
		 * @since 4.4.0
		 * @param array $attrs Job attributes
		 * @param string $id Job ID
		 */
		$attrs = apply_filters( "{$this->identifier}_new_job_attrs", array(
			'data'       => $data,
			'options'    => $options,
		), $job_id );

		// ensure a few must-have attributes
		$attrs = wp_parse_args( array(
			'id'         => $job_id,
			'created_at' => current_time( 'mysql' ),
			'status'     => 'queued',
		), $attrs );

		update_option( "{$this->identifier}_job_{$job_id}" , json_encode( $attrs ) );

		$job = new stdClass();

		foreach ( $attrs as $key => $value ) {
			$job->{$key} = $value;
		}

		/**
		 * Run when a job is created
		 *
		 * @since 4.4.0
		 * @param object $job The created job
		 */
		do_action( "{$this->identifier}_job_created", $job );

		return $job;
	}


	/**
	 * Get a job (by default the first in the queue)
	 *
	 * @since 4.4.0
	 * @param string $id Optional. Job ID. Will return first job in queue if not
	 *                   provided.
	 * @return object|null The found job object or null
	 */
	public function get_job( $id = null ) {
		global $wpdb;

		if ( ! $id ) {

			$key        = $this->identifier . '_job_%';
			$queued     = '%"status":"queued"%';
			$processing = '%"status":"processing"%';

			$results = $wpdb->get_var( $wpdb->prepare( "
				SELECT option_value
				FROM {$wpdb->options}
				WHERE option_name LIKE %s
				AND ( option_value LIKE %s OR option_value LIKE %s )
				ORDER BY option_id ASC
				LIMIT 1
			", $key, $queued, $processing ) );

		} else {
			$results = get_option( "{$this->identifier}_job_{$id}" );
		}

		if ( ! empty( $results ) ) {

			$job = new stdClass();

			foreach ( json_decode( $results, true ) as $key => $value ) {
				$job->{$key} = $value;
			}

		} else {
			return null;
		}

		/**
		 * Filter job as returned from the database
		 *
		 * @since 4.4.0
		 * @param object $job
		 */
		return apply_filters( "{$this->identifier}_returned_job", $job );
	}


	/**
	 * Handle
	 *
	 * Process jobs while remaining within server memory and time limit constraints.
	 *
	 * @since 4.4.0
	 */
	protected function handle() {

		$this->lock_process();

		do {

			// Get next job in the queue
			$job = $this->get_job();

			// Indicate that the job has started processing
			if ( 'processing' != $job->status ) {

				$job->status                = 'processing';
				$job->started_processing_at = current_time( 'mysql' );

				$this->update_job( $job );
			}

			// Start processing
			$this->process_job( $job );

		} while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );

		$this->unlock_process();

		// Start next job or complete process
		if ( ! $this->is_queue_empty() ) {
			$this->dispatch();
		} else {
			$this->complete();
		}

		wp_die();
	}


	/**
	 * Process a job
	 *
	 * Default implementation is to loop over job data and passing each item to
	 * the task handler. Subclasses are, however, welcome to override this method
	 * to create totally different job processing implementations - see
	 * WC_CSV_Import_Suite_Background_Import in CSV Import for an example.
	 *
	 * @since 4.4.0
	 * @param object $job
	 */
	protected function process_job( $job ) {

		foreach ( $job->data as $key => $value ) {

			// pass the item to task handler
			$task = $this->task( $value, $job->options );

			if ( false !== $task ) {
				$job->data[ $key ] = $task;
			} else {
				unset( $job->data[ $key ] );
			}

			if ( $this->time_exceeded() || $this->memory_exceeded() ) {
				// job limits reached
				break;
			}
		}

		// Update or complete current job
		if ( ! empty( $job->data ) ) {
			$this->update_job( $job );
		} else {
			$this->complete_job( $job );
		}
	}


	/**
	 * Update job attrs
	 *
	 * @since 4.4.0
	 * @param object $job
	 * @return $this
	 */
	public function update_job( $job ) {

		if ( ! empty( $job ) ) {
			$job->updated_at = current_time( 'mysql' );

			update_option( "{$this->identifier}_job_{$job->id}" , json_encode( $job ) );
		}

		/**
		 * Run when a job is updated
		 *
		 * @since 4.4.0
		 * @param object $job The updated job
		 */
		do_action( "{$this->identifier}_job_updated", $job );

		return $this;
	}


	/**
	 * Handle job completion
	 *
	 * @since 4.4.0
	 * @param object $job
	 */
	public function complete_job( $job ) {

		$job->status       = 'completed';
		$job->completed_at = current_time( 'mysql' );

		update_option( "{$this->identifier}_job_{$job->id}", json_encode( $job ) );

		/**
		 * Run when a job is completed
		 *
		 * @since 4.4.0
		 * @param object $job The completed job
		 */
		do_action( "{$this->identifier}_job_complete", $job );
	}


	/**
	 * Handle job failure
	 *
	 * Default implementation does not call this method directly, but it's
	 * provided as a convenience method for subclasses that may call this to
	 * indicate that a particular job has failed for some reason.
	 *
	 * @since 4.4.0
	 * @param object $job
	 * @param string $reason Optional. Reason for failure.
	 */
	public function fail_job( $job, $reason = '' ) {

		$job->status    = 'failed';
		$job->failed_at = current_time( 'mysql' );

		if ( $reason ) {
			$job->failure_reason = $reason;
		}

		update_option( "{$this->identifier}_job_{$job->id}", json_encode( $job ) );

		/**
		 * Run when a job is failed
		 *
		 * @since 4.4.0
		 * @param object $job The failed job
		 */
		do_action( "{$this->identifier}_job_failed", $job );
	}


	/**
	 * Handle job queue completion
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 *
	 * @since 4.4.0
	 */
	protected function complete() {

		// unschedule the cron healthcheck
		$this->clear_scheduled_event();
	}

	/**
	 * Schedule cron healthcheck
	 *
	 * @since 4.4.0
	 * @param array $schedules
	 * @return mixed
	 */
	public function schedule_cron_healthcheck( $schedules ) {

		$interval = property_exists( $this, 'cron_interval' ) ? $this->cron_interval : 5;

		/**
		 * Filter cron health check interval
		 *
		 * @since 4.4.0
		 * @param int $interval Interval in minutes
		 */
		$interval = apply_filters( "{$this->identifier}_cron_interval", $interval );

		// adds every 5 minutes to the existing schedules.
		$schedules[ $this->identifier . '_cron_interval' ] = array(
			'interval' => MINUTE_IN_SECONDS * $interval,
			'display'  => sprintf( __( 'Every %d Minutes' ), $interval ),
		);

		return $schedules;
	}


	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 *
	 * @since 4.4.0
	 */
	public function handle_cron_healthcheck() {

		if ( $this->is_process_running() ) {
			// background process already running
			exit;
		}

		if ( $this->is_queue_empty() ) {
			// no data to process
			$this->clear_scheduled_event();
			exit;
		}

		$this->dispatch();
	}


	/**
	 * Schedule cron health check event
	 *
	 * @since 4.4.0
	 */
	protected function schedule_event() {

		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time(), $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}


	/**
	 * Clear scheduled health check event
	 *
	 * @since 4.4.0
	 */
	protected function clear_scheduled_event() {

		$timestamp = wp_next_scheduled( $this->cron_hook_identifier );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->cron_hook_identifier );
		}
	}


	/**
	 * Handle task
	 *
	 * Implement this method to perform any actions required on each
	 * item in job data. Return the modified item for further processing
	 * in the next pass through, or return false to remove the
	 * item from the job.
	 *
	 * @since 4.4.0
	 * @param mixed $item Job data item to iterate over
	 * @param mixed $options Job options
	 * @return mixed
	 */
	abstract protected function task( $item, $options = null );


}

endif;
