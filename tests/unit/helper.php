<?php

namespace SkyVerge\WC_Plugin_Framework\Unit_Tests;

use \WP_Mock as Mock;
use Patchwork as p;

/**
 * Helper Class Unit Tests
 *
 * @package SkyVerge\WC_Plugin_Framework\Unit_Tests
 * @since 4.0.1-1
 */
class Helper extends Test_Case {


	/**
	 * Test str_starts_with() when multibyte functions are *not* enabled
	 *
	 * @since 4.3.0-dev
	 * @dataProvider provider_str_starts_with_ascii
	 */
	public function test_str_starts_with_ascii( $asserts_as_true, $haystack, $needle ) {

		// force ASCII handling
		p\redefine( 'SV_WC_Helper::multibyte_loaded', function() { return false; } );

		if ( $asserts_as_true ) {
			$this->assertTrue( \SV_WC_Helper::str_starts_with( $haystack, $needle ) );
		} else {
			$this->assertFalse( \SV_WC_Helper::str_starts_with( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_starts_with() ASCII test
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_str_starts_with_ascii() {

		return [
			[ true, 'SkyVerge', 'Sky' ],
			[ true, 'SkyVerge', '' ], // empty needle
			[ true, 'SkyVerge', 'ಠ' ],  // empty needle as a result of ASCII replacement
			[ true, 'ಠ_ಠ', 'ಠ' ], // ASCII for both haystack/needle
			[ false, 'SkyVerge', 'verge' ],
			[ false, 'SkyVerge', 'sky' ] // case-sensitivity
		];
	}


	/**
	 * Test str_starts_with() when multibyte functions are enabled
	 *
	 * @since 4.3.0-dev
	 * @dataProvider provider_str_starts_with_mb
	 */
	public function test_str_starts_with_mb( $asserts_as_true, $haystack, $needle ) {

		if ( ! extension_loaded( 'mbstring' ) ) {
			$this->markTestSkipped( 'Multibyte string functions are not available, skipping.' );
		}

		if ( $asserts_as_true ) {
			$this->assertTrue( \SV_WC_Helper::str_starts_with( $haystack, $needle ) );
		} else {
			$this->assertFalse( \SV_WC_Helper::str_starts_with( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_starts_with() multibyte test
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_str_starts_with_mb() {

		return [
			[ true, 'SkyVerge', 'Sky' ],
			[ true, 'SkyVerge', '' ], // empty needle
			[ false, 'SkyVerge', 'ಠ' ],  // UTF-8
			[ true, 'ಠ_ಠ', 'ಠ' ], // UTF-8
			[ false, 'SkyVerge', 'verge' ],
			[ false, 'SkyVerge', 'sky' ] // case-sensitivity
		];
	}


	/**
	 * Test SV_WC_Helper::wc_notice_count()
	 *
	 * @since 4.3.0-dev
	 */
	public function test_wc_notice_count() {

		// test 0 return value if function doens't exist
		$this->assertEquals( 0, \SV_WC_Helper::wc_notice_count() );

		// mock wc_notice_count() function
		Mock::wpFunction( 'wc_notice_count', array(
			'args' => array( 'error' ),
			'return' => 666,
		) );

		// test the return value is as expected
		$this->assertEquals( 666, \SV_WC_Helper::wc_notice_count( 'error' ) );
	}


	/**
	 * Test SV_WC_Helper::wc_add_notice()
	 *
	 * @since 4.3.0-dev
	 */
	public function test_wc_add_notice() {

		// mock wc_add_notice() function
		Mock::wpFunction( 'wc_add_notice', array(
			'args' => array( 'This is a success message.', 'success' ),
			'return' => null,
		) );

		$this->assertNull( \SV_WC_Helper::wc_add_notice( 'This is a success message.', 'success' ) );
	}


	/**
	 * Test SV_WC_Helper::wc_print_notice()
	 *
	 * @since 4.3.0-dev
	 */
	public function test_wc_print_notice() {

		// mock wc_print_notice() function
		Mock::wpFunction( 'wc_print_notice', array(
			'args' => array( 'This is a notice message.', 'notice' ),
			'return' => null,
		) );

		$this->assertNull( \SV_WC_Helper::wc_print_notice( 'This is a notice message.', 'notice' ) );
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
}
