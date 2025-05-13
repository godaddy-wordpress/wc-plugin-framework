<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Payment_Gateway;

use Generator;
use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Enums\PaymentFormContext;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\PaymentFormContextChecker;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use WooCommerce;
use WP_Mock;

class PaymentFormContextCheckerTest extends TestCase
{
	/** @var Mockery\MockInterface&PaymentFormContextChecker */
	private $testObject;

	private array $originalGet;

	public function setUp() : void
	{
		parent::setUp();

		$this->testObject = Mockery::mock(PaymentFormContextChecker::class)
			->shouldAllowMockingProtectedMethods()
			->makePartial();

		$this->originalGet = $_GET;
	}

	public function tearDown() : void
	{
		parent::tearDown();

		$_GET = $this->originalGet;
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\PaymentFormContextChecker::getContextSessionKeyName()
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
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\PaymentFormContextChecker::maybeSetContext()
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

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\PaymentFormContextChecker::getCurrentPaymentFormContext()
	 *
	 * @dataProvider providerCanGetCurrentPaymentFormContext
	 *
	 * @throws ReflectionException
	 */
	public function testCanGetCurrentPaymentFormContext(
		bool $isCheckoutPayPage,
		bool $isCheckout,
		array $getParams,
		?string $expected
	) : void {
		$this->mockStaticMethod(SV_WC_Helper::class, 'isCheckoutPayPage')
			->once()
			->andReturn($isCheckoutPayPage);

		$_GET = $getParams;

		WP_Mock::userFunction('is_checkout')
			->zeroOrMoreTimes()
			->andReturn($isCheckout);

		$this->assertSame(
			$expected,
			$this->invokeInaccessibleMethod($this->testObject, 'getCurrentPaymentFormContext')
		);
	}

	/** @see testCanGetCurrentPaymentFormContext */
	public function providerCanGetCurrentPaymentFormContext() : Generator
	{
		yield 'customer pay page' => [
			'isCheckoutPayPage' => true,
			'isCheckout'        => true,
			'getParams'         => ['pay_for_order' => 'yes'],
			'expected'          => PaymentFormContext::CustomerPayPage,
		];

		yield 'checkout pay page' => [
			'isCheckoutPayPage' => true,
			'isCheckout'        => true,
			'getParams'         => [],
			'expected'          => PaymentFormContext::CheckoutPayPage,
		];

		yield 'checkout' => [
			'isCheckoutPayPage' => false,
			'isCheckout'        => true,
			'getParams'         => [],
			'expected'          => PaymentFormContext::Checkout,
		];

		yield 'unknown' => [
			'isCheckoutPayPage' => false,
			'isCheckout'        => false,
			'getParams'         => [],
			'expected'          => null,
		];
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\PaymentFormContextChecker::getStoredPaymentFormContext()
	 * @throws ReflectionException
	 */
	public function testCanGetStoredPaymentFormContext() : void
	{
		$this->testObject->expects('getContextSessionKeyName')
			->once()
			->andReturn('TEST_CONTEXT_SESSION_KEY_NAME');

		WP_Mock::userFunction('WC')
			->once()
			->andReturn($wooCommerce = Mockery::mock(WooCommerce::class));

		$session = Mockery::mock('WC_Session');
		$session->expects('get')
			->once()
			->with('TEST_CONTEXT_SESSION_KEY_NAME')
			->andReturn('checkout');

		$wooCommerce->session = $session;

		$this->assertSame(
			PaymentFormContext::Checkout,
			$this->invokeInaccessibleMethod($this->testObject, 'getStoredPaymentFormContext')
		);
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\PaymentFormContextChecker::currentContextRequiresTermsAndConditionsAcceptance()
	 *
	 * @dataProvider providerCanDetermineCurrentContextRequiresTermsAndConditionsAcceptance
	 *
	 * @throws ReflectionException
	 */
	public function testCanDetermineCurrentContextRequiresTermsAndConditionsAcceptance(
		string $storedPaymentFormContext,
		bool $termsAndConditionsEnabled,
		bool $expected
	) : void {
		$this->testObject->expects('getStoredPaymentFormContext')
			->once()
			->andReturn($storedPaymentFormContext);

		WP_Mock::userFunction('wc_terms_and_conditions_checkbox_enabled')
			->atMost()->once()->andReturn($termsAndConditionsEnabled);

		$this->assertSame(
			$expected,
			$this->testObject->currentContextRequiresTermsAndConditionsAcceptance()
		);
	}

	/** @see testCanDetermineCurrentContextRequiresTermsAndConditionsAcceptance */
	public function providerCanDetermineCurrentContextRequiresTermsAndConditionsAcceptance() : Generator
	{
		yield 'customer pay page and T&C enabled' => [
			'storedPaymentFormContext'  => PaymentFormContext::CustomerPayPage,
			'termsAndConditionsEnabled' => true,
			'expected'                  => true,
		];

		yield 'customer pay page but T&C disabled' => [
			'storedPaymentFormContext'  => PaymentFormContext::CustomerPayPage,
			'termsAndConditionsEnabled' => false,
			'expected'                  => false,
		];

		yield 'not customer pay page and T&C enabled' => [
			'storedPaymentFormContext'  => PaymentFormContext::CheckoutPayPage,
			'termsAndConditionsEnabled' => true,
			'expected'                  => false,
		];
	}
}
