<?php

use \SkyVerge\WooCommerce\PluginFramework\v5_5_1 as Framework;
use \SkyVerge\WooCommerce\Test_Plugin\Plugin as Plugin;

/**
 * Tests for the base plugin class.
 *
 * @see Framework\SV_WC_Plugin
 */
class SV_WC_Plugin_Test extends \Codeception\TestCase\WPTestCase {


	/** @var Plugin  */
	private $plugin;


	/**
	 * Gets the singleton instance of the test plugin.
	 *
	 * @return Plugin
	 */
	protected function get_plugin() {

		if ( null === $this->plugin ) {
			$this->plugin = sv_wc_test_plugin();
		}

		return $this->plugin;
	}


	/** @see Framework\SV_WC_Plugin::get_id() */
	public function test_get_id() {

		$this->assertEquals( 'test_plugin', $this->get_plugin()->get_id() );
	}


	/** @see Framework\SV_WC_Plugin::get_id_dasherized() */
	public function test_get_id_dasherized() {

		$this->assertEquals( 'test-plugin', $this->get_plugin()->get_id_dasherized() );
	}


	/** @see Framework\SV_WC_Plugin::get_version() */
	public function test_get_version() {

		$this->assertEquals( '1.0.0', $this->get_plugin()->get_version() );
	}


	/** @see Framework\SV_WC_Plugin::get_plugin_file() */
	public function test_get_plugin_file() {

		$this->assertEquals( 'test-plugin/test-plugin.php', $this->get_plugin()->get_plugin_file() );
	}


	/** @see Framework\SV_WC_Plugin::get_plugin_path() */
	public function test_get_plugin_path() {

		$path = $this->get_plugin()->get_plugin_path();

		$this->assertStringEndsWith( 'wp-content/plugins/test-plugin', $path );
		$this->assertStringStartsNotWith( 'http', $path );
	}


	/** @see Framework\SV_WC_Plugin::get_plugin_url() */
	public function test_get_plugin_url() {

		$url = $this->get_plugin()->get_plugin_url();

		$this->assertStringEndsWith( 'wp-content/plugins/test-plugin', $url );
		$this->assertStringStartsWith( 'http', $url );
	}


	/** @see Framework\SV_WC_Plugin::get_woocommerce_uploads_path() */
	public function test_get_woocommerce_uploads_path() {

		$path = $this->get_plugin()->get_woocommerce_uploads_path();

		$this->assertStringEndsWith( 'wp-content/uploads/woocommerce_uploads', $path );
		$this->assertStringStartsNotWith( 'http', $path );
	}


	/** @see Framework\SV_WC_Plugin::get_framework_file() */
	public function test_get_framework_file() {

		$this->assertStringEndsWith( 'class-sv-wc-plugin.php', $this->get_plugin()->get_framework_file() );
	}


	/** @see Framework\SV_WC_Plugin::get_framework_path() */
	public function test_get_framework_path() {

		$path = $this->get_plugin()->get_framework_path();

		$this->assertStringEndsWith( 'vendor/skyverge/wc-plugin-framework/woocommerce', $path );
		$this->assertStringStartsNotWith( 'http', $path );
	}


	/** @see Framework\SV_WC_Plugin::get_framework_assets_url() */
	public function test_get_framework_assets_url() {

		$url = $this->get_plugin()->get_framework_assets_url();

		$this->assertStringEndsWith( 'vendor/skyverge/wc-plugin-framework/woocommerce/assets', $url );
		$this->assertStringStartsWith( 'http', $url );
	}


	/** @see Framework\SV_WC_Plugin::get_framework_assets_path() */
	public function test_get_framework_assets_path() {

		$path = $this->get_plugin()->get_framework_assets_path();

		$this->assertStringEndsWith( 'vendor/skyverge/wc-plugin-framework/woocommerce/assets', $path );
		$this->assertStringStartsNotWith( 'http', $path );
	}


	/** @see Framework\SV_WC_Plugin::get_dependency_handler() */
	public function test_get_dependency_handler() {

		$this->assertInstanceOf( '\SkyVerge\WooCommerce\PluginFramework\v5_5_1\SV_WC_Plugin_Dependencies', $this->get_plugin()->get_dependency_handler() );
	}


	/** @see Framework\SV_WC_Plugin::get_lifecycle_handler() */
	public function test_get_lifecycle_handler() {

		$this->assertInstanceOf( '\SkyVerge\WooCommerce\PluginFramework\v5_5_1\Plugin\Lifecycle', $this->get_plugin()->get_lifecycle_handler() );
	}


	/** @see Framework\SV_WC_Plugin::is_plugin_active() */
	public function test_is_plugin_active() {

		$check_plugins = [
			'invalid.php'     => false, // non-existent
			'woocommerce'     => false, // must specify .php
			'woocommerce.php' => true,
			'test-plugin.php' => true,
		];

		foreach ( $check_plugins as $plugin_name => $expected_result ) {

			$this->assertEquals( $expected_result, $this->get_plugin()->is_plugin_active( $plugin_name ) );
		}
	}


}
