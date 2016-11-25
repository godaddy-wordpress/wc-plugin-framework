<?php
/**
 * WooCommerce Payment Gateway Framework
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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Apple-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Sets up the Apple Pay API.
 *
 * @since 4.6.0-dev
 */
class SV_WC_Payment_Gateway_Apple_Pay_API extends SV_WC_API_Base {


	/** @var \SV_WC_Payment_Gateway the gateway instance */
	protected $gateway;


	/**
	 * Constructs the class.
	 *
	 * @since 4.6.0-dev
	 * @param \SV_WC_Payment_Gateway the gateway instance
	 */
	public function __construct( SV_WC_Payment_Gateway $gateway ) {

		$this->gateway = $gateway;

		$this->request_uri = 'https://apple-pay-gateway-cert.apple.com/paymentservices/startSession';

		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );

		$this->set_response_handler( 'SV_WC_Payment_Gateway_Apple_Pay_API_Response' );
	}


	/**
	 * Validates the Apple Pay merchant.
	 *
	 * @since 4.6.0-dev
	 * @param string $url the validation URL
	 * @param string $merchant_id the merchant ID to validate
	 * @param string $domain_name the verified domain name
	 * @param string $display_name the merchant display name
	 * @return \SV_WC_Payment_Gateway_Apple_Pay_API_Response the response object
	 */
	public function validate_merchant( $url, $merchant_id, $domain_name, $display_name ) {

		$this->request_uri = $url;

		$request = $this->get_new_request();

		$request->set_merchant_data( $merchant_id, $domain_name, $display_name );

		return $this->perform_request( $request );
	}


	/**
	 * Set the PEM file required for authentication with the Global Gateway API
	 *
	 * @since 4.0.0
	 * @param resource $curl_handle
	 */
	public function set_cert_file( $curl_handle ) {

		if ( ! $curl_handle ) {
			return;
		}

		curl_setopt( $curl_handle, CURLOPT_SSLCERT, get_option( 'sv_wc_apple_pay_cert_path' ) );
	}


	/**
	 * Perform the remote request.
	 *
	 * WP 4.6 decided to make adding our own `curl_setopt` impossible, so we have to build a custom
	 * request in those cases.
	 *
	 * @since 4.1.4
	 * @param string $request_uri the request URL
	 * @param string $request_args the request args as used by `wp_safe_remote_request()`
	 * @return array|WP_Error
	 */
	protected function do_remote_request( $request_uri, $request_args ) {

		// create a custom request for WP 4.6+
		if ( version_compare( get_bloginfo( 'version' ), '4.6', '>=' ) ) {

			$headers = $request_args['headers'];
			$type    = $request_args['method'];
			$data    = $request_args['body'];

			$options = array(
				'timeout'          => $request_args['timeout'],
				'useragent'        => $request_args['user-agent'],
				'blocking'         => $request_args['blocking'],
				'follow_redirects' => false,
				'verify'           => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
				'hooks'            => new Requests_Hooks(),
			);

			// set PEM file cert for requests
			$options['hooks']->register( 'curl.before_send', array( $this, 'set_cert_file' ) );

			// documented by WP in wp-includes/class-wp-http.php
			$options['verify'] = apply_filters( 'https_ssl_verify', $options['verify'] );

			try {

				$response = Requests::request( $request_uri, $headers, $data, $type, $options );

				// convert the response into an array
				$http_response = new WP_HTTP_Requests_Response( $response );
				$response      = $http_response->to_array();

				// add the original object to the array
				$response['http_response'] = $http_response;

			} catch ( Requests_Exception $e ) {

				$response = new WP_Error( 'http_request_failed', $e->getMessage() );
			}

		// otherwise, do a good old-fashioned request
		} else {

			// set PEM file cert for requests
			add_action( 'http_api_curl', array( $this, 'set_cert_file' ) );

			$response = wp_safe_remote_request( $request_uri, $request_args );
		}

		return $response;
	}


	/** Validation methods ****************************************************/


	protected function do_pre_parse_response_validation() {


	}


	protected function do_post_parse_response_validation() {

		$response = $this->get_response();

		if ( $response->get_status_code() && 200 !== $response->get_status_code() ) {
			throw new SV_WC_API_Exception( $response->get_status_message() );
		}
	}


	/** Helper methods ********************************************************/


	/**
	 * Gets a new request object.
	 *
	 * @since 4.6.0-dev
	 * @param array $type Optional. The desired request type
	 * @return \SV_WC_Payment_Gateway_Apple_Pay_API_Request the request object
	 */
	protected function get_new_request( $type = array() ) {

		return new SV_WC_Payment_Gateway_Apple_Pay_API_Request( $this->get_gateway() );
	}


	/**
	 * Gets the gateway instance.
	 *
	 * @since 4.6.0-dev
	 * @return \SV_WC_Payment_Gateway
	 */
	protected function get_gateway() {

		return $this->gateway;
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @since 4.6.0-dev
	 * @return \SV_WC_Payment_Gateway_Plugin
	 */
	protected function get_plugin() {

		return $this->get_gateway()->get_plugin();
	}


}
