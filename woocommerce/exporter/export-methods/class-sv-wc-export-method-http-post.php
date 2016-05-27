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

if ( ! class_exists( 'SV_WC_Export_Method_HTTP_POST' ) ) :

/**
 * Export HTTP POST Class
 *
 * Simple wrapper for wp_remote_post() to POST exported data to remote URLs
 *
 * @since 4.3.0-1
 */
class SV_WC_Export_Method_HTTP_POST extends SV_WC_Export_Method {


	/** @var string MIME Content Type */
	private $content_type;

	/** @var string HTTP POST Url */
	private $http_post_url;


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
	 *     @type string $content_type MIME Content-Type for the file
	 *     @type string $http_post_url URL to POST data to
	 * }
	 */
	 public function __construct( $hook_prefix, $args ) {

		// parent constructor
		parent::__construct( $hook_prefix );

		$this->content_type   = $args['content_type'];
		$this->http_post_args = $args['http_post_url'];
	}


	/**
	 * Performs an HTTP POST to the specified URL with the exported data
	 *
	 * @since 4.3.0-1
	 * @param string $filename unused
	 * @param string $data the data to include the HTTP POST body
	 * @throws Exception WP HTTP error handling
	 */
	public function perform_action( $filename, $data ) {

		/**
		 * Allow actors to modify HTTP POST args
		 *
		 * @since 4.3.0-1
		 * @param array $args
		 */
		$args = apply_filters( $this->hook_prefix . 'http_post_args', array(
			'timeout'     => 60,
			'redirection' => 0,
			'httpversion' => '1.0',
			'sslverify'   => true,
			'blocking'    => true,
			'headers'     => array(
				'accept'       => $this->content_type,
				'content-type' => $this->content_type,
			),
			'body'        => $data,
			'cookies'     => array(),
			'user-agent'  => "WordPress " . $GLOBALS['wp_version'],
		) );

		$response = wp_safe_remote_post( $this->http_post_url, $args );

		// check for errors
		if ( is_wp_error( $response ) ) {

			throw new Exception( $response->get_error_message() );
		}

		return $response;
	}

} // end \SV_WC_Export_Method_HTTP_POST class

endif;
