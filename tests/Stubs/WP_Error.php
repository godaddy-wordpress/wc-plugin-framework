<?php

/**
 * Minimal WP_Error stub for unit tests.
 *
 * The real WP_Error is not available in the WP_Mock test environment.
 * This stub provides just enough surface area for code that constructs
 * or inspects WP_Error instances to work under test.
 */
class WP_Error {

	public $code;
	public $message;
	public $data;

	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}

	public function get_error_data() {
		return $this->data;
	}
}
