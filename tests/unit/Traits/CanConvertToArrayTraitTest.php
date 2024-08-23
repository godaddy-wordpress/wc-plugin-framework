<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\Unit\Traits;

use Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Tests\TestCase;
use SkyVerge\WooCommerce\PluginFramework\v5_13_1\Traits\CanConvertToArrayTrait;
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
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_1\Traits\CanConvertToArrayTrait::toArray()
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_1\Traits\CanConvertToArrayTrait::toArrayShouldPropertyBeAccessible()
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
	) {
		$this->setInaccessiblePropertyValue($this->subject, 'toArrayIncludePrivate', $includePrivateProperties);
		$this->setInaccessiblePropertyValue($this->subject, 'toArrayIncludeProtected', $includeProtectedProperties);
		$this->setInaccessiblePropertyValue($this->subject, 'toArrayIncludePublic', $includePublicProperties);

		$this->assertSame($expectedProperties, $this->subject->toArray());
	}

	/** @see testCanConvertPropertiesToArray */
	public function providerCanConvertPropertiesToArray(): \Generator
	{
		yield 'no properties included' => [
			'includePrivateProperties' => false,
			'includeProtectedProperties' => false,
			'includePublicProperties' => false,
			'expectedProperties' => [],
		];

		yield 'private properties included' => [
			'includePrivateProperties' => true,
			'includeProtectedProperties' => false,
			'includePublicProperties' => false,
			'expectedProperties' => [
				'privateProperty' => 'private',
			],
		];

		yield 'private and protected properties included' => [
			'includePrivateProperties' => true,
			'includeProtectedProperties' => true,
			'includePublicProperties' => false,
			'expectedProperties' => [
				'privateProperty' => 'private',
				'protectedProperty' => 'protected',
			],
		];

		yield 'private, protected, and public properties included' => [
			'includePrivateProperties' => true,
			'includeProtectedProperties' => true,
			'includePublicProperties' => true,
			'expectedProperties' => [
				'privateProperty' => 'private',
				'protectedProperty' => 'protected',
				'publicProperty' => 'public',
			],
		];

		yield 'private and public properties included' => [
			'includePrivateProperties' => true,
			'includeProtectedProperties' => false,
			'includePublicProperties' => true,
			'expectedProperties' => [
				'privateProperty' => 'private',
				'publicProperty' => 'public',
			],
		];

		yield 'protected and public properties included' => [
			'includePrivateProperties' => false,
			'includeProtectedProperties' => true,
			'includePublicProperties' => true,
			'expectedProperties' => [
				'protectedProperty' => 'protected',
				'publicProperty' => 'public',
			],
		];
	}

	/**
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_1\Traits\CanConvertToArrayTrait::toArray()
	 */
	public function testCanConvertNestedProperties(): void
	{
		$this->markTestIncomplete('TODO');
	}

	/**
	 * Tests that can determine whether an item can be converted to array.
	 *
	 * @covers \SkyVerge\WooCommerce\PluginFramework\v5_13_1\Traits\CanConvertToArrayTrait::canConvertItemToArray()
	 *
	 * @throws Exception
	 */
	public function testCanDetermineWhetherCanConvertItemToArray()
	{
		$trait = $this->getMockForTrait(CanConvertToArrayTrait::class);
		$method = $this->getInaccessibleMethod($trait, 'canConvertItemToArray');

		$this->assertFalse($method->invokeArgs($trait, [null]));
		$this->assertFalse($method->invokeArgs($trait, ['foo']));
		$this->assertFalse($method->invokeArgs($trait, [['foo' => 'bar']]));
		$this->assertFalse($method->invokeArgs($trait, [123]));
		$this->assertFalse($method->invokeArgs($trait, [new stdClass()]));
		$this->assertTrue($method->invokeArgs($trait, [new class() {
			public function toArray()
			{
				return [];
			}
		}]));
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
