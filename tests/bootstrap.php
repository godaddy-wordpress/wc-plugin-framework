<?php

// Patchwork must loaded first
require_once( dirname( dirname( __FILE__ ) ) . '/vendor/antecedent/patchwork/Patchwork.php' );

/**
 * WC Plugin Framework Unit/Integration Tests Bootstrap
 *
 * @since 4.0.1-1
 */
class SV_WC_Plugin_Framework_Tests_Bootstrap {


	/** @var \SV_WC_Plugin_Framework_Tests_Bootstrap instance */
	protected static $instance = null;

	public $test_suite;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string framework directory */
	public $framework_dir;


	/**
	 * Setup the unit testing environment
	 *
	 * @since 4.0.1-1
	 */
	public function __construct() {

		ini_set( 'display_errors','on' );
		error_reporting( E_ALL );

		$this->tests_dir       = dirname( __FILE__ );
		$this->framework_dir   = dirname( $this->tests_dir );

		// set test type, default to unit if not set
		$arg_key = array_search( '--testsuite', $GLOBALS['argv'] ) + 1;
		$this->test_suite = ( $arg_key > 1 ) ? $GLOBALS['argv'][ $arg_key ] : 'unit';

		// wpMock
		require_once( $this->framework_dir . '/vendor/autoload.php' );

		if ( $this->is_unit_tests() ) {

			// framework exits; if not defined
			define( 'ABSPATH', true );

			require_once( $this->tests_dir . '/unit/test-case.php' );

		} else {

			// TODO
			require_once( $this->tests_dir . '/integration/test-case.php' );
		}

		// load framework files
		$this->load_framework();
	}


	public function load_framework() {

		require_once( $this->framework_dir . '/woocommerce/class-sv-wc-plugin.php' );
		require_once( $this->framework_dir . '/woocommerce/class-sv-wc-helper.php' );
		require_once( $this->framework_dir . '/woocommerce/class-sv-wc-plugin-compatibility.php' );


		echo "Loaded Framework..." . PHP_EOL;
	}


	public function get_tests_path() {
		return $this->tests_dir;
	}

	public function get_framework_path() {
		return $this->framework_dir;
	}

	public function is_unit_tests() {
		return 'unit' === $this->test_suite;
	}

	public function is_integration_tests() {

		return 'integration' === $this->test_suite;
	}

	/**
	 * Get the single class instance
	 *
	 * @since 2.2
	 * @return WC_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}


function bootstrap() {
	return SV_WC_Plugin_Framework_Tests_Bootstrap::instance();
}

bootstrap();
