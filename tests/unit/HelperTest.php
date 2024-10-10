<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_1\Tests\Unit;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_15_1\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_1\SV_WC_Plugin_Compatibility;
use SkyVerge\WooCommerce\PluginFramework\v5_15_1\Tests\TestCase;
use WP_Mock;

class HelperTest extends TestCase
{
	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_1\SV_WC_Helper::is_wc_navigation_enabled()
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
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_1\SV_WC_Helper::is_wc_navigation_enabled()
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

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_1\SV_WC_Helper::getWooCommerceObjectMetaValue()
	 *
	 * @runInSeparateProcess
	 *
	 * @throws ReflectionException
	 */
	public function testCanGetWooCommerceDataObjectMetaValueUsingOrderUtil() : void
	{
		require_once PLUGIN_ROOT_DIR.'/tests/Mocks/OrderUtil.php';

		$object = Mockery::mock('WC_Data');

		$object->expects('get_meta')->never();

		$this->mockStaticMethod(OrderUtil::class, 'get_post_or_object_meta')
			->once()
			->with(null, $object, $field = 'TEST_FIELD', true)
			->andReturn($value = 'WC_DATA_META_VALUE');

		$this->assertSame($value, SV_WC_Helper::getWooCommerceObjectMetaValue($object, $field));
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_1\SV_WC_Helper::getWooCommerceObjectMetaValue()
	 *
	 * @runInSeparateProcess
	 */
	public function testCanGetWooCommerceDataObjectMetaValueWithoutUsingOrderUtil() : void
	{
		$object = Mockery::mock('WC_Data');

		$object->expects('get_meta')
			->once()
			->with($field = 'TEST_FIELD', true)
			->andReturn($value = 'WC_DATA_META_VALUE');

		$this->assertSame($value, SV_WC_Helper::getWooCommerceObjectMetaValue($object, $field));
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_1\SV_WC_Helper::getWooCommerceObjectMetaValue()
	 *
	 * @runInSeparateProcess
	 *
	 * @throws ReflectionException
	 */
	public function testCanGetWordPressPostMetaValueUsingOrderUtil() : void
	{
		require_once PLUGIN_ROOT_DIR.'/tests/Mocks/OrderUtil.php';

		$object = Mockery::mock('WP_Post');

		WP_Mock::userFunction('get_post_meta')->never();

		$this->mockStaticMethod(OrderUtil::class, 'get_post_or_object_meta')
			->once()
			->with($object, null, $field = 'TEST_FIELD', true)
			->andReturn($value = 'WP_POST_META_VALUE');

		$this->assertSame($value, SV_WC_Helper::getWooCommerceObjectMetaValue($object, $field));
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_1\SV_WC_Helper::getWooCommerceObjectMetaValue()
	 *
	 * @runInSeparateProcess
	 */
	public function testCanGetWordPressPostMetaValueWithoutUsingOrderUtil() : void
	{
		$object = Mockery::mock('WP_Post');
		$object->ID = 123;

		WP_Mock::userFunction('get_post_meta')
			->once()
			->with($object->ID, $field = 'TEST_FIELD', true)
			->andReturn($value = 'WP_POST_META_VALUE');

		$this->assertSame($value, SV_WC_Helper::getWooCommerceObjectMetaValue($object, $field));
	}
}
