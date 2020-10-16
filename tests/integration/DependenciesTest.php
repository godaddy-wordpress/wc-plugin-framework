<?php

/**
 * Tests for the SV_WC_Plugin_Dependencies class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Dependencies
 */
class DependenciesTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var \SkyVerge\WooCommerce\TestPlugin\Plugin instance */
	protected $plugin;


	protected function _before() {


	}


	protected function _after() {


	}


	/** Tests *********************************************************************************************************/


	/**
	 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Dependencies::get_active_scripts_optimization_plugins()
	 */
	public function test_get_active_scripts_optimization_plugins() {

		$this->assertEquals( [], $this->get_plugin()->get_dependency_handler()->get_active_scripts_optimization_plugins() );
	}


	/**
	 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Dependencies::is_scripts_optimization_plugin_active()
	 */
	public function test_is_scripts_optimization_plugin_active() {

		$this->assertEquals( false, $this->get_plugin()->get_dependency_handler()->is_scripts_optimization_plugin_active() );
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Gets the plugin instance.
	 *
	 * @return \SkyVerge\WooCommerce\TestPlugin\Plugin
	 */
	protected function get_plugin() {

		if ( null === $this->plugin ) {
			$this->plugin = sv_wc_test_plugin();
		}

		return $this->plugin;
	}


}
