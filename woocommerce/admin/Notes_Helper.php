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
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Admin;

use Automattic\WooCommerce\Admin\Notes as WooCommerce_Admin_Notes;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Admin\\Notes_Helper' ) ) :

/**
 * Helper class for WooCommerce enhanced admin notes.
 *
 * @since 5.6.0
 */
class Notes_Helper {


	/** Conditional methods *******************************************************************************************/


	/**
	 * Determines if any notes with the given name exist.
	 *
	 * @since 5.6.0
	 *
	 * @param string $name note name
	 * @return bool
	 */
	public static function note_with_name_exists( $name ) {

		return ! empty( self::get_note_ids_with_name( $name ) );
	}


	/** Getter methods ************************************************************************************************/


	/**
	 * Gets a note with the given name.
	 *
	 * @since 5.6.0
	 *
	 * @param string $name name of the note to get
	 * @return WooCommerce_Admin_Notes\WC_Admin_Note|null
	 */
	public static function get_note_with_name( $name ) {

		$note     = null;
		$note_ids = self::get_note_ids_with_name( $name );

		if ( ! empty( $note_ids ) ) {

			$note_id = current( $note_ids );

			$note = WooCommerce_Admin_Notes\WC_Admin_Notes::get_note( $note_id );
		}

		return $note ?: null;
	}


	/**
	 * Gets all notes with the given name.
	 *
	 * @since 5.6.0
	 *
	 * @param string $name note name
	 * @return int[]
	 */
	public static function get_note_ids_with_name( $name ) {

		$note_ids = [];

		try {

			/** @var WooCommerce_Admin_Notes\DataStore $data_store */
			$data_store = \WC_Data_Store::load( 'admin-note' );

			$note_ids = $data_store->get_notes_with_name( $name );

		} catch ( \Exception $exception ) {}

		return $note_ids;
	}


	/**
	 * Gets all note IDs from the given source.
	 *
	 * @since 5.6.1
	 *
	 * @param string $source note source
	 * @return int[]
	 */
	public static function get_note_ids_with_source( $source ) {
		global $wpdb;

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT note_id FROM {$wpdb->prefix}wc_admin_notes WHERE source = %s ORDER BY note_id ASC",
				$source
			)
		);
	}


	/**
	 * Deletes all notes from the given source.
	 *
	 * @since 5.6.1
	 *
	 * @param string $source source name
	 */
	public static function delete_notes_with_source( $source ) {

		foreach ( self::get_note_ids_with_source( $source ) as $note_id ) {

			if ( $note = WooCommerce_Admin_Notes\WC_Admin_Notes::get_note( $note_id ) ) {
				$note->delete();
			}
		}
	}


}

endif;
