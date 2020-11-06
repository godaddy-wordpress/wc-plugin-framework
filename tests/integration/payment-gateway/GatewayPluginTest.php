<?php

/**
 * Tests for the gateway plugin class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin
 */
class GatewayPluginTest extends \Codeception\TestCase\WPTestCase {


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
	 * Tests get_id.
	 */
	public function test_get_id() {

		$this->assertEquals( 'gateway_test_plugin', $this->get_plugin()->get_id() );
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
