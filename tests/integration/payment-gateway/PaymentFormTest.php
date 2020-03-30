<?php

use SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Payment_Gateway_Payment_Form;

/**
 * Tests for the SV_WC_Payment_Gateway_Payment_Form class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_6_1\SV_WC_Payment_Gateway_Payment_Form
 */
class PaymentFormTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var \SkyVerge\WooCommerce\GatewayTestPlugin\Plugin instance */
	protected $plugin;


	protected function _before() {

		require_once 'woocommerce/payment-gateway/Frontend/Script_Handler.php';
		require_once 'woocommerce/payment-gateway/class-sv-wc-payment-gateway-payment-form.php';
	}


	protected function _after() {


	}


	/** Tests *********************************************************************************************************/


	/**
	 * @see SV_WC_Payment_Gateway_Payment_Form::get_js_handler_class_name.
	 */
	public function test_get_js_handler_class_name() {

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_Payment_Form::class, 'get_js_handler_class_name' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->get_plugin()->get_gateway()->get_payment_form_instance() );

		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( 'SV_WC_Payment_Form_Handler', $result );
		$this->assertNotEquals( 'SV_WC_Payment_Form_Handler', $result );
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
