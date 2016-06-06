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

if ( ! class_exists( 'SV_WC_Export_Method_SFTP' ) ) :

/**
 * Export SFTP Class
 *
 * Simple wrapper for ssh2_* functions to upload an exported file to a remote
 * server via FTP over SSH
 *
 * @since 4.3.0-1
 */
class SV_WC_Export_Method_SFTP extends SV_WC_Export_Method_File_Transfer {


	/** @var resource sftp connection resource */
	private $sftp_link;


	/**
	 * Connect to SSH server, authenticate via password, and set up SFTP link
	 *
	 * See parent constructor for full method documentation
	 *
	 * @since 4.3.0-1
	 * @see SV_WC_Export_Method_File_Transfer::__construct()
	 * @throws Exception - ssh2 extension not installed, failed SSH / SFTP connection, failed authentication
	 * @param string $hook_prefix
	 * @param array $args
	 */
	 public function __construct( $hook_prefix, $args ) {

		// parent constructor
		parent::__construct( $hook_prefix, $args );

		// check if ssh2 extension is installed
		if ( ! function_exists( 'ssh2_connect' ) ) {

			throw new Exception( __( 'SSH2 Extension is not installed, cannot connect via SFTP.', 'woocommerce-plugin-framework' ) );
		}

		// setup connection
		$this->ssh_link = ssh2_connect( $this->server, $this->port );

		// check for successful connection
		if ( ! $this->ssh_link ) {

			/* translators: Placeholders: %1$s - server address, %2$s - server port. */
			throw new Exception( sprintf( __( 'Could not connect via SSH to %1$s on port %2$s, check server address and port.', 'woocommerce-plugin-framework' ), $this->server, $this->port ) );
		}

		// authenticate via password and check for successful authentication
		if ( ! ssh2_auth_password( $this->ssh_link, $this->username, $this->password ) ) {

			/* translators: Placeholders: %s - username */
			throw new Exception( sprintf( __( "Could not authenticate via SSH with username %s and password. Check username and password.", 'woocommerce-plugin-framework' ), $this->username ) );
		}

		// setup SFTP link
		$this->sftp_link = ssh2_sftp( $this->ssh_link );

		// check for successful SFTP link
		if ( ! $this->sftp_link ) {

			throw new Exception( __( 'Could not setup SFTP link', 'woocommerce-plugin-framework' ) );
		}
	}


	/**
	 * Open remote file and write exported data into it
	 *
	 * @since 4.3.0-1
	 * @param string $filename remote filename to create
	 * @param string $data file content to upload
	 * @throws Exception Open remote file failure or write data failure
	 */
	public function perform_action( $filename, $data ) {

		// open a file on the remote system for writing
		$stream = fopen( "ssh2.sftp://{$this->sftp_link}/{$this->path}{$filename}", 'w+' );

		// check for fopen failure
		if ( ! $stream ) {

			/* translators: Placeholders: %s - file name */
			throw new Exception( sprintf( __( "Could not open remote file: %s.", 'woocommerce-plugin-framework' ), $filename ) );
		}

		// write exported data to opened remote file
		if ( false === fwrite( $stream, $data ) ) {

			/* translators: Placeholders: %s - file name */
			throw new Exception( sprintf( __( "Could not write data to remote file: %s.", 'woocommerce-plugin-framework' ), $filename ) );
		}

		// close file
		fclose( $stream );
	}


} // end \SV_WC_Export_Method_SFTP class

endif;