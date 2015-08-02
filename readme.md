# WooCommerce Plugin Framework

This is a SkyVerge library module: a full featured WooCommerce Plugin Framework

## Sample API Implementation

The following is a sample basic REST API implementation using the API classes/interfaces provided by the framework. Comments are largely omitted for brevity:

```php
class WC_Acme_API extends SV_WC_API_Base {

	/** the base API endpoint */
	const API_ENDPOINT = 'https://api.acme.com';

	/** @var string Acme API key */
	private $api_key;

	public function __construct( $api_key ) {

		// set auth creds
		$this->api_key = $api_key;

		// set up the request defaults
		$this->request_uri = self::API_ENDPOINT;
		$this->set_request_content_type_header( 'application/x-www-form-urlencoded' );
		$this->set_request_accept_header( 'application/json' );
		$this->set_request_header( 'Authorization', 'Token ' . $this->api_key );

		$this->response_handler = 'SV_WC_API_JSON_Response';
	}

	// get some API resource by name
	public function get_product( $name ) {
		return $this->perform_request( $this->get_new_request( array( 'method' => 'GET', 'path' => '/products', 'params' => array( 'name' => $name ) ) ) );
	}

	// create some API resource
	public function create_product( $name, $sku ) {
		return $this->perform_request( $this->get_new_request( array( 'method' => 'POST', 'path' => '/products', 'params' => array( 'name' => $name, 'sku' => $sku ) ) ) );
	}

	// handle non-200 responses
	protected function do_pre_parse_response_validation() {
		if ( 200 != $this->get_response_code() ) {
			// need the parsed response, which we don't have access to yet
			$response = $this->get_parsed_response( $this->get_raw_response_body() );
			throw new SV_WC_API_Exception( $response->error->message );
		}
	}

	protected function get_new_request( $args = array() ) {
		return new SV_WC_API_REST_Request( $args['method'], $args['path'], $args['params'] );
	}

	protected function get_plugin() {
		return wc_acme();
	}

}

// usage
$api = new WC_Acme_API( $secret_key );
$api->create_product( 'widget', '122' );
$product = $api->get_product( 'widget' );
echo $product->sku;
```
