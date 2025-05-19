<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Payment_Gateway\External_Checkout\Google_Pay;

use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Google_Pay\Admin;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Payment_Gateway\External_Checkout\Google_Pay\Admin
 */
class AdminTest extends TestCase
{
	/** @var Mockery\MockInterface&Admin */
	private $testObject;

	public function setUp() : void
	{
		parent::setUp();

		$this->testObject = Mockery::mock(Admin::class)
			->shouldAllowMockingProtectedMethods()
			->makePartial();
	}

	/**
	 * @covers ::get_settings()
	 */
	public function testCanGetSettings() : void
	{
		$this->testObject->expects('get_display_location_options')
			->twice()
			->andReturn($displayLocationOptions = [
				'TEST_DISPLAY_LOCATION_OPTION_KEY' => 'TEST_DISPLAY_LOCATION_OPTION_VALUE',
			]);

		$this->testObject->expects('get_connection_settings')
			->once()
			->andReturn($connectionSettings = ['TEST_CONNECTION_SETTINGS']);

		$expectedSettings = [
			[
				'title' => 'Google Pay',
				'type'  => 'title',
			],
			[
				'id'      => 'sv_wc_google_pay_enabled',
				'title'   => 'Enable / Disable',
				'desc'    => 'Accept Google Pay',
				'type'    => 'checkbox',
				'default' => 'no',
			],
			[
				'id'    => 'sv_wc_google_pay_merchant_id',
				'title' => 'Merchant ID',
				'desc'  => 'A Google merchant identifier issued after registration with the <a href="https://pay.google.com/business/console" target="_blank">Google Pay & Wallet Console</a>. 12-18 characters. Required in production environment.',
				'type'  => 'text',
			],
			[
				'id'      => 'sv_wc_google_pay_display_locations',
				'title'   => 'Allow Google Pay on',
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'css'     => 'width: 350px;',
				'options' => $displayLocationOptions,
				'default' => array_keys($displayLocationOptions),
			],
			[
				'id'      => 'sv_wc_google_pay_button_style',
				'title'   => 'Button Style',
				'type'    => 'select',
				'options' => [
					'black' => 'Black',
					'white' => 'White',
				],
				'default' => 'black',
			],
			[
				'type' => 'sectionend',
			],
			...$connectionSettings,
		];

		WP_Mock::onFilter('woocommerce_get_settings_google_pay')
			->with($expectedSettings)
			->reply($expectedSettings);

		$this->assertSame($expectedSettings, $this->testObject->get_settings());
	}
}
