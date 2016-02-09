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
 * @package   SkyVerge/WooCommerce/API/Request
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'SV_WC_API_XML_Request' ) ) {

/**
 * Base XML API request class.
 *
 * @since 4.3.0-dev
 */
abstract class SV_WC_API_XML_Request extends XMLWriter implements SV_WC_API_Request {

	/** @var string The complete request XML */
	protected $request_xml;


	/**
	 * Build the request.
	 *
	 * @since 4.3.0-dev
	 */
	public function __construct() {

		// Create XML document in memory
		$this->openMemory();

		// Set XML version & encoding
		$this->startDocument( '1.0', 'UTF-8' );
	}


	/**
	 * Get the method for this request.
	 *
	 * @since 4.3.0-dev
	 */
	public function get_method() { }


	/**
	 * Get the path for this request.
	 *
	 * @since 4.3.0-dev
	 * @return string
	 */
	public function get_path() {
		return '';
	}


	/**
	 * Get the complete request XML.
	 *
	 * @since 4.3.0-dev
	 * @return string
	 */
	public function to_xml() {

		if ( ! empty( $this->request_xml ) ) {
			return $this->request_xml;
		}

		$this->endDocument();

		return $this->request_xml = $this->outputMemory();
	}


	/**
	 * Get the string representation of this request
	 *
	 * @since 4.3.0-dev
	 * @see SV_WC_API_Request::to_string()
	 * @return string
	 */
	public function to_string() {

		$request = $this->to_xml();

		$dom = new DOMDocument();

		// suppress errors for invalid XML syntax issues
		if ( @$dom->loadXML( $request ) ) {
			$dom->formatOutput = true;
			$request = $dom->saveXML();
		}

		return $request;
	}


	/**
	 * Get the string representation of this request with any and all sensitive elements masked
	 * or removed.
	 *
	 * @since 4.3.0-dev
	 * @see SV_WC_API_Request::to_string_safe()
	 * @return string
	 */
	public function to_string_safe() {

		return $this->to_string();
	}


}

}
