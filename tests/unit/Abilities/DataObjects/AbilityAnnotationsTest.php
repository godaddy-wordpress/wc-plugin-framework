<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_5\Tests\Unit\Abilities\DataObjects;

use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\DataObjects\AbilityAnnotations;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Tests\TestCase;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\DataObjects\AbilityAnnotations
 */
final class AbilityAnnotationsTest extends TestCase
{
	/**
	 * @covers ::__construct
	 */
	public function testCanConstruct() : void
	{
		$abilityAnnotations = new AbilityAnnotations(true, true, true);

		$this->assertTrue($abilityAnnotations->readonly);
		$this->assertTrue($abilityAnnotations->destructive);
		$this->assertTrue($abilityAnnotations->idempotent);
	}

	/**
	 * @covers ::toArray
	 */
	public function testCanConvertToArray() : void
	{
		$abilityAnnotations = new AbilityAnnotations(true, true, true);

		$this->assertSame(
			[
				'readonly' => true,
				'destructive' => true,
				'idempotent' => true,
			],
			$abilityAnnotations->toArray()
		);
	}
}
