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
 * @package   SkyVerge/WooCommerce/Exporter/Export-Methods
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Export_Method_Email' ) ) :

/**
 * Export Email Class
 *
 * Helper class for emailing exported file
 *
 * @since 4.3.0-1
 */
class SV_WC_Export_Method_Email extends SV_WC_Export_Method {


	/** @var string temporary filename to be deleted */
	private $temp_filename;

	/** @var string email recipients */
	private $email_recipients;

	/** @var string email subject */
	private $email_subject;

	/** @var string email message */
	private $email_message;

	/** @var string email id */
	private $email_id;


	/**
	 * Initialize the export method
	 *
	 * See parent constructor for full method documentation
	 *
	 * @since 4.3.0-1
	 * @see SV_WC_Export_Method::__construct()
	 * @param string $hook_prefix
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string $email_recipients Email recipients
	 *     @type string $email_subject Email subject
	 *     @type string $email_message Email message
	 *     @type string $email_id Email ID
	 * }
	 */
	 public function __construct( $hook_prefix, $args ) {

		// parent constructor
		parent::__construct( $hook_prefix );

		$this->email_recipients = $args['email_recipients'];
		$this->email_subject    = $args['email_subject'];
		$this->email_message    = $args['email_message'];
		$this->email_id         = $args['email_id'];
	}


	/**
	 * Emails the admin with the exported file as an attachment
	 *
	 * @since 4.3.0-1
	 * @param string $filename the attachment filename
	 * @param string $data the data to attach to the email
	 */
	public function perform_action( $filename, $data ) {

		$filename = $this->create_temp_file( $filename, $data );

		if ( ! empty( $filename ) ) {
			$this->email_export( $filename );
		}

	}


	/**
	 * Create temp file
	 *
	 * @since 4.3.0-1
	 * @param string $filename the attachment filename
	 * @param string $data the data to write to the file
	 * @return string $filename
	 */
	private function create_temp_file( $filename, $data ) {

		// prepend the temp directory
		$filename = get_temp_dir() . $filename;

		// create the file
		touch( $filename );

		// open the file, write file, and close it
		$fp = @fopen( $filename, 'w+');

		@fwrite( $fp, $data );
		@fclose( $fp );

		// make sure the temp file is removed after the email is sent
		$this->temp_filename = $filename;
		register_shutdown_function( array( $this, 'unlink_temp_file' ) );

		return $filename;
	}


	/**
	 * Unlink temp file
	 *
	 * @since 4.3.0-1
	 */
	public function unlink_temp_file() {

		if ( $this->temp_filename ) {
			@unlink( $this->temp_filename );
		}
	}


	/**
	 * Email the export
	 *
	 * @since 4.3.0-1
	 * @param string $filename the attachment filename
	 */
	private function email_export( $filename ) {

		// init email args
		$mailer  = WC()->mailer();
		$to      = ( $email = $this->email_recipients ) ? $email : get_option( 'admin_email' );
		$subject = $this->email_subject;
		$message = $this->email_message;

		// Allow actors to change the email headers.
		$headers = apply_filters( 'woocommerce_email_headers', "Content-Type: text/plain\r\n", $this->email_id );

		$attachments  = array( $filename );
		$content_type = 'text/plain';

		// send email
		$mailer->send( $to, $subject, $message, $headers, $attachments, $content_type );
	}

} // end \SV_WC_Export_Method_Email class

endif;
