<?php

/**
 * Minimal WCS_ATT_Scheme stub for unit tests.
 *
 * The real class is provided by WooCommerce Subscriptions' bundled ("All Products for Subscriptions")
 * scheme mechanism, which is not available in the WP_Mock test environment. This stub only implements
 * the getters SubscriptionProductHelper reads from a scheme.
 */
class WCS_ATT_Scheme {

	private $data;

	public function __construct( array $data = [] ) {
		$this->data = $data;
	}

	public function get_period() {
		return $this->data['period'] ?? '';
	}

	public function get_interval() {
		return $this->data['interval'] ?? 1;
	}

	public function get_length() {
		return $this->data['length'] ?? 0;
	}

	public function get_trial_length() {
		return $this->data['trial_length'] ?? 0;
	}

	public function get_trial_period() {
		return $this->data['trial_period'] ?? '';
	}

	public function get_signup_fee() {
		return $this->data['signup_fee'] ?? 0.0;
	}
}
