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
 * @package   SkyVerge/WooCommerce/API/Response
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_API_XML_Response' ) ) :

/**
 * Base XML API response class.
 *
 * @since 4.3.0-dev
 */
abstract class SV_WC_API_XML_Response implements SV_WC_API_Response {


	/** @var string string representation of this response */
	protected $raw_response_xml;

	/** @var SimpleXMLElement XML object */
	protected $response_xml;


	/**
	 * Build an XML object from the raw response.
	 *
	 * @since 4.3.0-dev
	 * @param string $raw_response_xml The raw response XML
	 */
	public function __construct( $raw_response_xml ) {

		$this->raw_response_xml = $raw_response_xml;

		// LIBXML_NOCDATA ensures that any XML fields wrapped in [CDATA] will be included as text nodes
		$this->response_xml = new SimpleXMLElement( $raw_response_xml, LIBXML_NOCDATA );
	}


	/**
	 * Get the string representation of this response.
	 *
	 * @since 4.3.0-dev
	 * @return string
	 */
	public function to_string() {

		$response = $this->raw_response_xml;

		$dom = new DOMDocument();

		// suppress errors for invalid XML syntax issues
		if ( @$dom->loadXML( $response ) ) {
			$dom->formatOutput = true;
			$response = $dom->saveXML();
		}

		return $response;
	}


	/**
	 * Get the string representation of this response with any and all sensitive elements masked
	 * or removed.
	 *
	 * @since 4.3.0-dev
	 * @see SV_WC_API_Response::to_string_safe()
	 * @return string
	 */
	public function to_string_safe() {

		return $this->to_string();
	}


}

endif; // class exists check
