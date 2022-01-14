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
 * @package   SkyVerge/WooCommerce/Compatibility
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2022, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_12;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_12\\SV_WC_Product_Compatibility' ) ) :


/**
 * WooCommerce product compatibility class.
 *
 * @since 4.6.0
 */
class SV_WC_Product_Compatibility extends SV_WC_Data_Compatibility {


	/**
	 * Gets a product property.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $object the product object
	 * @param string $prop the property name
	 * @param string $context if 'view' then the value will be filtered
	 * @param array $compat_props compatibility arguments, unused since 5.5.0
	 * @return mixed
	 */
	public static function get_prop( $object, $prop, $context = 'edit', $compat_props = [] ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Product::get_prop()' );

		return parent::get_prop( $object, $prop, $context, self::$compat_props );
	}


	/**
	 * Sets an products's properties.
	 *
	 * Note that this does not save any data to the database.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $object the product object
	 * @param array $props the new properties as $key => $value
	 * @param array $compat_props compatibility arguments, unused since 5.5.0
	 * @return bool|\WP_Error
	 */
	public static function set_props( $object, $props, $compat_props = [] ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Product::set_props()' );

		return parent::set_props( $object, $props, self::$compat_props );
	}


	/**
	 * Gets a product's parent product.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $product the product object
	 * @return \WC_Product|bool
	 */
	public static function get_parent( \WC_Product $product ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_get_product( \WC_Product::get_parent_id() )' );

		return wc_get_product( $product->get_parent_id() );
	}


	/**
	 * Updates product stock.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $product the product object
	 * @param null|int $amount optional: the new stock quantity
	 * @param string $mode optional: can be set (default), add, or subtract
	 * @return int
	 */
	public static function wc_update_product_stock( \WC_Product $product, $amount = null, $mode = 'set' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_update_product_stock()' );

		return wc_update_product_stock( $product, $amount, $mode );
	}


	/**
	 * Gets the product price HTML from text.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $product the product object
	 * @return string
	 */
	public static function wc_get_price_html_from_text( \WC_Product $product ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_get_price_html_from_text()' );

		return wc_get_price_html_from_text();
	}


	/**
	 * Gets the product price including tax.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $product the product object
	 * @param int $qty optional: the quantity
	 * @param string $price optional: the product price
	 * @return string
	 */
	public static function wc_get_price_including_tax( \WC_Product $product, $qty = 1, $price = '' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_get_price_including_tax()' );

		return wc_get_price_including_tax( $product, [
			'qty'   => $qty,
			'price' => $price,
		] );
	}


	/**
	 * Gets the product price excluding tax.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $product the product object
	 * @param int $qty optional: The quantity
	 * @param string $price optional: the product price
	 * @return string
	 */
	public static function wc_get_price_excluding_tax( \WC_Product $product, $qty = 1, $price = '' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_get_price_excluding_tax()' );

		return wc_get_price_excluding_tax( $product, [
			'qty'   => $qty,
			'price' => $price,
		] );
	}


	/**
	 * Gets the product price to display.
	 *
	 * @since 4.6.0
	 *
	 * @param \WC_Product $product the product object
	 * @param string $price optional: the product price
	 * @param int $qty optional: the quantity
	 * @return string
	 */
	public static function wc_get_price_to_display( \WC_Product $product, $price = '', $qty = 1 ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_get_price_to_display()' );

		return wc_get_price_to_display( $product, [
			'qty'   => $qty,
			'price' => $price,
		] );
	}


	/**
	 * Gets the product category list.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $product the product object
	 * @param string $sep optional: the list separator
	 * @param string $before optional: to display before the list
	 * @param string $after optional: to display after the list
	 * @return string
	 */
	public static function wc_get_product_category_list( \WC_Product $product, $sep = ', ', $before = '', $after = '' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_get_product_category_list()' );

		$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

		return wc_get_product_category_list( $id, $sep, $before, $after );
	}


	/**
	 * Formats the product rating HTML.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Product $product the product object, unused since 5.5.0
	 * @param null|string $rating optional: the product rating
	 * @return string
	 */
	public static function wc_get_rating_html( \WC_Product $product, $rating = null ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_get_rating_html()' );

		return wc_get_rating_html( $rating );
	}


}


endif;
