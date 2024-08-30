<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_0\Tests\Unit\Helpers;

use Generator;
use SkyVerge\WooCommerce\PluginFramework\v5_15_0\Helpers\NumberHelper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_0\Tests\TestCase;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v5_15_0\Helpers\NumberHelper
 */
final class NumberHelperTest extends TestCase
{
	/**
	 * @covers ::format()
	 * @dataProvider providerCanFormat
	 */
	public function testCanFormat($number, string $expected) : void
	{
		$this->assertSame($expected, NumberHelper::format($number));
	}

	/** @see testCanFormat */
	public function providerCanFormat(): Generator
	{
		yield '5' => [
			'number' => 5,
			'expected' => '5.00',
		];

		yield '10.5' => [
			'number' => 10.5,
			'expected' => '10.50',
		];

		yield '1,350.66' => [
			'number' => 1350.66,
			'expected' => '1350.66',
		];
	}
}
