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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_API_REST_Request' ) ) :


/**
 * Base REST API Request class
 *
 * @since 4.0.0
 */
class SV_WC_API_REST_Request implements SV_WC_API_Request {


	/** @var string the request method, one of HEAD, GET, PUT, PATCH, POST, DELETE */
	protected $method;

	/** @var string the request path */
	protected $path;

	/** @var array the request parameters, if any */
	protected $params;


	/**
	 * Construct REST request object
	 *
	 * @since 4.0.0
	 * @param string $method the request method, one of HEAD, GET, PUT, PATCH, POST, DELETE
	 * @param string $path optional request path
	 * @param array $params optional associative array of request parameters
	 */
	public function __construct( $method, $path = '', $params = array() ) {
		$this->method = $method;
		$this->path   = $path;
		$this->params = $params;
	}


	/** Getter Methods ******************************************************/


	/**
	 * Returns the method for this request: one of HEAD, GET, PUT, PATCH, POST, DELETE
	 *
	 * @since 4.0.0
	 * @see SV_WC_API_Request::get_method()
	 * @return string the request method
	 */
	public function get_method() {
		return $this->method;
	}


	/**
	 * Returns the request path
	 *
	 * @since 4.0.0
	 * @see SV_WC_API_Request::get_path()
	 * @return string the request path
	 */
	public function get_path() {
		return $this->path;
	}


	/**
	 * Returns the request params, if any
	 *
	 * @since 4.0.0
	 * @return array the request params
	 */
	public function get_params() {
		return $this->params;
	}


	/** API Helper Methods ******************************************************/


	/**
	 * Returns the string representation of this request
	 *
	 * @since 4.0.0
	 * @see SV_WC_API_Request::to_string()
	 * @return string request
	 */
	public function to_string() {

		// URL encode params
		return http_build_query( $this->get_params() );
	}


	/**
	 * Returns the string representation of this request with any and all
	 * sensitive elements masked or removed
	 *
	 * @since 4.0.0
	 * @see SV_WC_API_Request::to_string_safe()
	 * @return string the request, safe for logging/displaying
	 */
	public function to_string_safe() {

		return $this->to_string();
	}


}

endif;
