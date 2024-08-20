<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_0\Tests\Unit;

use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_13_0\PaymentFormContextChecker;
use SkyVerge\WooCommerce\PluginFramework\v5_13_0\Tests\TestCase;
use WooCommerce;
use WP_Mock;

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

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_0\PaymentFormContextChecker::maybeSetContext()
	 */
	public function testCanSetContext() : void
	{
		$this->testObject->expects('getCurrentPaymentFormContext')
			->once()
			->andReturn('TEST_FORM_CONTEXT');

		$this->testObject->expects('getContextSessionKeyName')
			->once()
			->andReturn('TEST_CONTEXT_SESSION_KEY_NAME');

		WP_Mock::userFunction('WC')
			->once()
			->andReturn($wooCommerce = Mockery::mock(WooCommerce::class));

		$session = Mockery::mock('WC_Session');
		$session->expects('set')
			->once()
			->with('TEST_CONTEXT_SESSION_KEY_NAME', 'TEST_FORM_CONTEXT');

		$wooCommerce->session = $session;

		$this->testObject->maybeSetContext();

		$this->assertConditionsMet();
	}
}
