<?php

use \SkyVerge\WooCommerce\PluginFramework\v5_5_1 as Framework;

/**
 * Perform tests for the helper class that handles utility functions related to country entities.
 *
 * @see Framework\Country_Helper
 */
class Country_Helper_Test extends \Codeception\TestCase\WPTestCase {


	protected function _before() {

		require_once 'woocommerce/Country_Helper.php';
	}


	/** @see Framework\Country_Helper::convert_alpha_country_code() */
	public function test_convert_alpha_country_code() {

		$convert_codes = [
			'US'      => 'USA',
			'USA'     => 'US',
			'UNKNOWN' => 'UNKNOWN',
		];

		foreach ( $convert_codes as $input_value => $expected_result ) {

			$output_value = Framework\Country_Helper::convert_alpha_country_code( $input_value );

			$this->assertIsString( $output_value );
			$this->assertEquals( $expected_result, $output_value );
		}
	}


}
