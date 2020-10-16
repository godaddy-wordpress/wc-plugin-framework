<?php

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Payment_Form;

/**
 * Tests for the SV_WC_Payment_Gateway_Payment_Form class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Payment_Form
 */
class PaymentFormTest extends \Codeception\TestCase\WPTestCase {


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


	/**
	 * @see SV_WC_Payment_Gateway_Payment_Form::get_js_handler_args.
	 */
	public function test_get_js_handler_args() {

		$method  = new ReflectionMethod( SV_WC_Payment_Gateway_Payment_Form::class, 'get_js_handler_args' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->get_plugin()->get_gateway()->get_payment_form_instance() );

		$expected_result = [
			'plugin_id'               => $this->get_plugin()->get_id(),
			'id'                      => $this->get_plugin()->get_gateway()->get_id(),
			'id_dasherized'           => $this->get_plugin()->get_gateway()->get_id_dasherized(),
			'type'                    => $this->get_plugin()->get_gateway()->get_payment_type(),
			'csc_required'            => $this->get_plugin()->get_gateway()->csc_enabled(),
			'csc_required_for_tokens' => $this->get_plugin()->get_gateway()->csc_enabled_for_tokens(),
		];

		$this->assertNotEmpty( $result );

		// because assertArraySubset is being deprecated
		foreach ( $expected_result as $key => $value ) {

			$this->assertArrayHasKey( $key, $result );
			$this->assertSame( $value, $result[ $key ] );
		}
	}


	/**
	 * @see SV_WC_Payment_Gateway_Payment_Form::render_js.
	 */
	public function test_render_js() {

		global $wc_queued_js;

		// reset queued scripts
		$wc_queued_js = '';

		$this->get_plugin()->get_gateway()->get_payment_form_instance()->render_js();

		$this->assertStringContainsString( 'function load_test_gateway_payment_form_handler', $wc_queued_js );
		$this->assertStringContainsString( 'window.jQuery( document.body ).on( \'sv_wc_payment_form_handler_v5_10_0_loaded\', load_test_gateway_payment_form_handler );', $wc_queued_js );
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
