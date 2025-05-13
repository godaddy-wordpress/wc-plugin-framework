<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit;

use Generator;
use Mockery;
use ReflectionException;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Plugin_Compatibility;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use WC_Data;
use WP_Mock;

class HelperTest extends TestCase
{
	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Helper::is_wc_navigation_enabled()
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
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Helper::is_wc_navigation_enabled()
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
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Helper::getWooCommerceObjectMetaValue()
	 *
	 * @throws ReflectionException
	 */
	public function testCanGetWooCommerceDataObjectMetaValue() : void
	{
		$object = Mockery::mock('WC_Data');

		$this->mockStaticMethod(SV_WC_Helper::class, 'getPostOrObjectMetaCompat')
			->once()
			->with(null, $object, $field = 'TEST_FIELD', true)
			->andReturn($value = 'WC_DATA_META_VALUE');

		$this->assertSame($value, SV_WC_Helper::getWooCommerceObjectMetaValue($object, $field));
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Helper::getWooCommerceObjectMetaValue()
	 *
	 * @throws ReflectionException
	 */
	public function testCanGetWordPressPostMetaValue() : void
	{
		$object = Mockery::mock('WP_Post');

		$this->mockStaticMethod(SV_WC_Helper::class, 'getPostOrObjectMetaCompat')
			->once()
			->with($object, null, $field = 'TEST_FIELD', true)
			->andReturn($value = 'WP_POST_META_VALUE');

		$this->assertSame($value, SV_WC_Helper::getWooCommerceObjectMetaValue($object, $field));
	}

	/**
	 * @dataProvider providerCanGetPostOrObjectMetaCompat()
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\SV_WC_Helper::getPostOrObjectMetaCompat()
	 *
	 * @param bool $hasData
	 * @param bool $hasPostId
	 * @param mixed $expected
	 *
	 * @throws ReflectionException
	 */
	public function testCanGetPostOrObjectMetaCompat(
		bool $hasData,
		bool $hasPostId,
		$expected
	) : void
	{
		$key = 'test';
		$object = null;

		$post = Mockery::mock('WP_Post');

		if ($hasPostId) {
			$post->ID = 123;
		}

		if ($hasData) {
			$object = Mockery::mock('WC_Data');

			$object->expects('get_meta')
				->times((int) ($hasData))
				->with($key, true)
				->andReturn('WC_DATA_META_VALUE');
		}

		WP_Mock::userFunction('get_post_meta')
			->times((int) (! $hasData && $hasPostId))
			->with(123, $key, true)
			->andReturn('WP_POST_META_VALUE');

		$this->assertSame($expected, SV_WC_Helper::getPostOrObjectMetaCompat($post, $object, $key, true));
	}

	/** @see testCanGetPostOrObjectMetaCompat() */
	public function providerCanGetPostOrObjectMetaCompat() : Generator
	{
		yield 'data is set, method does not exist' => [
			'hasData'   => true,
			'hasPostId' => false,
			'expected'  => 'WC_DATA_META_VALUE',
		];

		/*
		 * Note: It seems there's no sane way to test the get$key() method
		 * on a WC_Data mock, thus no test case for 'data is set, method exists'.
		 */

		yield 'data not set, no post ID' => [
			'hasData'   => false,
			'hasPostId' => false,
			'expected'  => false,
		];

		yield 'data not set, has post ID' => [
			'hasData'   => false,
			'hasPostId' => true,
			'expected'  => 'WP_POST_META_VALUE',
		];
	}
}
