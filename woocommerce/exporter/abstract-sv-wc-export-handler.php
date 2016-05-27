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

if ( ! class_exists( 'SV_WC_Export_Handler' ) ) :

/**
 * Abstract Export Handler class
 *
 * Handles export actions/methods
 *
 * Child classes must at least implement
 *
 *
 * @since 4.3.0-1
 */
abstract class SV_WC_Export_Handler {


	/** @var array order IDs or customer IDs to export */
	public $ids;

	/** @var string file name for export or download */
	public $filename;


	/**
	 * Initializes the export object from an array of valid order/customer IDs and sets the filename
	 *
	 * @since 4.3.0-1
	 * @param int|array $ids orders/customer IDs to export / download
	 * @param string $export_type what is being exported, `orders` or `customers`
	 * @return \SV_WC_Export_Handler
	 */
	public function __construct( $ids, $export_type = 'orders' ) {

		// handle single order/customer exports
		if ( ! is_array( $ids ) ) {

			$ids = array( $ids );
		}

		$this->export_type = $export_type;

		/**
		 * Filter the order/customer IDs
		 *
		 * @since 3.9.1
		 * @param array $id, order IDs or customer IDs
		 * @param \SV_WC_Export_Handler $this, handler instance
		 */

		// TODO: Question - is it okay to move here (used to be in CSV Export generator constructor) ?
		// reason to move here: make sure that ids are filtered even before touching the generator,
		// so that it applies regardless of the generator implementation. {IT 2016-05-26}
		$this->ids = apply_filters( $this->get_prefix() . 'export_ids', $ids, $this );

		// set file name
		$this->filename = $this->replace_filename_variables();
	}


	/**
	 * Exports orders/customers to file and downloads via browser
	 *
	 * @since 4.3.0-1
	 */
	public function download() {

		$this->export_via( 'download' );
	}


	/**
	 * Exports test file and downloads via browser
	 *
	 * @since 4.3.0-1
	 */
	public function test_download() {

		$this->test_export_via( 'download' );
	}


	/**
	 * Exports orders/customers to file and uploads to remote server
	 *
	 * @since 4.3.0-1
	 */
	public function upload() {

		$this->export_via( 'ftp' );
	}


	/**
	 * Exports test file and uploads to remote server
	 *
	 * @since 4.3.0-1
	 */
	public function test_upload() {

		$this->test_export_via( 'ftp' );
	}


	/**
	 * Exports orders/customers and HTTP POSTs to remote server
	 *
	 * @since 4.3.0-1
	 */
	public function http_post() {

		$this->export_via( 'http_post' );
	}


	/**
	 * Exports test and HTTP POSTs to remote server
	 *
	 * @since 4.3.0-1
	 */
	public function test_http_post() {

		$this->test_export_via( 'http_post' );
	}


	/**
	 * Exports orders/customers to file and emails admin with the file as attachment
	 *
	 * @since 4.3.0-1
	 */
	public function email() {

		$this->export_via( 'email' );
	}


	/**
	 * Exports test file and emails admin with the file as attachment
	 *
	 * @since 4.3.0-1
	 */
	public function test_email() {

		$this->test_export_via( 'email' );
	}


	/**
	 * Exports via the given method
	 *
	 * @since 4.3.0-1
	 * @param string $method the export method, `download`, `ftp`, `http_post`, `email`
	 */
	public function export_via( $method ) {

		// try to set unlimited script timeout
		@set_time_limit( 0 );

		try {

			// get method (download, FTP, etc)
			$export = $this->get_export_method( $method );

			if ( ! is_object( $export ) ) {

				throw new Exception( sprintf( __( 'Invalid Export Method: %s', 'woocommerce-plugin-framework' ), $method ) );
			}

			if ( 'orders' == $this->export_type ) {

				// mark each order as exported
				// this must be done before download, as the download function exits()
				// to prevent additional output from contaminating the file
				$this->mark_orders_as_exported( $method );
			}

			$generated_data = $this->get_generator( $this->ids )->get_output( $this->export_type );

			$result = $export->perform_action( $this->filename, $generated_data );

			// Log any results/responses provided by the export method
			if ( $result ) {
				$this->get_plugin()->log( $e->getMessage() );
			}

		} catch ( Exception $e ) {

			// log errors
			$this->get_plugin()->log( $e->getMessage() );
		}
	}


	/**
	 * Exports a test file via the given method
	 *
	 * @since 4.3.0-1
	 * @param string $method the export method
	 * @return string 'Success' or error message
	 */
	public function test_export_via( $method ) {

		// try to set unlimited script timeout
		@set_time_limit( 0 );

		try {

			// get method (download, FTP, etc)
			$export = $this->get_export_method( $method );

			if ( ! is_object( $export ) ) {

				/** translators: %s - export method identifier */
				throw new Exception( sprintf( __( 'Invalid Export Method: %s', 'woocommerce-plugin-framework' ), $method ) );
			}

			// simple test file
			$export->perform_action( $this->get_test_filename(), $this->get_test_data() );

			return __( 'Test was successful!', 'woocommerce-plugin-framework' );

		} catch ( Exception $e ) {

			// log errors
			$this->get_plugin()->log( $e->getMessage() );

			/** translators: %s - error message */
			return sprintf( __( 'Test failed: %s', 'woocommerce-plugin-framework' ), $e->getMessage() );
		}
	}


