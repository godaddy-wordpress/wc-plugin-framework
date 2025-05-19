<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Traits;

use Exception;
use Generator;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanConvertToArrayTrait;
use stdClass;

final class CanConvertToArrayTraitTest extends TestCase
{
	protected TestCanConvertToArray $subject;

	public function setUp() : void
	{
		parent::setUp();

		$this->subject = new TestCanConvertToArray();
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanConvertToArrayTrait::toArray()
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanConvertToArrayTrait::toArrayShouldPropertyBeAccessible()
	 *
	 * @dataProvider providerCanConvertPropertiesToArray
	 *
	 * @param bool $includePrivateProperties
	 * @param bool $includeProtectedProperties
	 * @param bool $includePublicProperties
	 * @param array $expectedProperties
	 * @return void
	 * @throws Exception
	 */
	public function testCanConvertPropertiesToArray(
		bool $includePrivateProperties,
		bool $includeProtectedProperties,
		bool $includePublicProperties,
		array $expectedProperties
	) : void {
		$this->setInaccessiblePropertyValue($this->subject, 'toArrayIncludePrivate', $includePrivateProperties);
		$this->setInaccessiblePropertyValue($this->subject, 'toArrayIncludeProtected', $includeProtectedProperties);
		$this->setInaccessiblePropertyValue($this->subject, 'toArrayIncludePublic', $includePublicProperties);

		$this->assertSame($expectedProperties, $this->subject->toArray());
	}

	/** @see testCanConvertPropertiesToArray */
	public function providerCanConvertPropertiesToArray() : Generator
	{
		yield 'no properties included' => [
			'includePrivateProperties'   => false,
			'includeProtectedProperties' => false,
			'includePublicProperties'    => false,
			'expectedProperties'         => [],
		];

		yield 'private properties included' => [
			'includePrivateProperties'   => true,
			'includeProtectedProperties' => false,
			'includePublicProperties'    => false,
			'expectedProperties'         => [
				'privateProperty' => 'private',
			],
		];

		yield 'private and protected properties included' => [
			'includePrivateProperties'   => true,
			'includeProtectedProperties' => true,
			'includePublicProperties'    => false,
			'expectedProperties'         => [
				'privateProperty'   => 'private',
				'protectedProperty' => 'protected',
			],
		];

		yield 'private, protected, and public properties included' => [
			'includePrivateProperties'   => true,
			'includeProtectedProperties' => true,
			'includePublicProperties'    => true,
			'expectedProperties'         => [
				'privateProperty'   => 'private',
				'protectedProperty' => 'protected',
				'publicProperty'    => 'public',
			],
		];

		yield 'private and public properties included' => [
			'includePrivateProperties'   => true,
			'includeProtectedProperties' => false,
			'includePublicProperties'    => true,
			'expectedProperties'         => [
				'privateProperty' => 'private',
				'publicProperty'  => 'public',
			],
		];

		yield 'protected and public properties included' => [
			'includePrivateProperties'   => false,
			'includeProtectedProperties' => true,
			'includePublicProperties'    => true,
			'expectedProperties'         => [
				'protectedProperty' => 'protected',
				'publicProperty'    => 'public',
			],
		];
	}

	/**
	 * Tests nested objects that may or may not use the same trait.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanConvertToArrayTrait::toArray()
	 *
	 * @dataProvider providerCanConvertNestedProperties
	 *
	 * @param TestSubjectWithNestedProperty $object
	 * @param mixed $nestedObject
	 * @param array $expected
	 */
	public function testCanConvertNestedProperties(
		TestSubjectWithNestedProperty $object,
		$nestedObject,
		array $expected
	) : void {
		$object->nestedProperty = $nestedObject;

		$this->assertSame($expected, $object->toArray());
	}

	/** @see testCanConvertNestedProperties */
	public function providerCanConvertNestedProperties() : Generator
	{
		yield 'Nested object is null' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => null,
			'expected'     => ['nestedProperty' => null],
		];

		yield 'Nested object is a primitive type' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => 'test',
			'expected'     => ['nestedProperty' => 'test'],
		];

		$randomObject = new class {
		};

