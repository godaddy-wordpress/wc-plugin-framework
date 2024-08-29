<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_14_0\Tests\Unit;

use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_14_0\SV_WC_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_14_0\Tests\TestCase;
use WP_Mock;

/**
 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_14_0\SV_WC_Plugin
 */
class PluginTest extends TestCase
{
	/**
	 * @var Mockery\MockInterface&SV_WC_Plugin
	 */
	private $testObject;

	public function setUp() : void
	{
		parent::setUp();

		$this->testObject = Mockery::mock(SV_WC_Plugin::class)
			->shouldAllowMockingProtectedMethods()
			->makePartial();
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_14_0\SV_WC_Plugin::logger()
	 * @throws ReflectionException
	 */
	public function testCanGetLogger() : void
	{
		WP_Mock::userFunction('wc_get_logger')
			->once()
			->andReturn($logger = Mockery::mock('WC_Logger_Interface'));

		$this->assertSame(
			$logger,
			$this->invokeInaccessibleMethod($this->testObject, 'logger')
		);

		$this->assertSame(
			$logger,
			$this->invokeInaccessibleMethod($this->testObject, 'logger')
		);
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_14_0\SV_WC_Plugin::assert()
	 * @throws ReflectionException
	 */
	public function testCanAssert() : void
	{
		WP_Mock::userFunction('wp_debug_backtrace_summary')->never();

		$this->testObject->expects('logger')->never();

		$this->assertNull(
			$this->invokeInaccessibleMethod($this->testObject, 'assert', true)
		);
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_14_0\SV_WC_Plugin::assert()
	 * @throws ReflectionException
	 */
	public function testCanCatchFailedAssertion() : void
	{
		$initValue = ini_get('zend.assertions');
		ini_set('zend.assertions', 1);

		WP_Mock::userFunction('wp_debug_backtrace_summary')
			->once()
			->andReturn('TEST_DEBUG_BACKTRACE_SUMMARY');

		$this->testObject->expects('logger')
			->once()
			->andReturn($logger = Mockery::mock('WC_Logger_Interface'));

		$logger->expects('debug')
			->once()
			->with('Assertion failed, backtrace summery: TEST_DEBUG_BACKTRACE_SUMMARY');

		$this->assertNull(
			$this->invokeInaccessibleMethod($this->testObject, 'assert', false)
		);

		ini_set('zend.assertions', $initValue);
	}
}
