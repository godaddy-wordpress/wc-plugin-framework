<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Payment_Gateway\External_Checkout\Google_Pay;

use Generator;
use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Google_Pay\Google_Pay;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Google_Pay\Google_Pay
 */
class GooglePayTest extends TestCase
{
	/** @var Mockery\MockInterface&Google_Pay */
	private $testObject;

	public function setUp() : void
	{
		parent::setUp();

		$this->testObject = Mockery::mock(Google_Pay::class)
			->shouldAllowMockingProtectedMethods()
			->makePartial();
	}

	/**
	 * @covers ::get_merchant_id()
	 * @dataProvider providerCanGetMerchantId
	 *
	 * @throws ReflectionException
	 */
	public function testCanGetMerchantId($merchantId, string $expected) : void
	{
		$this->setInaccessiblePropertyValue($this->testObject, 'id', $id = 'TEST_ID');

		WP_Mock::userFunction('get_option')
			->once()
			->with("sv_wc_{$id}_merchant_id")
			->andReturn($merchantId);

		$this->assertSame($expected, $this->testObject->get_merchant_id());
	}

	/** @see testCanGetMerchantId */
	public function providerCanGetMerchantId() : Generator
	{
		yield 'valid value' => [
			'merchantId' => 'TEST_MERCHANT_ID',
			'expected'   => 'TEST_MERCHANT_ID',
		];

		yield 'empty string value' => [
			'merchantId' => '',
			'expected'   => '',
		];

		yield 'scalar value: bool' => [
			'merchantId' => false,
			'expected'   => '',
		];

		yield 'scalar value: int' => [
			'merchantId' => 123,
			'expected'   => '123',
		];

		yield 'scalar value: float' => [
			'merchantId' => 45.6,
			'expected'   => '45.6',
		];

		yield 'non-string value: array' => [
			'merchantId' => ['test'],
			'expected'   => '',
		];
	}

	/**
	 * @covers ::get_gateway_merchant_id()
	 */
	public function testCanGetGatewayMerchantId() : void
	{
		$this->testObject->expects('get_processing_gateway')
			->andReturn($gateway = new class {
				public string $merchantId = 'TEST_MERCHANT_ID';

				public function get_merchant_id() : string
				{
					return $this->merchantId;
				}
			});

		$this->assertSame($gateway->merchantId, $this->testObject->get_gateway_merchant_id());
	}

	/**
	 * @covers ::get_merchant_name()
	 */
	public function testCanGetMerchantName() : void
	{
		WP_Mock::userFunction('get_bloginfo')
			->once()
			->with('name')
			->andReturn($merchantName = 'TEST_MERCHANT_NAME');

		WP_Mock::onFilter('sv_wc_google_pay_merchant_name')
			->with($merchantName)->reply($merchantName);

		$this->assertSame($merchantName, $this->testObject->get_merchant_name());
	}
}
