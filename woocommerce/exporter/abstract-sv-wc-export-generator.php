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

if ( ! class_exists( 'SV_WC_Export_Generator' ) ) :

/**
 * Export Generator
 *
 * Converts customer/order data into the exported format
 *
 * @since 4.3.0-1
 */
abstract class SV_WC_Export_Generator {


	/** @var array order IDs or customer IDs */
	public $ids;


	/**
	 * Setup the IDs to export
	 *
	 * @since 4.3.0-1
	 * @param array $ids
	 * @return \SV_WC_Export_Generator
	 */
	public function __construct( $ids ) {
		$this->ids = $ids;
	}


	/**
	 * Get output for the provided export type
	 *
	 * @since 4.3.0-1
	 * @param string $type Output type, such as `orders` or `customers`
	 * @return string
	 */
	abstract public function get_output( $type );

}

endif;
