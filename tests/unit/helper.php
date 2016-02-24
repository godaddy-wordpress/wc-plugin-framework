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


	/** Test string functions *************************************************/


	/**
	 * Test str_starts_with() when multibyte functions are *not* enabled
	 *
	 * @since 4.3.0-dev
	 * @see \SV_WC_Helper::str_starts_with()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
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
	 * @see \SV_WC_Helper::str_starts_with()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
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
	 * Test str_ends_with() when multibyte functions are *not* enabled
	 *
	 * @since 4.3.0-dev
	 * @see \SV_WC_Helper::str_ends_with()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
	 * @dataProvider provider_str_ends_with_ascii
	 */
	public function test_str_ends_with_ascii( $asserts_as_true, $haystack, $needle ) {

		// force ASCII handling
		p\redefine( 'SV_WC_Helper::multibyte_loaded', function() { return false; } );

		if ( $asserts_as_true ) {
			$this->assertTrue( \SV_WC_Helper::str_ends_with( $haystack, $needle ) );
		} else {
			$this->assertFalse( \SV_WC_Helper::str_ends_with( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_ends_with() ASCII test
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_str_ends_with_ascii() {

		return [
			[ true, 'SkyVerge', 'erge' ],
			[ true, 'SkyVerge', '' ], // empty needle
			[ false, 'SkyVerge', 'ಠ' ],  // empty needle as a result of ASCII replacement
			[ false, 'ಠ_ಠ', 'ಠ' ], // ASCII replaced as empty string for both haystack/needle
			[ false, 'SkyVerge', 'sky' ],
			[ false, 'SkyVerge', 'verge' ] // case-sensitivity
		];
	}


	/**
	 * Test str_ends_with() when multibyte functions are enabled
	 *
	 * @since 4.3.0-dev
	 * @see \SV_WC_Helper::str_ends_with()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
	 * @dataProvider provider_str_ends_with_mb
	 */
	public function test_str_ends_with_mb( $asserts_as_true, $haystack, $needle ) {

		if ( ! extension_loaded( 'mbstring' ) ) {
			$this->markTestSkipped( 'Multibyte string functions are not available, skipping.' );
		}

		if ( $asserts_as_true ) {
			$this->assertTrue( \SV_WC_Helper::str_ends_with( $haystack, $needle ) );
		} else {
			$this->assertFalse( \SV_WC_Helper::str_ends_with( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_ends_with() multibyte test
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_str_ends_with_mb() {

		return [
			[ true, 'SkyVerge', 'erge' ],
			[ true, 'SkyVerge', '' ], // empty needle
			[ false, 'SkyVerge', 'ಠ' ],  // UTF-8
			[ true, 'ಠ_ಠ', 'ಠ' ], // UTF-8
			[ false, 'SkyVerge', 'sky' ],
			[ false, 'SkyVerge', 'verge' ] // case-sensitivity
		];
	}


	/**
	 * Test \SV_WC_Helper::str_to_ascii()
	 *
	 * @see \SV_WC_Helper::str_to_ascii()
	 * @since 4.3.0-dev
	 * @dataProvider provider_test_str_to_ascii
	 */
	public function test_str_to_ascii( $string, $ascii ) {

		$this->assertEquals( \SV_WC_Helper::str_to_ascii( $string ), $ascii );
	}


	/**
	 * Data provider for UTF-8 to pure ASCII strings
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_test_str_to_ascii() {

		return [
			[ 'skyverge', 'skyverge' ],
			[ 'a\bc`1/2*3', 'a\bc`1/2*3' ],
			[ 'question mark�', 'question mark' ],
			[ 'poker♠1♣2♥3♦abc', 'poker123abc' ],
			[ 'one half ½', 'one half ' ], // note the whitespace on the right
			[ '10¢', '10' ] // that's not a c, that's ¢ as in cent, on some fonts this might not be obvious
		];
	}


	/**
	 * Test str_to_sane_utf8()
	 *
	 * @see \SV_WC_Helper::str_to_sane_utf8()
	 * @since 4.3.0-dev
	 * @dataProvider provider_test_str_to_sane_utf8
	 */
	public function test_str_to_sane_utf8( $string, $utf8 ) {

		$this->assertEquals( \SV_WC_Helper::str_to_sane_utf8( $string ), $utf8 );
	}


	/**
	 * Data provider for crazy UTF-8 to sane UTF-8 strings
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_test_str_to_sane_utf8() {

		return [
			[ 'إن شاء الله!', 'إن شاء الله!' ], // non-latin UTF-8, but still sane
			[ 'a\bc`1/2*3', 'a\bc1/2*3' ],
			[ 'question mark�', 'question mark' ],
			[ 'poker♠1♣2♥3♦abc', 'poker123abc' ],
			[ 'one half ½', 'one half ' ], // note the whitespace on the right
			[ '10¢', '10¢' ] // that's not a c, that's ¢ as in cent, on some fonts this might not be obvious
		];
	}


	/**
	 * Test str_exists() when multibyte functions are *not* enabled
	 *
	 * @since 4.3.0-dev
	 * @see \SV_WC_Helper::str_exists()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
	 * @dataProvider provider_str_exists_ascii
	 */
	public function test_str_exists_ascii( $asserts_as_true, $haystack, $needle ) {

		// force ASCII handling
		p\redefine( 'SV_WC_Helper::multibyte_loaded', function() { return false; } );

		if ( $asserts_as_true ) {
			$this->assertTrue( \SV_WC_Helper::str_exists( $haystack, $needle ) );
		} else {
			$this->assertFalse( \SV_WC_Helper::str_exists( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_exists() ASCII test
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_str_exists_ascii() {

		return [
			[ true, 'SkyVerge', 'erge' ],
			[ false, 'SkyVerge', '' ], // empty needle
			[ false, 'SkyVerge', 'ಠ' ],  // UTF-8
			[ false, 'ಠ_ಠ', 'ಠ' ], // UTF-8 that does exist in string, but doesn't when forced to ASCII
			[ false, 'SkyVerge', 'sky' ], // case-sensitivity
			[ true, 'SkyVerge', 'V' ], // single-char, case-sensitive
		];
	}


	/**
	 * Test str_exists() when multibyte functions are enabled
	 *
	 * @since 4.3.0-dev
	 * @see \SV_WC_Helper::str_exists()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
	 * @dataProvider provider_str_exists_mb
	 */
	public function test_str_exists_mb( $asserts_as_true, $haystack, $needle ) {

		if ( ! extension_loaded( 'mbstring' ) ) {
			$this->markTestSkipped( 'Multibyte string functions are not available, skipping.' );
		}

		if ( $asserts_as_true ) {
			$this->assertTrue( \SV_WC_Helper::str_exists( $haystack, $needle ) );
		} else {
			$this->assertFalse( \SV_WC_Helper::str_exists( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_exists() multibyte test
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_str_exists_mb() {

		return [
			[ true, 'SkyVerge', 'erge' ],
			[ false, 'SkyVerge', '' ], // empty needle
			[ false, 'SkyVerge', 'ಠ' ],  // UTF-8
			[ true, 'ಠ_ಠ', 'ಠ' ], // UTF-8 that does exist in string
			[ false, 'SkyVerge', 'sky' ], // case-sensitivity
			[ true, 'SkyVerge', 'V' ], // single-char, case-sensitive
		];
	}


	/** Test WC notice functions **********************************************/


	/**
	 * Test SV_WC_Helper::wc_notice_count()
	 *
	 * @see \SV_WC_Helper::wc_notice_count()
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
	 * @see \SV_WC_Helper::wc_add_notice()
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
	 * @see \SV_WC_Helper::wc_print_notice()
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
	 * @see SV_WC_Helper::get_wc_log_file_url()
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
	 * Test \SV_WC_Helper::get_post()
	 *
	 * @see \SV_WC_Helper::get_post()
	 * @since 4.3.0-dev
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
	 * Test \SV_WC_Helper::get_request()
	 *
	 * @see \SV_WC_Helper::get_request()
	 * @since 4.3.0-dev
	 * @dataProvider provider_test_get_request_associative_array
	 */
	public function test_get_request( $request_key, $request_value ) {

		$_REQUEST[ $request_key ]  = $request_value;

		$this->assertEquals( \SV_WC_Helper::get_request( $request_key ), trim( $request_value ) );
		$this->assertEquals( \SV_WC_Helper::get_request( 'invalidKey' ), '' );
	}


	/**
	 * Test \SV_WC_Helper::array_insert_after
	 *
	 * @see \SV_WC_Helper::array_insert_after()
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


	/**
	 * Test SV_WC_Helper::f__
	 *
	 * @since 4.3.0-dev
	 */
	public function test_f__() {

		$string = 'String';

		Mock::wpPassthruFunction( '__' );

		$this->assertEquals( $string, \SV_WC_Helper::f__( $string ) );
	}

	/**
	 * Test SV_WC_Helper::f_x
	 *
	 * @since 4.3.0-dev
	 */
	public function test_f_x() {

		$string = 'String';

		Mock::wpPassthruFunction( '_x' );

		$this->assertEquals( $string, \SV_WC_Helper::f_x( $string, 'string-context' ) );
	}

	/*
	 * Test SV_WC_Helper::f_e
	 *
	 * @since 4.3.0-dev
	 */
	public function test_f_e() {

		$string = 'String';

		Mock::wpFunction( '_e', array(
			'args'   => array( $string, '*' ),
			'return' => function( $string ) { echo $string; },
		) );

		\SV_WC_Helper::f_e( $string );

		$this->expectOutputString( $string );
	}


	/**
	 * Test \SV_WC_Helper::array_to_xml()
	 *
	 * @see \SV_WC_Helper::array_to_xml()
	 * @since 4.3.0-dev
	 */
	public function test_array_to_xml() {

		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->startDocument( '1.0', 'UTF-8' );

		\SV_WC_Helper::array_to_xml( $xml, 'foo', array(
			array( 'test' ),
			array( 'bar' => 'baz' ),
			array( '@attributes' => array(
				'attribute' => 'value',
			) ),
		) );

		$xml->endDocument();

		$output   = $xml->outputMemory();

		// Mind newlines, empty last line and indentation
		$expected = <<<MSG
<?xml version="1.0" encoding="UTF-8"?>
<foo>test</foo><foo><bar>baz</bar></foo><foo attribute="value"/>

MSG;
		$this->assertEquals( $output, $expected );
	}


	/**
	 * Test \SV_WC_Helper::number_format()
	 *
	 * @see \SV_WC_Helper::number_format()
	 * @since 4.3.0-dev
	 * @dataProvider provider_test_number_format
	 */
	public function test_number_format( $original_number, $formatted_number ) {

		$result = \SV_WC_Helper::number_format( $original_number );

		$this->assertTrue( is_numeric( $result ), true  );
		$this->assertEquals( $result, $formatted_number );
	}


	/**
	 * Test SV_WC_Helper::convert_country_code()
	 *
	 * @dataProvider provider_test_convert_country_code
	 * @since 4.3.0-dev
	 */
	public function test_convert_country_code( $input_code, $converted_code ) {

		$this->assertEquals( $converted_code, \SV_WC_Helper::convert_country_code( $input_code )  );

		// 2 digits codes are converted into 3 digits and vice versa
		if ( 2 === strlen( $input_code ) ) {
			$this->assertEquals( strlen( $converted_code ), 3 );
		} elseif ( 3 === strlen( $input_code ) ) {
			$this->assertEquals( strlen( $converted_code ), 2 );
		}
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
	 * Get an array with formatted numbers
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_test_number_format() {

		return [
			[ '1', '1.00' ],
			[ '10', '10.00' ],
		    [ '1000', '1000.00' ],
			[ '1.23', '1.23' ],
			[ '10.20', '10.20' ],
			[ '10.201', '10.20' ],
			[ '1000.90', '1000.90'],
		];
	}


	/**
	 * Provider for an associative array with different keys and values
	 *
	 * @since 4.3.0-dev
	 * @return array
	 */
	public function provider_test_get_request_associative_array() {

		return [ [
			'empty'       , '',
			'integer'     , 123,
			'float'       , 1.23,
			'string'      , 'abc',
			'whitespace'  , ' abc ',
			'true'        , true,
			'false'       , false,
			'array'       , [ 'a', 'b', 'c' ],
			'array_assoc' , [ 'a' => 'x', 'b' => 'y', 'c' => 'z' ],
			'CaMeLKey'    , 'CaMeLvalue',
			'UCKey'       , 'UCValue',
			'lckey'       , 'lcvalue',
		] ];
	}


}
