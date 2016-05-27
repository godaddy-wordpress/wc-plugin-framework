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

if ( ! class_exists( 'SV_WC_Export_Method' ) ) :

/**
 * Abstract Export Method
 *
 * Sets up basic Export Method class & defines a simple interface that
 * export-methods must implement to provide an export action.
 *
 * @since 4.3.0-1
 */
abstract class SV_WC_Export_Method {


	/** @var string action/filter hook prefix */
	protected $hook_prefix;


	/**
	 * Initialize the export method
	 *
	 * @since 4.3.0-1
	 * @param string $hook_prefix Action/Filter hook prefix. Used when calling
	 *                            actions & filters, ie `do_action( "{$hook_prefix}_action" )`
	 */
	public function __construct( $hook_prefix ) {

		$this->hook_prefix = $hook_prefix;
	}


	/**
	 * This method should perform the export action, e.g. download the exported
	 * file via the browser or uploading via FTP to a remote server
	 *
	 * @since 4.3.0-1
	 * @param string $filename the export file's filename
	 * @param string $data the exported data
	 * @return mixed|null
	 */
	abstract public function perform_action( $filename, $data );

}

endif;
