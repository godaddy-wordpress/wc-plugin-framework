<?php

namespace SkyVerge\WC_Plugin_Framework\Unit_Tests;

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
}
