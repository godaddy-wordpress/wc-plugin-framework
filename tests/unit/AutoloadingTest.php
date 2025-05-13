<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit;

use Generator;
use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Addresses;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Admin\Notes_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\API\Abstract_Cacheable_API_Base;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Handlers\Country_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\Blocks as Payment_Gateway_Blocks;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Admin;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\External_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Frontend;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Google_Pay as Google_Pay_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Orders;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\Handlers;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\PaymentFormContextChecker;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Plugin\Lifecycle;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\REST_API;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Settings_API;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Admin_Notice_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_Admin_Payment_Token_Editor;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_API_Authorization_Response;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_API_Get_Tokenized_Payment_Methods_Response;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_API_Response;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_Apple_Pay_AJAX;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_Integration_Subscriptions;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_Payment_Tokens_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WP_Admin_Message_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WP_Job_Batch_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;

/**
 * @covers composer.json/autoload
 */
class AutoloadingTest extends TestCase
{
	/**
	 * @dataProvider providerCanAutoload
	 */
	public function testCanAutoload(string $className) : void
	{
		Mockery::mock('\WP_REST_Controller');
		Mockery::mock('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType');

		$this->assertTrue(class_exists($className) || interface_exists($className));
	}

	/** @see testCanAutoload */
	public function providerCanAutoload() : Generator
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
			Settings_API\Abstract_Settings::class,
			Settings_API\Setting::class,
			Settings_API\Control::class,
			Abstract_Cacheable_API_Base::class,
			Notes_Helper::class,
			Addresses\Address::class,
			Addresses\Customer_Address::class,
			SV_WC_Payment_Gateway_API_Authorization_Response::class,
			SV_WC_Payment_Gateway_API_Get_Tokenized_Payment_Methods_Response::class,
			SV_WC_Payment_Gateway_API_Response::class,
			SV_WC_Payment_Gateway_Admin_Payment_Token_Editor::class,
			SV_WC_Payment_Gateway_Exception::class,
			SV_WC_Payment_Gateway_Apple_Pay_AJAX::class,
			SV_WC_Payment_Gateway_Integration_Subscriptions::class,
			SV_WC_Payment_Gateway_Payment_Tokens_Handler::class,
			SV_WC_Payment_Gateway_Integration::class,
			\SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\REST_API::class,
			REST_API::class,
			REST_API\Controllers\Settings::class,
			SV_WP_Job_Batch_Handler::class,
			SV_WC_Admin_Notice_Handler::class,
			SV_WP_Admin_Message_Handler::class,
			Lifecycle::class,
		];

		foreach ($list as $className) {
			yield $className => [$className];
		}
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Plugin::setupClassAliases()
	 *
	 * @throws ReflectionException
	 */
	public function testClassAliases() : void
	{
		$aliases = [
			Country_Helper::class            => \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Country_Helper::class,
			PaymentFormContextChecker::class => \SkyVerge\WooCommerce\PluginFramework\v5_15_11\PaymentFormContextChecker::class,
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
