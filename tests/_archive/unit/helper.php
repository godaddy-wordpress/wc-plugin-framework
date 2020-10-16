<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Unit;

use \WP_Mock as Mock;
use \Patchwork as p;
use \SkyVerge\WooCommerce\PluginFramework\v5_10_0 as PluginFramework;

/**
 * Helper Class Unit Tests
 *
 * @package SkyVerge\WooCommerce\PluginFramework\Tests\Unit
 * @since 4.0.1-1
 */
class Helper extends Test_Case {


	/** Test string functions *************************************************/


	/**
	 * Test str_starts_with() when multibyte functions are *not* enabled
	 *
	 * @since 4.3.0
	 * @see \SV_WC_Helper::str_starts_with()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
	 * @dataProvider provider_str_starts_with_ascii
	 */
	public function test_str_starts_with_ascii( $asserts_as_true, $haystack, $needle ) {

		// force ASCII handling
		p\redefine( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper::multibyte_loaded', function() { return false; } );

		if ( $asserts_as_true ) {
			$this->assertTrue( PluginFramework\SV_WC_Helper::str_starts_with( $haystack, $needle ) );
		} else {
			$this->assertFalse( PluginFramework\SV_WC_Helper::str_starts_with( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_starts_with() ASCII test
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
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
			$this->assertTrue( PluginFramework\SV_WC_Helper::str_starts_with( $haystack, $needle ) );
		} else {
			$this->assertFalse( PluginFramework\SV_WC_Helper::str_starts_with( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_starts_with() multibyte test
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
	 * @see \SV_WC_Helper::str_ends_with()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
	 * @dataProvider provider_str_ends_with_ascii
	 */
	public function test_str_ends_with_ascii( $asserts_as_true, $haystack, $needle ) {

		// force ASCII handling
		p\redefine( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper::multibyte_loaded', function() { return false; } );

		if ( $asserts_as_true ) {
			$this->assertTrue( PluginFramework\SV_WC_Helper::str_ends_with( $haystack, $needle ) );
		} else {
			$this->assertFalse( PluginFramework\SV_WC_Helper::str_ends_with( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_ends_with() ASCII test
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
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
			$this->assertTrue( PluginFramework\SV_WC_Helper::str_ends_with( $haystack, $needle ) );
		} else {
			$this->assertFalse( PluginFramework\SV_WC_Helper::str_ends_with( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_ends_with() multibyte test
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
	 * @dataProvider provider_test_str_to_ascii
	 */
	public function test_str_to_ascii( $string, $ascii ) {

		$this->assertEquals( PluginFramework\SV_WC_Helper::str_to_ascii( $string ), $ascii );
	}


	/**
	 * Data provider for UTF-8 to pure ASCII strings
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
	 * @dataProvider provider_test_str_to_sane_utf8
	 */
	public function test_str_to_sane_utf8( $string, $utf8 ) {

		$this->assertEquals( PluginFramework\SV_WC_Helper::str_to_sane_utf8( $string ), $utf8 );
	}


	/**
	 * Data provider for crazy UTF-8 to sane UTF-8 strings
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
	 * @see \SV_WC_Helper::str_exists()
	 * @param bool $asserts_as_true true if data passes true assert, passes false assertion otherwise
	 * @param string $haystack
	 * @param string $needle
	 * @dataProvider provider_str_exists_ascii
	 */
	public function test_str_exists_ascii( $asserts_as_true, $haystack, $needle ) {

		// force ASCII handling
		p\redefine( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper::multibyte_loaded', function() { return false; } );

		if ( $asserts_as_true ) {
			$this->assertTrue( PluginFramework\SV_WC_Helper::str_exists( $haystack, $needle ) );
		} else {
			$this->assertFalse( PluginFramework\SV_WC_Helper::str_exists( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_exists() ASCII test
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
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
			$this->assertTrue( PluginFramework\SV_WC_Helper::str_exists( $haystack, $needle ) );
		} else {
			$this->assertFalse( PluginFramework\SV_WC_Helper::str_exists( $haystack, $needle ) );
		}
	}


	/**
	 * Data Provider for str_exists() multibyte test
	 *
	 * @since 4.3.0
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


	/**
	 * Test str_truncate() when multibyte functions are *not* enabled
	 *
	 * @since 4.3.0
	 * @see \SV_WC_Helper::str_truncate()
	 */
	public function test_str_truncate_ascii() {

		// force ASCII handling
		p\redefine( '\SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper::multibyte_loaded', function() { return false; } );

		$the_string = 'The quick brown fox jumps ಠ_ಠ';

		// no truncation needed / non-ASCII removed
		$this->assertEquals( 'The quick brown fox jumps _', PluginFramework\SV_WC_Helper::str_truncate( $the_string, 30 ) );

		// simple truncation
		$this->assertEquals( 'The quick brown ...', PluginFramework\SV_WC_Helper::str_truncate( $the_string, 19 ) );

		// custom omission string
		$this->assertEquals( 'The quick brown fo-', PluginFramework\SV_WC_Helper::str_truncate( $the_string, 19, '-' ) );
	}


	/**
	 * Test str_truncate() when multibyte functions are enabled
	 *
	 * @since 4.3.0
	 * @see \SV_WC_Helper::str_truncate()
	 */
	public function test_str_truncate_mb() {

		if ( ! extension_loaded( 'mbstring' ) ) {
			$this->markTestSkipped( 'Multibyte string functions are not available, skipping.' );
		}

		$the_string = 'The quick brown fox jumps ಠ_ಠ';

		// no truncation needed
		$this->assertEquals( 'The quick brown fox jumps ಠ_ಠ', PluginFramework\SV_WC_Helper::str_truncate( $the_string, 30 ) );

		// simple truncation
		$this->assertEquals( 'The quick brown ...', PluginFramework\SV_WC_Helper::str_truncate( $the_string, 19 ) );

		// custom omission string
		$this->assertEquals( 'The quick brown fox jumps ಠ-', PluginFramework\SV_WC_Helper::str_truncate( $the_string, 28, '-' ) );
	}


	/** Test WC notice functions **********************************************/


	/**
	 * Test SV_WC_Helper::wc_notice_count()
	 *
	 * @see \SV_WC_Helper::wc_notice_count()
	 * @since 4.3.0
	 */
	public function test_wc_notice_count() {

		// set up args
		$type  = 'error';
		$count = 666;

		// test 0 return value if function doens't exist
		$this->assertEquals( 0, PluginFramework\SV_WC_Helper::wc_notice_count( $type ) );

		// mock wc_notice_count() function
		Mock::wpFunction( 'wc_notice_count', array(
			'args'   => array( $type ),
			'return' => $count,
		) );

		// test the return value is as expected
		$this->assertEquals( $count, PluginFramework\SV_WC_Helper::wc_notice_count( $type ) );
	}


	/**
	 * Test SV_WC_Helper::wc_add_notice()
	 *
	 * @see \SV_WC_Helper::wc_add_notice()
	 * @since 4.3.0
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

		$this->assertNull( PluginFramework\SV_WC_Helper::wc_add_notice( $message, $type ) );
	}


	/**
	 * Test SV_WC_Helper::wc_print_notice()
	 *
	 * @see \SV_WC_Helper::wc_print_notice()
	 * @since 4.3.0
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

		$this->assertNull( PluginFramework\SV_WC_Helper::wc_print_notice( $message, $type ) );
	}


	/**
	 * Test SV_WC_Helper::get_wc_log_file_url()
	 *
	 * @see SV_WC_Helper::get_wc_log_file_url()
	 * @since 4.3.0
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

		$this->assertEquals( $admin_url . $path, PluginFramework\SV_WC_Helper::get_wc_log_file_url( $handle ) );
	}


	/**
	 * Test \SV_WC_Helper::get_post()
	 *
	 * @see \SV_WC_Helper::get_post()
	 * @since 4.3.0
	 */
	public function test_get_post() {

		$key = 'sv_test_key';

		// Test for an unset key
		$this->assertEquals( '', PluginFramework\SV_WC_Helper::get_post( $key ) );

		$_POST[ $key ] = 'value';

		// Check that a value is returned
		$this->assertEquals( 'value', PluginFramework\SV_WC_Helper::get_post( $key ) );

		$_POST[ $key ] = ' untrimmed-value ';

		// Check that the value is trimmed
		$this->assertEquals( 'untrimmed-value', PluginFramework\SV_WC_Helper::get_post( $key ) );
	}


	/**
	 * Test \SV_WC_Helper::get_order_line_items()
	 *
	 * @see \SV_WC_Helper::get_order_line_items()
	 * @since 4.3.0
	 */
	public function test_get_order_line_items() {


		$expected_item = new \stdClass();
		$expected_item->id = 777;
		$expected_item->name = 'SkyShirt';
		$expected_item->description = '';
		$expected_item->quantity = 1;
		$expected_item->item_total = '99.99';
		$expected_item->line_total = '99.99';
		$expected_item->meta = null;
		$expected_item->product = $this->get_wc_product_mock();
		$expected_item->item = $this->get_wc_item_data();

		$this->getMockBuilder( 'WC_Order_Item_Meta_Mock' )
			->setMethods( [ 'get_formatted'] )
			->setMockClassName( 'WC_Order_Item_Meta' )
			->getMock()
			->method( 'get_formatted' )
			->willReturn( [ 'label' => 'Size', 'value' => 'Large' ] );

		$actual_line_items = PluginFramework\SV_WC_Helper::get_order_line_items( $this->get_wc_order_mock() );

		$this->assertEquals( [ $expected_item ], $actual_line_items );

		$actual_line_items = current( $actual_line_items );

		$this->assertObjectHasAttribute( 'id', $actual_line_items );
		$this->assertObjectHasAttribute( 'name', $actual_line_items );
		$this->assertObjectHasAttribute( 'description', $actual_line_items );
		$this->assertObjectHasAttribute( 'quantity', $actual_line_items );
		$this->assertObjectHasAttribute( 'item_total', $actual_line_items );
		$this->assertObjectHasAttribute( 'line_total', $actual_line_items );
		$this->assertObjectHasAttribute( 'meta', $actual_line_items );
		$this->assertObjectHasAttribute( 'product', $actual_line_items );
		$this->assertObjectHasAttribute( 'item', $actual_line_items );
	}


	/**
	 * Get a simple mock object for the WC_Order class
	 *
	 * @since 4.3.0
	 * @return \WC_Order mocked order object
	 */
	protected function get_wc_order_mock() {

		// create a mock class for WC_Order
		$order = $this->getMockBuilder( 'WC_Order' )->setMethods( [
				'get_items',
				'get_product_from_item',
				'get_item_total',
				'get_line_total',
			] )->getMock();

		// stub WC_Order::get_items()
		$order->method( 'get_items' )->willReturn( [ 777 => $this->get_wc_item_data() ] );

		// stub WC_Order::get_product_from_item()
		$order->method( 'get_product_from_item' )->willReturn( $this->get_wc_product_mock() );

		// stub WC_Order::get_item_total()/get_line_total()
		$order->method( 'get_item_total' )->willReturn( '99.99' );
		$order->method( 'get_line_total' )->willReturn( '99.99' );


		return $order;
	}


	/**
	 * Get a simple mock object for the WC_Product class
	 *
	 * @since 4.3.0
	 * @return \PHPUnit_Framework_MockObject_Builder_InvocationMocker
	 */
	protected function get_wc_product_mock() {
		return $this->getMockBuilder( 'WC_Product' )->setMethods( [ 'get_sku' ] )->getMock()->method( 'get_sku' )->willReturn( 'SKYSHIRT' );
	}


	/**
	 * Returns an array of item data that matches the format WC returns item data
	 * in
	 *
	 * @since 4.3.0
	 * @return array
	 */
	protected function get_wc_item_data() {

		return [
			'name'            => 'SkyShirt',
			'type'            => 'line_item',
			'item_meta'       => [ 'label' => 'Size', 'value' => 'Large' ],
			'item_meta_array' => [ ],
			'qty'             => 1,
			'tax_class'       => '',
			'product_id'      => 666,
			'variation_id'    => 0,
			'line_subtotal'   => '99.99',
			'line_total'      => '99.99',
			'line_tax'        => '0',
			'line_tax_data'   => '',
		];
	}


	/**
	 * Test \SV_WC_Helper::get_request()
	 *
	 * @see \SV_WC_Helper::get_request()
	 * @since 4.3.0
	 * @dataProvider provider_test_get_request_associative_array
	 */
	public function test_get_request( $request_key, $request_value ) {

		$_REQUEST[ $request_key ]  = $request_value;

		$this->assertEquals( PluginFramework\SV_WC_Helper::get_request( $request_key ), trim( $request_value ) );
		$this->assertEquals( PluginFramework\SV_WC_Helper::get_request( 'invalidKey' ), '' );
	}


	/**
	 * Test \SV_WC_Helper::array_insert_after
	 *
	 * @see \SV_WC_Helper::array_insert_after()
	 * @since 4.3.0
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

		$this->assertArrayHasKey( key( $added_array ), PluginFramework\SV_WC_Helper::array_insert_after( $target_array, $insert_point, $added_array ) );

		// Test a key that doesn't exist
		$insert_point = 'bad-key';

		$this->assertEquals( $target_array, PluginFramework\SV_WC_Helper::array_insert_after( $target_array, $insert_point, $added_array ) );
	}


	/**
	 * Test SV_WC_Helper::f__
	 *
	 * @since 4.3.0
	 */
	public function test_f__() {

		$string = 'String';

		Mock::wpPassthruFunction( '__' );

		$this->assertEquals( $string, PluginFramework\SV_WC_Helper::f__( $string ) );
	}

	/**
	 * Test SV_WC_Helper::f_x
	 *
	 * @since 4.3.0
	 */
	public function test_f_x() {

		$string = 'String';

		Mock::wpPassthruFunction( '_x' );

		$this->assertEquals( $string, PluginFramework\SV_WC_Helper::f_x( $string, 'string-context' ) );
	}

	/*
	 * Test SV_WC_Helper::f_e
	 *
	 * @since 4.3.0
	 */
	public function test_f_e() {

		$string = 'String';

		Mock::wpFunction( '_e', array(
			'args'   => array( $string, '*' ),
			'return' => function( $string ) { echo $string; },
		) );

		PluginFramework\SV_WC_Helper::f_e( $string );

		$this->expectOutputString( $string );
	}


	/**
	 * Test \SV_WC_Helper::array_to_xml()
	 *
	 * @see \SV_WC_Helper::array_to_xml()
	 * @since 4.3.0
	 */
	public function test_array_to_xml() {

		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->startDocument( '1.0', 'UTF-8' );

		PluginFramework\SV_WC_Helper::array_to_xml( $xml, 'foo', array(
			array( 'value' ),
			array(
				'bar' => array(
					'baz' => 'value'
				),
				'baz' => '<invalid-value',
			),
			array( '@attributes' => array(
				'attribute' => 'value',
			) ),
		) );

		$xml->endDocument();

		$output = $xml->outputMemory();

		// Mind newlines, empty last line and indentation
		$expected = <<<MSG
<?xml version="1.0" encoding="UTF-8"?>
<foo>value</foo><foo><bar><baz>value</baz></bar><baz><![CDATA[<invalid-value]]></baz></foo><foo attribute="value"/>

MSG;
		$this->assertEquals( $output, $expected );
	}


	/**
	 * Test \SV_WC_Helper::list_array_items()
	 *
	 * @see \SV_WC_Helper::list_array_items()
	 *
	 * @since 5.2.0
	 *
	 * @dataProvider provider_test_list_array_items
	 */
	public function test_list_array_items( $items, $conjunction, $separator, $expected ) {

		$this->assertEquals( $expected, PluginFramework\SV_WC_Helper::list_array_items( $items, $conjunction, $separator ) );
	}


	/**
	 * Data provider for test_list_array_items()
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function provider_test_list_array_items() {

		$items = [ 'one', 'two', 'three', 'four', 'five' ];

		return [
			[ $items, null, '', 'one, two, three, four, and five' ],                         // method defaults
			[ $items, new \stdClass(), new \stdClass(), 'one, two, three, four, and five' ], // bad param types
			[ $items, 'or', '', 'one, two, three, four, or five' ],                          // custom conjunction
			[ $items, '', '; ', 'one; two; three; four; five' ],                             // empty conjunction, custom separator
			[ $items, 'with', '; ', 'one; two; three; four; with five' ],                    // custom conjunction, custom separator
			[ array_slice( $items, 0, 3 ), null, '', 'one, two, and three' ],                // 3 items
			[ array_slice( $items, 0, 2 ), null, '', 'one and two' ],                        // 2 items
			[ array_slice( $items, 0, 2 ), 'or', '', 'one or two' ],                         // 2 items, custom conjunction
			[ array_slice( $items, 0, 2 ), 'or', '; ', 'one or two' ],                       // 2 items, custom conjunction, custom separator
			[ [ 'one' ], '', '', 'one' ],                                                    // 1 item
			[ [], 'or', '; ', '' ],                                                          // no items
		];
	}


	/**
	 * Test \SV_WC_Helper::number_format()
	 *
	 * @see \SV_WC_Helper::number_format()
	 * @since 4.3.0
	 * @dataProvider provider_test_number_format
	 */
	public function test_number_format( $original_number, $formatted_number ) {

		$result = PluginFramework\SV_WC_Helper::number_format( $original_number );

		$this->assertTrue( is_numeric( $result ), true  );
		$this->assertEquals( $result, $formatted_number );
	}


	/**
	 * Test SV_WC_Helper::convert_country_code()
	 *
	 * @dataProvider provider_test_convert_country_code
	 * @since 4.3.0
	 */
	public function test_convert_country_code( $input_code, $converted_code ) {

		$this->assertEquals( $converted_code, PluginFramework\SV_WC_Helper::convert_country_code( $input_code )  );

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
	 * @since 4.3.0
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
	 * @since 4.3.0
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
	 * @since 4.3.0
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
