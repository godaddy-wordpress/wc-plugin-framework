<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_My_Payment_Methods;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

/**
 * Tests for the SV_WC_Payment_Gateway_My_Payment_Methods class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_My_Payment_Methods
 */
class MyPaymentMethodsTest extends \Codeception\TestCase\WPTestCase {


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
	 * @see SV_WC_Payment_Gateway_My_Payment_Methods::get_js_handler_class_name.
	 */
	public function test_get_js_handler_class_name() {

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_Plugin::class, 'get_my_payment_methods_instance' );
		$method->setAccessible( true );

		$payment_methods = $method->invoke( $this->get_plugin() );

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_My_Payment_Methods::class, 'get_js_handler_class_name' );
		$method->setAccessible( true );

		$result = $method->invoke( $payment_methods );

		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( 'SV_WC_Payment_Methods_Handler', $result );
		$this->assertNotEquals( 'SV_WC_Payment_Methods_Handler', $result );
	}


	/**
	 * @see SV_WC_Payment_Gateway_My_Payment_Methods::get_js_handler_args.
	 */
	public function test_get_js_handler_args() {

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_Plugin::class, 'get_my_payment_methods_instance' );
		$method->setAccessible( true );

		$payment_methods = $method->invoke( $this->get_plugin() );

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_My_Payment_Methods::class, 'get_js_handler_args' );
		$method->setAccessible( true );

		$result = $method->invoke( $payment_methods );

		$expected_result = [
			'id'              => $this->get_plugin()->get_id(),
			'slug'            => $this->get_plugin()->get_id_dasherized(),
			'has_core_tokens' => false,
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'      => wp_create_nonce( 'wc_' . $this->get_plugin()->get_id() . '_save_payment_method' ),
			'i18n'            => [
				'edit_button'   => esc_html__( 'Edit', 'woocommerce-plugin-framework' ),
				'cancel_button' => esc_html__( 'Cancel', 'woocommerce-plugin-framework' ),
				'save_error'    => esc_html__( 'Oops, there was an error updating your payment method. Please try again.', 'woocommerce-plugin-framework' ),
				'delete_ays'    => esc_html__( 'Are you sure you want to delete this payment method?', 'woocommerce-plugin-framework' ),
			],
		];

		$this->assertNotEmpty( $result );

		// because assertArraySubset is being deprecated
		foreach ( $expected_result as $key => $value ) {

			$this->assertArrayHasKey( $key, $result );
			$this->assertSame( $value, $result[ $key ] );
		}
	}


	/**
	 * @see SV_WC_Payment_Gateway_My_Payment_Methods::render_js.
	 */
	public function test_render_js() {

		global $wc_queued_js;

		// reset queued scripts
		$wc_queued_js = '';

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_Plugin::class, 'get_my_payment_methods_instance' );
		$method->setAccessible( true );

		$payment_methods = $method->invoke( $this->get_plugin() );

		$property = new ReflectionProperty( SV_WC_Payment_Gateway_My_Payment_Methods::class, 'has_tokens' );
		$property->setAccessible( true );

		$property->setValue( $payment_methods, true );

		$payment_methods->render_js();

		$this->assertStringContainsString( 'function load_gateway_test_plugin_payment_methods_handler', $wc_queued_js );
		$this->assertStringContainsString( 'window.jQuery( document.body ).on( \'sv_wc_payment_methods_handler_v5_10_0_loaded\', load_gateway_test_plugin_payment_methods_handler );', $wc_queued_js );
	}


	/** Helper methods ************************************************************************************************/


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
