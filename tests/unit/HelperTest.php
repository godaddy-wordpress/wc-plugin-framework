<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_3\Tests\Unit;

use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_15_3\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_3\SV_WC_Plugin_Compatibility;
use SkyVerge\WooCommerce\PluginFramework\v5_15_3\Tests\TestCase;

class HelperTest extends TestCase
{
	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_3\SV_WC_Helper::is_wc_navigation_enabled()
	 *
	 * @throws ReflectionException
	 */
	public function testCanDetermineIfNavigationFeaturedEnabled() : void
	{
		$this->mockStaticMethod(SV_WC_Plugin_Compatibility::class, 'is_wc_version_gte')
			->once()
			->with('9.3')
			->andReturnFalse();

		$this->mockStaticMethod(SV_WC_Helper::class, 'isEnhancedNavigationFeatureEnabled')
			->once()
			->andReturnTrue();

		$this->mockStaticMethod(SV_WC_Helper::class, 'enhancedNavigationDeprecationNotice')
			->never();

		$this->assertTrue(SV_WC_Helper::is_wc_navigation_enabled());
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_3\SV_WC_Helper::is_wc_navigation_enabled()
	 *
	 * @throws ReflectionException
	 */
	public function testAlwaysDetermineNavigationFeaturedDisabled() : void
	{
		$this->mockStaticMethod(SV_WC_Plugin_Compatibility::class, 'is_wc_version_gte')
			->once()
			->with('9.3')
			->andReturnTrue();

		$this->mockStaticMethod(SV_WC_Helper::class, 'enhancedNavigationDeprecationNotice')
			->once();

		$this->mockStaticMethod(SV_WC_Helper::class, 'isEnhancedNavigationFeatureEnabled')
			->never();

		$this->assertFalse(SV_WC_Helper::is_wc_navigation_enabled());
	}
}
