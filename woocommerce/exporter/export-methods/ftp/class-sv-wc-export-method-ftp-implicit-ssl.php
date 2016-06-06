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

if ( ! class_exists( 'SV_WC_Export_Method_FTP_Implicit_SSL' ) ) :

/**
 * Export FTP over Implicit SSL Class
 *
 * Wrapper for cURL functions to transfer a file over FTP with implicit SSL
 *
 * @since 4.3.0-1
 */
class SV_WC_Export_Method_FTP_Implicit_SSL extends SV_WC_Export_Method_File_Transfer {


	/** @var resource cURL resource handle */
	private $curl_handle;

	/** @var string cURL URL for upload */
	private $url;


	/**
	 * Connect to FTP server over Implicit SSL/TLS
	 *
	 * See parent constructor for full method documentation
	 *
	 * @since 4.3.0-1
	 * @see SV_WC_Export_Method_File_Transfer::__construct()
	 * @throws Exception
	 * @param string $hook_prefix
	 * @param array $args
	 */
	 public function __construct( $hook_prefix, $args ) {

		// parent constructor
		parent::__construct( $hook_prefix, $args );

		// set host/initial path
		$this->url = "ftps://{$this->server}/{$this->path}";

		// setup connection
		$this->curl_handle = curl_init();

		// check for successful connection
		if ( ! $this->curl_handle ) {

			throw new Exception( __( 'Could not initialize cURL.', 'woocommerce-plugin-framework' ) );
		}

		// connection options
		$options = array(
			CURLOPT_USERPWD        => $this->username . ':' . $this->password,
			CURLOPT_SSL_VERIFYPEER => false, // don't verify SSL
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_FTP_SSL        => CURLFTPSSL_ALL, // require SSL For both control and data connections
			CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_DEFAULT, // let cURL choose the FTP authentication method (either SSL or TLS)
			CURLOPT_UPLOAD         => true,
			CURLOPT_PORT           => $this->port,
			CURLOPT_TIMEOUT        => $this->timeout,
		);

		// cURL FTP enables passive mode by default, so disable it by enabling the
		// PORT command
		if ( ! $this->passive_mode ) {

			$options[ CURLOPT_FTPPORT ] = '-';
		}

		/**
		 * Filter FTP over Implicit SSL cURL options
		 *
		 * @since 4.3.0-1
		 * @param array $options
		 * @param \SV_WC_Export_Method_FTP_Implicit_SSL instance
		 */
		$options = apply_filters( $this->hook_prefix . 'ftp_over_implicit_curl_options', $options, $this );

		// set connection options, use foreach so useful errors can be caught
		// instead of a generic "cannot set options" error with curl_setopt_array()
		foreach ( $options as $option_name => $option_value ) {

			if ( ! curl_setopt( $this->curl_handle, $option_name, $option_value ) ) {

				throw new Exception( sprintf( __( 'Could not set cURL option: %s', 'woocommerce-plugin-framework' ), $option_name ) );
			}
		}
	}


	/**
	 * Upload the file by writing into temporary memory and upload the stream to
	 * remote file
	 *
	 * @since 4.3.0-1
	 * @param string $filename remote file name to create
	 * @param string $data file content to upload
	 * @throws Exception Open remote file failure or write data failure
	 */
	public function perform_action( $filename, $data ) {

		// set file name
		if ( ! curl_setopt( $this->curl_handle, CURLOPT_URL, $this->url . $filename ) ) {

			// translators: Placeholders: %s - name of file to be updloaded
			throw new Exception( sprintf( __( 'Could not set cURL file name: %s', 'woocommerce-plugin-framework' ), $filename ) );
		}

		// open memory stream for writing
		$stream = fopen( 'php://temp', 'w+' );

		// check for valid stream handle
		if ( ! $stream ) {

			throw new Exception( __( 'Could not open php://temp for writing.', 'woocommerce-plugin-framework' ) );
		}

		// write data into the temporary stream
		fwrite( $stream, $data );

		// rewind the stream pointer
		rewind( $stream );

		// set the file to be uploaded
		if ( ! curl_setopt( $this->curl_handle, CURLOPT_INFILE, $stream ) ) {

			// translators: Placeholders: %s - name of file to be updloaded
			throw new Exception( sprintf( __( 'Could not load file %s', 'woocommerce-plugin-framework' ), $filename ) );
		}

		// upload file
		if ( ! curl_exec( $this->curl_handle ) ) {

			// translators: Placeholders: %1$s - cURL error number, %2$s - cURL error message
			throw new Exception( sprintf( __( 'Could not upload file. cURL Error: [%1$s] - %2$s', 'woocommerce-plugin-framework' ), curl_errno( $this->curl_handle ), curl_error( $this->curl_handle ) ) );
		}

		// close the stream handle
		fclose( $stream );
	}


	/**
	 * Attempt to close cURL handle
	 *
	 * @since 4.3.0-1
	 */
	public function __destruct() {

		// errors suppressed here as they are not useful
		@curl_close( $this->curl_handle );
	}


} // end \SV_WC_Export_Method_FTP_Implicit_SSL class

endif;
