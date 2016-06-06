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

if ( ! class_exists( 'SV_WC_Export_Method_File_Transfer' ) ) :

/**
 * Export File Transfer Class
 *
 * Simple abstract class that handles getting FTP credentials and connection information for
 * all of the FTP methods (FTP, FTPS, FTP over implicit SSL, SFTP)
 *
 * @since 4.3.0-1
 */
abstract class SV_WC_Export_Method_File_Transfer extends SV_WC_Export_Method {


	/** @var string the FTP server address */
	protected $server;

	/** @var string the FTP username */
	protected $username;

	/** @var string the FTP user password*/
	protected $password;

	/** @var string the FTP server port */
	protected $port;

	/** @var string the path to change to after connecting */
	protected $path;

	/** @var string the FTP security type, either `none`, `ftps`, `ftp-ssl`, `sftp` */
	protected $security;

	/** @var bool true to enable passive mode for the FTP connection, false otherwise */
	protected $passive_mode;

	/** @var int the timeout for the FTP connection in seconds */
	protected $timeout;


	/**
	 * Setup FTP information and check for any invalid/missing info
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

		$args = wp_parse_args( $args, array(
			'ftp_server'       => '',
			'ftp_username'     => '',
			'ftp_password'     => '',
			'ftp_port'         => '',
			'ftp_path'         => '',
			'ftp_security'     => '',
			'ftp_passive_mode' => 'no',
		) );

		// set connection info
		$this->server       = $args['ftp_server'];
		$this->username     = $args['ftp_username'];
		$this->password     = $args['ftp_password'];
		$this->port         = $args['ftp_port'];
		$this->path         = $args['ftp_path'];
		$this->security     = $args['ftp_security'];
		$this->passive_mode = 'yes' === $args['ftp_passive_mode' );

		/**
		 * Allow actors to adjust the FTP timeout
		 *
		 * @since 4.3.0-1
		 * @param int $timeout Timeout in seconds
		 */
		$this->timeout = apply_filters( $this->hook_prefix . 'ftp_timeout', 30 );

		// check for blank username
		if ( ! $this->username ) {

			throw new Exception( __( 'FTP Username is blank.', 'woocommerce-plugin-framework' ) );
		}

		/* allow blank passwords */

		// check for blank server
		if ( ! $this->server ) {

			throw new Exception( __( 'FTP Server is blank.', 'woocommerce-plugin-framework' ) );
		}

		// check for blank port
		if ( ! $this->port ) {

			throw new Exception ( __( 'FTP Port is blank.', 'woocommerce-plugin-framework' ) );
		}
	}

} // end \SV_WC_Export_Method_File_Transfer class

endif;
