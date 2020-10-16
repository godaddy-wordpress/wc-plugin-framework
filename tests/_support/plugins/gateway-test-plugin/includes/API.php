<?php

namespace SkyVerge\WooCommerce\GatewayTestPlugin;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

defined( 'ABSPATH' ) or exit;

class API implements Framework\SV_WC_Payment_Gateway_API {

	public function credit_card_authorization( \WC_Order $order ) { }

	public function credit_card_charge( \WC_Order $order ) { }

	public function credit_card_capture( \WC_Order $order ) { }

	public function check_debit( \WC_Order $order ) { }

	public function refund( \WC_Order $order ) { }

	public function void( \WC_Order $order ) { }

	public function tokenize_payment_method( \WC_Order $order ) { }

	public function update_tokenized_payment_method( \WC_Order $order ) { }

	public function supports_update_tokenized_payment_method() { }

	public function remove_tokenized_payment_method( $token, $customer_id ) { }

	public function supports_remove_tokenized_payment_method() {

		return false;
	}

	public function get_tokenized_payment_methods( $customer_id ) { }

	public function supports_get_tokenized_payment_methods() { }

	public function get_request() { }

	public function get_response() { }

	public function get_order() { }
}

