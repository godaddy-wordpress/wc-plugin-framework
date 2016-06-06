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


	/**
	 * Get file header/document start
	 *
	 * Should return appropriate header/start element, such as CSV column names
	 * or XML wrapper elements, etc.  Return null to skip writing a footer.
	 *
	 * @since 4.3.0-1
	 * @param string $type
	 * @return string|null
	 */
	abstract public function get_header( $type );


	/**
	 * Get file footer/document end
	 *
	 * Should return appropriate footer/end element, such as closing XML wrapper
	 * tags. Return null to skip writing a footer.
	 *
	 * @since 4.3.0-1
	 * @param string $type
	 * @return string|null
	 */
	abstract public function get_footer( $type );


	/**
	 * Get output for the provided export type
	 *
	 * Return null to skip writing content to the export file.
	 *
	 * @since 4.3.0-1
	 * @param int/array $ids Array of object IDs to get output for
	 * @param string $type Output type, such as `orders` or `customers`
	 * @return string|null
	 */
	abstract public function get_output( $ids, $type );

}

endif;