		yield 'Nested object is an object that doesn\'t use the trait' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => $randomObject,
			'expected'     => ['nestedProperty' => $randomObject],
		];

		yield 'Nested object is an object that uses the trait' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => new TestSubjectWithNestedProperty(),
			'expected'     => ['nestedProperty' => ['nestedProperty' => null]],
		];

		yield 'Nested object is an array of primitive types' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => ['a', 'b'],
			'expected'     => ['nestedProperty' => ['a', 'b']],
		];

		yield 'Nested object is an associative array of primitive types' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => ['a' => 'b'],
			'expected'     => ['nestedProperty' => ['a' => 'b']],
		];

		yield 'Nested object is an associative array of objects that doesn\'t use the trait' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => ['a' => $randomObject],
			'expected'     => ['nestedProperty' => ['a' => $randomObject]],
		];

		yield 'Nested object is an array of objects that use the trait' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => [new TestSubjectWithNestedProperty()],
			'expected'     => [
				'nestedProperty' => [
					['nestedProperty' => null],
				],
			],
		];

		yield 'Nested object is an associative array of objects that use the trait' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => ['a' => new TestSubjectWithNestedProperty()],
			'expected'     => [
				'nestedProperty' => [
					'a' => ['nestedProperty' => null],
				],
			],
		];

		yield 'Nested object is a two-dimensional array' => [
			'object'       => new TestSubjectWithNestedProperty(),
			'nestedObject' => [
				['a' => $randomObject],
				['a' => $randomObject],
			],
			'expected'     => [
				'nestedProperty' => [
					['a' => $randomObject],
					['a' => $randomObject],
				],
			],
		];
	}

	/**
	 * Tests that can determine whether an item can be converted to array.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanConvertToArrayTrait::canConvertItemToArray()
	 *
	 * @throws Exception
	 */
	public function testCanDetermineWhetherCanConvertItemToArray() : void
	{
		$trait = $this->getMockForTrait(CanConvertToArrayTrait::class);
		$method = $this->getInaccessibleMethod($trait, 'canConvertItemToArray');

		$this->assertFalse($method->invokeArgs($trait, [null]));
		$this->assertFalse($method->invokeArgs($trait, ['foo']));
		$this->assertFalse($method->invokeArgs($trait, [['foo' => 'bar']]));
		$this->assertFalse($method->invokeArgs($trait, [123]));
		$this->assertFalse($method->invokeArgs($trait, [new stdClass()]));
		$this->assertTrue($method->invokeArgs($trait, [
			new class() {
				public function toArray() : array
				{
					return [];
				}
			},
		]));
	}

	/**
	 * Tests nested objects infinite recursion prevention.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Traits\CanConvertToArrayTrait::toArray()
	 */
	public function testCanConvertNestedPropertiesWontDoInfiniteRecursion() : void
	{
		$objectA = new TestSubjectWithNestedProperty();
		$objectB = new TestSubjectWithNestedProperty();
		$objectC = new TestSubjectWithNestedProperty();

		$objectA->nestedProperty = $objectB;
		$objectB->nestedProperty = $objectC;
		$objectC->nestedProperty = $objectA;

		// tests that a infinite recursion is prevented
		$this->assertSame([
			// object A
			'nestedProperty' => [
				// object B
				'nestedProperty' => [
					// object C
					'nestedProperty' => [],
				],
			],
		], $objectA->toArray());

		// tests that the recursion flag won't affect subsequent toArray() calls
		// also makes sure the recursion flag acts per instance, not per class
		$this->assertSame([
			// object B
			'nestedProperty' => [
				// object C
				'nestedProperty' => [
					// object A
					'nestedProperty' => [],
				],
			],
		], $objectB->toArray());
	}
}

/** Dummy class for testing {@see CanConvertToArrayTrait} */
final class TestCanConvertToArray
{
	use CanConvertToArrayTrait;

	private string $privateProperty = 'private';
	protected string $protectedProperty = 'protected';
	public string $publicProperty = 'public';

	/** @var bool this property should never be included in arrays because it's not initialized */
	public bool $unInitializedProperty;
}

final class TestSubjectWithNestedProperty
{
	use CanConvertToArrayTrait;

	public $nestedProperty;
}
