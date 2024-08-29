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
}
