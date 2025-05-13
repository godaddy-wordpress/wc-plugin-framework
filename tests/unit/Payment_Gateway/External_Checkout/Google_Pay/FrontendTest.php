<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Payment_Gateway\External_Checkout\Google_Pay;

use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\External_Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Google_Pay\Frontend;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Google_Pay\Frontend
 */
class FrontendTest extends TestCase
{
	/** @var Mockery\MockInterface&Frontend */
	private $testObject;

	/** @var Mockery\MockInterface&SV_WC_Payment_Gateway */
	private $gateway;

	/** @var Mockery\MockInterface&External_Checkout */
	private $handler;

	public function setUp() : void
	{
		parent::setUp();

		Mockery::mock('\WC_Payment_Gateway');

		$this->gateway = Mockery::mock(SV_WC_Payment_Gateway::class);
		$this->handler = Mockery::mock(External_Checkout::class);

		$this->testObject = Mockery::mock(Frontend::class)
			->shouldAllowMockingProtectedMethods()
			->makePartial();

		$this->testObject->allows('get_gateway')->andReturn($this->gateway);
		$this->testObject->allows('get_handler')->andReturn($this->handler);
	}

	/**
	 * @covers ::get_js_handler_args()
	 * @throws ReflectionException
	 */
	public function testCanGetJsHandlerArgs() : void
	{
		$this->gateway->allows('get_id')
			->andReturn($gatewayId = 'TEST_GATEWAY_ID');

		$this->gateway->allows('get_id_dasherized')
			->andReturn($gatewayIdDasherized = 'TEST_GATEWAY_ID_DASHERIZED');

		$this->handler->allows('get_gateway_merchant_id')
			->andReturn($gatewayMerchantId = 'TEST_GATEWAY_MERCHANT_ID');

		$this->gateway->allows('get_environment')
			->andReturn('production');

		$this->gateway->allows('get_plugin')
			->andReturn($gatewayPlugin = Mockery::mock(SV_WC_Payment_Gateway_Plugin::class));

		$this->testObject->allows('get_plugin')
			->andReturn($gatewayPlugin);

		$gatewayPlugin->allows('get_id')
			->andReturn($gatewayPluginId = 'TEST_GATEWAY_PLUGIN_ID');

		$gatewayPlugin->allows('get_gateway')
			->andReturn($this->gateway);

		$this->handler->expects('get_merchant_id')
			->andReturn($merchantId = 'TEST_MERCHANT_ID');

		$this->handler->expects('get_merchant_name')
			->andReturn($merchantName = 'TEST_MERCHANT_NAME');

		WP_Mock::userFunction('admin_url')
			->once()
			->with('admin-ajax.php')
			->andReturn($ajaxUrl = 'https://domain.test/admin-ajax.php');

		WP_Mock::userFunction('wp_create_nonce')
			->once()
			->with('wc_'.$gatewayId.'_google_pay_recalculate_totals')
			->andReturn($recalculateTotalsNonce = 'TEST_RECALCULATE_TOTALS_NONCE');

		WP_Mock::userFunction('wp_create_nonce')
			->once()
			->with('wc_'.$gatewayId.'_google_pay_process_payment')
			->andReturn($processNonce = 'TEST_PROCESS_NONCE');

		$this->handler->expects('get_button_style')
			->andReturn($buttonStyle = 'TEST_BUTTON_STYLE');

		$this->handler->expects('get_supported_networks')
			->andReturn($cardTypes = 'TEST_CARD_TYPES');

		$this->handler->expects('get_available_countries')
			->andReturn($availableCountries = 'TEST_AVAILABLE_COUNTRIES');

		WP_Mock::userFunction('get_woocommerce_currency')
			->once()
			->andReturn($currency = 'USD');

		$expectedArgs = [
			'plugin_id'                => $gatewayPluginId,
			'merchant_id'              => $merchantId,
			'merchant_name'            => $merchantName,
			'gateway_id'               => $gatewayId,
			'gateway_id_dasherized'    => $gatewayIdDasherized,
			'gateway_merchant_id'      => $gatewayMerchantId,
			'environment'              => 'PRODUCTION',
			'ajax_url'                 => $ajaxUrl,
			'recalculate_totals_nonce' => $recalculateTotalsNonce,
			'process_nonce'            => $processNonce,
			'button_style'             => $buttonStyle,
			'card_types'               => $cardTypes,
			'available_countries'      => $availableCountries,
			'currency_code'            => $currency,
			'generic_error'            => 'An error occurred, please try again or try an alternate form of payment',
		];

		WP_Mock::onFilter('wc_'.$gatewayId.'_google_pay_js_handler_params')
			->with($expectedArgs)->reply($expectedArgs);

		$this->assertSame($expectedArgs, $this->invokeInaccessibleMethod($this->testObject, 'get_js_handler_args'));
	}
}
