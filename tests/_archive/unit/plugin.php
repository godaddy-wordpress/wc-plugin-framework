<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit;

use \WP_Mock as Mock;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Plugin Test
 *
 * @package SV_WC_Plugin_Framework\Tests
 * @since 4.0.1-1
 */
class Plugin extends Test_Case {

	public function test_constructor() {

		$this->assertInstanceOf( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin', $this->plugin() );
	}

	public function test_clone() {

		Mock::wpFunction( '_doing_it_wrong', array(
			'args' => array( '__clone', '*', '*' ),
			'return' => function() { echo "foo"; },
		) );

		Mock::wpPassthruFunction( '__' );
		Mock::wpPassthruFunction( 'esc_html__' );

		clone $this->plugin();

		$this->expectOutputString( 'foo' );
	}

	public function test_plug_action_links() {

		$actions = array();

		$new_actions = $this->plugin()->plugin_action_links( $actions );

		$this->assertEquals( $new_actions, array() );
	}


	public function test_get_id() {

		$this->assertEquals( 'mock', $this->plugin()->get_id() );
	}

	public function test_get_id_dasherized() {

		$this->assertEquals( 'mock', $this->plugin()->get_id_dasherized() );
	}

	public function test_get_version() {

		$this->assertEquals( '7.7.7', $this->plugin()->get_version() );
	}

	public function test_get_plugin_version_name() {

		$this->assertEquals( 'wc_mock_version', $this->plugin()->get_plugin_version_name() );
	}

	public function test_get_api_log_message() {

		$data = array(
			'method'     => 'POST',
			'uri'        => 'http://skyverge.com',
			'user-agent' => 'WooCommerce-Mock/0.1.0 (WooCommerce/2.4.0; WordPress/4.2.3)',
			'headers'    => array( 'content-type' => 'application/xml', 'accept' => 'application/xml' ),
			'body'       => '<?xml version="1.0" encoding="UTF-8"?><sv></sv>',
			'duration'   => '7.77s',
		);

		$expected_message = <<<MSG
Request
method: POST
uri: http://skyverge.com
user-agent: WooCommerce-Mock/0.1.0 (WooCommerce/2.4.0; WordPress/4.2.3)
headers: Array
(
    [content-type] => application/xml
    [accept] => application/xml
)
body: <?xml version="1.0" encoding="UTF-8"?><sv></sv>
duration: 7.77s

MSG;

		$actual_message = $this->plugin()->get_api_log_message( $data );

		$this->assertEquals( $expected_message, $actual_message );
	}

	protected function plugin() {

		// functions used as part of constructor
		Mock::wpPassthruFunction( 'untrailingslashit' );
		Mock::wpPassthruFunction( 'trailingslashit' );

		Mock::wpFunction( 'plugin_dir_path', array(
			'args'   => bootstrap()->get_framework_path() . '/woocommerce/class-sv-wc-plugin.php',
			'return' => bootstrap()->get_framework_path() . '/woocommerce',
		) );

		Mock::wpFunction( 'is_admin', array(
			'return' => true,
		) );

		Mock::wpPassthruFunction( 'plugin_basename' );

		Mock::wpFunction( 'has_action', array(
			'return' => true,
		) );

		Mock::wpPassthruFunction( 'register_activation_hook' );
		Mock::wpPassthruFunction( 'register_deactivation_hook' );

		Mock::wpFunction( 'is_ajax', array( 'return' => false ) );

		Mock::wpPassthruFunction( 'wp_parse_args' );

		$args = array(
			'mock',
			'7.7.7',
			array(
				'dependencies' => array(
					'php_extensions' => array( 'json' ),
					'php_functions'  => array(),
					'php_settings'   => array(),
				),
				'text_domain' => 'mock',
			),
		);

		return $this->getMockBuilder( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Plugin' )
							 ->setConstructorArgs( $args )
							 ->getMockForAbstractClass();
	}

}
