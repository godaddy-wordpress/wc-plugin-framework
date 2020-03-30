<?php

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Payment_Gateway_My_Payment_Methods;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Payment_Gateway_Plugin;

/**
 * Tests for the SV_WC_Payment_Gateway_My_Payment_Methods class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Payment_Gateway_My_Payment_Methods
 */
class MyPaymentMethodsTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var \SkyVerge\WooCommerce\GatewayTestPlugin\Plugin instance */
	protected $plugin;


	protected function _before() {

		require_once 'woocommerce/payment-gateway/Frontend/Script_Handler.php';
		require_once 'woocommerce/payment-gateway/class-sv-wc-payment-gateway-my-payment-methods.php';
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
