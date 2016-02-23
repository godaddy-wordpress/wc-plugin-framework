<?php

namespace SkyVerge\WC_Plugin_Framework\Unit_Tests;

use \WP_Mock as Mock;

/**
 * Helper Class Unit Tests
 *
 * @package SkyVerge\WC_Plugin_Framework\Unit_Tests
 * @since 4.0.1-1
 */
class Helper extends Test_Case {


	/**
	 * @dataProvider provider_test_str_starts_with_true
	 */
	public function test_str_starts_with_true( $haystack, $needle ) {

		$this->assertTrue( \SV_WC_Helper::str_starts_with( $haystack, $needle ) );
	}

	/**
	 *
	 * @dataProvider provider_test_str_starts_with_false
	 */
	public function test_str_starts_with_false( $haystack, $needle ) {

		$this->assertFalse( \SV_WC_Helper::str_starts_with( $haystack, $needle ) );
	}


	/**
	 * Test SV_WC_Helper::wc_notice_count()
	 *
	 * @since 4.3.0-dev
	 */
	public function test_wc_notice_count() {

		// set up args
		$type  = 'error';
		$count = 666;

		// test 0 return value if function doens't exist
		$this->assertEquals( 0, \SV_WC_Helper::wc_notice_count( $type ) );

		// mock wc_notice_count() function
		Mock::wpFunction( 'wc_notice_count', array(
			'args'   => array( $type ),
			'return' => $count,
		) );

		// test the return value is as expected
		$this->assertEquals( $count, \SV_WC_Helper::wc_notice_count( $type ) );
	}


	/**
	 * Test SV_WC_Helper::wc_add_notice()
	 *
	 * @since 4.3.0-dev
	 */
	public function test_wc_add_notice() {

		// set up args
		$message = 'This is a success message.';
		$type    = 'success';

		// mock wc_add_notice() function
		Mock::wpFunction( 'wc_add_notice', array(
			'args'   => array( $message, $type ),
			'return' => null,
		) );

		$this->assertNull( \SV_WC_Helper::wc_add_notice( $message, $type ) );
	}


	/**
	 * Test SV_WC_Helper::wc_print_notice()
	 *
	 * @since 4.3.0-dev
	 */
	public function test_wc_print_notice() {

		// set up args
		$message = 'This is a notice message.';
		$type    = 'notice';

		// mock wc_print_notice() function
		Mock::wpFunction( 'wc_print_notice', array(
			'args'   => array( $message, $type ),
			'return' => null,
		) );

		$this->assertNull( \SV_WC_Helper::wc_print_notice( $message, $type ) );
	}


	/**
	 * Test SV_WC_Helper::get_wc_log_file_url()
	 *
	 * @since 4.3.0-dev
	 */
	public function test_get_wc_log_file_url() {

		// set up args
		$admin_url = 'http://skyverge.dev/wp-admin/';
		$handle    = 'plugin';
		$path      = 'admin.php?page=wc-status&tab=logs&log_file=' . $handle . '-' . $handle . '-log';

		// mock wp_hash() function
		Mock::wpPassthruFunction( 'wp_hash' );

		// mock sanitize_file_name() function
		Mock::wpPassthruFunction( 'sanitize_file_name' );

		// mock admin_url() function
		Mock::wpFunction( 'admin_url', array(
			'args'   => array( $path ),
			'return' => $admin_url . $path,
		) );

		$this->assertEquals( $admin_url . $path, \SV_WC_Helper::get_wc_log_file_url( $handle ) );
	}


	/**
	 * Test SV_WC_Helper::convert_country_code()
	 *
	 * @dataProvider provider_test_convert_country_code
	 * @since 4.3.0-dev
	 */
	public function test_convert_country_code( $input_code, $converted_code ) {

		$this->assertEquals( $converted_code, \SV_WC_Helper::convert_country_code( $input_code )  );
	}


	public function provider_test_str_starts_with_true() {

		return [
			[ 'SkyVerge', 'Sky' ],
			[ 'SkyVerge', '' ],
			[ 'ಠ_ಠ', 'ಠ' ], // UTF-8
		];
	}

	public function provider_test_str_starts_with_false() {

		return [
			[ 'SkyVerge', 'verge' ],
			[ 'SkyVerge', 'sky' ], //case-sensitive
		];
	}


	/**
	 * Convert Country code provider
	 *
	 * @since 4.3.0-dev
	 */
	public function provider_test_convert_country_code() {

		return [
			[ 'US', 'USA' ],
			[ 'CA', 'CAN' ],
			[ 'ES', 'ESP' ],
			[ 'ITA', 'IT' ],
			[ 'ZAF', 'ZA' ],
		];
	}


	/**
	 * Test \SV_WC_Helper::get_post
	 */
	public function test_get_post() {

		$key = 'sv_test_key';

		// Test for an unset key
		$this->assertEquals( '', \SV_WC_Helper::get_post( $key ) );

		$_POST[ $key ] = 'value';

		// Check that a value is returned
		$this->assertEquals( 'value', \SV_WC_Helper::get_post( $key ) );

		$_POST[ $key ] = ' untrimmed-value ';

		// Check that the value is trimmed
		$this->assertEquals( 'untrimmed-value', \SV_WC_Helper::get_post( $key ) );
	}


	/**
	 * Test \SV_WC_Helper::test_get_request
	 */
	public function test_get_request() {

		$key = 'sv_test_key';

		// Test for an unset key
		$this->assertEquals( '', \SV_WC_Helper::get_request( $key ) );

		$_REQUEST[ $key ] = 'value';

		// Check that a value is returned
		$this->assertEquals( 'value', \SV_WC_Helper::get_request( $key ) );

		$_REQUEST[ $key ] = ' untrimmed-value ';

		// Check that the value is trimmed
		$this->assertEquals( 'untrimmed-value', \SV_WC_Helper::get_request( $key ) );
	}


	/**
	 * Test \SV_WC_Helper::array_insert_after
	 *
	 * @since 4.3.0-dev
	 */
	public function test_array_insert_after() {

		$target_array = array(
			'1' => 1,
			'2' => 2,
			'3' => 3,
		);

		$added_array = array(
			'2.5' => 2.5,
		);

		$insert_point = '2';

		$this->assertArrayHasKey( key( $added_array ), \SV_WC_Helper::array_insert_after( $target_array, $insert_point, $added_array ) );

		// Test a key that doesn't exist
		$insert_point = 'bad-key';

		$this->assertEquals( $target_array, \SV_WC_Helper::array_insert_after( $target_array, $insert_point, $added_array ) );
	}
}
