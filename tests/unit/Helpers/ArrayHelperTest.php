<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\Unit\Helpers;

use ArrayAccess;
use Generator;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Helpers\ArrayHelper;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\TestCase;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v5_13_1\Helpers\ArrayHelper
 */
final class ArrayHelperTest extends TestCase
{
	/**
	 * @covers ::accessible()
	 * @dataProvider providerCanDetermineIsAccessible
	 *
	 * @param mixed $item
	 * @param bool $expected
	 * @return void
	 */
	public function testCanDetermineIsAccessible($item, bool $expected): void
	{
		$this->assertSame($expected, ArrayHelper::accessible($item));
	}

	/** @see testCanDetermineIsAccessible */
	public function providerCanDetermineIsAccessible(): Generator
	{
		yield 'array is accessible' => [
			'item' => ['foo' => 'bar'],
			'expected' => true,
		];

		yield 'ArrayAccess is accessible' => [
			'item' => $this->getArrayAccessObject(),
			'expected' => true,
		];

		yield 'object without ArrayAccess is not accessible' => [
			'item' => new \stdClass(),
			'expected' => false,
		];

		yield 'integer is not accessible' => [
			'item' => 10,
			'expected' => false,
		];

		yield 'string is not accessible' => [
			'item' => 'string',
			'expected' => false,
		];
	}

	/**
	 * @covers ::except()
	 * @dataProvider providerExcept
	 *
	 * @param array $inputArray
	 * @param array|string $keysToExclude
	 * @param array $expectedOutputArray
	 * @return void
	 */
	public function testExcept(array $inputArray, $keysToExclude, array $expectedOutputArray): void
	{
		$this->assertSame(
			$expectedOutputArray,
			ArrayHelper::except($inputArray, $keysToExclude)
		);
	}

	/** @see testExcept */
	public function providerExcept(): Generator
	{
		yield 'array of keys' => [
			'inputArray' => [
				'apple' => 'red',
				'banana' => 'yellow',
				'cucumber' => 'green',
			],
			'keysToExclude' => ['apple', 'cucumber'],
			'expectedOutputArray' => [
				'banana' => 'yellow',
			],
		];

		yield 'key as a string' => [
			'inputArray' => [
				'apple' => 'red',
				'banana' => 'yellow',
				'cucumber' => 'green',
			],
			'keysToExclude' => 'banana',
			'expectedOutputArray' => [
				'apple' => 'red',
				'cucumber' => 'green',
			],
		];

		yield 'keys to exclude do not exist' => [
			'inputArray' => [
				'apple' => 'red',
				'banana' => 'yellow',
				'cucumber' => 'green',
			],
			'keysToExclude' => ['invalid'],
			'expectedOutputArray' => [
				'apple' => 'red',
				'banana' => 'yellow',
				'cucumber' => 'green',
			],
		];
	}

	/**
	 * @covers ::remove()
	 */
	public function testCanRemove(): void
	{
		$this->markTestIncomplete('TODO');
	}

	/**
	 * @covers ::wrap()
	 * @dataProvider providerCanWrap
	 *
	 * @param mixed $item
	 * @param array $expectedOutput
	 * @return void
	 */
	public function testCanWrap($item, array $expectedOutput): void
	{
		$this->assertEquals($expectedOutput, ArrayHelper::wrap($item));
	}

	/** @see testCanWrap */
	public function providerCanWrap(): Generator
	{
		yield 'already an array' => [
			'item' => ['foo' => 'bar'],
			'expectedOutput' => ['foo' => 'bar'],
		];

		yield 'integer' => [
			'item' => 50,
			'expectedOutput' => [50],
		];

		yield 'null item' => [
			'item' => null,
			'expectedOutput' => [],
		];

		yield 'empty string' => [
			'item' => '',
			'expectedOutput' => [],
		];
	}

	/**
	 * @covers ::exists()
	 * @dataProvider providerCanDetermineKeyExists
	 *
	 * @param array|ArrayAccess $input
	 * @param string|int $key
	 * @param bool $expected
	 * @return void
	 */
	public function testCanDetermineKeyExists($input, $key, bool $expected): void
	{
		$this->assertSame(
			$expected,
			ArrayHelper::exists($input, $key)
		);
	}

	/** @see testCanDetermineKeyExists */
	public function providerCanDetermineKeyExists(): Generator
	{
		yield 'ArrayAccess' => [
			'input' => $this->getArrayAccessObject(),
			'key' => 'key',
			'expected' => true,
		];

		yield 'array with string key that exists' => [
			'input' => ['foo' => 'bar'],
			'key' => 'foo',
			'expected' => true,
		];

		yield 'array with string key that does not exist' => [
			'input' => ['foo' => 'bar'],
			'key' => 'invalid',
			'expected' => false,
		];

		yield 'array with integer key that exists' => [
			'input' => ['chocolate', 'vanilla'],
			'key' => 1,
			'expected' => true,
		];

		yield 'array with integer key that does not exist' => [
			'input' => ['chocolate', 'vanilla'],
			'key' => 5,
			'expected' => false,
		];
	}

	protected function getArrayAccessObject(): ArrayAccess
	{
		return new class implements ArrayAccess
		{

			public function offsetExists($offset)
			{
				return true;
			}

			public function offsetGet($offset)
			{
				return 1;
			}

			public function offsetSet($offset, $value)
			{
				// TODO: Implement offsetSet() method.
			}

			public function offsetUnset($offset)
			{
				// TODO: Implement offsetUnset() method.
			}
		};
	}
}
