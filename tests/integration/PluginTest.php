<?php

/**
 * Tests for the base plugin class.
 *
 * @see \SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin
 */
class PluginTest extends \Codeception\TestCase\WPTestCase {


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
	 * Tests get_id.
	 */
	public function test_get_id() {

		$this->assertEquals( 'test_plugin', $this->get_plugin()->get_id() );
	}


	/**
	 * Tests get_id_dasherized.
	 */
	public function test_get_id_dasherized() {

		$this->assertEquals( 'test-plugin', $this->get_plugin()->get_id_dasherized() );
	}


	/**
	 * Tests get_version.
	 */
	public function test_get_version() {

		$this->assertEquals( '1.0.0', $this->get_plugin()->get_version() );
	}


	/**
	 * Tests get_plugin_file.
	 */
	public function test_get_plugin_file() {

		$this->assertEquals( 'test-plugin/test-plugin.php', $this->get_plugin()->get_plugin_file() );
	}


	/**
	 * Tests get_plugin_path.
	 */
	public function test_get_plugin_path() {

		$path = $this->get_plugin()->get_plugin_path();

		$this->assertStringEndsWith( 'wp-content/plugins/test-plugin', $path );
		$this->assertStringStartsNotWith( 'http', $path );
	}


	/**
	 * Tests get_plugin_url.
	 */
	public function test_get_plugin_url() {

		$url = $this->get_plugin()->get_plugin_url();

		$this->assertStringEndsWith( 'wp-content/plugins/test-plugin', $url );
		$this->assertStringStartsWith( 'http', $url );
	}


	/**
	 * Tests get_woocommerce_uploads_path.
	 */
	public function test_get_woocommerce_uploads_path() {

		$path = $this->get_plugin()->get_woocommerce_uploads_path();

		$this->assertStringEndsWith( 'wp-content/uploads/woocommerce_uploads', $path );
		$this->assertStringStartsNotWith( 'http', $path );
	}


	/**
	 * Tests get_framework_file.
	 */
	public function test_get_framework_file() {

		$this->assertStringEndsWith( 'class-sv-wc-plugin.php', $this->get_plugin()->get_framework_file() );
	}


	/**
	 * Tests get_framework_path.
	 */
	public function test_get_framework_path() {

		$path = $this->get_plugin()->get_framework_path();

		$this->assertStringEndsWith( 'vendor/skyverge/wc-plugin-framework/woocommerce', $path );
		$this->assertStringStartsNotWith( 'http', $path );
	}


	/**
	 * Tests get_framework_assets_url.
	 */
	public function test_get_framework_assets_url() {

		$url = $this->get_plugin()->get_framework_assets_url();

		$this->assertStringEndsWith( 'vendor/skyverge/wc-plugin-framework/woocommerce/assets', $url );
		$this->assertStringStartsWith( 'http', $url );
	}


	/**
	 * Tests get_framework_assets_path.
	 */
	public function test_get_framework_assets_path() {

		$path = $this->get_plugin()->get_framework_assets_path();

		$this->assertStringEndsWith( 'vendor/skyverge/wc-plugin-framework/woocommerce/assets', $path );
		$this->assertStringStartsNotWith( 'http', $path );
	}


	/**
	 * Tests get_dependency_handler.
	 */
	public function test_get_dependency_handler() {

		$this->assertInstanceOf( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin_Dependencies', $this->get_plugin()->get_dependency_handler() );
	}


	/**
	 * Tests get_lifecycle_handler.
	 */
	public function test_get_lifecycle_handler() {

		$this->assertInstanceOf( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\Plugin\Lifecycle', $this->get_plugin()->get_lifecycle_handler() );
	}


	/**
	 * Tests is_plugin_active()
	 *
	 * @param mixed $plugin plugin name
	 * @param bool $expected the expected return value
	 *
	 * @dataProvider provider_is_plugin_active
	 */
	public function test_is_plugin_active( $plugin, $expected ) {

		$this->assertEquals( $expected, $this->get_plugin()->is_plugin_active( $plugin ) );
	}


	/**
	 * Provider for test_is_plugin_active()
	 *
	 * @return array
	 */
	public function provider_is_plugin_active() {

		return [
			[ 'woocommerce', false ],
			[ 'woocommerce.php', true ],
		];
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
