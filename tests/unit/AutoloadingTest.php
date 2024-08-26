<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\Unit;

use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Admin;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\External_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Frontend;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Orders;
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
			External_Checkout::class,
			Admin::class,
			Frontend::class,
			Orders::class,
		];

		foreach ($list as $className) {
			$this->assertInstanceOf($className, Mockery::mock($className)->makePartial());
		}
	}
}
