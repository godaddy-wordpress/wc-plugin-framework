<?php

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Payment_Gateway_Apple_Pay;
use SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Payment_Gateway_Apple_Pay_Frontend;

/**
 * Tests for the SV_WC_Payment_Gateway_Apple_Pay_Frontend class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Payment_Gateway_Apple_Pay_Frontend
 */
class ApplePayFrontendTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var \SkyVerge\WooCommerce\GatewayTestPlugin\Plugin instance */
	protected $plugin;


	protected function _before() {

		require_once 'woocommerce/payment-gateway/Frontend/Script_Handler.php';
		require_once 'woocommerce/payment-gateway/apple-pay/class-sv-wc-payment-gateway-apple-pay.php';
		require_once 'woocommerce/payment-gateway/apple-pay/class-sv-wc-payment-gateway-apple-pay-frontend.php';
	}


	protected function _after() {


	}


	/** Tests *********************************************************************************************************/


	/**
	 * @see SV_WC_Payment_Gateway_Apple_Pay_Frontend::get_js_handler_class_name.
	 */
	public function test_get_js_handler_class_name() {

		$property = new ReflectionProperty( SV_WC_Payment_Gateway_Apple_Pay::class, 'frontend' );
		$property->setAccessible( true );

		$frontend = $property->getValue( $this->get_plugin()->get_apple_pay_instance() );

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_Apple_Pay_Frontend::class, 'get_js_handler_class_name' );
		$method->setAccessible( true );

		$result = $method->invoke( $frontend );

		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( 'SV_WC_Apple_Pay_Handler', $result );
		$this->assertNotEquals( 'SV_WC_Apple_Pay_Handler', $result );
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
