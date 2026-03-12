<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Tests\unit\Abilities\DataObjects;

use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects\AbilityCategory;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Tests\TestCase;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects\AbilityCategory
 */
final class AbilityCategoryTest extends TestCase
{
	/**
	 * @covers ::__construct
	 */
	public function testCanConstruct() : void
	{
		$category = new AbilityCategory('ability-category', 'Ability Category', 'Description...', ['key' => 'value']);

		$this->assertSame('ability-category', $category->slug);
		$this->assertSame('Ability Category', $category->label);
		$this->assertSame('Description...', $category->description);
		$this->assertSame(['key' => 'value'], $category->meta);
	}

	/**
	 * @covers ::toArray
	 */
	public function testCanConvertToArray() : void
	{
		$category = new AbilityCategory('ability-category', 'Ability Category', 'Description...', ['key' => 'value']);

		$this->assertSame(
			[
				'label' => $category->label,
				'description' => $category->description,
				'meta' => $category->meta,
			],
			$category->toArray()
		);
	}
}