	/**
	 * Returns the export method object
	 *
	 * @since 4.3.0-1
	 * @param string $method the export method, `download`, `ftp`, `http_post`, or `email`
	 * @return object the export method
	 */
	private function get_export_method( $method ) {

		$prefix    = $this->get_prefix();
		$base_path = $this->get_plugin()->get_framework_path() . '/exporter/export-methods';

		require_once( "{$base_path}/abstract-sv-wc-export-method.php" );

		// get the export method specified
		switch ( $method ) {

			case 'download':
				require_once( "{$base_path}/class-sv-wc-export-method-download.php" );
				return new SV_WC_Export_Method_Download( $prefix, $this->get_content_type() );

			case 'ftp':
				// abstract FTP class
				require_once( "{$base_path}/ftp/abstract-sv-wc-export-method-file-transfer.php" );

				$args = array(
					'ftp_server'       => get_option( $prefix . 'ftp_server' ),
					'ftp_username'     => get_option( $prefix . 'ftp_username' ),
					'ftp_password'     => get_option( $prefix . 'ftp_password' ),
					'ftp_password'     => get_option( $prefix . 'ftp_password', '' ),
					'ftp_port'         => get_option( $prefix . 'ftp_port' ),
					'ftp_path'         => get_option( $prefix . 'ftp_path', '' ),
					'ftp_security'     => get_option( $prefix . 'ftp_security' ),
					'ftp_passive_mode' => get_option( $prefix . 'ftp_passive_mode' ),
				);

				switch ( $args['ftp_security'] ) {

					// FTP over SSH
					case 'sftp' :
						require_once( "{$base_path}/ftp/class-sv-wc-export-method-sftp.php" );
						return new SV_WC_Export_Method_SFTP( $prefix, $args );

					// FTP with Implicit SSL
					case 'ftp_ssl' :
						require_once( "{$base_path}/ftp/class-sv-wc-export-method-ftp-implicit-ssl.php" );
						return new SV_WC_Export_Method_FTP_Implicit_SSL( $prefix, $args );

					// FTP with explicit SSL/TLS *or* regular FTP
					case 'ftps' :
					case 'none' :
						require_once( "{$base_path}/ftp/class-sv-wc-export-method-ftp.php" );
						return new SV_WC_Export_Method_FTP( $prefix, $args );
				}
				break;

			case 'http_post':
				require_once( "{$base_path}/class-sv-wc-export-method-http-post.php" );

				$args = array(
					'content_type'  => $this->get_content_type(),
					'http_post_url' => get_option( $prefix . 'http_post_url' ),
				);

				return new SV_WC_Export_Method_HTTP_POST( $prefix, $args );

			case 'email':
				require_once( "{$base_path}/class-sv-wc-export-method-email.php" );

				/**
				 * Allow actors to change the email subject used for exports emails.
				 *
				 * @since 4.3.0-1
				 * @param string the subject as set in the plugin settings
				 */
				$subject = apply_filters( $prefix . 'export_email_subject', get_option( $prefix . 'email_subject' ) );
				$message = sprintf( __( 'Order Export for %s', 'woocommerce-plugin-framework' ), date_i18n( wc_date_format(), current_time( 'timestamp' ) ) );

				$args = array(
					'email_recipients' => get_option( $prefix . 'email_recipients' ),
					'email_subject'    => $subject,
					'email_message'    => $message,
					'email_id'         => $this->get_id(),
				);

				return new SV_WC_Export_Method_Email( $prefix, $args );

			default:

				/**
				 * Get Export Method
				 *
				 * Triggered when getting the export method. This is designed for
				 * custom methods to hook in and load their class so it can be
				 * returned and used.
				 *
				 * @since 4.3.0-1
				 * @param \SV_WC_Export_Handler $this, handler instance
				 */
				do_action( $prefix . 'get_export_method', $this );

				$class_name = sprintf( $exporter->get_custom_export_method_class_prefix() . '%s', ucwords( strtolower( $method ) ) );

				return class_exists( $class_name ) ? new $class_name( $prefix ) : null;
		}
	}


