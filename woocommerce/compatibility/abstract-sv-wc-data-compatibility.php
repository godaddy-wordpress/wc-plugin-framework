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

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_12\\SV_WC_Data_Compatibility' ) ) :


/**
 * WooCommerce data compatibility class.
 *
 * @since 4.6.0
 * @deprecated 5.5.0
 */
abstract class SV_WC_Data_Compatibility {


	/** @deprecated 5.5.0 backwards compatibility property map */
	protected static $compat_props = [];


	/**
	 * Gets an object property.
	 *
	 * @see \WC_Data::get_prop()
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $prop the property name
	 * @param string $context if 'view' then the value will be filtered
	 * @param array $compat_props compatibility properties unused since 5.5.0
	 * @return null|mixed
	 */
	public static function get_prop( $object, $prop, $context = 'edit', $compat_props = [] ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Data::get_prop()' );

		return is_callable( [ $object, "get_{$prop}" ] ) ? $object->{"get_{$prop}"}( $context ) : null;
	}


	/**
	 * Sets an object's properties.
	 *
	 * Note that this does not save any data to the database.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param array $props the new properties as $key => $value
	 * @param array $compat_props compatibility properties, unused since 5.5.0
	 * @return bool|\WP_Error
	 */
	public static function set_props( $object, $props, $compat_props = [] ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Data::set_props()' );

		return $object->set_props( $props );
	}


	/**
	 * Gets an object's stored meta value.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $key the meta key
	 * @param bool $single whether to get the meta as a single item. Defaults to `true`
	 * @param string $context if 'view' then the value will be filtered
	 * @return mixed
	 */
	public static function get_meta( $object, $key = '', $single = true, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Data::get_meta()' );

		return $object->get_meta( $key, $single, $context );
	}


	/**
	 * Stores an object meta value.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $key the meta key
	 * @param string $value the meta value
	 * @param bool $unique optional: whether the meta should be unique
	 */
	public static function add_meta_data( $object, $key, $value, $unique = false ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Data::add_meta_data()' );

		$object->add_meta_data( $key, $value, $unique );
		$object->save_meta_data();
	}


	/**
	 * Updates an object's stored meta value.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $key the meta key
	 * @param string $value the meta value
	 * @param int|string $meta_id optional: the specific meta ID to update
	 */
	public static function update_meta_data( $object, $key, $value, $meta_id = '' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Data::update_meta_data()' );

		$object->update_meta_data( $key, $value, $meta_id );
		$object->save_meta_data();
	}


	/**
	 * Deletes an object's stored meta value.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $key the meta key
	 */
	public static function delete_meta_data( $object, $key ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Data::delete_meta_data()' );

		$object->delete_meta_data( $key );
		$object->save_meta_data();
	}


}


endif;
