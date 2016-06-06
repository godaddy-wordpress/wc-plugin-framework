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

if ( ! class_exists( 'SV_WC_Export_Method_Download' ) ) :

/**
 * Export Method Download
 *
 * Helper class for downloading an exported file via the browser
 *
 * @since 4.3.0-1
 */
class SV_WC_Export_Method_Download extends SV_WC_Export_Method {


	/** @var string MIME Content Type */
	private $content_type;


	/**
	 * Initialize the export method
	 *
	 * See parent constructor for full method documentation
	 *
	 * @since 4.3.0-1
	 * @see SV_WC_Export_Method::__construct()
	 * @param string $hook_prefix
	 * @param string $content_type MIME Content-Type for the file
	 */
	 public function __construct( $hook_prefix, $content_type ) {

		// parent constructor
		parent::__construct( $hook_prefix );

		$this->content_type = $content_type;
	}

	/**
	 * Downloads the exported file via the browser
	 *
	 * @since 4.3.0-1
	 * @param string $filename
	 * @param string $data the data to download
	 */
	public function perform_action( $filename, $data ) {

		$charset = get_option( 'blog_charset' );

		/**
		 * Filter the content type for the download
		 *
		 * @since 4.3.0-1
		 * @param string $content_type
		 */
		$content_type = apply_filters( $this->hook_prefix . 'download_content_type', "{$this->content_type}; charset={$charset}" );

		// set headers for download
		header( $content_type );
		header( sprintf( 'Content-Disposition: attachment; filename="%s"', $filename ) );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		/**
		 * Allow plugins to add additional headers
		 *
		 * @since 4.3.0-1
		 */
		do_action( $this->hook_prefix . 'download_after_headers' );

		$this->write_output( $data, $charset );

		exit; // prevent output contamination
	}


	/**
	 * Write exported data to output
	 *
	 * @since 4.3.0-1
	 * @param string $data
	 * @param string $charset
	 */
	protected function write_output( $data, $charset ) {

		// set the output encoding
		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			iconv_set_encoding( 'output_encoding', $charset );
		} else {
			ini_set( 'default_charset', 'UTF-8' );
		}

		// clear the output buffer
		@ini_set( 'zlib.output_compression', 'Off' );
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'output_handler', '' );

		// open the output buffer for writing
		$fp = fopen( 'php://output', 'w' );

		// write the data to the output buffer
		fwrite( $fp, $data );

		// close the output buffer
		fclose( $fp );
	}

} // end \SV_WC_Export_Method_Download class

endif;