	/**
	 * Marks orders as exported by setting the `_[exporter_prefix_]is_exported` order meta flag
	 *
	 * @since 4.3.0-1
	 * @param string $method the export method, `download`, `ftp`, `http_post`, or `email`
	 */
	public function mark_orders_as_exported( $method = 'download' ) {

		$file_type = $this->get_file_type_name();

		foreach ( $this->ids as $order_id ) {

			// add exported flag
			update_post_meta( $order_id, '_wc_' . $this->get_id() . '_is_exported', 1 );

			$order = wc_get_order( $order_id );

			switch ( $method ) {

				// note that order downloads using the AJAX order action are not marked or noted, only bulk order downloads
				case 'download':
					/** translators: %s - file type name, such as CSV or XML */
					$order_note = sprintf( __( 'Order exported to %s and successfully downloaded.', 'woocommerce-plugin-framework' ), $file_type );
					break;

				case 'ftp':
					/** translators: %s - file type name, such as CSV or XML */
					$order_note = sprintf( __( 'Order exported to %s and successfully uploaded to server.', 'woocommerce-plugin-framework' ), $file_type );
					break;

				case 'http_post':
					/** translators: %s - file type name, such as CSV or XML */
					$order_note = sprintf( __( 'Order exported to %s and successfully POSTed to remote server.', 'woocommerce-plugin-framework' ), $file_type );
					break;

				case 'email':
					/** translators: %s - file type name, such as CSV or XML */
					$order_note = sprintf( __( 'Order exported to %s and successfully emailed.', 'woocommerce-plugin-framework' ), $file_type );
					break;
			}

			/**
			 * Filter if an order note should be added when an order is successfully exported
			 *
			 * @since 4.3.0-1
			 * @param bool $add_order_note true if the order note should be added, false otherwise
			 */
			if ( apply_filters( $this->get_prefix() . 'add_order_note', true ) ) {
				$order->add_order_note( $order_note );
			}

			/**
			 * Order Exported Action.
			 *
			 * Fired when an order is automatically exported. Note this includes
			 * orders exported via the Orders bulk action.
			 *
			 * @since 4.3.0-1
			 * @param WC_Order $order order being exported
			 * @param string $method how the order is exported (ftp, download, etc)
			 * @param string $order_note order note message
			 * @param \SV_WC_Export_Handler $this, handler instance
			 */
			do_action( $this->get_prefix() . 'order_exported', $order, $method, $order_note, $this );
		}
	}


	/**
	 * Replaces variables in file name setting (e.g. %%timestamp%% becomes 2013_03_20_16_22_14 )
	 *
	 * @since 4.3.0-1
	 * @return string filename with variables replaced
	 */
	private function replace_filename_variables() {

		$prefix               = $this->get_prefix();
		$pre_replace_filename = get_option( 'orders' == $this->export_type ? "{$prefix}order_filename" : "{$prefix}customer_filename" );

		$variables   = array( '%%timestamp%%', '%%order_ids%%' );
		$replacement = array( date( 'Y_m_d_H_i_s', current_time( 'timestamp' ) ), implode( '-', $this->ids ) );

		$post_replace_filename = str_replace( $variables, $replacement, $pre_replace_filename );

		/**
		 * Filter exported file name
		 *
		 * @since 4.3.0-1
		 * @param string $filename Filename after replacing variables
		 * @param string $pre_replace_filename Filename before replacing variables
		 * @param array $ids Array of entity (customer or order) IDs being exported
		 */
		return apply_filters( $prefix . 'filename', $post_replace_filename, $pre_replace_filename, $this->ids );
	}


	/**
	* Return the prefix to use when loading settings from wp_options,
	* calling hooks, etc.
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	protected function get_prefix() {

		return 'wc_' . $this->get_id() . '_';
	}


	/**
	 * Get the ID for the Export Handler, used primarily to namespace filter & action
	 * hook names, as well as loading export options
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	protected function get_id() {

		return $this->get_plugin()->get_id();
	}


	/**
	 * Return the plugin class instance associated with this Export Handler
	 *
	 * Child classes must implement this to return their plugin class instance
	 *
	 * This is used for defining the plugin ID used in action/filter names, as well
	 * as handling logging.
	 *
	 * @since 4.3.0-1
	 * @return \SV_WC_Plugin
	 */
	abstract protected function get_plugin();


	/**
	 * Return the export generator class instance associated with this Export Handler
	 *
	 * Child classes must implement this to return their generator class instance
	 *
	 * @since 4.3.0-1
	 * @param array $ids Array of object IDs to pass to generator
	 * @return \SV_WC_Export_Generator
	 */
	abstract protected function get_generator( $ids );


	/**
	 * Return the MIME content type for file transfer/download, such as `text/csv`
	 *
	 * Child classes must implement this to return their content type
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	abstract protected function get_content_type();


	/**
	 * Return the file extension, such as `csv` or `xml`
	 *
	 * Child classes must implement this to return their file extension
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	abstract protected function get_file_extension();


	/**
	 * Return the name for the file type, such as `CSV` or `XML`
	 *
	 * Child classes must implement this to return their file type name
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	abstract protected function get_file_type_name();


	/**
	 * Return the filename for test export, such as `test.csv`
	 *
	 * Child classes must implement this method
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	abstract protected function get_test_filename();


	/**
	 * Return the data (file contents) for test export
	 *
	 * Child classes must implement this method
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	abstract protected function get_test_data();


	/**
	 * Return the custom export method class prefix, such as `WC_Customer_Order_CSV_Export_Custom_Method_`
	 *
	 * Child classes must implement this method
	 *
	 * TODO: deprecate this in favor of always using `SV_WC_Export_Custom_Method_` ? {IT 2016-05-26}
	 *
	 * @since 4.3.0-1
	 * @return string
	 */
	abstract protected function get_custom_export_method_class_prefix();


} //end \SV_WC_Export_Handler class

endif;
