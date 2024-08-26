<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\Unit;

use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\Handlers\Abstract_Hosted_Payment_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\Handlers\Abstract_Payment_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\Handlers\Capture;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\TestCase;

/**
 * @covers composer.json/autoload
 */
class AutoloadingTest extends TestCase
{
	public function testCanAutoload() : void
	{
		$list = [
			Capture::class,
			Abstract_Payment_Handler::class,
			Abstract_Hosted_Payment_Handler::class,
		];

		foreach ($list as $className) {
			$this->assertInstanceOf($className, Mockery::mock($className)->makePartial());
		}
	}
}
