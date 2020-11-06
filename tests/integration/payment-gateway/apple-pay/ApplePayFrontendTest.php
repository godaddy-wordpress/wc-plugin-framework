<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Apple_Pay;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Apple_Pay_Frontend;

/**
 * Tests for the SV_WC_Payment_Gateway_Apple_Pay_Frontend class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Apple_Pay_Frontend
 */
class ApplePayFrontendTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var \SkyVerge\WooCommerce\GatewayTestPlugin\Plugin instance */
	protected $plugin;


	protected function _before() {

	}


	protected function _after() {


	}


	/** Tests *********************************************************************************************************/


	/**
	 * @see SV_WC_Payment_Gateway_Apple_Pay_Frontend::get_js_handler_class_name.
	 */
	public function test_get_js_handler_class_name() {

		$frontend_instance = $this->get_frontend_instance();

		$method  = new ReflectionMethod( $frontend_instance, 'get_js_handler_class_name' );
		$method->setAccessible( true );

		$result = $method->invoke( $frontend_instance );

		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( 'SV_WC_Apple_Pay_Handler', $result );
		$this->assertNotEquals( 'SV_WC_Apple_Pay_Handler', $result );
	}


	/**
	 * @see SV_WC_Payment_Gateway_Apple_Pay_Frontend::get_js_handler_params.
	 */
	public function test_get_js_handler_args() {

		$frontend_instance = $this->get_frontend_instance();

		$method  = new ReflectionMethod( $frontend_instance, 'get_js_handler_args' );
		$method->setAccessible( true );

		$result = $method->invoke( $frontend_instance );

		$get_handler_method = new ReflectionMethod( $frontend_instance, 'get_handler' );
		$get_handler_method->setAccessible( true );

		$expected_result = [
			'gateway_id'               => $this->get_plugin()->get_gateway()->get_id(),
			'gateway_id_dasherized'    => $this->get_plugin()->get_gateway()->get_id_dasherized(),
			'merchant_id'              => $get_handler_method->invoke( $frontend_instance )->get_merchant_id(),
			'ajax_url'                 => admin_url( 'admin-ajax.php' ),
			'validate_nonce'           => wp_create_nonce( 'wc_' . $this->get_plugin()->get_gateway()->get_id() . '_apple_pay_validate_merchant' ),
			'recalculate_totals_nonce' => wp_create_nonce( 'wc_' . $this->get_plugin()->get_gateway()->get_id() . '_apple_pay_recalculate_totals' ),
			'process_nonce'            => wp_create_nonce( 'wc_' . $this->get_plugin()->get_gateway()->get_id() . '_apple_pay_process_payment' ),
			'generic_error'            => __( 'An error occurred, please try again or try an alternate form of payment', 'woocommerce-plugin-framework' ),
		];

		$this->assertNotEmpty( $result );

		// because assertArraySubset is being deprecated
		foreach ( $expected_result as $key => $value ) {

			$this->assertArrayHasKey( $key, $result );
			$this->assertSame( $value, $result[ $key ] );
		}
	}


	/**
	 * @see SV_WC_Payment_Gateway_Apple_Pay_Frontend::enqueue_js_handler.
	 */
	public function test_enqueue_js_handler() {

		global $wc_queued_js;

		// reset queued scripts
		$wc_queued_js = '';

		$frontend_instance = $this->get_frontend_instance();

		$method  = new ReflectionMethod( $frontend_instance, 'enqueue_js_handler' );
		$method->setAccessible( true );

		$method->invokeArgs( $frontend_instance, [[]] );

		$this->assertStringContainsString( 'function load_test_gateway_apple_pay_handler', $wc_queued_js );
		$this->assertStringContainsString( 'window.jQuery( document.body ).on( \'sv_wc_apple_pay_handler_v5_10_0_loaded\', load_test_gateway_apple_pay_handler );', $wc_queued_js );
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Gets the Apple Pay frontend instance.
	 *
	 * @return SV_WC_Payment_Gateway_Apple_Pay_Frontend
	 */
	private function get_frontend_instance() {

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_Plugin::class, 'get_apple_pay_instance' );
		$method->setAccessible( true );

		$apple_pay_instance = $method->invoke( $this->get_plugin() );

		$apple_pay_instance = $this->make( $apple_pay_instance, [
			'get_supporting_gateways' => [ $this->get_plugin()->get_gateway() ],
		] );

		return new SV_WC_Payment_Gateway_Apple_Pay_Frontend( $this->get_plugin(), $apple_pay_instance );
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @return \SkyVerge\WooCommerce\GatewayTestPlugin\Plugin
	 */
	protected function get_plugin() {

		if ( null === $this->plugin ) {
			$this->plugin = sv_wc_gateway_test_plugin();
		}

		return $this->plugin;
	}


}
