<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Traits;

use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanGetNewInstanceTrait;

class CanGetNewInstanceTraitTest extends TestCase
{
	/**
	 * Tests that it can get new instance with arguments.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanGetNewInstanceTrait::getNewInstance()
	 */
	public function testItCanGetNewInstanceWithArgs() : void
	{
		$class = $this->getTestClass();
		$newInstance = $class::getNewInstance('value1', 'value2');

		$this->assertSame('value1', $newInstance->arg1);
		$this->assertSame('value2', $newInstance->arg2);
		$this->assertInstanceOf(get_class($class), $newInstance);
	}

	/**
	 * Tests that it can get new instance without arguments.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanGetNewInstanceTrait::getNewInstance()
	 */
	public function testItCanGetNewInstanceWithoutArgs() : void
	{
		$class = $this->getTestClass();
		$newInstance = $class::getNewInstance();

		$this->assertNull($newInstance->arg1);
		$this->assertNull($newInstance->arg2);
		$this->assertInstanceOf(get_class($class), $newInstance);
	}

	/**
	 * Anonymous Class for Testing Trait.
	 *
	 * @see testItCanGetNewInstance
	 */
	private function getTestClass()
	{
		return new class {
			use CanGetNewInstanceTrait;

			public ?string $arg1 = null;
			public ?string $arg2 = null;

			public function __construct(?string $arg1 = null, ?string $arg2 = null)
			{
				$this->arg1 = $arg1;
				$this->arg2 = $arg2;
			}
		};
	}
}
