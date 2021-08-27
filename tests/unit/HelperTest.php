<?php

define( 'ABSPATH', true );

class HelperTest extends \Codeception\Test\Unit {

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	protected function _before() {

		require_once( 'woocommerce/class-sv-wc-helper.php' );
	}


}
