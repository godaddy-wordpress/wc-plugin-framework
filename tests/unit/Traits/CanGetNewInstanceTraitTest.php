<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\Unit\Traits;

use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\TestCase;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Traits\CanGetNewInstanceTrait;

class CanGetNewInstanceTraitTest extends TestCase
{
	/**
	 * Tests that it can get new instance.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_1\Traits\CanGetNewInstanceTrait::getNewInstance()
	 */
	public function testItCanGetNewInstance()
	{
		$class = $this->getTestClass();
		$newInstance = $class::getNewInstance('value1', 'value2');

		$this->assertSame('value1', $newInstance->arg1);
		$this->assertSame('value2', $newInstance->arg2);
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

			public $arg1;
			public $arg2;

			public function __construct($arg1 = null, $arg2 = null)
			{
				$this->arg1 = $arg1;
				$this->arg2 = $arg2;
			}
		};
	}
}
