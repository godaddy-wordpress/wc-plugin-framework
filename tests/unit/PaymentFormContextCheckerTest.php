<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_0\Tests\Unit;

use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_13_0\PaymentFormContextChecker;
use SkyVerge\WooCommerce\PluginFramework\v5_13_0\Tests\TestCase;

class PaymentFormContextCheckerTest extends TestCase
{
	/** @var Mockery\MockInterface&PaymentFormContextChecker */
	private $testObject;

	public function setUp() : void
	{
		parent::setUp();

		require_once PLUGIN_ROOT_DIR.'/woocommerce/payment-gateway/PaymentFormContextChecker.php';

		$this->testObject = Mockery::mock(PaymentFormContextChecker::class)
			->shouldAllowMockingProtectedMethods()
			->makePartial();
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_0\PaymentFormContextChecker::getContextSessionKeyName()
	 * @throws ReflectionException
	 */
	public function testCanGetContextSessionKeyName() : void
	{
		$this->setInaccessiblePropertyValue($this->testObject, 'gatewayId', 'TEST_GATEWAY_ID');

		$this->assertSame(
			'wc_TEST_GATEWAY_ID_payment_form_context',
			$this->invokeInaccessibleMethod($this->testObject, 'getContextSessionKeyName')
		);
	}
}
