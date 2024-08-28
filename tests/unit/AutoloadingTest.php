<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\Unit;

use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Addresses as Addresses;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Admin\Notes_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\API\Abstract_Cacheable_API_Base;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Handlers\Country_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\Blocks as Payment_Gateway_Blocks;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Admin;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\External_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Frontend;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Google_Pay as Google_Pay_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\External_Checkout\Orders;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\Handlers as Handlers;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Payment_Gateway\PaymentFormContextChecker;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Settings_API as Settings_API;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\SV_WC_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\TestCase;

/**
 * @covers composer.json/autoload
 */
class AutoloadingTest extends TestCase
{
	public function testCanAutoload() : void
	{
		require_once PLUGIN_ROOT_DIR.'/woocommerce/api/class-sv-wc-api-base.php';

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
			Settings_API\Abstract_Settings::class,
			Settings_API\Setting::class,
			Settings_API\Control::class,
			Abstract_Cacheable_API_Base::class,
			Notes_Helper::class,
			Addresses\Address::class,
			Addresses\Customer_Address::class,
		];

		Mockery::mock('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType');

		foreach ($list as $className) {
			$this->assertInstanceOf($className, Mockery::mock($className)->makePartial());
		}
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_1\SV_WC_Plugin::setupClassAliases()
	 *
	 * @throws ReflectionException
	 */
	public function testClassAliases() : void
	{
		$aliases = [
			Country_Helper::class            => '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_13_1\\Country_Helper',
			PaymentFormContextChecker::class => '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_13_1\\PaymentFormContextChecker',
		];

		foreach ($aliases as $alias) {
			$this->assertFalse(class_exists($alias));
		}

		$this->invokeInaccessibleMethod(
			Mockery::mock(SV_WC_Plugin::class)->makePartial(),
			'setupClassAliases'
		);

		foreach ($aliases as $class => $alias) {
			$this->assertInstanceOf($class, Mockery::mock($alias));
		}
	}
}
