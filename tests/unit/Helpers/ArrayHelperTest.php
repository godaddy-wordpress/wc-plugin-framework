<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Helpers;

use ArrayAccess;
use Generator;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Helpers\ArrayHelper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Helpers\ArrayHelper
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
	 * Tests that can remove items from an array.
	 *
	 * @covers ::remove()
	 * @dataProvider providerCanRemove
	 *
	 * @param array<string, mixed> $input
	 * @param string|string[] $keysToRemove
	 * @param array $expected
	 */
	public function testCanRemoveItemsFromArray(array $input, $keysToRemove, array $expected) : void
	{
		ArrayHelper::remove($input, $keysToRemove);
		$this->assertEquals($expected, $input);
	}

	/** @see testCanRemoveItemsFromArray */
	public function providerCanRemove() : Generator
	{
		yield 'remove 1 key from one item array' => [
			'input'        => ['test' => 1],
			'keysToRemove' => 'test',
			'expected'     => [],
		];

		yield 'remove 1 key from two item array' => [
			'input'        => ['test' => 2, 'second' => ['nested' => 3]],
			'keysToRemove' => 'second',
			'expected'     => ['test' => 2],
		];

		yield 'remove 2 keys' => [
			'input'        => ['test' => 2, 'second' => ['nested' => 3], 'third' => 4],
			'keysToRemove' => ['second', 'third'],
			'expected'     => ['test' => 2],
		];

		yield 'remove 2 keys that are one level deep' => [
			'input' => [
				'resource' => [
					'first'  => 1,
					'second' => 2,
				],
			],
			'keysToRemove' => ['resource.first', 'resource.second'],
			'expected'     => ['resource' => []],
		];
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

	/**
	 * Tests that the helper will return default with key not found.
	 *
	 * @covers ::get()
	 */
	public function testCanReturnDefaultWhenGetArrayValueByKeyNotFound() : void
	{
		$this->assertNull(ArrayHelper::get([], 'key'), 'ArrayHelper::get() does not return null by default as expected');
		$this->assertEquals('myDefault', ArrayHelper::get([], 'key', 'myDefault'));
		$this->assertNotEquals('myDefault', ArrayHelper::get(['key' => 'value'], 'key', 'myDefault'));
	}

	/**
	 * Tests that can retrieve array value by key without PHP error/warning.
	 *
	 * @covers ::get()
	 * @dataProvider providerCanGetArrayValueByKey
	 *
	 * @param mixed $array
	 * @param int|string $key
	 * @param mixed $expected
	 * @return void
	 */
	public function testCanGetArrayValueByKey($array, $key, $expected) : void
	{
		$this->assertSame($expected, ArrayHelper::get($array, $key));
	}

	/** @see testCanGetArrayValueByKey() */
	public function providerCanGetArrayValueByKey() : Generator
	{
		yield 'Existing string key in a key/value array' => [
			'array'    => ['key' => 'found'],
			'key'      => 'key',
			'expected' => 'found',
		];

		yield 'Nonexistent string key in a key/value array' => [
			'array'    => ['key' => 'notfound'],
			'key'      => 'value',
			'expected' => null,
		];

		yield 'Existing nested key with dot notation' => [
			'array'    => ['key' => ['nested' => ['deeply' => 'found']]],
			'key'      => 'key.nested.deeply',
			'expected' => 'found',
		];

		yield 'Nonexistent nested key with dot notation' => [
			'array'    => ['key' => ['nested' => ['deeply' => 'notfound']]],
			'key'      => 'key.nested.more.deeply',
			'expected' => null,
		];

		yield 'Existing dot-notated key as key is returned without iteration' => [
			'array'    => ['dot.notated.key' => 'found'],
			'key'      => 'dot.notated.key',
			'expected' => 'found',
		];

		yield 'Existing numeric index' => [
			'array'    => ['foo', 'bar', 'baz'],
			'key'      => 1,
			'expected' => 'bar',
		];

		yield 'Nonexistent numeric index' => [
			'array'    => ['foo', 'bar', 'baz'],
			'key'      => 3,
			'expected' => null,
		];

		yield 'Existing numeric string index' => [
			'array'    => ['foo', 'bar', 'baz'],
			'key'      => '2',
			'expected' => 'baz',
		];

		yield 'Nonexistent numeric string index' => [
			'array'    => ['foo', 'bar', 'baz'],
			'key'      => '3',
			'expected' => null,
		];
	}

	protected function getArrayAccessObject(): ArrayAccess
	{
		return new class implements ArrayAccess
		{

			public function offsetExists($offset) : bool
			{
				return true;
			}

			public function offsetGet($offset) : int
			{
				return 1;
			}

			public function offsetSet($offset, $value) : void
			{
				// TODO: Implement offsetSet() method.
			}

			public function offsetUnset($offset) : void
			{
				// TODO: Implement offsetUnset() method.
			}
		};
	}
}
