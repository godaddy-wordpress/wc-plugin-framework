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
 * @package   SkyVerge/WooCommerce/Plugin/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2014, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_Plugin_Compatibility' ) ) :

/**
 * WooCommerce Compatibility Utility Class
 *
 * The unfortunate purpose of this class is to provide a single point of
 * compatibility functions for dealing with supporting multiple versions
 * of WooCommerce.
 *
 * The expected procedure is to remove methods from this class, using the
 * latest ones directly in code, as support for older versions of WooCommerce
 * are dropped.
 *
 * Current Compatibility: 2.1.x - 2.2
 *
 * @since 2.0
 */
class SV_WC_Plugin_Compatibility {

	// wc_get_order()
	// wc_get_product()


	/**
	 * Return an array of formatted item meta in format:
	 *
	 * array(
	 *   $meta_key => array(
	 *     'label' => $label,
	 *     'value' => $value
	 *   )
	 * )
	 *
	 * e.g.
	 *
	 * array(
	 *   'pa_size' => array(
	 *     'label' => 'Size',
	 *     'value' => 'Medium',
	 *   )
	 * )
	 *
	 * Backports the get_formatted() method to WC 2.1
	 *
	 * @since 2.2
	 * @see WC_Order_Item_Meta::get_formatted()
	 * @param \WC_Order_Item_Meta $item_meta order item meta class instance
	 * @param string $hide_prefix exclude meta when key is prefixed with this, defaults to `_`
	 * @return array
	 */
	public static function get_formatted_item_meta( WC_Order_Item_Meta $item_meta, $hide_prefix = '_' ) {

		if ( self::is_wc_version_gte_2_2() ) {

			return $item_meta->get_formatted( $hide_prefix );

		} else {

			if ( empty( $item_meta->meta ) ) {
				return array();
			}

			$formatted_meta = array();

			foreach ( (array) $item_meta->meta as $meta_key => $meta_values ) {

				if ( empty( $meta_values ) || ! is_array( $meta_values ) || ( ! empty( $hide_prefix ) && substr( $meta_key, 0, 1 ) == $hide_prefix ) ) {
					continue;
				}

				foreach ( $meta_values as $meta_value ) {

					// Skip serialised meta
					if ( is_serialized( $meta_value ) ) {
						continue;
					}

					$attribute_key = urldecode( str_replace( 'attribute_', '', $meta_key ) );

					// If this is a term slug, get the term's nice name
					if ( taxonomy_exists( $attribute_key ) ) {
						$term = get_term_by( 'slug', $meta_value, $attribute_key );

						if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
							$meta_value = $term->name;
						}

						// If we have a product, and its not a term, try to find its non-sanitized name
					} elseif ( $item_meta->product ) {
						$product_attributes = $item_meta->product->get_attributes();

						if ( isset( $product_attributes[ $attribute_key ] ) ) {
							$meta_key = wc_attribute_label( $product_attributes[ $attribute_key ]['name'] );
						}
					}

					$formatted_meta[ $meta_key ] = array(
						'label'     => wc_attribute_label( $attribute_key ),
						'value'     => apply_filters( 'woocommerce_order_item_display_meta_value', $meta_value ),
					);
				}
			}

			return $formatted_meta;
		}
	}


	/**
	 * Helper method to get the version of the currently installed WooCommerce
	 *
	 * @since 2.2-1
	 * @return string woocommerce version number or null
	 */
	private static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}


	/**
	 * Returns true if the installed version of WooCommerce is 2.2 or greater
	 *
	 * @since 2.2
	 * @return boolean true if the installed version of WooCommerce is 2.2 or greater
	 */
	public static function is_wc_version_gte_2_2() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.2', '>=' );
	}


	/**
	 * Returns true if the installed version of WooCommerce is greater than $version
	 *
	 * @since 2.0
	 * @param string $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gt( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
	}


}


endif; // Class exists check
