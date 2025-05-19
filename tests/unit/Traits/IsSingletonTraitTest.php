<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Traits;

use Exception;
use ReflectionClass;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\IsSingletonTrait;

class IsSingletonTraitTest extends TestCase
{
	protected TestSingleton $singleton;

	/**
	 * Runs a script for every test in this set.
	 *
	 * @throws Exception
	 */
	public function setUp() : void
	{
		parent::setUp();

		$this->singleton = new TestSingleton();

		$this->setInaccessiblePropertyValue($this->singleton, 'instance', $this->singleton);
	}

	/**
	 * Tests that it can determine whether an instance is loaded or not.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\IsSingletonTrait::isLoaded()
	 */
	public function testCanCheckIfIsLoaded() : void
	{
		self::assertTrue($this->singleton::isLoaded());

		$this->singleton::reset();

		self::assertFalse($this->singleton::isLoaded());
	}

	/**
	 * Tests that it can initialize and return an instance of self.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\IsSingletonTrait::getInstance()
	 */
	public function testCanGetInstance() : void
	{
		TestSingleton::reset();

		$instance = TestSingleton::getInstance();

		$this->assertInstanceOf(TestSingleton::class, $instance);
		$this->assertSame($instance, TestSingleton::getInstance());
	}

	/**
	 * Tests that an instance can be reset.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\IsSingletonTrait::reset()
	 */
	public function testCanBeReset() : void
	{
		$this->singleton::reset();

		$singleton = new ReflectionClass($this->singleton);
		$instance = $singleton->getProperty('instance');
		$instance->setAccessible(true);

		self::assertNull($instance->getValue());
	}
}

/** Dummy class for testing {@see IsSingletonTrait} */
final class TestSingleton
{
	use IsSingletonTrait;
}
