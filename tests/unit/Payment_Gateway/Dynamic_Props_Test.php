<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_2\Tests\unit\Payment_Gateway;

use Exception;
use Generator;
use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Tests\TestCase;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Payment_Gateway\Dynamic_Props;
use stdClass;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_1_2\Payment_Gateway\Dynamic_Props
 */
final class Dynamic_Props_Test extends TestCase
{
	/**
	 * @throws Exception
	 */
	public function tearDown() : void
	{
		// Reset the WeakMap between tests
		$instance = new Dynamic_Props();
		foreach (['map', 'use_weak_map'] as $prop) {
			$this->setInaccessiblePropertyValue($instance, $prop, null);
		}

		parent::tearDown();
	}

	public function providerStorageMode() : Generator
	{
		yield 'WeakMap' => [true];
		yield 'fallback' => [false];
	}

	protected function mockUseWeakmap(bool $useWeakMap) : void
	{
		$this->mockStaticMethod(Dynamic_Props::class, 'use_dynamic_props_class')
			->andReturn($useWeakMap);
	}

	/**
	 * @covers ::set
	 * @covers ::get
	 * @dataProvider providerStorageMode
	 */
	public function testCanSetKey(bool $useWeakMap) : void
	{
		$this->mockUseWeakmap($useWeakMap);

		$order = Mockery::mock('WC_Order');

		Dynamic_Props::set($order, 'custom_key', 'custom_value');

		$this->assertSame('custom_value', Dynamic_Props::get($order, 'custom_key'));
	}

	/**
	 * @covers ::get
	 * @dataProvider providerStorageMode
	 */
	public function testCanGetNestedKeyWithDefault(bool $useWeakMap) : void
	{
		$this->mockUseWeakmap($useWeakMap);

		$order = Mockery::mock('WC_Order');

		$this->assertSame('fallback', Dynamic_Props::get($order, 'payment', 'token', 'fallback'));
	}

	/**
	 * @covers ::set
	 * @covers ::get
	 * @dataProvider providerStorageMode
	 */
	public function testCanSetAndGetNestedKey(bool $useWeakMap) : void
	{
		$this->mockUseWeakmap($useWeakMap);

		$order = Mockery::mock('WC_Order');

		$payment = new stdClass();
		$payment->token = 'abc123';

		Dynamic_Props::set($order, 'payment', $payment);

		$this->assertSame('abc123', Dynamic_Props::get($order, 'payment', 'token'));
		$this->assertNull(Dynamic_Props::get($order, 'payment', 'nonexistent'));
		$this->assertSame('default', Dynamic_Props::get($order, 'payment', 'nonexistent', 'default'));
	}

	/**
	 * @covers ::get
	 * @dataProvider providerStorageMode
	 */
	public function testCanGetWhenNoValueSet(bool $useWeakMap) : void
	{
		$this->mockUseWeakmap($useWeakMap);

		$order = Mockery::mock('WC_Order');

		$this->assertNull(Dynamic_Props::get($order, 'key_not_set'));
		$this->assertNull(Dynamic_Props::get($order, 'key_not_set', 'nested_key_not_set_either'));
		$this->assertSame('default_value', Dynamic_Props::get($order, 'another_key_not_set', null, 'default_value'));
	}

	/**
	 * @covers ::unset
	 * @dataProvider providerStorageMode
	 */
	public function testCanUnset(bool $useWeakMap) : void
	{
		$this->mockUseWeakmap($useWeakMap);

		// first attempt to unset something that was never set
		$order = Mockery::mock('WC_Order');

		Dynamic_Props::unset($order, 'key_not_set');

		// now set and then unset
		Dynamic_Props::set($order, 'key_to_be_set', 'my_value');

		$this->assertSame('my_value', Dynamic_Props::get($order, 'key_to_be_set'));

		Dynamic_Props::unset($order, 'key_to_be_set');

		$this->assertNull(Dynamic_Props::get($order, 'key_to_be_set'));
	}
}
