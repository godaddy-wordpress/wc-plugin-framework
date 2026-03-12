<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Tests\unit\Abilities\DataObjects;

use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects\AbilityAnnotations;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Tests\TestCase;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects\Ability
 */
final class AbilityTest extends TestCase
{
	/**
	 * @covers ::__construct
	 */
	public function testCanConstruct() : void
	{
		$executeCallback = fn() => true;
		$permissionCallback = fn() => false;

		$annotations = Mockery::mock(AbilityAnnotations::class);

		$ability = new Ability(
			'ability/name',
			'Ability Name',
			'Description...',
			'category-slug',
			$executeCallback,
			$permissionCallback,
			['type' => 'integer'],
			['type' => 'object'],
			$annotations,
			false
		);

		$this->assertSame('ability/name', $ability->name);
		$this->assertSame('Ability Name', $ability->label);
		$this->assertSame('Description...', $ability->description);
		$this->assertSame($executeCallback, $ability->executeCallback);
		$this->assertSame($permissionCallback, $ability->permissionCallback);
		$this->assertSame(['type' => 'integer'], $ability->inputSchema);
		$this->assertSame(['type' => 'object'], $ability->outputSchema);
		$this->assertSame($annotations, $ability->annotations);
		$this->assertFalse($ability->showInRest);
	}

	/**
	 * @covers ::toArray
	 */
	public function testCanConvertToArray() : void
	{
		$ability = new Ability(
			'ability/name',
			'Ability Name',
			'Description...',
			'category-slug',
			fn() => true,
			fn() => false,
			['type' => 'integer'],
			['type' => 'object'],
			new AbilityAnnotations(true, false, true),
			false
		);

		$this->assertSame(
			[
				'label' => $ability->label,
				'description' => $ability->description,
				'category' => $ability->category,
				'execute_callback' => $ability->executeCallback,
				'permission_callback' => $ability->permissionCallback,
				'input_schema' => $ability->inputSchema,
				'output_schema' => $ability->outputSchema,
				'meta' => [
					'show_in_rest' => $ability->showInRest,
					'annotations' => [
						'readonly' => true,
						'destructive' => false,
						'idempotent' => true,
					],
				],
			],
			$ability->toArray()
		);
	}
}
