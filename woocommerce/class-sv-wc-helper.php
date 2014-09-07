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

if ( ! class_exists( 'SV_WC_Helper' ) ) :

	/**
	 * SkyVerge Helper Class
	 *
	 * The purpose of this class is to centralize common utility functions that
	 * are commonly used in SkyVerge plugins
	 *
	 * @since 2.2.0
	 */
	class SV_WC_Helper {


		/** String manipulation functions (all multi-byte safe) ***************/

		/**
		 * Returns true if the haystack string starts with needle
		 *
		 * Note: case-sensitive
		 *
		 * @since 2.2.0
		 * @param $haystack
		 * @param $needle
		 * @return bool
		 */
		public static function str_starts_with( $haystack, $needle) {

			if ( '' === $needle ) {
				return true;
			}

			if ( self::multibyte_loaded() ) {

				return 0 === mb_strpos( $haystack, $needle );

			} else {

				return 0 === strpos( self::str_to_ascii( $haystack ), self::str_to_ascii( $needle ) );
			}
		}


		/**
		 * Return true if the haystack string ends with needle
		 *
		 * Note: case-sensitive
		 *
		 * @since 2.2.0
		 * @param $haystack
		 * @param $needle
		 * @return bool
		 */
		public static function str_ends_with( $haystack, $needle ) {

			if ( '' === $needle ) {
				return true;
			}

			if ( self::multibyte_loaded() ) {

				return mb_substr( $haystack, -mb_strlen( $needle ) ) === $needle;

			} else {

				$haystack = self::str_to_ascii( $haystack );
				$needle   = self::str_to_ascii( $needle );

				return substr( $haystack, -strlen( $needle ) ) === $needle;
			}
		}


		/**
		 * Returns true if the needle exists in haystack
		 *
		 * Note: case-sensitive
		 *
		 * @since 2.2.0
		 * @param $haystack
		 * @param $needle
		 * @return bool
		 */
		public static function str_exists( $haystack, $needle ) {

			if ( self::multibyte_loaded() ) {

				return false !== mb_strpos( $haystack, $needle );

			} else {

				return false !== strpos( self::str_to_ascii( $haystack ), self::str_to_ascii( $needle ) );
			}
		}


		/**
		 * Truncates a given $string after a given $length if string is longer than
		 * $length. The last characters will be replaced with the $omission string
		 * for a total length not exceeding $length
		 *
		 * @since 2.2.0
		 * @param string $string text to truncate
		 * @param int $length total desired length of string, including omission
		 * @param string $omission omission text, defaults to '...'
		 * @return string
		 */
		public static function str_truncate( $string, $length, $omission = '...' ) {

			if ( self::multibyte_loaded() ) {

				// bail if string doesn't need to be truncated
				if ( mb_strlen( $string ) <= $length ) {
					return $string;
				}

				$length -= mb_strlen( $omission );

				return mb_substr( $string, 0, $length ) . $omission;

			} else {

				$string = self::str_to_ascii( $string );

				// bail if string doesn't need to be truncated
				if ( strlen( $string ) <= $length ) {
					return $string;
				}

				$length -= strlen( $omission );

				return substr( $string, 0, $length ) . $omission;
			}
		}


		/**
		 * Returns a string with all non-ASCII characters removed. This is useful
		 * for any string functions that expect only ASCII chars and can't
		 * safely handle UTF-8
		 *
		 * @since 2.2.0
		 * @param string $string string to make ASCII
		 * @return string|null ASCII string or null if error occurred
		 */
		public static function str_to_ascii( $string ) {

			return iconv('UTF-8', 'ASCII//IGNORE', $string);
		}


		/**
		 * Helper method to check if the multibyte extension is loaded, which
		 * indicates it's safe to use the mb_*() string methods
		 *
		 * @since 2.2.0
		 * @return bool
		 */
		private static function multibyte_loaded() {

			return extension_loaded( 'mbstring' );
		}


		/** Array functions ***************************************************/


		/**
		 * Insert the given element after the given key in the array
		 *
		 * Sample usage:
		 *
		 * given
		 *
		 * array( 'item_1' => 'foo', 'item_2' => 'bar' )
		 *
		 * array_insert_after( $array, 'item_1', array( 'item_1.5' => 'w00t' ) )
		 *
		 * becomes
		 *
		 * array( 'item_1' => 'foo', 'item_1.5' => 'w00t', 'item_2' => 'bar' )
		 *
		 * @since 2.2.0
		 * @param array $array array to insert the given element into
		 * @param string $insert_key key to insert given element after
		 * @param array $element element to insert into array
		 * @return array
		 */
		public static function array_insert_after( Array $array, $insert_key, Array $element ) {

			$new_array = array();

			foreach ( $array as $key => $value ) {

				$new_array[ $key ] = $value;

				if ( $insert_key == $key ) {

					foreach ( $element as $k => $v ) {
						$new_array[ $k ] = $v;
					}
				}
			}

			return $new_array;
		}


		/**
		 * Convert array into XML by recursively generating child elements
		 *
		 * First instantiate a new XML writer object:
		 *
		 * $xml = new XMLWriter();
		 *
		 * Open in memory (alternatively you can use a local URI for file output)
		 *
		 * $xml->openMemory();
		 *
		 * Then start the document
		 *
		 * $xml->startDocument( '1.0', 'UTF-8' );
		 *
		 * Don't forget to end the document and output the memory
		 *
		 * $xml->endDocument();
		 *
		 * $your_xml_string = $xml->outputMemory();
		 *
		 * @since 2.2.0
		 * @param \XMLWriter $xml_writer XML writer instance
		 * @param string|array $element_key name for element, e.g. <per_page>
		 * @param string|array $element_value value for element, e.g. 100
		 * @return string generated XML
		 */
		public static function array_to_xml( $xml_writer, $element_key, $element_value = array() ) {

			if ( is_array( $element_value ) ) {

				// handle attributes
				if ( '@attributes' === $element_key ) {
					foreach ( $element_value as $attribute_key => $attribute_value ) {

						$xml_writer->startAttribute( $attribute_key );
						$xml_writer->text( $attribute_value );
						$xml_writer->endAttribute();
					}
					return;
				}

				// handle multi-elements (e.g. multiple <Order> elements)
				if ( is_numeric( key( $element_value ) ) ) {

					// recursively generate child elements
					foreach ( $element_value as $child_element_key => $child_element_value ) {

						$xml_writer->startElement( $element_key );

						foreach ( $child_element_value as $sibling_element_key => $sibling_element_value ) {
							self::array_to_xml( $xml_writer, $sibling_element_key, $sibling_element_value );
						}

						$xml_writer->endElement();
					}

				} else {

					// start root element
					$xml_writer->startElement( $element_key );

					// recursively generate child elements
					foreach ( $element_value as $child_element_key => $child_element_value ) {
						self::array_to_xml( $xml_writer, $child_element_key, $child_element_value );
					}

					// end root element
					$xml_writer->endElement();
				}

			} else {

				// handle single elements
				if ( '@value' == $element_key ) {

					$xml_writer->text( $element_value );

				} else {

					// wrap element in CDATA tags if it contains illegal characters
					if ( false !== strpos( $element_value, '<' ) || false !== strpos( $element_value, '>' ) ) {

						$xml_writer->startElement( $element_key );
						$xml_writer->writeCdata( $element_value );
						$xml_writer->endElement();

					} else {

						$xml_writer->writeElement( $element_key, $element_value );
					}

				}

				return;
			}
		}


		/** Number helper functions *******************************************/


		/**
		 * Format a number with 2 decimal points, using a period for the decimal
		 * separator and no thousands separator.
		 *
		 * Commonly used for payment gateways which require amounts in this format.
		 *
		 * @since 3.0.0
		 * @param float $number
		 * @return string
		 */
		public static function number_format( $number ) {

			return number_format( $number, 2, '.', '' );
		}


		/** WooCommerce helper functions **************************************/


		/**
		 * Get order line items (products) in a neatly-formatted array of objects
		 * with properties:
		 *
		 * + id - item ID
		 * + name - item name, usually product title, processed through htmlentities()
		 * + description - formatted item meta (e.g. Size: Medium, Color: blue), processed through htmlentities()
		 * + quantity - item quantity
		 * + item_total - item total (line total divided by quantity, excluding tax & rounded)
		 * + line_total - line item total (excluding tax & rounded)
		 * + meta - formatted item meta array
		 * + product - item product or null if getting product from item failed
		 * + item - raw item array
		 *
		 * @since 3.0.0
		 * @param \WC_Order $order
		 * @return array
		 */
		public static function get_order_line_items( $order ) {

			$line_items = array();

			foreach ( $order->get_items() as $id => $item ) {

				$line_item = new stdClass();

				$product = $order->get_product_from_item( $item );

				// get meta + format it
				$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );

				$item_meta = SV_WC_Plugin_Compatibility::get_formatted_item_meta( $item_meta );

				if ( ! empty( $item_meta ) ) {

					$item_desc = array();

					foreach ( $item_meta as $meta ) {
						$item_desc[] = sprintf( '%s: %s', $meta['label'], $meta['value'] );
					}

					$item_desc = implode( ', ', $item_desc );

				} else {

					// default description to SKU
					$item_desc = is_callable( array( $product, 'get_sku') ) && $product->get_sku() ? sprintf( 'SKU: %s', $product->get_sku() ) : null;
				}

				$line_item->id          = $id;
				$line_item->name        = htmlentities( $item['name'], ENT_QUOTES, 'UTF-8', false );
				$line_item->description = htmlentities( $item_desc, ENT_QUOTES, 'UTF-8', false );
				$line_item->quantity    = $item['qty'];
				$line_item->item_total  = $order->get_item_total( $item );
				$line_item->line_total  = $order->get_line_total( $item );
				$line_item->meta        = $item_meta;
				$line_item->product     = is_object( $product ) ? $product : null;
				$line_item->item        = $item;

				$line_items[] = $line_item;
			}

			return $line_items;
		}


		/**
		 * Safely get and trim data from $_POST
		 *
		 * @since 3.0.0
		 * @param string $key array key to get from $_POST array
		 * @return string value from $_POST or blank string if $_POST[ $key ] is not set
		 */
		public static function get_post( $key ) {

			if ( isset( $_POST[ $key ] ) ) {
				return trim( $_POST[ $key ] );
			}

			return '';
		}


		/**
		 * Safely get and trim data from $_REQUEST
		 *
		 * @since 3.0.0
		 * @param string $key array key to get from $_REQUEST array
		 * @return string value from $_REQUEST or blank string if $_REQUEST[ $key ] is not set
		 */
		public static function get_request( $key ) {

			if ( isset( $_REQUEST[ $key ] ) ) {
				return trim( $_REQUEST[ $key ] );
			}

			return '';
		}


	}

endif; // Class exists check
