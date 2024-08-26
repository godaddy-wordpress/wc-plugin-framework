<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\Unit;

use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\Blocks as Payment_Gateway_Blocks;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Admin;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\External_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Frontend;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Google_Pay as Google_Pay_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Orders;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\Handlers as Handlers;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\TestCase;

/**
 * @covers composer.json/autoload
 */
class AutoloadingTest extends TestCase
{
	public function testCanAutoload() : void
	{
		$list = [
			Handlers\Capture::class,
			Handlers\Abstract_Payment_Handler::class,
			Handlers\Abstract_Hosted_Payment_Handler::class,
			External_Checkout::class,
			Admin::class,
			Frontend::class,
			Orders::class,
			Google_Pay_Checkout\Google_Pay::class,
			Google_Pay_Checkout\Admin::class,
			Google_Pay_Checkout\AJAX::class,
			Google_Pay_Checkout\Frontend::class,
			Payment_Gateway_Blocks\Gateway_Blocks_Handler::class,
			Payment_Gateway_Blocks\Gateway_Checkout_Block_Integration::class,
		];

		Mockery::mock('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType');

		foreach ($list as $className) {
			$this->assertInstanceOf($className, Mockery::mock($className)->makePartial());
		}
	}
}
