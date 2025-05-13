<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Helpers;

use Generator;
use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Helpers\PageHelper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Helpers\PageHelper
 */
final class PageHelperTest extends TestCase
{
	/**
	 * @covers ::isWooCommerceAnalyticsPage()
	 * @dataProvider providerCanDetermineIsWooCommerceAnalyticsPage
	 * @throws \ReflectionException
	 */
	public function testCanDetermineIsWooCommerceAnalyticsPage($pageData, bool $expected) : void
	{
		$pageController = Mockery::mock(\Automattic\WooCommerce\Admin\PageController::class);
		$pageController->expects('get_current_page')
			->once()
			->andReturn($pageData);

		$this->mockStaticMethod(PageHelper::class, 'getWooCommercePageController')
			->once()
			->andReturn($pageController);

		$this->assertSame($expected, PageHelper::isWooCommerceAnalyticsPage());
	}

	/** @see testCanDetermineIsWooCommerceAnalyticsPage */
	public function providerCanDetermineIsWooCommerceAnalyticsPage() : Generator
	{
		yield 'no page data' => [
			'pageData' => false,
			'expected' => false,
		];

		yield 'woocommerce home' => [
			'pageData' => [
				'id' => 'woocommerce-home',
				'parent' => 'woocommerce',
			],
			'expected' => false,
		];

		yield 'orders page' => [
			'pageData' => [
				'id' => 'woocommerce-custom-orders',
			],
			'expected' => false,
		];

		yield 'analytics overview' => [
			'pageData' => [
				'id' => 'woocommerce-analytics',
				'parent' => null,
			],
			'expected' => true,
		];

		yield 'analytics revenue' => [
			'pageData' => [
				'id' => 'woocommerce-analytics-revenue',
				'parent' => 'woocommerce-analytics',
			],
			'expected' => true,
		];

		yield 'analytics products' => [
			'pageData' => [
				'id' => 'woocommerce-analytics-products',
				'parent' => 'woocommerce-analytics',
			],
			'expected' => true,
		];
	}
}
